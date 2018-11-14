<?php
/**
 * Created by PhpStorm.
 * User: exxxa
 * Date: 14.11.2018
 * Time: 13:17
 */
namespace App\Library;

use Swift_Mailer;
use Swift_Message;
use Swift_SmtpTransport;

class CustomMail
{
    public function sendEMail($subject, $message)
    {
        $transport = (new Swift_SmtpTransport('smtp.yandex.ru', 465))
            ->setUsername(env("MAIL_LOGIN"))
            ->setPassword(env("MAIL_PASSWORD"))
            ->setEncryption('SSL');

        $mailer = new Swift_Mailer($transport);

        $message = (new Swift_Message($subject))
            ->setFrom([env("MAIL_LOGIN") . '@yandex.ru' => 'Профбюро Физико-технического факультета'])
            ->setTo([env("MAIL_ADMIN_EMAIL")])
            ->setBody($message);

        $mailer->send($message);
    }
}