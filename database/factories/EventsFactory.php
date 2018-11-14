<?php

use Faker\Generator as Faker;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

$factory->define(App\Events::class, function (Faker $faker) {

    return [
        'news_id' => '',
        'description'=>$faker->text(),
        'price'=>$faker->numberBetween(50,200),
        'date_start'=>$faker->date()
    ];
});
