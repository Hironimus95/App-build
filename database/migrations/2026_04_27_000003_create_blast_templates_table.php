<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('blast_templates', function (Blueprint $table) {
            $table->id();
            $table->enum('category', ['INCIDENT', 'MAINTENANCE']);
            $table->string('title');
            $table->text('body');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blast_templates');
    }
};
