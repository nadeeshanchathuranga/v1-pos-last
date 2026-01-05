<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompanyInfoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Insert a record into company_infos table
        DB::table('company_infos')->insert([
            'id' => 1,
            'name' => 'Amaak Multi Traders.',
            'address' => '71, Havelock Road, Colombo 05.',
            'phone' => '0705853555',
            'phone2' => '',
            'email' => 'amaansabith@gmail.com',
            'website' => '',
            'logo' => '/images/jaan_logo.jpg',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
