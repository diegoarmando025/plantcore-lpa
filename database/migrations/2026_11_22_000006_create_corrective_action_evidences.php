<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('corrective_action_evidences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('corrective_action_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->string('disk', 32)->default('local');
            $table->string('path', 255)->unique();
            $table->string('original_name', 255);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size');
            $table->string('note', 500)->nullable();
            $table->timestamps();
            $table->index(['corrective_action_id', 'created_at'], 'ca_evidence_action_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('corrective_action_evidences');
    }
};
