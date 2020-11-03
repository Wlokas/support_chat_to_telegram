<?php
require __DIR__ . '/config.php';
require __DIR__ . '/libs/RedBean.php';
require __DIR__ . '/libs/functions.php';

$memcached = new Memcached;
$memcached->addServer('localhost', 11211);

$telegram = new telegram(CFG['token']);

R::setup("mysql:host=".DB['host'].";dbname=".DB['base'], DB['user'], DB['pass']);
if (!R::testConnection()) exit;

$keys = $memcached->getAllKeys();
$keys_chsp = [];

foreach ($keys as $value) {
    if(preg_match('/chsp\d+/', $value)) {
        $keys_chsp[] = $value;
    }
}

$memcached->getDelayed($keys_chsp, true);
while ($data = $memcached->fetch()) {
    $key = $data['key'];
    $cas = $data['cas'];
    $data = $data['value'];
    if(time() - $data['last_update'] >= CFG['timeout']) {
        $memcached->delete($key);
        preg_match('/chsp(\d+)/', $key, $match);
        $session = R::findOne('sessions', 'id = ' . $match[1]);
        $session->status = 'close';
        R::store($session);

        $telegram->send(['from' => CFG['chat_id'],
            'message_id' => $session->telegram_id_message,
            'text' => '<b>Чат был закрыт по истечению таймаута</b>'
        ]);

        $btn[0][0] = ['text' => 'Диалог неактивен'];
        $telegram->send([
            'from' => $session->telegram,
            'text' => '<b>Чат был закрыт, так как пользователь перестал отвечать серверу.</b>',
            'reply' => [false, $btn]
        ]);
    }
}