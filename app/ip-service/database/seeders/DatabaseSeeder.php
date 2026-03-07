<?php

namespace Database\Seeders;

use App\Models\IpAddress;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Mirror
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            ['role' => 'super-admin', 'password' => 'synced']
        );

        $alice = User::firstOrCreate(
            ['email' => 'alice@example.com'],
            ['role' => 'user', 'password' => 'synced']
        );

        $bob = User::firstOrCreate(
            ['email' => 'bob@example.com'],
            ['role' => 'user', 'password' => 'synced']
        );

        // Sample IP addresses
        $ips = [
            // Alice's IPs
            [
                'ip_address' => '192.168.1.10',
                'label' => 'Alice Dev Laptop',
                'comment' => 'Primary development machine',
                'owner_id' => $alice->id,
            ],
            [
                'ip_address' => '10.0.0.5',
                'label' => 'Alice Home Server',
                'comment' => null,
                'owner_id' => $alice->id,
            ],
            [
                'ip_address' => '2001:db8::1',
                'label' => 'Alice IPv6 Node',
                'comment' => 'Test IPv6 entry',
                'owner_id' => $alice->id,
            ],
            // Bob's IPs
            [
                'ip_address' => '172.16.0.100',
                'label' => 'Bob Office PC',
                'comment' => 'Sits at desk 4B',
                'owner_id' => $bob->id,
            ],
            [
                'ip_address' => '10.10.10.1',
                'label' => 'Bob VPN Gateway',
                'comment' => null,
                'owner_id' => $bob->id,
            ],
            // Admin's IPs
            [
                'ip_address' => '203.0.113.1',
                'label' => 'Production Server',
                'comment' => 'External-facing web server',
                'owner_id' => $admin->id,
            ],
            [
                'ip_address' => '198.51.100.42',
                'label' => 'Staging Server',
                'comment' => 'Pre-production environment',
                'owner_id' => $admin->id,
            ],
        ];

        foreach ($ips as $ip) {
            IpAddress::firstOrCreate(
                ['ip_address' => $ip['ip_address']],
                [
                    'label' => $ip['label'],
                    'comment' => $ip['comment'],
                    'owner_id' => $ip['owner_id'],
                ]
            );
        }
    }
}
