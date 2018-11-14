<?php

namespace App\Conversations;

use App\Category;
use ATehnix\VkClient\Client;
use ATehnix\VkClient\Requests\Request;
use BotMan\BotMan\Messages\Attachments\Image;
use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;
use BotMan\BotMan\Messages\Outgoing\Question;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use TelegramBot\Api\BotApi;

class MainConversation extends Conversation
{

    public function defaultQuestion()
    {
        $question = Question::create('Добро пожаловать в уютное профбюро Физико-Технического факультета!')
            ->addButtons(array(
                Button::create('Новости')->value('news'),
                Button::create('Мероприятия')->value('events'),
                Button::create('Магазин сувениров')->value('shop'),
                Button::create('Состав профбюро')->value('contacts'),
                Button::create('Как нас найти?')->value('map'),
                Button::create('Полезные ссылки')->value('links'),
            ));

        return $this->say($question);
    }


    public function run()
    {

        $this->defaultQuestion();
    }
}
