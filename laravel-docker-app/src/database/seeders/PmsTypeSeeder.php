<?php

namespace Database\Seeders;

use App\Models\PmsType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PmsTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pmsTypes = [
            [
                'name' => 'Opera On-Premises',
                'code' => 'OPERA_ONPREM',
                'description' => 'Oracle Opera Property Management System - On-Premises Installation',
                'setup_requirements' => [
                    'it_contact_scheduled' => [
                        'type' => 'checkbox',
                        'label' => 'IT Contact Scheduled',
                        'description' => 'Has an IT appointment been scheduled?',
                        'required' => true,
                    ],
                    'server_ip' => [
                        'type' => 'text',
                        'label' => 'Opera Server IP Address',
                        'description' => 'IP address of the Opera server',
                        'required' => true,
                        'validation' => 'ip',
                    ],
                    'database_name' => [
                        'type' => 'text',
                        'label' => 'Database Name',
                        'description' => 'Name of the Opera database',
                        'required' => true,
                    ],
                    'interface_user' => [
                        'type' => 'text',
                        'label' => 'Interface Username',
                        'description' => 'Username for interface connections',
                        'required' => true,
                    ],
                    'vpn_required' => [
                        'type' => 'boolean',
                        'label' => 'VPN Required',
                        'description' => 'Is VPN access required?',
                        'required' => true,
                    ],
                ],
                'module_configurations' => [],
                'brand_configurations' => [],
            ],
            [
                'name' => 'OHIP (Opera Cloud)',
                'code' => 'OHIP',
                'description' => 'Oracle Hospitality Integration Platform - Cloud-based Opera',
                'setup_requirements' => [
                    'enterprise_id' => [
                        'type' => 'text',
                        'label' => 'Enterprise ID',
                        'description' => 'OHIP Enterprise ID',
                        'required' => true,
                    ],
                    'property_id' => [
                        'type' => 'text',
                        'label' => 'Property ID',
                        'description' => 'OHIP Property ID',
                        'required' => true,
                    ],
                    'client_id' => [
                        'type' => 'text',
                        'label' => 'Client ID',
                        'description' => 'OAuth Client ID',
                        'required' => true,
                    ],
                    'client_secret' => [
                        'type' => 'password',
                        'label' => 'Client Secret',
                        'description' => 'OAuth Client Secret',
                        'required' => true,
                    ],
                    'environment' => [
                        'type' => 'select',
                        'label' => 'Environment',
                        'description' => 'OHIP Environment',
                        'options' => ['Production', 'UAT', 'Test'],
                        'required' => true,
                    ],
                ],
                'module_configurations' => [],
                'brand_configurations' => [],
            ],
            [
                'name' => 'Protel',
                'code' => 'PROTEL',
                'description' => 'Protel Property Management System',
                'setup_requirements' => [
                    'protel_version' => [
                        'type' => 'select',
                        'label' => 'Protel Version',
                        'description' => 'Version of Protel system',
                        'options' => ['Air', 'MPE', 'OnPremise'],
                        'required' => true,
                    ],
                    'api_endpoint' => [
                        'type' => 'url',
                        'label' => 'API Endpoint',
                        'description' => 'Protel API URL',
                        'required' => true,
                    ],
                    'api_key' => [
                        'type' => 'password',
                        'label' => 'API Key',
                        'description' => 'Protel API Key',
                        'required' => true,
                    ],
                ],
                'module_configurations' => [],
                'brand_configurations' => [],
            ],
        ];

        foreach ($pmsTypes as $pmsType) {
            PmsType::updateOrCreate(
                ['code' => $pmsType['code']],
                $pmsType
            );
        }
    }
}
