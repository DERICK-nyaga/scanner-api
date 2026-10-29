<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Station;
use Illuminate\Support\Str;

class ImportStationsCommand extends Command
{
    protected $signature = 'stations:import';
    protected $description = 'Bulk import list of stations into the database';

    public function handle()
    {
        $identifiers = [
            'KE-3PL-Flexcom Kisumu-Rabuor-Station',
            'KE-3PL-Flexcom Baraka-Nanyuki -Station',
            'KE-3PL-Flexcom Chaka-Station',
            'KE-3PL-Flexcom Changamwe-Station',
            'KE-3PL-Flexcom Diani-Station',
            'KE-3PL-Flexcom Dundori Nakuru-Station',
            'KE-3PL-Flexcom Eldama Ravine-Station',
            'KE-3PL-Flexcom-Eldoret-Station',
            'KE-3PL-Flexcom Embu Kianjokoma -Station',
            'KE-3PL-Flexcom Gatunga-Marimanti-Station',
            'KE-3PL-Flexcom Ichuga-Nanyuki -Station',
            'KE-3PL-Flexcom Isebania-Station',
            'KE-3PL-Flexcom-Isiolo-Station',
            'KE-3PL-Flexcom Karagita-Station',
            'KE-3PL-Flexcom Kerugoya-Station',
            'KE-3PL-Flexcom Kirinyaga Kianyaga -Station',
            'KE-3PL-Flexcom Kisauni-Station',
            'KE-3PL-Flexcom Kisumu-Mamboleo -Station',
            'KE-3PL-Flexcom Kisumu-Otonglo-Station',
            'KE-3PL-Flexcom Kisumu Riat Mama j -Station',
            'KE-3PL-Flexcom Kisumu-Station',
            'KE-3PL-Flexcom Kisumu-United mall-Station',
            'KE-3PL-Flexcom Kwale-Station',
            'KE-3PL-Flexcom Lungalunga -Station',
            'KE-3PL-Flexcom Mai Mahiu-Station',
            'KE-3PL-Flexcom Maralal-Station',
            'KE-3PL-Flexcom Masara-Migori -Station',
            'KE-3PL-Flexcom Meru Downtown-station',
            'KE-3PL-Flexcom Meru -Station',
            'KE-3PL-Flexcom Migori Namba -Station',
            'KE-3PL-Flexcom Migori-Station',
            'KE-3PL-Flexcom Mikindani-Station',
            'KE-3PL-Flexcom Mombasa-Station',
            'KE-3PL-Flexcom Mtwapa stage -Station',
            'KE-3PL-Flexcom-Nakuru Downtown-Station',
            'KE-3PL-Flexcom Nakuru-kiamunyi-Station',
            'KE-3PL-Flexcom Nakuru-Station',
            'KE-3PL-Flexcom Nanyuki-Lavis-Station',
            'KE-3PL-Flexcom Nanyuki-Makutano Gawanaz-Station',
            'KE-3PL-Flexcom Nanyuki-Station',
            'KE-3PL-Flexcom Narok-Station',
            'KE-3PL-Flexcom Naromoru-Station',
            'KE-3PL-Flexcom-Nyali-Station',
            'KE-3PL-Flexcom Nyamasaria-Kisumu -Station',
            'KE-3PL-Flexcom Nyeri-Gatitu-Station',
            'KE-3PL-Flexcom Nyeri-Kingongo -Station',
            'KE-3PL-Flexcom Ol Kalou-Station',
            'KE-3PL-Flexcom Piai -Station',
            'KE-3PL-Flexcom Ravine Jebets Salon-Station',
            'KE-3PL-Flexcom Subukia -Station',
            'KE-3PL-Flexcom Thome-Station',
            'KE-3PL-Flexcom Timau-Station',
            'KE-3PL-Flexcom Trinity Enterprise Nakuru -Station',
            'KE-3PL-Flexcom Uriri town center Migori -Station'
        ];

        $createdCount = 0;

        foreach ($identifiers as $identifier) {
            $trimmedIdentifier = trim($identifier);

            // Clean name for display (e.g., "Kisumu Rabuor Station")
            $cleanName = str_replace(['KE-3PL-Flexcom', '-'], ['Station', ' '], $trimmedIdentifier);
            $cleanName = ucwords(trim(preg_replace('/\s+/', ' ', $cleanName)));

            // Generate unique email slug
            $slug = Str::slug($trimmedIdentifier);
            $email = "{$slug}@3plstation.com";

            Station::firstOrCreate(
                ['unique_identifier' => $trimmedIdentifier],
                [
                    'name' => $cleanName,
                    'email' => $email,
                    'password' => bcrypt('password123'),
                    'role' => 'user',
                    'disable_manual_typing' => false,
                ]
            );

            $createdCount++;
        }

        $this->info("Successfully processed {$createdCount} stations!");
    }
}