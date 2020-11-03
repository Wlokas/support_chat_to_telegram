<?php
    $memcached = new Memcached;
    $memcached->addServer('localhost', 11211);

    require 'config.php';
    require 'libs/RedBean.php';
    require 'libs/functions.php';

    $telegram = new telegram(CFG['token']);

    R::setup("mysql:host=".DB['host'].";dbname=".DB['base'], DB['user'], DB['pass']);
    if (!R::testConnection()) exit;


    if($_SERVER['REQUEST_METHOD'] == "POST") {
        if($_POST['type'] == 'new_message') {
            $sessions = new Chat($_COOKIE['chsp_id']);
            $sessions->addMessage('user', $_POST['text']);

            $session = $sessions->getSession();

            if($session->status == "created") {
                $btn[0][0] = [
                    'text' => '👥 Взять диалог',
                    'url' => 't.me/' . $telegram->getMe()->username . '?start=' . $session->id
                ];

                $message_id = $telegram->send([
                    'from' => CFG['chat_id'],
                    'text' => [
                        '📣 <b>Пользователь написал новое сообщение в чат!</b>',
                        '',
                        $_POST['text'],
                        '',
                        '<b>Нажмите на кнопку ниже чтобы взять диалог.</b>'
                    ],
                    'reply' => [
                        true, $btn
                    ]
                ]);

                $session->telegram_id_message = $message_id;
                $session->status = "wait_agent";
                R::store($session);
            } elseif($session->status == "active") {
                $btn[0][0] = ['text' => '🔒 Закрыть диалог'];
                $btn[0][1] = ['text' => '🔙 Отдать другому оператору'];
                $telegram->send([
                    'from' => $session->telegram,
                    'text' => $_POST['text'],
                    'reply' => [false, $btn]
                ]);
            }

            exit(json_encode(['status' => $session->status]));
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
                $session->updateTime();
                do {
                    $history = $session->getHistory($_GET['ts']);
                } while (!$history);
                exit(json_encode($history));
            }
        }
    }