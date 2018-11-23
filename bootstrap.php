<?php

/***
 * ss-panel v3 Bootstrap
 * @author orvice
 * @email sspanel@orx.me
 * @url https://github.com/orvice/ss-panel
 */

use App\Services\Boot;

define('VERSION', '20180627');

// Vendor Autoload
require BASE_PATH.'/vendor/autoload.php';



Boot::loadEnv();
Boot::setDebug();
//Boot::setVersion(VERSION);
// config time zone
Boot::setTimezone();
// Init db
Boot::bootDb();
