<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('playbook_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('playbook');
            $table->string('inventory');
            $table->text('command');
            $table->json('extra_vars')->nullable();
            $table->string('tags')->nullable();
            $table->string('limit')->nullable();
            $table->boolean('check_mode')->default(false);
            $table->enum('status', ['queued','running','success','failed','error','aborted'])->default('queued');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->integer('exit_code')->nullable();
            $table->text('summary')->nullable();
            $table->integer('hosts_ok')->default(0);
            $table->integer('hosts_changed')->default(0);
            $table->integer('hosts_unreachable')->default(0);
            $table->integer('hosts_failed')->default(0);
            $table->integer('hosts_skipped')->default(0);
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('user_id');
        });

        Schema::create('job_output_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('playbook_jobs')->cascadeOnDelete();
            $table->text('line');
            $table->string('type', 20)->default('output'); // output|error|changed|ok|recap
            $table->timestamp('created_at')->useCurrent();

            $table->index('job_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_output_lines');
        Schema::dropIfExists('playbook_jobs');
    }
};
