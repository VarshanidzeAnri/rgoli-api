<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discipline_user', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('discipline', 20);
            $table->primary(['user_id', 'discipline']);
            $table->index('discipline');
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('key', 10)->unique();
            $table->string('name', 200);
            $table->string('client_name', 160);
            $table->string('client_phone', 40)->nullable();
            $table->string('client_email', 191)->nullable();
            $table->string('address')->nullable();
            $table->string('discipline', 20)->index();
            $table->string('status', 20);
            $table->decimal('budget', 12, 2)->default(0);
            $table->decimal('discount_price', 12, 2)->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->unsignedTinyInteger('progress')->default(0);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable()->index();
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes()->index();
            $table->index(['status', 'discipline']);
        });

        Schema::create('project_user', function (Blueprint $table) {
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['project_id', 'user_id']);
            $table->index('user_id');
        });

        Schema::create('project_phases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name', 200);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status', 20)->default('todo');
            $table->timestamps();
            $table->index(['project_id', 'sort_order']);
        });

        Schema::create('client_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->date('paid_at')->index();
            $table->string('method', 20)->default('bank_transfer');
            $table->text('note')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes()->index();
        });

        Schema::create('issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('number');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status', 20)->default('todo');
            $table->string('priority', 20)->default('medium')->index();
            $table->date('due_date')->nullable()->index();
            $table->unsignedSmallInteger('points')->default(1);
            $table->unsignedInteger('position')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes()->index();
            $table->unique(['project_id', 'number']);
            $table->index(['status', 'position']);
            $table->index(['project_id', 'status']);
        });

        Schema::create('issue_user', function (Blueprint $table) {
            $table->foreignId('issue_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['issue_id', 'user_id']);
            $table->index('user_id');
        });

        Schema::create('sprints', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->text('goal')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('state', 20)->default('planned')->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('issue_sprint', function (Blueprint $table) {
            $table->foreignId('sprint_id')->constrained()->cascadeOnDelete();
            $table->foreignId('issue_id')->constrained()->cascadeOnDelete();
            $table->timestamp('added_at')->nullable();
            $table->primary(['sprint_id', 'issue_id']);
            $table->index('issue_id');
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issue_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['issue_id', 'created_at']);
        });

        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('original_name');
            $table->string('disk', 20)->default('local');
            $table->string('path', 512);
            $table->string('mime', 127);
            $table->unsignedBigInteger('size_bytes');
            $table->string('type', 10)->index();
            $table->timestamps();
            $table->softDeletes()->index();
        });

        Schema::create('salary_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 200);
            $table->decimal('amount', 12, 2);
            $table->text('note')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('decision_note')->nullable();
            $table->timestamps();
            $table->softDeletes()->index();
            $table->index(['user_id', 'status']);
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subject_type', 120);
            $table->unsignedBigInteger('subject_id');
            $table->string('event', 50);
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->index(['project_id', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->string('slug', 50)->nullable()->unique();
            $table->boolean('is_system')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn(['slug', 'is_system']);
        });

        foreach (['activity_logs', 'salary_requests', 'files', 'comments', 'issue_sprint', 'sprints', 'issue_user', 'issues', 'client_payments', 'project_phases', 'project_user', 'projects', 'discipline_user'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
