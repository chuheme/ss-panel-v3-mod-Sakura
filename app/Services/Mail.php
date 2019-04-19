<?php

namespace App\Services;

/***
 * Mail Service
 */

use App\Services\Mail\Mailgun;
use App\Services\Mail\Ses;
use App\Services\Mail\Smtp;
use App\Services\Mail\SendGrid;
use App\Services\Mail\NullMail;

class Mail
{
    /**
     * @return Mailgun|Ses|Smtp|null
     */
    public static function getClient()
    {
        $driver = Config::get("mailDriver");
        switch ($driver) {
            case "mailgun":
                return new Mailgun();
            case "ses":
                return new Ses();
            case "smtp":
                return new Smtp();
            case "sendgrid":
                return new SendGrid();
            default:
                return new NullMail();
        }
        return null;
    }

    /**
     * @param $template
     * @param $ary
     * @return mixed
     */
    public static function genHtml(string $template, array $ary = [])
    {
        $render = new \Slim\Views\PhpRenderer(BASE_PATH . '/resources/email/');
        return $render->fetch($template, $ary);
    }

    /**
     * @param $to
     * @param $subject
     * @param $template
     * @param $ary
     * @param $file
     * @return bool|void
     */
    public static function send($to, $subject, $template, $ary = [], $file = [])
    {
        $text = self::genHtml($template, $ary);
        return self::getClient()->send($to, $subject, $text, $file);
    }
}
