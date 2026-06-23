<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProjectTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            'Manuscript',
            'Frontend',
            'Backend',
            'Database',
            'Postman',
        ];

        foreach ($types as $name) {
            DB::table('project_types')->updateOrInsert(
                ['name' => $name],
                ['is_default' => true, 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }
}
