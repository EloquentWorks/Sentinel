<?php

namespace EloquentWorks\Sentinel\Services;

use EloquentWorks\Sentinel\Enums\CaseStatus;
use EloquentWorks\Sentinel\Enums\Priority;
use EloquentWorks\Sentinel\Events\CaseAssigned;
use EloquentWorks\Sentinel\Events\CaseOpened;
use EloquentWorks\Sentinel\Events\CaseResolved;
use EloquentWorks\Sentinel\Models\CaseAssignment;
use EloquentWorks\Sentinel\Models\CaseNote;
use EloquentWorks\Sentinel\Models\ModerationCase;
use EloquentWorks\Sentinel\Models\ModerationReport;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class CaseManager
{
    /**
     * Create a new class instance.
     *
     * @return void
     */
    public function __construct(
        private readonly AuditLogger $audit,
    ) {
        //
    }

    /**
     * Open a new moderation case.
     *
     * @param  array<int, string>  $tags
     * @param  array<string, mixed>  $metadata
     */
    public function open(
        ?Model $subject,
        string $title,
        Priority|string $priority = Priority::Normal,
        ?Authenticatable $openedBy = null,
        ?string $queue = null,
        array $tags = [],
        array $metadata = [],
    ): ModerationCase {
        // Get the moderation case model class from the configuration.
        $caseModel = config('sentinel.models.case');
        $priority = is_string($priority) ? Priority::from($priority) : $priority;
        $slaHours = (int) config(
            'sentinel.cases.sla_hours.'.$priority->value,
            72,
        );

        /** @var ModerationCase $case */
        $case = $caseModel::query()->create([
            'uuid' => (string) Str::uuid(),
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'title' => $title,
            'status' => CaseStatus::Open,
            'priority' => $priority,
            'queue' => $queue ?? config('sentinel.cases.default_queue', 'general'),
            'risk_score' => 0,
            'tags' => array_values(array_unique($tags)),
            'metadata' => $metadata ?: null,
            'opened_at' => now(),
            'due_at' => $slaHours > 0 ? now()->addHours($slaHours) : null,
        ]);

        // Log the case opening event and fire the CaseOpened event.
        $this->audit->log('case.opened', $openedBy, $subject, $case);
        event(new CaseOpened($case));

        // Return the newly created moderation case.
        return $case;
    }

    /**
     * Open a moderation case from an existing report.
     */
    public function fromReport(
        ModerationReport $report,
        ?Authenticatable $openedBy = null,
    ): ModerationCase {
        // Open a new moderation case using the details from the provided report.
        $case = $this->open(
            subject: $report->subject,
            title: $report->reason ?: 'Moderation report '.$report->uuid,
            priority: $report->priority,
            openedBy: $openedBy,
            tags: [$report->category],
            metadata: ['origin_report_uuid' => $report->uuid],
        );

        // Attach the report to the newly created case without detaching any existing reports.
        $case->reports()->syncWithoutDetaching([$report->getKey()]);

        // Log the report attachment event and fire the CaseOpened event.
        return $case;
    }

    /**
     * Attach an additional report to a moderation case.
     */
    public function attachReport(
        ModerationCase $case,
        ModerationReport $report,
        ?Authenticatable $actor = null,
    ): void {
        // Attach the report to the case without detaching any existing reports.
        $case->reports()->syncWithoutDetaching([$report->getKey()]);

        // Log the report attachment event in the audit log.
        $this->audit->log(
            event: 'case.report_attached',
            actor: $actor,
            subject: $case->subject,
            auditable: $case,
            metadata: ['report_id' => $report->getKey()],
        );
    }

    /**
     * Assign a moderator to a case, releasing the previous active assignment.
     */
    public function assign(
        ModerationCase $case,
        Authenticatable $moderator,
        ?Authenticatable $assignedBy = null,
    ): CaseAssignment {
        // Release any existing active assignments for the case before creating a new assignment.
        $case->assignments()
            ->where('active', true)
            ->update([
                'active' => false,
                'released_at' => now(),
            ]);

        // Ensure the moderator is an Eloquent model; throw an exception if not.
        $moderatorModel = $moderator instanceof Model
            ? $moderator
            : throw new InvalidArgumentException('Moderator must be an Eloquent model.');

        // If assignedBy is provided, ensure it is an Eloquent model; otherwise, set it to null.
        $assignedByModel = $assignedBy instanceof Model ? $assignedBy : null;

        /** @var CaseAssignment $assignment */
        $assignment = $case->assignments()->create([
            'moderator_type' => $moderatorModel->getMorphClass(),
            'moderator_id' => $moderatorModel->getKey(),
            'assigned_by_type' => $assignedByModel?->getMorphClass(),
            'assigned_by_id' => $assignedByModel?->getKey(),
            'active' => true,
            'assigned_at' => now(),
        ]);

        // Log the case assignment event in the audit log and fire the CaseAssigned event.
        $this->audit->log(
            event: 'case.assigned',
            actor: $assignedBy,
            subject: $case->subject,
            auditable: $case,
            metadata: ['moderator_id' => $moderatorModel->getKey()],
        );

        // Fire the CaseAssigned event to notify listeners of the new assignment.
        event(new CaseAssigned($assignment));

        // Return the newly created case assignment.
        return $assignment;
    }

    /**
     * Add a note to a moderation case.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function note(
        ModerationCase $case,
        Authenticatable $author,
        string $body,
        string $visibility = 'internal',
        array $metadata = [],
    ): CaseNote {
        // Ensure the author is an Eloquent model; throw an exception if not.
        $authorModel = $author instanceof Model
            ? $author
            : throw new InvalidArgumentException('Author must be an Eloquent model.');

        /** @var CaseNote $note */
        $note = $case->notes()->create([
            'author_type' => $authorModel->getMorphClass(),
            'author_id' => $authorModel->getKey(),
            'body' => $body,
            'visibility' => $visibility,
            'metadata' => $metadata ?: null,
        ]);

        // Log the case note addition event in the audit log.
        $this->audit->log(
            event: 'case.note_added',
            actor: $author,
            subject: $case->subject,
            auditable: $case,
            metadata: ['note_id' => $note->getKey()],
        );

        // Return the currently created case note.
        return $note;
    }

    /**
     * Resolve a moderation case and release its active assignment.
     */
    public function resolve(
        ModerationCase $case,
        Authenticatable $actor,
        string $resolution,
    ): ModerationCase {
        // Store the current state of the case before making changes for auditing purposes.
        $before = $case->toArray();

        // Update the case status to resolved, set the resolution, and record the resolved timestamp.
        $case->forceFill([
            'status' => CaseStatus::Resolved,
            'resolution' => $resolution,
            'resolved_at' => now(),
        ])->save();

        // Release any existing active assignments for the case upon resolution.
        $case->assignments()
            ->where('active', true)
            ->update([
                'active' => false,
                'released_at' => now(),
            ]);

        // Refresh the case instance to get the latest state after updates.
        $freshCase = $case->fresh();

        // Log the case resolution event in the audit log.
        $this->audit->log(
            'case.resolved',
            $actor,
            $case->subject,
            $case,
            $before,
            $freshCase->toArray(),
        );

        // Fire the CaseResolved event to notify listeners of the case resolution.
        event(new CaseResolved($freshCase));

        // Return the refreshed case instance after resolution.
        return $freshCase;
    }

    /**
     * Escalate a case to a higher priority or moderation queue.
     */
    public function escalate(
        ModerationCase $case,
        Authenticatable $actor,
        Priority $priority = Priority::Urgent,
        ?string $queue = null,
    ): ModerationCase {
        // Store the current state of the case before making changes for auditing purposes.
        $case->forceFill([
            'status' => CaseStatus::Escalated,
            'priority' => $priority,
            'queue' => $queue ?? $case->queue,
        ])->save();

        // Log the case escalation event in the audit log with relevant metadata.
        $this->audit->log(
            event: 'case.escalated',
            actor: $actor,
            subject: $case->subject,
            auditable: $case,
            metadata: ['priority' => $priority->value],
        );

        // Return the refreshed case instance after escalation.
        return $case->fresh();
    }
}
