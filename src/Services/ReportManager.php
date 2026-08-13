<?php

declare(strict_types=1);

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
    public function __construct(private readonly AuditLogger $audit) {}

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
        $model = config('sentinel.models.report');
        $priority = is_string($priority) ? Priority::from($priority) : $priority;
        $reporterModel = $reporter instanceof Model ? $reporter : null;

        /** @var ModerationReport $report */
        $report = $model::query()->create([
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

        $this->audit->log('report.created', $reporter, $subject, $report, metadata: ['category' => $category]);
        event(new ReportCreated($report));

        return $report;
    }

    public function triage(ModerationReport $report, Authenticatable $moderator, ?Priority $priority = null): ModerationReport
    {
        $before = $report->toArray();
        $report->forceFill([
            'status' => ReportStatus::Triaged,
            'priority' => $priority ?? $report->priority,
            'triaged_at' => now(),
        ])->save();
        $this->audit->log('report.triaged', $moderator, $report->subject, $report, $before, $report->fresh()->toArray());
        event(new ReportTriaged($report->fresh()));
        return $report->fresh();
    }

    public function dismiss(ModerationReport $report, Authenticatable $moderator, ?string $reason = null): ModerationReport
    {
        $before = $report->toArray();
        $report->forceFill(['status' => ReportStatus::Dismissed, 'resolved_at' => now(), 'resolution' => $reason])->save();
        $this->audit->log('report.dismissed', $moderator, $report->subject, $report, $before, $report->fresh()->toArray());
        return $report->fresh();
    }

    public function markDuplicate(ModerationReport $report, ModerationReport $original, Authenticatable $moderator): ModerationReport
    {
        $report->forceFill(['status' => ReportStatus::Duplicate, 'duplicate_of_id' => $original->getKey(), 'resolved_at' => now()])->save();
        $this->audit->log('report.duplicate', $moderator, $report->subject, $report, metadata: ['original_report_id' => $original->getKey()]);
        return $report->fresh();
    }
}
