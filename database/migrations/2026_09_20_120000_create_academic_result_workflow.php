<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grading_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->string('session', 9);
            $table->unsignedInteger('version');
            $table->unsignedTinyInteger('ca_max')->default(30);
            $table->unsignedTinyInteger('exam_max')->default(70);
            $table->unsignedTinyInteger('pass_mark')->default(40);
            $table->json('bands');
            $table->string('repeat_rule')->default('all');
            $table->unsignedInteger('graduation_credits')->nullable();
            $table->decimal('graduation_cgpa', 4, 2)->nullable();
            $table->json('required_courses')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['department_id', 'session', 'version']);
        });
        Schema::table('results', function (Blueprint $table) {
            $table->string('workflow_status')->default('draft')->index();
            $table->string('outcome_status')->default('graded');
            $table->string('attempt_type')->default('regular');
            $table->unsignedInteger('attempt_number')->default(1);
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('grading_policy_id')->nullable()->constrained('grading_policies')->restrictOnDelete();
            $table->json('policy_snapshot')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('score', 5, 2)->nullable()->change();
            $table->index(['department_id', 'session', 'semester', 'course_code'], 'result_course_scope');
        });
        Schema::create('result_revisions', function (Blueprint $table) {
            $table->id();
            // Academic history prevents parent deletion from cascading into recorded results.
            $table->foreignId('result_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('version');
            $table->string('action');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->unsignedBigInteger('approver_id')->nullable();
            $table->text('reason');
            $table->json('before')->nullable();
            $table->json('after');
            $table->timestamp('created_at')->useCurrent();
        });
        Schema::create('result_corrections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('result_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('base_version');
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('proposed');
            $table->text('reason');
            $table->text('decision_reason')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });
        Schema::create('result_appeals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->string('session', 9);
            $table->string('semester');
            $table->string('course_code');
            $table->text('message');
            $table->string('evidence_path')->nullable();
            $table->string('status')->default('open');
            $table->text('response')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        Schema::create('transcript_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->string('session', 9)->nullable();
            $table->string('semester')->nullable();
            $table->string('purpose', 1000);
            $table->string('status')->default('pending');
            $table->text('decision_reason')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        Schema::create('transcript_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transcript_request_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->uuid('verification_code')->unique();
            $table->string('path')->unique();
            $table->unsignedInteger('version');
            $table->boolean('official')->default(false);
            $table->string('status')->default('valid');
            $table->json('result_versions');
            $table->string('sha256', 64)->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('revocation_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transcript_documents');
        Schema::dropIfExists('transcript_requests');
        Schema::dropIfExists('result_appeals');
        Schema::dropIfExists('result_corrections');
        Schema::dropIfExists('result_revisions');
        Schema::table('results', function (Blueprint $table) {
            $table->dropForeign(['grading_policy_id']);
            $table->dropForeign(['submitted_by']);
            $table->dropForeign(['approved_by']);
            $table->dropIndex('result_course_scope');
            $table->dropColumn(['workflow_status', 'outcome_status', 'attempt_type', 'attempt_number', 'version', 'grading_policy_id', 'policy_snapshot', 'published_at', 'submitted_by', 'approved_by']);
        });
        Schema::dropIfExists('grading_policies');
    }
};
