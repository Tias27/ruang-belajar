<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = \Faker\Factory::create('id_ID');
        
        $programStudis = [
            'Teknik Informatika',
            'Sistem Informasi',
            'Teknik Elektro',
            'Teknik Sipil',
            'Ilmu Komunikasi',
            'Manajemen',
            'Akuntansi',
            'Psikologi',
            'Kedokteran',
            'Hukum',
            'Sastra Inggris',
            'Hubungan Internasional'
        ];

        $users = [];
        $password = Hash::make('password');

        for ($i = 0; $i < 150; $i++) {
            $name = $faker->name;
            // Create a clean username from the name
            $cleanName = preg_replace('/[^a-zA-Z0-9 ]/', '', $name);
            $username = strtolower(str_replace(' ', '.', $cleanName)) . rand(10, 99);
            
            $users[] = [
                'name' => $name,
                'username' => $username,
                'nim' => $faker->unique()->numerify('##########'),
                'program_studi' => $faker->randomElement($programStudis),
                'angkatan' => $faker->numberBetween(2021, 2025),
                'role' => 'mahasiswa',
                'email' => $username . '@student.univ.ac.id',
                'password' => $password,
                'created_at' => now()->subDays(rand(1, 30)),
                'updated_at' => now(),
            ];
        }

        // Insert in chunks of 50
        foreach (array_chunk($users, 50) as $chunk) {
            User::insert($chunk);
        }
    }
}
