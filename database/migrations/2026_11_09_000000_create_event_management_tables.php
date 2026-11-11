<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code', 20)->nullable()->unique();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('role')->default('staff')->index();
            $table->string('phone', 40)->nullable();
            $table->boolean('is_active')->default(true);
        });

        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('student_number', 50)->unique();
            $table->string('name');
            $table->string('email')->nullable()->index();
            $table->string('academic_program')->nullable();
            $table->unsignedSmallInteger('year_of_study')->nullable();
            $table->string('phone', 40)->nullable();
            $table->timestamps();
        });

        Schema::create('venues', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('type')->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_available')->default(true);
            $table->timestamps();
        });

        Schema::create('event_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('event_categories')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('type')->default('competition');
            $table->text('description')->nullable();
            $table->date('event_date')->nullable()->index();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('location')->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->dateTime('registration_opens_at')->nullable();
            $table->dateTime('registration_closes_at')->nullable();
            $table->string('status')->default('draft')->index();
            $table->timestamps();
        });

        Schema::create('event_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('schedule_date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('location')->nullable();
            $table->timestamps();
        });

        Schema::create('event_staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('assignment')->default('coordinator');
            $table->timestamps();
            $table->unique(['event_id', 'user_id', 'assignment']);
        });

        Schema::create('venue_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('booking_date')->index();
            $table->time('start_time');
            $table->time('end_time');
            $table->string('purpose');
            $table->string('status')->default('pending')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_note')->nullable();
            $table->timestamps();
        });

        Schema::create('lecturers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('organization')->nullable();
            $table->timestamps();
        });

        Schema::create('seminars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('topic');
            $table->text('description')->nullable();
            $table->timestamps();
        });
        Schema::create('seminar_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seminar_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lecturer_id')->nullable()->constrained()->nullOnDelete();
            $table->date('session_date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('location')->nullable();
            $table->timestamps();
        });

        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->foreignId('leader_student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->timestamps();
            $table->unique(['event_id', 'name']);
        });
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('pending')->index();
            $table->dateTime('registered_at')->nullable();
            $table->timestamps();
            $table->unique(['event_id', 'student_id']);
        });
        Schema::create('attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('checked_in_at')->nullable();
            $table->string('status')->default('absent');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('judge_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['event_id', 'user_id']);
        });
        Schema::create('judging_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('max_score', 7, 2)->default(100);
            $table->decimal('weight', 7, 2)->default(1);
            $table->timestamps();
        });
        Schema::create('judge_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('judging_criterion_id')->constrained()->cascadeOnDelete();
            $table->foreignId('judge_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('registration_id')->constrained()->cascadeOnDelete();
            $table->decimal('score', 7, 2);
            $table->text('comments')->nullable();
            $table->timestamps();
            $table->unique(['judging_criterion_id', 'judge_user_id', 'registration_id'], 'judge_scores_unique_entry');
        });
        Schema::create('results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('registration_id')->constrained()->cascadeOnDelete();
            $table->decimal('total_score', 9, 2)->default(0);
            $table->unsignedInteger('rank')->nullable();
            $table->string('status')->default('provisional');
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('finalized_at')->nullable();
            $table->timestamps();
            $table->unique(['event_id', 'registration_id']);
        });
        Schema::create('mis_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('direction');
            $table->string('status')->default('pending');
            $table->json('details')->nullable();
            $table->timestamps();
        });

        Schema::create('certificate_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('body')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamps();
        });
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('registration_id')->constrained()->cascadeOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('certificate_templates')->nullOnDelete();
            $table->string('certificate_number')->unique();
            $table->string('type')->default('participation');
            $table->string('file_path')->nullable();
            $table->timestamps();
            $table->unique(['event_id', 'registration_id', 'type']);
        });
        Schema::create('awards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('registration_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('place')->nullable();
            $table->decimal('cash_amount', 12, 2)->default(0);
            $table->string('distribution_status')->default('pending');
            $table->date('distributed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('refreshment_items', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('unit')->default('servings');
            $table->timestamps();
        });
        Schema::create('refreshment_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('refreshment_item_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(0);
            $table->decimal('estimated_cost', 12, 2)->default(0);
            $table->string('status')->default('planned');
            $table->timestamps();
            $table->unique(['event_id', 'refreshment_item_id']);
        });
        Schema::create('refreshment_distributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('refreshment_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('registration_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->dateTime('distributed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->text('reason');
            $table->string('document_path')->nullable();
            $table->string('status')->default('pending')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_note')->nullable();
            $table->timestamps();
        });
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('details')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('refreshment_distributions');
        Schema::dropIfExists('refreshment_plans');
        Schema::dropIfExists('refreshment_items');
        Schema::dropIfExists('awards');
        Schema::dropIfExists('certificates');
        Schema::dropIfExists('certificate_templates');
        Schema::dropIfExists('mis_sync_logs');
        Schema::dropIfExists('results');
        Schema::dropIfExists('judge_scores');
        Schema::dropIfExists('judging_criteria');
        Schema::dropIfExists('judge_assignments');
        Schema::dropIfExists('attendance');
        Schema::dropIfExists('registrations');
        Schema::dropIfExists('teams');
        Schema::dropIfExists('seminar_sessions');
        Schema::dropIfExists('seminars');
        Schema::dropIfExists('lecturers');
        Schema::dropIfExists('venue_bookings');
        Schema::dropIfExists('event_staff');
        Schema::dropIfExists('event_schedules');
        Schema::dropIfExists('events');
        Schema::dropIfExists('event_categories');
        Schema::dropIfExists('venues');
        Schema::dropIfExists('students');
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
            $table->dropColumn(['role', 'phone', 'is_active']);
        });
        Schema::dropIfExists('departments');
    }
};
