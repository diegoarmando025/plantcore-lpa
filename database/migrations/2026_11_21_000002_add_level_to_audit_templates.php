<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('audit_templates', function (Blueprint $table): void {
            $table->unsignedTinyInteger('level')->default(1);
        });
    }
    public function down(): void {
        Schema::table('audit_templates', fn (Blueprint $table) => $table->dropColumn('level'));
    }
};
