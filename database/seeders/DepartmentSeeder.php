<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['name' => 'Ministry of Finance', 'code' => 'MOF'],
            ['name' => 'Ministry of Interior', 'code' => 'MOI'],
            ['name' => 'Ministry of Education', 'code' => 'MOE'],
            ['name' => 'Ministry of Health', 'code' => 'MOH'],
            ['name' => 'Ministry of Public Works', 'code' => 'MPW'],
        ];

        foreach ($departments as $dept) {
            Department::updateOrCreate(
                ['code' => $dept['code']],
                $dept
            );
        }
    }
}
