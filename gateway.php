<?php
    require 'config.php';
    require 'libs/RedBean.php';
    require 'libs/functions.php';

    R::setup("mysql:host=".DB['host'].";dbname=".DB['base'], DB['user'], DB['pass']);
    if (!R::testConnection()) exit;

    $memcached = new Memcached;
    $memcached->addServer('localhost', 11211);

    $response = json_decode(file_get_contents('php://input'), TRUE);
    if (count($response) == 0) exit;

    if ($response['message']) {
        $etc = 'message';
    } elseif ($response['callback_query']) {
        $etc = 'callback_query';
    } elseif ($response['edited_message']) {
        $etc = 'edited_message';
    }

    $msg = $response[$etc];

    define('DATA', [
        'cmd' => explode(' ', $msg[($msg['text']) ? 'text' : 'data']),
        'msg' => $msg['text'],
        'from' => $msg['from']['id'],
        'reply' => $msg['reply_to_message'],
        'login' => $msg['from']['username'],
        'photo' => $msg['photo'],
        'nick' => $msg['from']['first_name'].' '.$msg['from']['last_name'],
        'mid' => $msg['message']['message_id'],
        'chat' => ($msg['chat']['id']) ? $msg['chat']['id'] : $msg['message']['chat']['id'],
        'member' => $msg['new_chat_member'],
        'left_member' => $msg['left_chat_member']
    ]);
    $telegram = new telegram(CFG['token'], DATA['from']);

    if(DATA['chat'] == CFG['chat_id']) {
        require 'modules/chat.php';
        chat();
    }
    elseif(DATA['chat'] > 0) {
        require 'modules/dialog.php';
        dialog();
    }