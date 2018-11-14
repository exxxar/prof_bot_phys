<?php

namespace App\Http\Controllers;

use App\Conversations\MainConversation;
use App\UsersLog;
use BotMan\BotMan\BotMan;
use BotMan\BotMan\Messages\Attachments\Image;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;
use BotMan\BotMan\Messages\Outgoing\Question;
use Illuminate\Http\Request;
use App\Conversations\ExampleConversation;
use Illuminate\Support\Facades\Log;

class BotManController extends Controller
{
    /**
     * Place your BotMan logic here.
     */
    public function handle()
    {
        $botman = app('botman');

        $botman->listen();
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function tinker()
    {
        return view('tinker');
    }



    public function startConversation(BotMan $bot){


        $userlog = new UsersLog;
        $userlog->name = $bot->getUser()->getFirstName()." ".$bot->getUser()->getLastName();
        $userlog->nickname = $bot->getUser()->getUsername();
        $userlog->chat_id = $bot->getMessage()->getRecipient();
        $userlog->info =  "";
        $userlog->ip = $this->getIp();
        $userlog->description = "стартовый экран";
        $userlog->save();

        $attachment = new Image('https://pp.userapi.com/c845523/v845523778/1014cd/LxaIrZvzqGk.jpg');
        $message = OutgoingMessage::create('Физико-Технический факультет')
            ->withAttachment($attachment);

        $bot->reply($message);

        $question = Question::create('*Начни с этого момента*')
            ->addButtons([
                Button::create("Главное меню")->value('main'),
                Button::create("О проекте")->value('about'),
            ]);

        $bot->reply($question,["parse_mode"=>"Markdown"]);


    }


    public function getMainMenu(BotMan $bot){
        $bot->startConversation(new MainConversation);
    }

    public function getAbout(BotMan $bot){
        $question = Question::create('*Данный Бот разработан Ростиславом Горбачевым*')
            ->addButtons([
                Button::create("Главное меню")->value('main'),
                Button::create("О проекте")->value('about'),
            ]);

        $bot->reply($question,["parse_mode"=>"Markdown"]);
    }


}
