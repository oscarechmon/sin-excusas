<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Service;
use Illuminate\Database\Seeder;

/**
 * Personal inicial. Los especialistas se crean sin cuenta de usuario para
 * mostrar que un trabajador puede existir sin acceso al sistema (§25).
 */
class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $employees = [
            ['name' => 'Ana Torres', 'position' => 'Especialista facial'],
            ['name' => 'Lucía Ramos', 'position' => 'Especialista corporal'],
            ['name' => 'Carmen Díaz', 'position' => 'Podóloga'],
        ];

        $serviceIds = Service::pluck('id');

        foreach ($employees as $data) {
            $employee = Employee::firstOrCreate(
                ['name' => $data['name']],
                $data + ['active' => true]
            );

            if ($employee->services()->doesntExist() && $serviceIds->isNotEmpty()) {
                $employee->services()->sync($serviceIds);
            }
        }
    }
}
