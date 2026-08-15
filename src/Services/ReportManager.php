<?php

namespace EloquentWorks\Sentinel\Services;

use EloquentWorks\Sentinel\Enums\Priority;
use EloquentWorks\Sentinel\Enums\ReportStatus;
use EloquentWorks\Sentinel\Events\ReportCreated;
use EloquentWorks\Sentinel\Events\ReportTriaged;
use EloquentWorks\Sentinel\Models\ModerationReport;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class ReportManager
{
    /**
     * Create a new class instance.
     *
     * @param  AuditLogger  $audit
     * @return void
     */
    public function __construct(
        private readonly AuditLogger $audit,
    ) {
        //
    }

    /**
     * Create a new moderation report.
     *
     * @param  Model  $reportable
     * @param  Authenticatable|null  $reporter
     * @param  Model|null  $subject
     * @param  string  $category
     * @param  string|null  $reason
     * @param  string|null  $description
     * @param  Priority|string  $priority
     * @param  string  $source
     * @param  array<string, mixed>  $metadata
     * @return ModerationReport
     */
    public function create(
        Model $reportable,
        ?Authenticatable $reporter = null,
        ?Model $subject = null,
        string $category = 'other',
        ?string $reason = null,
        ?string $description = null,
        Priority|string $priority = Priority::Normal,
        string $source = 'user',
        array $metadata = [],
    ): ModerationReport {
        // Get the report model class from the configuration.
        $reportModel = config('sentinel.models.report');
        $priority = is_string($priority) ? Priority::from($priority) : $priority;
        $reporterModel = $reporter instanceof Model ? $reporter : null;

        /** @var ModerationReport $report */
        $report = $reportModel::query()->create([
            'uuid' => (string) Str::uuid(),
            'reporter_type' => $reporterModel?->getMorphClass(),
            'reporter_id' => $reporterModel?->getKey(),
            'reportable_type' => $reportable->getMorphClass(),
            'reportable_id' => $reportable->getKey(),
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'category' => $category,
            'reason' => $reason,
            'description' => $description,
            'priority' => $priority,
            'status' => ReportStatus::New,
            'source' => $source,
            'ip_address' => app()->bound('request') ? request()->ip() : null,
            'user_agent' => app()->bound('request') ? request()->userAgent() : null,
            'metadata' => $metadata ?: null,
        ]);

        // Log the report creation event and fire the ReportCreated event.
        $this->audit->log(
            event: 'report.created',
            actor: $reporter,
            subject: $subject,
            auditable: $report,
            metadata: ['category' => $category],
        );
        
        // Fire the ReportCreated event to notify listeners of the new report.
        event(new ReportCreated($report));

        // Return the created ModerationReport instance.
        return $report;
    }

    /**
     * Mark a report as triaged and optionally change its priority.
     *
     * @param  ModerationReport  $report
     * @param  Authenticatable  $moderator
     * @param  Priority|null  $priority
     * @return ModerationReport
     */
    public function triage(
        ModerationReport $report,
        Authenticatable $moderator,
        ?Priority $priority = null,
    ): ModerationReport {
        // Get the current state of the report before making changes.
        $before = $report->toArray();

        // Update the report's status to 'triaged', set the priority if provided, and record the triage timestamp.
        $report->forceFill([
            'status' => ReportStatus::Triaged,
            'priority' => $priority ?? $report->priority,
            'triaged_at' => now(),
        ])->save();

        // Get the fresh state of the report after the changes.
        $freshReport = $report->fresh();

        $this->audit->log(
            'report.triaged',
            $moderator,
            $report->subject,
            $report,
            $before,
            $freshReport->toArray(),
        );

        // Fire the ReportTriaged event to notify listeners that the report has been triaged.
        event(new ReportTriaged($freshReport));

        // Return the updated ModerationReport instance.
        return $freshReport;
    }

    /**
     * Dismiss a report with an optional resolution reason.
     *
     * @param  ModerationReport  $report
     * @param  Authenticatable  $moderator
     * @param  string|null  $reason
     * @return ModerationReport
     */
    public function dismiss(
        ModerationReport $report,
        Authenticatable $moderator,
        ?string $reason = null,
    ): ModerationReport {
        // Get the current state of the report before making changes.
        $before = $report->toArray();

        // Update the report's status to 'dismissed', set the resolution reason, and record the resolution timestamp.
        $report->forceFill([
            'status' => ReportStatus::Dismissed,
            'resolved_at' => now(),
            'resolution' => $reason,
        ])->save();

        // Get the fresh state of the report after the changes.
        $freshReport = $report->fresh();

        // Log the report dismissal event with the moderator, subject, and before/after states.
        $this->audit->log(
            'report.dismissed',
            $moderator,
            $report->subject,
            $report,
            $before,
            $freshReport->toArray(),
        );

        // Return the updated ModerationReport instance.
        return $freshReport;
    }

    /**
     * Mark a report as a duplicate of another report.
     *
     * @param  ModerationReport  $report
     * @param  ModerationReport  $original
     * @param  Authenticatable  $moderator
     * @return ModerationReport
     */
    public function markDuplicate(
        ModerationReport $report,
        ModerationReport $original,
        Authenticatable $moderator,
    ): ModerationReport {
        // Get the current state of the report before making changes.
        $before = $report->toArray();

        // Update the report's status to 'duplicate', set the original report ID, and record the resolution timestamp.
        $report->forceFill([
            'status' => ReportStatus::Duplicate,
            'duplicate_of_id' => $original->getKey(),
            'resolved_at' => now(),
        ])->save();

        // Get the fresh state of the report after the changes.
        $this->audit->log(
            event: 'report.duplicate',
            actor: $moderator,
            subject: $report->subject,
            auditable: $report,
            metadata: ['original_report_id' => $original->getKey()],
        );

        // Return the updated ModerationReport instance.
        return $report->fresh();
    }
}
