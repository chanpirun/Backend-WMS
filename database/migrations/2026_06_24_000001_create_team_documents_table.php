<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * This table stores standalone team contributions (NOT linked to project submissions).
     * Any member can post documentation visible to tagged co-authors.
     */
    public function up(): void
    {
        Schema::create('team_documents', function (Blueprint $table) {
            $table->id();

            // Submitter
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Contribution title
            $table->string('title');

            // Description / notes (optional)
            $table->text('description')->nullable();

            // Tagged co-authors (JSON array of user IDs)
            $table->json('tagged_member_ids')->nullable();

            // Tagged co-author names (JSON array — denormalized for display)
            $table->json('tagged_member_names')->nullable();

            // File paths (each nullable — member uploads only what they have)
            $table->string('manual_doc_path')->nullable();
            $table->string('manual_doc_name')->nullable();

            $table->string('source_code_path')->nullable();
            $table->string('source_code_name')->nullable();

            $table->string('database_path')->nullable();
            $table->string('database_name')->nullable();

            $table->string('final_doc_path')->nullable();
            $table->string('final_doc_name')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_documents');
    }
};
