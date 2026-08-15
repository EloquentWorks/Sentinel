<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('sentinel.tables.reports', 'sentinel_reports'), function (Blueprint $t): void {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->string('reporter_type')->nullable();
            $t->unsignedBigInteger('reporter_id')->nullable();
            $t->string('reportable_type');
            $t->unsignedBigInteger('reportable_id');
            $t->string('subject_type')->nullable();
            $t->unsignedBigInteger('subject_id')->nullable();
            $t->string('category', 100)->default('other')->index();
            $t->string('reason')->nullable();
            $t->text('description')->nullable();
            $t->string('priority', 20)->default('normal')->index();
            $t->string('status', 30)->default('new')->index();
            $t->string('source', 60)->default('user')->index();
            $t->string('ip_address', 45)->nullable();
            $t->text('user_agent')->nullable();
            $t->json('metadata')->nullable();
            $t->unsignedBigInteger('duplicate_of_id')->nullable()->index();
            $t->timestamp('triaged_at')->nullable();
            $t->timestamp('resolved_at')->nullable();
            $t->text('resolution')->nullable();
            $t->timestamps();
            $t->index(['reportable_type', 'reportable_id'], 'sentinel_reportable_idx');
            $t->index(['subject_type', 'subject_id'], 'sentinel_report_subject_idx');
            $t->index(['reporter_type', 'reporter_id'], 'sentinel_reporter_idx');
        });
        Schema::create(config('sentinel.tables.cases', 'sentinel_cases'), function (Blueprint $t): void {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->string('subject_type')->nullable();
            $t->unsignedBigInteger('subject_id')->nullable();
            $t->string('title');
            $t->string('status', 30)->default('open')->index();
            $t->string('priority', 20)->default('normal')->index();
            $t->string('queue', 100)->default('general')->index();
            $t->unsignedSmallInteger('risk_score')->default(0)->index();
            $t->json('tags')->nullable();
            $t->json('metadata')->nullable();
            $t->text('resolution')->nullable();
            $t->timestamp('opened_at')->nullable();
            $t->timestamp('due_at')->nullable()->index();
            $t->timestamp('resolved_at')->nullable();
            $t->timestamps();
            $t->index(['subject_type', 'subject_id'], 'sentinel_case_subject_idx');
        });
        Schema::create(config('sentinel.tables.case_reports', 'sentinel_case_reports'), function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('case_id');
            $t->unsignedBigInteger('report_id');
            $t->timestamps();
            $t->unique(['case_id', 'report_id'], 'sentinel_case_report_unique');
        });
        Schema::create(config('sentinel.tables.notes', 'sentinel_case_notes'), function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('case_id')->index();
            $t->string('author_type');
            $t->unsignedBigInteger('author_id');
            $t->longText('body');
            $t->string('visibility', 30)->default('internal');
            $t->json('metadata')->nullable();
            $t->timestamps();
            $t->index(['author_type', 'author_id'], 'sentinel_note_author_idx');
        });
        Schema::create(config('sentinel.tables.assignments', 'sentinel_case_assignments'), function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('case_id')->index();
            $t->string('moderator_type');
            $t->unsignedBigInteger('moderator_id');
            $t->string('assigned_by_type')->nullable();
            $t->unsignedBigInteger('assigned_by_id')->nullable();
            $t->boolean('active')->default(true)->index();
            $t->timestamp('assigned_at');
            $t->timestamp('released_at')->nullable();
            $t->timestamps();
            $t->index(['moderator_type', 'moderator_id'], 'sentinel_assignment_mod_idx');
        });
        Schema::create(config('sentinel.tables.actions', 'sentinel_actions'), function (Blueprint $t): void {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->unsignedBigInteger('case_id')->nullable()->index();
            $t->string('actor_type');
            $t->unsignedBigInteger('actor_id');
            $t->string('target_type')->nullable();
            $t->unsignedBigInteger('target_id')->nullable();
            $t->string('external_type')->nullable();
            $t->unsignedBigInteger('external_id')->nullable();
            $t->string('type', 50)->index();
            $t->string('status', 30)->default('pending')->index();
            $t->string('source_package', 30)->nullable()->index();
            $t->text('reason')->nullable();
            $t->text('failure_reason')->nullable();
            $t->json('metadata')->nullable();
            $t->timestamp('expires_at')->nullable();
            $t->timestamp('applied_at')->nullable();
            $t->timestamp('revoked_at')->nullable();
            $t->timestamps();
            $t->index(['actor_type', 'actor_id'], 'sentinel_action_actor_idx');
            $t->index(['target_type', 'target_id'], 'sentinel_action_target_idx');
            $t->index(['external_type', 'external_id'], 'sentinel_action_ext_idx');
        });
        Schema::create(config('sentinel.tables.audit', 'sentinel_audit_logs'), function (Blueprint $t): void {
            $t->id();
            $t->string('actor_type')->nullable();
            $t->unsignedBigInteger('actor_id')->nullable();
            $t->string('subject_type')->nullable();
            $t->unsignedBigInteger('subject_id')->nullable();
            $t->string('auditable_type')->nullable();
            $t->unsignedBigInteger('auditable_id')->nullable();
            $t->string('event', 120)->index();
            $t->string('ip_address', 45)->nullable();
            $t->text('user_agent')->nullable();
            $t->json('before')->nullable();
            $t->json('after')->nullable();
            $t->json('metadata')->nullable();
            $t->timestamp('created_at')->index();
            $t->index(['actor_type', 'actor_id'], 'sentinel_audit_actor_idx');
            $t->index(['subject_type', 'subject_id'], 'sentinel_audit_subject_idx');
            $t->index(['auditable_type', 'auditable_id'], 'sentinel_audit_object_idx');
        });
        Schema::create(config('sentinel.tables.watchlist', 'sentinel_watchlist'), function (Blueprint $t): void {
            $t->id();
            $t->string('subject_type');
            $t->unsignedBigInteger('subject_id');
            $t->string('added_by_type');
            $t->unsignedBigInteger('added_by_id');
            $t->text('reason');
            $t->string('severity', 20)->default('medium')->index();
            $t->boolean('active')->default(true)->index();
            $t->timestamp('expires_at')->nullable()->index();
            $t->json('metadata')->nullable();
            $t->timestamps();
            $t->index(['subject_type', 'subject_id'], 'sentinel_watch_subject_idx');
        });
        Schema::create(config('sentinel.tables.holds', 'sentinel_content_holds'), function (Blueprint $t): void {
            $t->id();
            $t->string('reportable_type');
            $t->unsignedBigInteger('reportable_id');
            $t->string('actor_type');
            $t->unsignedBigInteger('actor_id');
            $t->text('reason');
            $t->boolean('active')->default(true)->index();
            $t->timestamp('expires_at')->nullable();
            $t->timestamp('released_at')->nullable();
            $t->json('metadata')->nullable();
            $t->timestamps();
            $t->index(['reportable_type', 'reportable_id'], 'sentinel_hold_reportable_idx');
        });
        Schema::create(config('sentinel.tables.rules', 'sentinel_automation_rules'), function (Blueprint $t): void {
            $t->id();
            $t->string('name');
            $t->string('event', 120)->index();
            $t->boolean('enabled')->default(true)->index();
            $t->integer('priority')->default(0)->index();
            $t->json('conditions')->nullable();
            $t->json('actions');
            $t->boolean('stop_processing')->default(false);
            $t->unsignedInteger('cooldown_seconds')->default(0);
            $t->unsignedBigInteger('trigger_count')->default(0);
            $t->timestamp('last_triggered_at')->nullable();
            $t->timestamps();
        });
        Schema::create(config('sentinel.tables.macros', 'sentinel_macros'), function (Blueprint $t): void {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->text('description')->nullable();
            $t->json('actions');
            $t->boolean('enabled')->default(true)->index();
            $t->string('created_by_type')->nullable();
            $t->unsignedBigInteger('created_by_id')->nullable();
            $t->json('metadata')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['macros', 'rules', 'holds', 'watchlist', 'audit', 'actions', 'assignments', 'notes', 'case_reports', 'cases', 'reports'] as $key) {
            Schema::dropIfExists(config('sentinel.tables.'.$key));
        }
    }
};
