<?php

use Faker\Generator as Faker;

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

$factory->define(App\Products::class, function (Faker $faker) {

    return [
        'category_id'=>$faker->numberBetween(0,5),
        'title' => $faker->name,
        'description' => $faker->text(),
        'image' => $faker->imageUrl(),
        'price' => $faker->numberBetween(10.0,2000.0),
        'count' => $faker->numberBetween(10,200),
    ];
});
