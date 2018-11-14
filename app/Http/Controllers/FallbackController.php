<?php

namespace App\Http\Controllers;

use App\UsersLog;
use BotMan\BotMan\BotMan;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\Question;
use Illuminate\Http\Request;

class FallbackController extends Controller
{
    //

    public function index(BotMan $bot)
    {

        $userlog = new UsersLog;
        $userlog->name = $bot->getUser()->getFirstName()." ".$bot->getUser()->getLastName();
        $userlog->nickname = $bot->getUser()->getUsername();
        $userlog->chat_id = $bot->getMessage()->getRecipient();
        $userlog->info = "";
        $userlog->ip = $this->getIp();
        $userlog->description = "Ошибка в написании команды:".$bot->getMessage()->getText();
        $userlog->save();

        $question = Question::create("Данная команда не найдена, попробуйте одну из списка!\xF0\x9F\x98\x81")
            ->addButtons([
                Button::create("Главное меню")->value('main'),
                Button::create("Стартовый экран")->value('/start')
            ]);

        $bot->reply($question);

    }
}
