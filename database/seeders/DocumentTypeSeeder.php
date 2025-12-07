<?php

namespace Database\Seeders;

use App\Models\Company\DocumentType;
use Illuminate\Database\Seeder;

class DocumentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultTypes = [
            [
                'name_ar' => 'National ID / Residence',
                'name_en' => 'National ID / Residence',
                'slug' => 'national-id-residence',
                'is_default' => true,
                'company_id' => null,
                'order' => 1,
                'is_active' => true,
            ],
            [
                'name_ar' => 'Passport',
                'name_en' => 'Passport',
                'slug' => 'passport',
                'is_default' => true,
                'company_id' => null,
                'order' => 2,
                'is_active' => true,
            ],
            [
                'name_ar' => 'Employment Contract',
                'name_en' => 'Employment Contract',
                'slug' => 'employment-contract',
                'is_default' => true,
                'company_id' => null,
                'order' => 3,
                'is_active' => true,
            ],
            [
                'name_ar' => 'Experience Certificates',
                'name_en' => 'Experience Certificates',
                'slug' => 'experience-certificates',
                'is_default' => true,
                'company_id' => null,
                'order' => 4,
                'is_active' => true,
            ],
            [
                'name_ar' => 'Training Certificates',
                'name_en' => 'Training Certificates',
                'slug' => 'training-certificates',
                'is_default' => true,
                'company_id' => null,
                'order' => 5,
                'is_active' => true,
            ],
            [
                'name_ar' => 'CV / Resume',
                'name_en' => 'CV / Resume',
                'slug' => 'cv-resume',
                'is_default' => true,
                'company_id' => null,
                'order' => 6,
                'is_active' => true,
            ],
        ];

        // First, deactivate all existing default types
        DocumentType::where('is_default', true)
            ->whereNull('company_id')
            ->update(['is_active' => false]);

        // Then, create or update only the required types
        foreach ($defaultTypes as $type) {
            DocumentType::updateOrCreate(
                ['slug' => $type['slug']],
                $type
            );
        }
    }
}
