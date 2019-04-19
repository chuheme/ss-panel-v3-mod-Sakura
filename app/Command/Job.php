<?php

namespace App\Command;

use App\Models\Node;
use App\Models\User;
use App\Models\RadiusBan;
use App\Models\LoginIp;
use App\Models\Speedtest;
use App\Models\Shop;
use App\Models\Bought;
use App\Models\Coupon;
use App\Models\Ip;
use App\Models\NodeInfoLog;
use App\Models\NodeOnlineLog;
use App\Models\TrafficLog;
use App\Models\DetectLog;
use App\Models\BlockIp;
use App\Models\TelegramSession;
use App\Models\EmailVerify;
use App\Models\Relay;
use App\Services\Config;
use App\Utils\Radius;
use App\Utils\Tools;
use App\Services\Mail;
use App\Utils\QQWry;
use App\Utils\GA;
use App\Utils\Telegram;
use App\Models\Disconnect;
use App\Models\UnblockIp;
use Cloudflare\API as Cloudflare;
use CloudXNS\Api as CloudXNS;

class Job
{
    public static function syncnode()
    {
        $nodes = Node::all();
        foreach ($nodes as $node) {
            if ($node->sort==0) {
                $ip=gethostbyname($node->server);
                $node->node_ip=$ip;
                $node->save();
            }
        }
    }

    public static function backup()
    {
        mkdir('/tmp/ssmodbackup/');

        $db_address_array = explode(':', Config::get('db_host'));

        system('mysqldump --user='.Config::get('db_username').' --password='.Config::get('db_password').' --host='.$db_address_array[0].' '.(isset($db_address_array[1])?'-P '.$db_address_array[1]:'').' '.Config::get('db_database').' announcement auto blockip bought code coupon disconnect_ip link login_ip payback radius_ban shop speedtest ss_invite_code ss_node ss_password_reset ticket unblockip user user_token email_verify detect_list relay paylist> /tmp/ssmodbackup/mod.sql', $ret);


        system('mysqldump --opt --user='.Config::get('db_username').' --password='.Config::get('db_password').' --host='.$db_address_array[0].' '.(isset($db_address_array[1])?'-P '.$db_address_array[1]:'').' -d '.Config::get('db_database').' alive_ip ss_node_info ss_node_online_log user_traffic_log detect_log telegram_session >> /tmp/ssmodbackup/mod.sql', $ret);

        if (Config::get('enable_radius')) {
            $db_address_array = explode(':', Config::get('radius_db_host'));
            system('mysqldump --user='.Config::get('radius_db_user').' --password='.Config::get('radius_db_password').' --host='.$db_address_array[0].' '.(isset($db_address_array[1])?'-P '.$db_address_array[1]:'').''.Config::get('radius_db_database').'> /tmp/ssmodbackup/radius.sql', $ret);
        }

        system("cp ".Config::get('auto_backup_webroot')."/config/.config.php /tmp/ssmodbackup/configbak.php", $ret);
        echo $ret;
        system("zip -r /tmp/ssmodbackup.zip /tmp/ssmodbackup/* -P ".Config::get('auto_backup_passwd'), $ret);

        $subject = Config::get('appName')."-备份成功";
        $to = Config::get('auto_backup_email');
        $text = "您好，系统已经为您自动备份，请查看附件，用您设定的密码解压。" ;
        try {
            Mail::send($to, $subject, 'news/backup.phtml', [
                "text" => $text
            ], [
                "/tmp/ssmodbackup.zip"
            ]);
        } catch (Exception $e) {
            echo $e->getMessage();
        }

        system("rm -rf /tmp/ssmodbackup", $ret);
        system("rm /tmp/ssmodbackup.zip", $ret);

        Telegram::Send("备份完毕了喵~今天又是安全祥和的一天呢。");
    }

    public static function UserGa()
    {
        $users = User::all();
        foreach ($users as $user) {
            $ga = new GA();
            $secret = $ga->createSecret();

            $user->ga_token=$secret;
            $user->save();
        }
    }

    public static function syncnasnode()
    {
        $nodes = Node::all();
        foreach ($nodes as $node) {
            if ($node->sort==1) {
                $ip=gethostbyname($node->server);
                $node->node_ip=$ip;
                $node->save();

                Radius::AddNas($node->node_ip, $node->server);
            }
        }
    }

    public static function DailyJob()
    {
        $nodes = Node::all();
        $total_traffic = 0;
        foreach ($nodes as $node) {
            $total_traffic += $node->node_bandwidth/1024/1024/1024;
            if ($node->sort == 0 || $node->sort == 10) {
                if (date("d")==$node->bandwidthlimit_resetday) {
                    $node->node_bandwidth=0;
                    $node->save();
                }
            }
        }
        Telegram::Send("目前服务器记录的总流量是 ".round($total_traffic,2)." GB 的呢～");

        NodeInfoLog::where("log_time", "<", time()-86400*3)->delete();
        NodeOnlineLog::where("log_time", "<", time()-86400*3)->delete();
        TrafficLog::where("log_time", "<", time()-86400*3)->delete();
        DetectLog::where("datetime", "<", time()-86400*3)->delete();
        Speedtest::where("datetime", "<", time()-86400*3)->delete();
        EmailVerify::where("expire_in", "<", time()-86400*3)->delete();
        Telegram::Send("姐姐姐姐，数据库被清理了，感觉身体被掏空了呢~");

        //auto reset
        $boughts=Bought::all();
        foreach ($boughts as $bought) {
            $user=User::where("id", $bought->userid)->first();

            if ($user == null) {
                $bought->delete();
                continue;
            }

            $shop=Shop::where("id", $bought->shopid)->first();

            if ($shop == null) {
                $bought->delete();
                continue;
            }

            if($shop->reset() != 0 && $shop->reset_value() != 0 && $shop->reset_exp() != 0) {
              if(time() - $shop->reset_exp() * 86400 < $bought->datetime) {
                if(intval((time() - $bought->datetime) / 86400) % $shop->reset() == 0 && intval((time() - $bought->datetime) / 86400) != 0) {
                  $user->transfer_enable = Tools::toGB($shop->reset_value());
                  $user->u = 0;
                  $user->d = 0;
                  $user->last_day_t = 0;
                  $user->save();

                  $subject = Config::get('appName')."-您的流量被重置了";
                  $to = $user->email;
                  $text = "您好，根据您所订购的订单 ID:".$bought->id."，流量已经被重置为".$shop->reset_value().'GB' ;
                  try {
                      Mail::send($to, $subject, 'news/warn.phtml', [
                          "user" => $user,"text" => $text
                      ], [
                      ]);
                  } catch (Exception $e) {
                      echo $e->getMessage();
                  }
                }
              }
            }

        }


        $users = User::all();
        foreach ($users as $user) {
            $user->last_day_t=($user->u+$user->d);
            $user->save();


            if (date("d") == $user->auto_reset_day) {
                $user->u = 0;
                $user->d = 0;
                $user->last_day_t = 0;
                $user->transfer_enable = $user->auto_reset_bandwidth*1024*1024*1024;
                $user->save();

                $subject = Config::get('appName')."-您的流量被重置了";
                $to = $user->email;
                $text = "您好，根据管理员的设置，流量已经被重置为".$user->auto_reset_bandwidth.'GB' ;
                try {
                    Mail::send($to, $subject, 'news/warn.phtml', [
                        "user" => $user,"text" => $text
                    ], [
                    ]);
                } catch (Exception $e) {
                    echo $e->getMessage();
                }
            }
        }



        #https://github.com/shuax/QQWryUpdate/blob/master/update.php

        $copywrite = file_get_contents("http://update.cz88.net/ip/copywrite.rar");

        $newmd5 = md5($copywrite);
        $oldmd5 = file_get_contents(BASE_PATH."/storage/qqwry.md5");

        if ($newmd5 != $oldmd5) {
            file_put_contents(BASE_PATH."/storage/qqwry.md5", $newmd5);
            $qqwry = file_get_contents("http://update.cz88.net/ip/qqwry.rar");
            if ($qqwry != "") {
                $key = unpack("V6", $copywrite)[6];
                for ($i=0; $i<0x200; $i++) {
                    $key *= 0x805;
                    $key ++;
                    $key = $key & 0xFF;
                    $qqwry[$i] = chr(ord($qqwry[$i]) ^ $key);
                }
                $qqwry = gzuncompress($qqwry);
                rename(BASE_PATH."/storage/qqwry.dat", BASE_PATH."/storage/qqwry.dat.bak");
                $fp = fopen(BASE_PATH."/storage/qqwry.dat", "wb");
                fwrite($fp, $qqwry);
                fclose($fp);
            }
        }

        $iplocation = new QQWry();
        $location=$iplocation->getlocation("8.8.8.8");
        $Userlocation = $location['country'];
        if (iconv('gbk', 'utf-8//IGNORE', $Userlocation)!="美国") {
            unlink(BASE_PATH."/storage/qqwry.dat");
            rename(BASE_PATH."/storage/qqwry.dat.bak", BASE_PATH."/storage/qqwry.dat");
        }



        if (Config::get('enable_auto_backup')) {
            Job::backup();
        }

        Job::updateDownload();
    }

    public static function updateDownload()
    {
        $old_version_file = file_get_contents(BASE_PATH."/public/download/version.json");
        $new_version_file = file_get_contents("https://github.com/SakuraSa233/panel-download/raw/master/version.json");
        if ($old_version_file != $new_version_file){
            $old_versions = json_decode($old_version_file, true);
            $new_versions = json_decode($new_version_file, true);
            foreach ($old_versions as $file => $version){
                if ($version != $new_versions[$file]){
                    system("wget https://github.com/SakuraSa233/panel-download/raw/master/".$file." -O ".BASE_PATH."/public/download/".$file, $ret);
                    echo $ret;
                }
            }
            system("wget https://github.com/SakuraSa233/panel-download/raw/master/version.json -O ".BASE_PATH."/public/download/version.json", $ret);
            echo $ret;
        }
    }

    public static function CheckJob()
    {
        // Detect connect quantity begin
        $users = User::where('node_connector', '>', 0)->get();

        $full_alive_ips = Ip::where("datetime", ">=", time()-60)->orderBy("ip")->get();

        $alive_ipset = array();

        foreach ($full_alive_ips as $full_alive_ip) {
            $full_alive_ip->ip = Tools::getRealIp($full_alive_ip->ip);
            $is_node = Node::where("node_ip", $full_alive_ip->ip)->first();
            if($is_node) {
                continue;
            }

            if (!isset($alive_ipset[$full_alive_ip->userid])) {
                $alive_ipset[$full_alive_ip->userid] = new \ArrayObject();
            }

            $alive_ipset[$full_alive_ip->userid]->append($full_alive_ip);
        }



        foreach ($users as $user) {
            $alive_ips = (isset($alive_ipset[$user->id])?$alive_ipset[$user->id]:new \ArrayObject());
            $ips = array();

            $disconnected_ips = explode(",", $user->disconnect_ip);

            foreach ($alive_ips as $alive_ip) {
                if (!isset($ips[$alive_ip->ip]) && !in_array($alive_ip->ip, $disconnected_ips)) {
                    $ips[$alive_ip->ip]=1;
                    if ($user->node_connector < count($ips)) {
                        // Block connect begin
                        $isDisconnect = Disconnect::where('id', '=', $alive_ip->ip)->where('userid', '=', $user->id)->first();

                        if ($isDisconnect == null) {
                            $disconnect = new Disconnect();
                            $disconnect->userid = $user->id;
                            $disconnect->ip = $alive_ip->ip;
                            $disconnect->datetime = time();
                            $disconnect->save();

                            if ($user->disconnect_ip == null||$user->disconnect_ip == "") {
                                $user->disconnect_ip = $alive_ip->ip;
                            } else {
                                $user->disconnect_ip .= ",".$alive_ip->ip;
                            }
                            $user->save();
                        }
                        // Block connect end
                    }
                }
            }
        }
        // Detect online quantity end

        // Unblock IP begin
        $disconnecteds = Disconnect::where("datetime", "<", time()-300)->get();
        foreach ($disconnecteds as $disconnected) {
            $user = User::where('id', '=', $disconnected->userid)->first();

            $ips = explode(",", $user->disconnect_ip);
            $new_ips = "";
            $first = 1;

            foreach ($ips as $ip) {
                if ($ip != $disconnected->ip && $ip != "") {
                    if ($first == 1) {
                        $new_ips .= $ip;
                        $first = 0;
                    } else {
                        $new_ips .= ",".$ip;
                    }
                }
            }

            $user->disconnect_ip = $new_ips;

            if ($new_ips == "") {
                $user->disconnect_ip = null;
            }

            $user->save();

            $disconnected->delete();
        }
        // Unblock IP end

        // Auto renew Begin
        $boughts=Bought::where("renew", "<", time())->where("renew", "<>", 0)->get();
        foreach ($boughts as $bought) {
            $user=User::where("id", $bought->userid)->first();

            if ($user == null) {
                $bought->delete();
                continue;
            }

            if ($user->money>=$bought->price) {
                $shop=Shop::where("id", $bought->shopid)->first();

                if ($shop == null) {
                    $bought->delete();
                    continue;
                }

                $user->money=$user->money-$bought->price;

                $user->save();

                $shop->buy($user, 1);

                $bought->renew=0;
                $bought->save();


                $bought_new=new Bought();
                $bought_new->userid=$user->id;
                $bought_new->shopid=$shop->id;
                $bought_new->datetime=time();
                $bought_new->renew=time()+$shop->auto_renew*86400;
                $bought_new->price=$bought->price;
                $bought_new->coupon="";
                $bought_new->save();

                $subject = Config::get('appName')."-续费成功";
                $to = $user->email;
                $text = "您好，系统已经为您自动续费，商品名：".$shop->name.",金额:".$bought->price." 元。" ;
                try {
                    Mail::send($to, $subject, 'news/warn.phtml', [
                        "user" => $user,"text" => $text
                    ], [
                    ]);
                } catch (Exception $e) {
                    echo $e->getMessage();
                }

                if (file_exists(BASE_PATH."/storage/".$bought->id.".renew")) {
                    unlink(BASE_PATH."/storage/".$bought->id.".renew");
                }
            } else {
                if (!file_exists(BASE_PATH."/storage/".$bought->id.".renew")) {
                    $subject = Config::get('appName')."-续费失败";
                    $to = $user->email;
                    $text = "您好，系统为您自动续费商品名：".$shop->name.",金额:".$bought->price." 元 时，发现您余额不足，请及时充值，当您充值之后，稍等一会系统就会自动扣费为您续费了。" ;
                    try {
                        Mail::send($to, $subject, 'news/warn.phtml', [
                            "user" => $user,"text" => $text
                        ], [
                        ]);
                    } catch (Exception $e) {
                        echo $e->getMessage();
                    }
                    $myfile = fopen(BASE_PATH."/storage/".$bought->id.".renew", "w+") or die("Unable to open file!");
                    $txt = "1";
                    fwrite($myfile, $txt);
                    fclose($myfile);
                }
            }
        }
        // Auto renew end

        // Database clean start
        Ip::where("datetime", "<", time()-300)->delete();
        UnblockIp::where("datetime", "<", time()-300)->delete();
        BlockIp::where("datetime", "<", time()-86400)->delete();
        TelegramSession::where("datetime", "<", time()-900)->delete();
        // Database clean end

        // Panel version detect begin
        $latest_content = file_get_contents("https://github.com/SakuraSa233/ss-panel-v3-mod-Sakura/raw/master/bootstrap.php");
        $newmd5 = md5($latest_content);
        $oldmd5 = md5(file_get_contents(BASE_PATH."/bootstrap.php"));

        if ($latest_content!="") {
            if ($newmd5 == $oldmd5) {
                if (file_exists(BASE_PATH."/storage/update.md5")) {
                    unlink(BASE_PATH."/storage/update.md5");
                }
            } else {
                if (!file_exists(BASE_PATH."/storage/update.md5")) {
                    $adminUser = User::where("is_admin", "=", "1")->get();
                    foreach ($adminUser as $user) {
                        $subject = Config::get('appName')."-系统提示";
                        $to = $user->email;
                        $text = "管理员您好，系统发现有了新版本，您可以到 <a href=\"https://github.com/SakuraSa233/ss-panel-v3-mod-Sakura/wiki/Changelog\">https://github.com/SakuraSa233/ss-panel-v3-mod-Sakura/Changelog</a> 按照步骤进行升级。" ;
                        try {
                            Mail::send($to, $subject, 'news/warn.phtml', [
                                "user" => $user,"text" => $text
                            ], [
                            ]);
                        } catch (Exception $e) {
                            echo $e->getMessage();
                        }
                    }

                    Telegram::Send("姐姐姐姐，面板程序有更新了呢~看看你的邮箱吧~");

                    $myfile = fopen(BASE_PATH."/storage/update.md5", "w+") or die("Unable to open file!");
                    $txt = "1";
                    fwrite($myfile, $txt);
                    fclose($myfile);
                }
            }
        }
        // Panel version detect end


        /**
         * Process node
         * @author glzjin && SakuraSa233
         */
        // Process node begin
        $nodes = Node::all();
        foreach ($nodes as $node) {

            /**
             * Sync node ip in ss_node and relay table
             * @author SakuraSa233
             */
            // Sync node begin
            if ($node->dns_type == 'CNAME'){    // only CNAME node could be dynamic node
                if ($node->sort != 999 && $node->sort != 9) {
                    $ip = gethostbyname($node->dns_value);
                    if ($ip != $node->node_ip){
                        // process ss_node table
                        $node->node_ip = $ip;
                        $node->save();
                        // process relay table
                        $relay_rules = Relay::where('dist_node_id', $node->id)->get();
                        foreach ($relay_rules as $relay_rule){
                            $relay_rule->dist_ip = $ip;
                            $relay_rule->save();
                        }
                    }
                }
            }
            // Sync node end

            // Process new node
            if ($node->online_status == 0 && time() - $node->node_heartbeat <= 90 && ($node->sort == 0 || $node->sort == 10)){
                $node->online_status = 1;
                $node->save();
            }

            // Process node offline start
            if ($node->isNodeOnline() == true && time() - $node->node_heartbeat > 120) {
                $node->online_status = -1;
                $node->save();
                if (Config::get('node_offline_warn')){
                    $adminUser = User::where("is_admin", "=", "1")->get();
                    foreach ($adminUser as $user) {
                        $subject = Config::get('appName').'-系统警告';
                        $to = $user->email;
                        $text = '管理员您好，系统发现节点 '.$node->name.' 掉线了，请您及时处理。' ;
                        try {
                            Mail::send($to, $subject, 'news/warn.phtml', [
                                'user' => $user,'text' => $text
                            ], [
                            ]);
                        } catch (Exception $e) {
                            echo $e->getMessage();
                        }
                    }
                }
                $notice_text = '喵喵喵~ '.$node->name.' 节点掉线了喵~';
                if (($node->sort==0 || $node->sort==10) && Config::get('dns_provider') != 'none'){
                    $Temp_node = Node::where('node_class', '<=', $node->node_class)->where(
                        function ($query) use ($node) {
                        $query->where('node_group', '=', $node->node_group)
                            ->orWhere('node_group', '=', 0);
                        }
                    )->whereRaw('UNIX_TIMESTAMP() - `node_heartbeat` < 60')->inRandomOrder()->first();
                    $notice_text .= '域名解析被切换到了 '.$Temp_node->name.' 上了喵~';

                    switch(Config::get('dns_provider'))
                    {
                        case 'cloudxns':
                            $api=new CloudXNS();
                            $api->setApiKey(Config::get('cloudxns_apikey'));
                            $api->setSecretKey(Config::get('cloudxns_apisecret'));

                            $api->setProtocol(true);

                            $domain_json=json_decode($api->domain->domainList());

                            foreach ($domain_json->data as $domain) {
                                if (strpos($domain->domain, Config::get('cloudxns_domain')) != false) {
                                    $domain_id = $domain->id;
                                    break;
                                }
                            }

                            $record_json = json_decode($api->record->recordList($domain_id, 0, 0, 2000));

                            foreach ($record_json->data as $record) {
                                if (($record->host.".".Config::get('cloudxns_domain')) == $node->server) {
                                    $record_id = $record->record_id;
                                    $record_host = $record->host;
                                    $api->record->recordDelete($record_id, $domain_id);
                                }
                            }
                            $api->record->recordAdd($domain_id, $record_host, $Temp_node->server, 'CNAME', 55, 600, 1);
                            break;
                                
                        case 'cloudflare':
                            // init API interface
                            $key = new Cloudflare\Auth\APIKey(Config::get('cloudflare_email'), Config::get('cloudflare_key'));
                            $adapter = new Cloudflare\Adapter\Guzzle($key);
                            $dns = new Cloudflare\Endpoints\DNS($adapter);
                            $zoneid = Config::get('cloudflare_zoneid');
                            // delete former record
                            $details = $dns->listRecords($zoneid, '', $node->server)->result;
                            foreach ($details as $detail){
                                 $dns->deleteRecord($zoneid, $detail->id);
                            }
                            // add new record
                            $dns->addRecord($zoneid, 'CNAME', $node->server, $Temp_node->server, 1, false);
                            break;
                    }
                }

                Telegram::Send($notice_text);
            }
            // Process node offline end

            // Process node recover begin
            if ($node->isNodeOnline() === false && time() - $node->node_heartbeat < 60) {
                $node->online_status = 1;
                $node->save();
                if (Config::get('node_offline_warn')){
                    $adminUser = User::where("is_admin", "=", "1")->get();
                    foreach ($adminUser as $user) {
                        $subject = Config::get('appName').'-系统提示';
                        $to = $user->email;
                        $text = '管理员您好，系统发现节点 '.$node->name.' 恢复上线了。' ;
                        try {
                            Mail::send($to, $subject, 'news/warn.phtml', [
                                'user' => $user,'text' => $text
                            ], [
                            ]);
                        } catch (Exception $e) {
                            echo $e->getMessage();
                        }
                    }
                }
                $notice_text = '喵喵喵~ '.$node->name.' 节点恢复了喵~';
                if (($node->sort==0 || $node->sort==10) && Config::get('dns_provider') != 'none'){
                    switch(Config::get('dns_provider'))
                    {
                        case 'cloudxns':
                            $api=new CloudXNS();
                            $api->setApiKey(Config::get('cloudxns_apikey'));//修改成自己API KEY
                            $api->setSecretKey(Config::get('cloudxns_apisecret'));//修改成自己的SECERET KEY

                            $api->setProtocol(true);

                            $domain_json=json_decode($api->domain->domainList());

                            foreach ($domain_json->data as $domain) {
                                if (strpos($domain->domain, Config::get('cloudxns_domain')) != false) {
                                    $domain_id = $domain->id;
                                    break;
                                }
                            }

                            $record_json=json_decode($api->record->recordList($domain_id, 0, 0, 2000));

                            switch($node->dns_type)
                            {
                                case 'A':
                                    $IPs = explode(',', $node->dns_value);
                                    foreach ($record_json->data as $record) {
                                        if (($record->host.".".Config::get('cloudxns_domain')) == $node->server) {
                                            $record_id = $record->record_id;
                                            $record_host = $record->host;
                                            $api->record->recordDelete($record_id, $domain_id);
                                        }
                                    }
                                    foreach ($IPs as $IP){
                                        $api->record->recordAdd($domain_id, $record_host, $IP, 'A', 55, 600, 1);
                                    }
                                    break;
                                case 'CNAME':
                                    $api->record->recordAdd($domain_id, $record_host, $node->dns_value, 'A', 55, 600, 1);
                                    break;
                                case 'A&AAAA':
                                    $IP = explode('|', $node->dns_value);
                                    $IPv4s = explode(',', $IPs[0]);
                                    $IPv6s = explode(',', $IPs[1]);
                                    foreach ($IPv4s as $IPv4){
                                        $api->record->recordAdd($domain_id, $record_host, $IPv4, 'A', 55, 600, 1);
                                    }
                                    foreach ($IPv6s as $IPv6){
                                        $api->record->recordAdd($domain_id, $record_host, $IPv6, 'AAAA', 55, 600, 1);
                                    }
                                    break;
                            }
                            break;
                        case 'cloudflare':
                            // init API interface
                            $key = new Cloudflare\Auth\APIKey(Config::get('cloudflare_email'), Config::get('cloudflare_key'));
                            $adapter = new Cloudflare\Adapter\Guzzle($key);
                            $dns = new Cloudflare\Endpoints\DNS($adapter);
                            $zoneid = Config::get('cloudflare_zoneid');
                            // delete former record
                            $details = $dns->listRecords($zoneid, '', $node->server)->result;
                            foreach ($details as $detail){
                                $dns->deleteRecord($zoneid, $detail->id);
                            }
                            // add record
                            switch($node->dns_type)
                            {
                                case 'A':
                                    $IPs = explode(',', $node->dns_value);
                                    foreach ($IPs as $IP){
                                        $dns->addRecord($zoneid, 'A', $node->server, $IP, 1, false);
                                    }
                                    break;
                                case 'CNAME':
                                    $dns->addRecord($zoneid, 'CNAME', $node->server, $node->dns_value, 1, false);
                                    break;
                                case 'A&AAAA':
                                    $IP = explode('|', $node->dns_value);
                                    $IPv4s = explode(',', $IPs[0]);
                                    $IPv6s = explode(',', $IPs[1]);
                                    foreach ($IPv4s as $IPv4){
                                        $dns->addRecord($zoneid, 'A', $node->server, $IPv4, 1, false);
                                    }
                                    foreach ($IPv6s as $IPv6){
                                        $dns->addRecord($zoneid, 'AAAA', $node->server, $IPv6, 1, false);
                                    }
                                    break;
                            }
                    }
                    $notice_text .= '域名解析被切换回来了喵~';
                }
                Telegram::Send($notice_text);
            }
            // Process node recover end
        }
        // Process node end


        // Detect login location begin
        if (Config::get("login_warn")) {
            $iplocation = new QQWry();
            $Logs = LoginIp::where("datetime", ">", time()-60)->get();
            foreach ($Logs as $log) {
                $UserLogs=LoginIp::where("userid", "=", $log->userid)->orderBy("id", "desc")->take(2)->get();
                if ($UserLogs->count()==2) {
                    $i = 0;
                    $Userlocation = "";
                    foreach ($UserLogs as $userlog) {
                        if ($i == 0) {
                            $location=$iplocation->getlocation($userlog->ip);
                            $ip=$userlog->ip;
                            $Userlocation = $location['country'];
                            $i++;
                        } else {
                            $location=$iplocation->getlocation($userlog->ip);
                            $nodes=Node::where("node_ip", "LIKE", $ip.'%')->first();
                            $nodes2=Node::where("node_ip", "LIKE", $userlog->ip.'%')->first();
                            if ($Userlocation!=$location['country']&&$nodes==null&&$nodes2==null) {
                                $user=User::where("id", "=", $userlog->userid)->first();
                                $subject = Config::get('appName')."-系统警告";
                                $to = $user->email;
                                $text = "您好，系统发现您的账号在 ".iconv('gbk', 'utf-8//IGNORE', $Userlocation)." 有异常登录，请您自己自行核实登录行为。有异常请及时修改密码。" ;
                                try {
                                    Mail::send($to, $subject, 'news/warn.phtml', [
                                        "user" => $user,"text" => $text
                                    ], [
                                    ]);
                                } catch (Exception $e) {
                                    echo $e->getMessage();
                                }
                            }
                        }
                    }
                }
            }
        }
        // Detect login location end





        // Process user begin
        $users = User::all();
        foreach ($users as $user) {
            if (($user->transfer_enable<=$user->u+$user->d||$user->enable==0||(strtotime($user->expire_in)<time()&&strtotime($user->expire_in)>644447105))&&RadiusBan::where("userid", $user->id)->first()==null) {
                $rb=new RadiusBan();
                $rb->userid=$user->id;
                $rb->save();
                Radius::Delete($user->email);
            }

            if (strtotime($user->expire_in) < time() && (((Config::get('enable_account_expire_reset')&& strtotime($user->expire_in) < time()) ? $user->transfer_enable != Tools::toGB(Config::get('enable_account_expire_reset_traffic')) : true) && ((Config::get('enable_class_expire_reset')&& ($user->class!=0 && strtotime($user->class_expire)<time() && strtotime($user->class_expire) > 1420041600))? $user->transfer_enable != Tools::toGB(Config::get('enable_class_expire_reset_traffic')) : true))) {
                if (Config::get('enable_account_expire_reset')) {
                    $user->transfer_enable = Tools::toGB(Config::get('enable_account_expire_reset_traffic'));
                    $user->u = 0;
                    $user->d = 0;
                    $user->last_day_t = 0;

                    $subject = Config::get('appName')."-您的用户账户已经过期了";
                    $to = $user->email;
                    $text = "您好，系统发现您的账号已经过期了。流量已经被重置为".Config::get('enable_account_expire_reset_traffic').'GB' ;
                    try {
                        Mail::send($to, $subject, 'news/warn.phtml', [
                            "user" => $user,"text" => $text
                        ], [
                        ]);
                    } catch (Exception $e) {
                        echo $e->getMessage();
                    }
                }
            }

            if (strtotime($user->expire_in)+((int)Config::get('enable_account_expire_delete_days')*86400)<time()) {
                if (Config::get('enable_account_expire_delete')) {
                    $subject = Config::get('appName')."-您的用户账户已经被删除了";
                    $to = $user->email;
                    $text = "您好，系统发现您的账号已经过期 ".Config::get('enable_account_expire_delete_days')." 天了，帐号已经被删除。" ;
                    try {
                        Mail::send($to, $subject, 'news/warn.phtml', [
                            "user" => $user,"text" => $text
                        ], [
                        ]);
                    } catch (Exception $e) {
                        echo $e->getMessage();
                    }

                    $user->kill_user();


                    continue;
                }
            }



            if ((int)Config::get('enable_auto_clean_uncheck_days') != 0 && max($user->last_check_in_time, strtotime($user->reg_date)) + ((int)Config::get('enable_auto_clean_uncheck_days')*86400) < time() && $user->class == 0) {
                if (Config::get('enable_auto_clean_uncheck')) {
                    $subject = Config::get('appName')."-您的用户账户已经被删除了";
                    $to = $user->email;
                    $text = "您好，系统发现您的账号已经 ".Config::get('enable_auto_clean_uncheck_days')." 天没签到了，帐号已经被删除。" ;
                    try {
                        Mail::send($to, $subject, 'news/warn.phtml', [
                            "user" => $user,"text" => $text
                        ], [
                        ]);
                    } catch (Exception $e) {
                        echo $e->getMessage();
                    }

                    Radius::Delete($user->email);

                    RadiusBan::where('userid', '=', $user->id)->delete();

                    $user->delete();


                    continue;
                }
            }


            if ((int)Config::get('enable_auto_clean_unused_days') != 0 && max($user->t, strtotime($user->reg_date)) + ((int)Config::get('enable_auto_clean_unused_days')*86400) < time() && $user->class == 0) {
                if (Config::get('enable_auto_clean_unused')) {
                    $subject = Config::get('appName')."-您的用户账户已经被删除了";
                    $to = $user->email;
                    $text = "您好，系统发现您的账号已经 ".Config::get('enable_auto_clean_unused_days')." 天没使用了，帐号已经被删除。" ;
                    try {
                        Mail::send($to, $subject, 'news/warn.phtml', [
                            "user" => $user,"text" => $text
                        ], [
                        ]);
                    } catch (Exception $e) {
                        echo $e->getMessage();
                    }

                    Radius::Delete($user->email);

                    RadiusBan::where('userid', '=', $user->id)->delete();

                    $user->delete();


                    continue;
                }
            }

            if ($user->class!=0 && (((Config::get('enable_account_expire_reset')&& strtotime($user->expire_in) < time()) ? $user->transfer_enable != Tools::toGB(Config::get('enable_account_expire_reset_traffic')) : true) && ((Config::get('enable_class_expire_reset')&& ($user->class!=0 && strtotime($user->class_expire)<time() && strtotime($user->class_expire) > 1420041600))? $user->transfer_enable != Tools::toGB(Config::get('enable_class_expire_reset_traffic')) : true)) && strtotime($user->class_expire)<time() && strtotime($user->class_expire) > 1420041600) {
                if (Config::get('enable_class_expire_reset')) {
                    $user->transfer_enable = Tools::toGB(Config::get('enable_class_expire_reset_traffic'));
                    $user->u = 0;
                    $user->d = 0;
                    $user->last_day_t = 0;

                    $subject = Config::get('appName')."-您的用户等级已经过期了";
                    $to = $user->email;
                    $text = "您好，系统发现您的账号等级已经过期了。流量已经被重置为".Config::get('enable_class_expire_reset_traffic').'GB' ;
                    try {
                        Mail::send($to, $subject, 'news/warn.phtml', [
                            "user" => $user,"text" => $text
                        ], [
                        ]);
                    } catch (Exception $e) {
                        echo $e->getMessage();
                    }
                }

                $user->class=0;
            }

            if ($user->class!=0 && strtotime($user->class_expire)<time() && strtotime($user->class_expire) > 1420041600) {
                $user->class=0;
            }

            $user->save();

            if ($user->class!=0 && (strtotime($user->class_expire) - time() < 259200) && (259240 <= strtotime($user->class_expire) - time()) && strtotime($user->class_expire) > 1420041600) {
                $subject = Config::get('appName')."-您的用户等级即将在3天后过期";
                $to = $user->email;
                $text = "您好，系统发现您的账号等级即将在 3 天后过期。如需继续使用本站服务，请登录用户中心续费；如果您已开启自动续费且余额充足，请忽略本邮件提醒。感谢您的再次使用。" ;
                try {
                    Mail::send($to, $subject, 'news/warn.phtml', [
                        "user" => $user,"text" => $text
                    ], [
                    ]);
                } catch (Exception $e) {
                    echo $e->getMessage();
                }
            }
        }
        // Process user end

        // Radius ban begin
        $rbusers = RadiusBan::all();
        foreach ($rbusers as $sinuser) {
            $user = User::where('id',$sinuser->userid)->first();

            if ($user == null) {
                $sinuser->delete();
                continue;
            }

            if ($user->enable==1&&(strtotime($user->expire_in)>time()||strtotime($user->expire_in)<644447105)&&$user->transfer_enable>$user->u+$user->d) {
                $sinuser->delete();
                Radius::Add($user, $user->passwd);
            }
        }
        // Radius ban end
    }
}
