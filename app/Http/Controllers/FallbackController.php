<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FallbackController extends Controller
{
    //

    public function index($bot)
    {

        $keyboard = [
            ["/main", '/about', '/callback']
        ];

        $bot->reply("Данная команда не найдена, попробуйте одну из списка!\xF0\x9F\x98\x81", [ 'reply_markup' =>json_encode([
            'keyboard' => $keyboard,
            'one_time_keyboard' => true,
            'resize_keyboard' => true
        ]) ]);

    }
}
