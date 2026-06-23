<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_submissions', function (Blueprint $table) {
            $table->foreignId('project_type_id')->nullable()->after('user_id')->constrained('project_types')->nullOnDelete();
            $table->json('team_member_ids')->nullable()->after('team_members');
        });
    }

    public function down(): void
    {
        Schema::table('project_submissions', function (Blueprint $table) {
            $table->dropForeign(['project_type_id']);
            $table->dropColumn(['project_type_id', 'team_member_ids']);
        });
    }
};
