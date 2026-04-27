<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('blast_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->enum('category', ['INCIDENT', 'MAINTENANCE']);
            $table->json('payload_json');
            $table->enum('status', ['QUEUED', 'RUNNING', 'DONE', 'FAILED', 'PARTIAL'])->default('QUEUED');
            $table->string('requested_by');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blast_jobs');
    }
};
