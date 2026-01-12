<?php

namespace Database\Seeders;

use App\Models\Admin\Company;
use App\Models\Company\LeaveType;
use Illuminate\Database\Seeder;

class DefaultLeaveTypesSeeder extends Seeder
{
    /**
     * Seed default leave types for all existing companies.
     */
    public function run(): void
    {
        $defaultTypes = [
            [
                'name' => 'Annual Leave / Vacation',
                'description' => 'Paid annual leave for vacation and personal time off.',
                'yearly_balance' => 21,
            ],
            [
                'name' => 'Sick Leave',
                'description' => 'Leave for sickness or medical appointments.',
                'yearly_balance' => 10,
            ],
            [
                'name' => 'Unpaid Leave',
                'description' => 'Unpaid leave for special cases outside the paid policy.',
                'yearly_balance' => 0,
            ],
            [
                'name' => 'Maternity/Paternity Leave',
                'description' => 'Leave related to childbirth or adoption.',
                'yearly_balance' => 90,
            ],
            [
                'name' => 'Emergency Leave',
                'description' => 'Short notice leave for emergencies and urgent situations.',
                'yearly_balance' => 5,
            ],
        ];

        Company::chunk(100, function ($companies) use ($defaultTypes) {
            foreach ($companies as $company) {
                foreach ($defaultTypes as $type) {
                    LeaveType::firstOrCreate(
                        [
                            'company_id' => $company->id,
                            'name' => $type['name'],
                        ],
                        [
                            'description' => $type['description'],
                            'yearly_balance' => $type['yearly_balance'],
                        ]
                    );
                }
            }
        });
    }
}


