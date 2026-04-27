<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('blast_job_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blast_job_id')->constrained('blast_jobs')->cascadeOnDelete();
            $table->foreignId('wa_group_id')->constrained('wa_groups')->restrictOnDelete();
            $table->enum('status', ['QUEUED', 'RUNNING', 'SUCCESS', 'FAILED'])->default('QUEUED');
            $table->integer('response_code')->nullable();
            $table->text('response_body')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blast_job_details');
    }
};
