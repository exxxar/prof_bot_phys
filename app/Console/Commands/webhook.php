<?php

namespace App\Console\Commands;

use GuzzleHttp\Client;
use Illuminate\Console\Command;

class webhook extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'webhook';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set webhook for Telegram Bot';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //
        $this->info("Start Telegram webhook");
        $url = $this->ask('Enter ngrok URL here');

        $endpoint = "https://api.telegram.org/bot".env("TELEGRAM_TOKEN")."/setWebhook";
        $client = new Client();
        $urlToken = "https://$url/botman";


        $response = $client->request('POST', $endpoint, ['query' => [
            'url' => $urlToken
        ]]);


        $statusCode = $response->getStatusCode();
        $content = $response->getBody();

        $this->info($content);
    }
}
