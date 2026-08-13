<?php

declare(strict_types=1);

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

final class CaseManager
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function open(
        ?Model $subject,
        string $title,
        Priority|string $priority = Priority::Normal,
        ?Authenticatable $openedBy = null,
        ?string $queue = null,
        array $tags = [],
        array $metadata = [],
    ): ModerationCase {
        $model = config('sentinel.models.case');
        $priority = is_string($priority) ? Priority::from($priority) : $priority;
        $sla = (int) config('sentinel.cases.sla_hours.'.$priority->value, 72);
        /** @var ModerationCase $case */
        $case = $model::query()->create([
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
            'due_at' => $sla > 0 ? now()->addHours($sla) : null,
        ]);
        $this->audit->log('case.opened', $openedBy, $subject, $case);
        event(new CaseOpened($case));
        return $case;
    }

    public function fromReport(ModerationReport $report, ?Authenticatable $openedBy = null): ModerationCase
    {
        $case = $this->open(
            $report->subject,
            title: $report->reason ?: 'Moderation report '.$report->uuid,
            priority: $report->priority,
            openedBy: $openedBy,
            tags: [$report->category],
            metadata: ['origin_report_uuid' => $report->uuid],
        );
        $case->reports()->syncWithoutDetaching([$report->getKey()]);
        return $case;
    }

    public function attachReport(ModerationCase $case, ModerationReport $report, ?Authenticatable $actor = null): void
    {
        $case->reports()->syncWithoutDetaching([$report->getKey()]);
        $this->audit->log('case.report_attached', $actor, $case->subject, $case, metadata: ['report_id' => $report->getKey()]);
    }

    public function assign(ModerationCase $case, Authenticatable $moderator, ?Authenticatable $assignedBy = null): CaseAssignment
    {
        $case->assignments()->where('active', true)->update(['active' => false, 'released_at' => now()]);
        $moderatorModel = $moderator instanceof Model ? $moderator : throw new \InvalidArgumentException('Moderator must be an Eloquent model.');
        $by = $assignedBy instanceof Model ? $assignedBy : null;
        /** @var CaseAssignment $assignment */
        $assignment = $case->assignments()->create([
            'moderator_type' => $moderatorModel->getMorphClass(),
            'moderator_id' => $moderatorModel->getKey(),
            'assigned_by_type' => $by?->getMorphClass(),
            'assigned_by_id' => $by?->getKey(),
            'active' => true,
            'assigned_at' => now(),
        ]);
        $this->audit->log('case.assigned', $assignedBy, $case->subject, $case, metadata: ['moderator_id' => $moderatorModel->getKey()]);
        event(new CaseAssigned($assignment));
        return $assignment;
    }

    public function note(ModerationCase $case, Authenticatable $author, string $body, string $visibility = 'internal', array $metadata = []): CaseNote
    {
        $authorModel = $author instanceof Model ? $author : throw new \InvalidArgumentException('Author must be an Eloquent model.');
        /** @var CaseNote $note */
        $note = $case->notes()->create([
            'author_type' => $authorModel->getMorphClass(),
            'author_id' => $authorModel->getKey(),
            'body' => $body,
            'visibility' => $visibility,
            'metadata' => $metadata ?: null,
        ]);
        $this->audit->log('case.note_added', $author, $case->subject, $case, metadata: ['note_id' => $note->getKey()]);
        return $note;
    }

    public function resolve(ModerationCase $case, Authenticatable $actor, string $resolution): ModerationCase
    {
        $before = $case->toArray();
        $case->forceFill(['status' => CaseStatus::Resolved, 'resolution' => $resolution, 'resolved_at' => now()])->save();
        $case->assignments()->where('active', true)->update(['active' => false, 'released_at' => now()]);
        $this->audit->log('case.resolved', $actor, $case->subject, $case, $before, $case->fresh()->toArray());
        event(new CaseResolved($case->fresh()));
        return $case->fresh();
    }

    public function escalate(ModerationCase $case, Authenticatable $actor, Priority $priority = Priority::Urgent, ?string $queue = null): ModerationCase
    {
        $case->forceFill(['status' => CaseStatus::Escalated, 'priority' => $priority, 'queue' => $queue ?? $case->queue])->save();
        $this->audit->log('case.escalated', $actor, $case->subject, $case, metadata: ['priority' => $priority->value]);
        return $case->fresh();
    }
}
