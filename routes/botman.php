<?php
use App\Http\Controllers\BotManController;
use ATehnix\VkClient\Client;
use ATehnix\VkClient\Requests\Request;
use BotMan\BotMan\BotMan;
use BotMan\BotMan\Messages\Attachments\Image;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\Drivers\Telegram\Extensions\Keyboard;
use BotMan\Drivers\Telegram\Extensions\KeyboardButton;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use TelegramBot\Api\BotApi;

$botman = resolve('botman');

$botman->hears("category ([0-9]+) ([0-9]+)",function($bot,$category,$page){

    $page=$page<0?0:$page;

    $products = DB::select('SELECT * FROM `products` WHERE `category_id`=? LIMIT 5 OFFSET '.($page*5), [$category]);

    if (count($products)>0) {
        foreach ($products as $p) {

            $attachment = new Image($p->image);
            $message = OutgoingMessage::create("$p->price Руб.\n_Осталось $p->count шт._")
                ->withAttachment($attachment);
            $bot->reply($message, ["parse_mode" => "Markdown"]);
            $question = Question::create("_Описание товара:_" . $p->description)
                ->addButtons([
                        Button::create("Добавить в корзину " . $p->title)->value('products ' . $p->id),
                        Button::create("Категории")->value('select_category'),
                        Button::create("Главная")->value('main')]
                );

            $bot->reply($question, ["parse_mode" => "Markdown"]);
        }
    }
    else {
        $question = Question::create("В данной категории товара нет!:(")
            ->addButtons([
                Button::create("Меню магазина")->value('shop'),
                Button::create("Главное меню")->value('main '),

            ]);
        $bot->reply($question,["parse_mode"=>"Markdown"]);
    }


    $question = Question::create("Что делаем дальше?")
        ->addButtons([
                Button::create("Предидущая подборка")->value('category '.$category.' '.($page-1>=0?$page-1:0)),
                Button::create("Следующая подборка")->value('category '.$category.' '.($page+1)),

        ]);

    $bot->reply($question,["parse_mode"=>"Markdown"]);
});

$botman->hears("clear_basket",function(BotMan $bot){
    $id= $bot->getMessage()->getRecipient();
    DB::delete("DELETE FROM `basket` WHERE `chat_id`=?",[$id]);

    $question = Question::create("*Меню корзины*\nКорзина пуста!")
        ->addButtons([
            Button::create("Главное меню")->value('main'),
            Button::create("<<Назад")->value('shop')
        ]);

    $bot->reply($question,["parse_mode"=>"Markdown"]);
});

$botman->hears("basket",function(BotMan $bot){
    $id= $bot->getMessage()->getRecipient();
    $basket=DB::select("SELECT `basket`.*,`products`.`price` FROM `basket` 
LEFT JOIN `products` ON (`products`.`id`=`basket`.`product_id`)
WHERE `basket`.`chat_id`=?",[$id]);

    if (count($basket)>=1) {

       $bot->reply("Товаров в корзине: " .count($basket));
       $sum = 0;
       for($i=0;$i<count($basket);$i++){
           $product=DB::select("SELECT * FROM `products` WHERE `id`=?",[$basket[$i]->product_id]);
           $sum +=$product[0]->price;
           $bot->reply(($i+1).") _".$product[0]->title."_ *".$product[0]->price."* Руб. ",["parse_mode"=>"Markdown"] );
       }
        $bot->reply("Сумарная цена *$sum* Руб. ",["parse_mode"=>"Markdown"] );

        $question = Question::create('*Меню корзины*')
            ->addButtons([
                Button::create("Заказать")->value('buy'),
                Button::create("Очистить корзину")->value('clear_basket'),
                Button::create("<<Назад")->value('shop')
            ]);
    }
    else
        $question = Question::create("*Меню корзины*\nКорзина пуста")
            ->addButtons([
                Button::create("<<Назад")->value('shop')
            ]);

    $bot->reply($question,["parse_mode"=>"Markdown"]);
});


$botman->hears("select_category",function(BotMan $bot){
    $categories = DB::table('category')->get();

    $buttons = [];
    foreach ($categories as $c)
        array_push($buttons,
            Button::create($c->name)->value('category '.$c->id.' 0'));

    $question = Question::create('Какая категория вас интересует?')
        ->addButtons($buttons)
        ->addButton(Button::create("<<Назад")->value('shop'));

    $bot->reply($question);
});

$botman->hears("buy",function(BotMan $bot){
    $id= $bot->getUser()->getId();
    DB::Table("basket")->where("chat_id","=","$id")->get();


    $question = Question::create("Спасибо! Товар на заказ отправлен!")
        ->addButton(Button::create("На главную")->value('main'));
    $bot->reply($question);

});


$botman->hears("products ([0-9]+)",function(BotMan $bot,$productId){
    Log::info("ProducId=".$productId);
    $basket = new \App\Basket;
    $basket->chat_id = $bot->getUser()->getId();
    $basket->product_id = $productId;
    $basket->save();

    $question = Question::create("Спасибо! Товар добавлен в корзину!")
        ->addButton(Button::create("Перейти в корзину")->value('basket'));
    $bot->reply($question);

});

$botman->hears("contacts",function(BotMan $bot) {
    $bot->reply("Контактные данные членов профбюро:");
    $api = new Client;
    $request = new Request('groups.getById', ['group_ids' => 14019468,'fields'=>'contacts'], env("VK_SERVICE_KEY"));

    $response = $api->send($request);

    $users = [];
    $usersIds = [];

    foreach ($response["response"] as $item){
        foreach ($item["contacts"] as $contact){
            // $u = $user;
            $u["id"] = $contact["user_id"];

            if (isset($contact["desc"]))
                $u["desc"] =$contact["desc"];

            if (array_has($contact,"phone"))
                $u["phone"]=$contact["phone"];
            else
                $u["phone"]="";

            array_push($users,$u);
            array_push($usersIds,$u["id"]);
        }
    }

    $requestUser = new Request('users.get', ['user_ids' => $usersIds,'fields'=>'photo_max'], env("VK_SERVICE_KEY"));
    $response = $api->send($requestUser);

    foreach ($response["response"] as $item){

        for($i=0;$i<count($users);$i++){
            if ($item["id"]==$users[$i]["id"]){
                $attachment = new Image($item["photo_max"]);

                $message = OutgoingMessage::create($item["first_name"]." ".$item["last_name"]."\n".$users[$i]["desc"]."\n"."https://vk.com/id".$users[$i]["id"]."\n".(array_has($users[$i],"phone")?$users[$i]["phone"]:"")
                )
                    ->withAttachment($attachment);
                $bot->reply($message,["parse_mode"=>"Markdown"]);
            }
        }

    }

    $question = Question::create("Что делаем дальше?")
        ->addButtons([
            Button::create("Возвращаемся на главную")->value('main'),
        ]);
     $bot->reply($question,["parse_mode"=>"Markdown"]);
});

$botman->hears("map",function(BotMan $bot) {

    $bot->sendRequest("sendLocation",[
        "chat_id"=>$bot->getMessage()->getRecipient(),
        "latitude"=>"48.0057338",
        "longitude"=>"37.7960337"
    ]);
    $bot->reply("г.Донецк, пр. Театральный,13, Кабинет 105");

    $question = Question::create("Что делаем дальше?")
        ->addButtons([
            Button::create("Возвращаемся на главную")->value('main'),
        ]);
    $bot->reply($question,["parse_mode"=>"Markdown"]);
});


$botman->hears("links",function(BotMan $bot) {
    $bot->reply("Полезные ссылки:");
    $api = new Client;
    $request = new Request('groups.getById', ['group_ids' => 14019468,'fields'=>'links'], env("VK_SERVICE_KEY"));

    $response = $api->send($request);

    foreach ($response["response"] as $item){
        foreach ($item["links"] as $link){
            // $u = $user;

            $attachment = new Image($link["photo_100"]);
            $message = OutgoingMessage::create($link["name"]."\n".$link["url"]
            )
                ->withAttachment($attachment);
            $bot->reply($message);
        }
    }


    $question = Question::create("Что делаем дальше?")
        ->addButtons([
            Button::create("Возвращаемся на главную")->value('main'),
        ]);
    $bot->reply($question,["parse_mode"=>"Markdown"]);
});

$botman->hears("shop",function($bot) {

    $question = Question::create('*Меню магазина*')
        ->addButtons([
            Button::create("Корзина")->value('basket'),
            Button::create("Категории товара")->value('select_category'),
            Button::create("<<Назад")->value('main')
        ]);

    $bot->reply($question,["parse_mode"=>"Markdown"]);
});

$botman->hears("pay",function($bot) {
    $chatId =  $this->getBot()->getMessage()->getRecipient();
    $title = "asdas";
    $description = "asdasd";
    $payload = "asdasd";
    $providerToken = "632593626:TEST:i56982357197";
    $startParameter = "asdasd";
    $currency = "UAH";
    $prices =[
        ['label' => 'Powerball 250Hz Classic Blue', 'amount' => 55100],
        ['label' => 'Powerball 250Hz Pro Blue', 'amount' => 79600],
        ['label' => 'Powerball 280Hz Autostart', 'amount' => 146400]
    ] ;
    $isFlexible = true;



    $result = $this->getBot()->getDriver()->sendRequest("sendInvoice",
        [
            'title' => $title,
            'description' => $description,
            'payload' => $payload,
            'provider_token' => $providerToken,
            'start_parameter' => $startParameter,
            'currency' => $currency,
            'prices' => json_encode($prices),
            //'is_flexible' => $isFlexible
        ]
        , $this->getBot()->getDriver()->getMessage());

    $this->getBot()->getDriver()->loadMessages();
});

$botman->hears("events",function($bot) {

});

$botman->hears("news",function($bot){
    $news= DB::table('news')->skip(0)->take(5)->get();

    $hasNews=false;
    foreach($news as $n) {
        if ($n->is_event)
            continue;

        $hasNews=true;
        $attachment = new Image($n->image);
        $message = OutgoingMessage::create("*$n->title*  \n[$n->date]\n\n  $n->message")
            ->withAttachment($attachment);

        $bot->reply($message,["parse_mode"=>"Markdown"]);
    }

    if (!$hasNews) {
        $question = Question::create("Новостей нет:(\nЧто делаем дальше?")
            ->addButtons(array(
                Button::create('Главная')->value('main')
            ));
        $bot->reply($question, ["parse_mode" => "Markdown"]);
    }


    if ($hasNews) {
        $question = Question::create('Что делаем дальше?')
            ->addButtons(array(
                Button::create('Главная')->value('main'),
                Button::create('Вперед')->value('next news 0'),
                Button::create('Назад')->value('prev news 5')
            ));

        $bot->reply($question, ["parse_mode" => "Markdown"]);
    }
});

$botman->hears(".*news ([0-9]+)",function($bot,$page){

    $page=$page<0?0:$page;

    $news= DB::table('news')->skip($page)->take(5)->get();

    if (count($news)>0) {
        foreach ($news as $n) {
            $attachment = new Image($n->image);
            $message = OutgoingMessage::create("*$n->title*  \n[$n->date]\n\n  _ $n->message _")
                ->withAttachment($attachment);

            $bot->reply($message, ["parse_mode" => "Markdown"]);
        }

        $question = Question::create('Что делаем дальше?')
            ->addButtons(array(
                Button::create('Главная')->value('main'),
                Button::create('Вперед')->value('next news ' . ($page + 5)),
                Button::create('Назад')->value('prev news ' . ($page - 5 > 0 ? $page - 5 : $page)),

            ));

        $bot->reply($question, ["parse_mode" => "Markdown"]);

    }
    else
    {
        $question = Question::create('Новостей нет!Что делаем дальше?')
            ->addButtons(array(
                Button::create('Главная')->value('main'),
                Button::create('В начало')->value('news '),
                Button::create('Назад')->value('prev news ' . ($page - 5 > 0 ? $page - 5 : $page)),

            ));

        $bot->reply($question, ["parse_mode" => "Markdown"]);
    }
});



$botman->hears('/start', BotManController::class.'@startConversation');
$botman->hears('main', BotManController::class.'@getMainMenu');
$botman->hears('about', BotManController::class.'@getAbout');

$botman->fallback('App\Http\Controllers\FallbackController@index');

