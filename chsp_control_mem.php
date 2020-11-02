<?php
require __DIR__ . '/config.php';
require __DIR__ . '/libs/RedBean.php';
require __DIR__ . '/libs/functions.php';

$memcached = new Memcached;
$memcached->addServer('localhost', 11211);

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
    if(time() - $data['last_update'] >= 1800) {
        $memcached->delete($key);
        preg_match('/chsp(\d+)/', $key, $match);
        $session = R::findOne('sessions', 'id = ' . $match[1]);
        $session->status = 'close';
        R::store($session);
    }
}