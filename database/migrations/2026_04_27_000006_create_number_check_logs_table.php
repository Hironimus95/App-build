<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('number_check_logs', function (Blueprint $table) {
            $table->id();
            $table->string('source_db');
            $table->string('raw_number');
            $table->string('normalized_number')->index();
            $table->boolean('is_valid');
            $table->boolean('exists_in_source');
            $table->timestamp('checked_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('number_check_logs');
    }
};
