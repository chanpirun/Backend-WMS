<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('project_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->json('tags');
            $table->enum('owner_type', ['individual', 'team'])->default('individual');
            $table->string('owner_name');
            $table->json('team_members')->nullable();
            $table->text('description');
            $table->string('cover_image_path');
            $table->string('document_path');
            $table->string('source_code_path');
            $table->string('dataset_path');
            $table->json('project_image_paths')->nullable();
            $table->string('demo_link')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('review_comment')->nullable();
            $table->enum('visibility', ['public', 'private'])->default('private');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_submissions');
    }
};
