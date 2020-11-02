<?php
    $memcached = new Memcached;
    $memcached->addServer('localhost', 11211);

    require 'config.php';
    require 'libs/RedBean.php';
    require 'libs/functions.php';

    R::setup("mysql:host=".DB['host'].";dbname=".DB['base'], DB['user'], DB['pass']);
    if (!R::testConnection()) exit;


    if($_SERVER['REQUEST_METHOD'] == "POST") {
        if($_POST['type'] == 'new_message') {
            $session = new Chat($_COOKIE['chsp_id']);
            $session->addMessage('user', $_POST['text']);

            exit(json_encode(['status' => $session->getSession()->status]));
        }
    }
    elseif($_SERVER['REQUEST_METHOD'] == "GET") {

        if(isset($_GET['check']) && isset($_COOKIE['chsp_id'])) {
            $session = new Chat($_COOKIE['chsp_id'], false);
            if($session->checkSession($_COOKIE['chsp_id'])) exit(json_encode(['status' => true]));
            else exit(json_encode(['status' => false]));
        }
        elseif(isset($_COOKIE['chsp_id'])) {
            set_time_limit(60);
            $session = new Chat($_COOKIE['chsp_id'], false);
            if($session->checkSession($_COOKIE['chsp_id'])) {
                do {
                    $history = $session->getHistory($_GET['ts']);
                } while (!$history);
                exit(json_encode($history));
            }
        }
    }