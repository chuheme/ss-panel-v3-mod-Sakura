<?php

namespace App\Utils;

use App\Services\Config;

class Telegram
{

    /**
     * ������Ϣ
     */
    public static function Send($messageText)
    {
        if ($_ENV['enable_telegram'] == 'true') {
            $bot = new \TelegramBot\Api\BotApi($_ENV['telegram_token']);

            $bot->sendMessage($_ENV['telegram_chatid'], $messageText);
        }
    }
    
    
    public static function SendMarkdown($messageText)
    {
        if ($_ENV['enable_telegram'] == 'true') {
            $bot = new \TelegramBot\Api\BotApi($_ENV['telegram_token']);

            $bot->sendMessage($_ENV['telegram_chatid'], $messageText, "Markdown");
        }
    }
}
