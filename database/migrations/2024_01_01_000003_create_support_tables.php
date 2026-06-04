<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('command');
            $table->integer('exit_code')->nullable();
            $table->integer('duration_ms')->nullable();
            $table->string('source', 20)->default('exec'); // exec|stream|gui
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        Schema::create('inventory_hosts', function (Blueprint $table) {
            $table->id();
            $table->string('hostname')->unique();
            $table->string('ip_address', 45)->nullable();
            $table->json('groups')->nullable();
            $table->json('vars')->nullable();
            $table->timestamp('last_ping')->nullable();
            $table->enum('ping_status', ['success','failed','unreachable','unknown'])->default('unknown');
            $table->json('ansible_facts')->nullable();
            $table->timestamps();
        });

        Schema::create('scheduled_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('playbook');
            $table->string('inventory')->nullable();
            $table->json('extra_vars')->nullable();
            $table->string('tags')->nullable();
            $table->string('limit')->nullable();
            $table->string('cron_expression');
            $table->boolean('enabled')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->timestamps();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_jobs');
        Schema::dropIfExists('inventory_hosts');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('failed_jobs');
    }
};
