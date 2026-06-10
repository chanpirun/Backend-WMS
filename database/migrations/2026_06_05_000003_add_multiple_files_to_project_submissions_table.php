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
        Schema::table('project_submissions', function (Blueprint $table) {
            if (!Schema::hasColumn('project_submissions', 'document_paths')) {
                $table->json('document_paths')->nullable()->after('document_path');
            }
            if (!Schema::hasColumn('project_submissions', 'source_code_paths')) {
                $table->json('source_code_paths')->nullable()->after('source_code_path');
            }
            if (!Schema::hasColumn('project_submissions', 'dataset_paths')) {
                $table->json('dataset_paths')->nullable()->after('dataset_path');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_submissions', function (Blueprint $table) {
            if (Schema::hasColumn('project_submissions', 'document_paths')) {
                $table->dropColumn('document_paths');
            }
            if (Schema::hasColumn('project_submissions', 'source_code_paths')) {
                $table->dropColumn('source_code_paths');
            }
            if (Schema::hasColumn('project_submissions', 'dataset_paths')) {
                $table->dropColumn('dataset_paths');
            }
        });
    }
};
