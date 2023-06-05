<?php

use Illuminate\Database\Seeder;

class AccountTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        \App\Account::truncate();

        $faker = \Faker\Factory::create();

        // And now, let's create a few articles in our database:
        for ($i = 0; $i < 500; $i++) {
            \App\Account::create([
                'title' => "Mrs",
                'phone' => $faker->numberBetween(10000000,90000000),
                'full_name'=>$faker->name('Male'),
                'type'=>'CC',
                'class'=>'A'
            ]);
        }
    }
}
