<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // $this->call(UsersTableSeeder::class);
        DB::table('users')->insert([
            'id'     => 1,
            'name'     => 'Shashank',
            'email'    => 'shashank@codershood.info',
            'password' => Hash::make('shashank'),
            'mobile'=>'950211091',
            'isVerified'=>0
        ]);
    }
}
