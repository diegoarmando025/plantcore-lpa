<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('plants', function (Blueprint $t) {
            $t->id(); $t->string('code', 25)->unique(); $t->string('name'); $t->timestamps();
        });
        Schema::create('plant_user', function (Blueprint $t) {
            $t->id(); $t->foreignId('plant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('role', 20); $t->unique(['plant_id', 'user_id']);
        });
        Schema::create('audit_templates', function (Blueprint $t) {
            $t->id(); $t->foreignId('plant_id')->constrained()->cascadeOnDelete();
            $t->string('name'); $t->string('area'); $t->boolean('active')->default(true); $t->timestamps();
        });
        Schema::create('audit_questions', function (Blueprint $t) {
            $t->id(); $t->foreignId('audit_template_id')->constrained()->cascadeOnDelete();
            $t->string('text'); $t->unsignedInteger('sort_order')->default(0);
        });
        Schema::create('audits', function (Blueprint $t) {
            $t->id(); $t->foreignId('plant_id')->constrained()->restrictOnDelete();
            $t->foreignId('audit_template_id')->constrained()->restrictOnDelete();
            $t->foreignId('assigned_to')->constrained('users')->restrictOnDelete();
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->date('scheduled_for'); $t->string('status', 20)->default('planned');
            $t->timestamp('completed_at')->nullable(); $t->timestamps();
        });
        Schema::create('audit_answers', function (Blueprint $t) {
            $t->id(); $t->foreignId('audit_id')->constrained()->cascadeOnDelete();
            $t->foreignId('audit_question_id')->constrained()->restrictOnDelete();
            $t->string('result', 10); $t->text('note')->nullable(); $t->timestamps();
            $t->unique(['audit_id','audit_question_id']);
        });
        Schema::create('corrective_actions', function (Blueprint $t) {
            $t->id(); $t->foreignId('audit_answer_id')->unique()->constrained()->restrictOnDelete();
            $t->foreignId('owner_id')->constrained('users')->restrictOnDelete();
            $t->text('description'); $t->string('status', 20)->default('open');
            $t->date('due_date')->nullable(); $t->timestamp('closed_at')->nullable(); $t->timestamps();
        });
    }
    public function down(): void
    {
        foreach (['corrective_actions','audit_answers','audits','audit_questions','audit_templates','plant_user','plants'] as $name) Schema::dropIfExists($name);
    }
};
