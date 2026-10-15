<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channels', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('chat_id')->unique();
            $table->string('username')->nullable();
            $table->string('language', 5)->default('fa');
            $table->text('template_text');
            $table->json('template_buttons');
            $table->timestamps();
        });

        Schema::create('channel_project', function (Blueprint $table) {
            $table->foreignId('channel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_default')->default(false);
            $table->primary(['channel_id', 'project_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_project');
        Schema::dropIfExists('channels');
    }
};
