<?php

namespace App\Services;

use Dotenv\Dotenv;
use Illuminate\Database\Capsule\Manager as Capsule;

class Boot
{
    public static function loadEnv()
    {
        // Env
        $env = new Dotenv(BASE_PATH);
        $env->load();
    }

    public static function setDebug()
    {
        // debug
        if ($_ENV['debug'] == "true") {
            define("DEBUG", true);
        }
    }

    public static function setVersion($version)
    {
        $System_Config['version'] = $version;
    }

    public static function setTimezone()
    {
        // config time zone
        date_default_timezone_set($_ENV['timeZone']);
    }

    public static function bootDb()
    {
        // Init Eloquent ORM Connection
        $capsule = new Capsule;
        $capsule->addConnection(Config::getDbConfig(), 'default');
        if ($_ENV['enable_radius'] == 'true') {
            $capsule->addConnection(Config::getRadiusDbConfig(), 'radius');
        }
        $capsule->bootEloquent();
    }
}
