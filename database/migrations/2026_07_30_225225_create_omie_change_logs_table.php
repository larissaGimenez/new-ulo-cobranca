<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('omie_change_logs', function (Blueprint $table) {
            $table->id();
            $table->string('ulo_source');
            $table->string('entity_type');
            $table->bigInteger('entity_id');
            $table->string('action');
            $table->jsonb('details')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('omie_change_logs');
    }
};
