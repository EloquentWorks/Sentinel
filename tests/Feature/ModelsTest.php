<?php

declare(strict_types=1);

namespace EloquentWorks\Sentinel\Tests\Feature;

use EloquentWorks\Sentinel\Enums\Priority;
use EloquentWorks\Sentinel\Enums\ReportStatus;
use EloquentWorks\Sentinel\Models\ModerationCase;
use EloquentWorks\Sentinel\Models\ModerationReport;
use EloquentWorks\Sentinel\Tests\TestCase;

final class ModelsTest extends TestCase
{
    public function test_report_casts_status_and_priority(): void
    {
        $report=ModerationReport::query()->create(['uuid'=>'00000000-0000-0000-0000-000000000001','reportable_type'=>'x','reportable_id'=>1,'category'=>'spam','priority'=>'high','status'=>'new','source'=>'test']);
        self::assertSame(Priority::High,$report->priority); self::assertSame(ReportStatus::New,$report->status);
    }
    public function test_case_can_link_reports(): void
    {
        $report=ModerationReport::query()->create(['uuid'=>'00000000-0000-0000-0000-000000000002','reportable_type'=>'x','reportable_id'=>1,'category'=>'spam','priority'=>'normal','status'=>'new','source'=>'test']);
        $case=ModerationCase::query()->create(['uuid'=>'00000000-0000-0000-0000-000000000003','title'=>'Test','status'=>'open','priority'=>'normal','queue'=>'general','opened_at'=>now()]);
        $case->reports()->attach($report); self::assertSame(1,$case->reports()->count());
    }
}
