<?php

namespace Database\Seeders;

use App\Models\BiometricLocation;
use Illuminate\Database\Seeder;

class BiometricLocationSeeder extends Seeder
{
    public function run(): void
    {
        $locations = [
            'City Admin - Bagsakan',
            'City Admin - Bulilit',
            'City Admin - IBT',
            'City Admin - Mampang',
            'City Admin - Market',
            'City Admin - Slaughterhaus',
            'City Admin - Sta. Cruz',
            'City agri - Ayala',
            'City Agri - Culianan',
            'City Agri - Curuan',
            'City Agri - Manicahan',
            'City Agri - Tumaga',
            'City Agri - Vitali',
            'City Agri- Main',
            'City Budget - Depot',
            'City Disaster (CDRRMO)',
            'City Engineer - Motorpool',
            'City Health Office',
            'City Human Resources',
            'City Registrar',
            'City Vet - Manicahan',
            'City Veterinarian',
            'CMO - BAC',
            'CMO - GAD',
            'CMO - Housing',
            'CMO - Museum',
            'Colegio - Ayala',
            'Colegio - Vitali',
            'Colegio-Vitali',
            'CSWD - CICL',
            'CSWD - Curuan',
            'CSWD - FO1',
            'CSWD - Main',
            'CSWD - OSCA',
            'CSWD - SDC',
            'CSWD - Sta Maria',
            'CSWD-Bahay Silangan',
            'CSWD-Trinity',
            'General Services Office',
            'OCNR',
            'Sanggunian Panlungsod',
            'Secretary to the SP',
            'Vitali Barangay Hall',
        ];

        foreach ($locations as $name) {
            BiometricLocation::firstOrCreate(['name' => $name]);
        }
    }
}
