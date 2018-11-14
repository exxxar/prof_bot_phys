<?php

use Illuminate\Database\Seeder;

class EventsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $items = DB::select('SELECT `news`.* 
        FROM `news` 
        LEFT JOIN `events` ON (`events`.`news_id`=`news`.`id`) 
        WHERE `news`.`is_event`=1 and `events`.`id` IS NULL');

        foreach($items as $i)
            factory(App\Events::class, 1)->create(['news_id'=>$i->id]);
    }
}
