<?php
    function dialog() {
        global $telegram;
        function getActiveSession($user) {
            global $memcached;
            $session = R::findOne('sessions', 'telegram = ? and status = "active"', [$user->telegram]);
            return ($session && $memcached->get('chsp' . $session->id)) ? $session->id : false;
        }

        $user = R::findOne('accounts', 'telegram = ?', [DATA['from']]);
        if($user) {
            if($user->username != DATA['login']) {
                $user->username = DATA['login'];
                R::store($user);
            }
            if (DATA['cmd'][0] == 'generateRandom') doGenerateInfo($user);
            if(!$user->name && !$user->description) {
                if(preg_match('/^(.*?) (.*)$/', DATA['msg'], $match)) {
                    $user->name = $match[1];
                    $user->description = $match[2];
                    R::store($user);

                    $telegram->send([
                        'from' => DATA['chat'],
                        'text' => [
                            '<b>Вы установили новые параметры:</b>',
                            '👔 Имя: ' . $match[1],
                            '👖 Должность: ' . $match[2],
                        ]
                    ]);
                } else {
                    $btn[0][0] = ['text' => '🎲 Сгенерировать рандомные', 'callback_data' => 'generateRandom'];
                    $telegram->send([
                        'from' => DATA['chat'],
                        'text' => [
                            '🧔 <b>Привет, напиши пожалуйста свое имя и должность,</b>',
                            '<b>которое будет указыватся в окне чата</b>',
                            '',
                            '<b>По форме: Имя Должность</b>',
                            '',
                            '<b>Пример: Андрей Оператор</b>'
                        ],
                        'reply' => [true, $btn]
                    ]);
                    exit();
                }
            }
            if (DATA['cmd'][0] == '/start' && !empty(DATA['cmd'][1])) doJoin($user);
            $id = getActiveSession($user);
            if($id) {
                if (DATA['msg'] == '🔒 Закрыть диалог') doClose($user, $id);
                if (DATA['msg'] == '🔙 Отдать другому оператору') doChange($user, $id);
                if(!empty(DATA['msg'])) doSend($user, $id);
            }
        }
    }

    function doSend($user, $id) {
        $chat = new Chat($id);
        $chat->addMessage('agent', DATA['msg']);
    }

    function doChange($user, $id) {
        global $telegram;
        $chat = new Chat($id);

        $chat->updateHistory(['type' => 'agent_change']);
        $session = $chat->getSession();
        $session->status = 'wait_agent';

        $btn[0][0] = [
            'text' => '👥 Взять диалог',
            'url' => 't.me/' . $telegram->getMe()->username . '?start=' . $session->id
        ];

        $message_id = $telegram->send([
            'from' => CFG['chat_id'],
            'text' => [
                '📣 <b>Оператор '. DATA['login'] .' отдал диалог пользователя</b>',
                '',
                '<b>Нажмите на кнопку ниже чтобы взять диалог.</b>'
            ],
            'reply' => [
                true, $btn
            ]
        ]);
        $session->telegram_id_message = $message_id;
        R::store($session);

        exit();
    }

    function doClose($user, $id_session) {
        global $telegram;
        $chat = new Chat($id_session);

        $chat->closeSession();
        $btn[0][0] = ['text' => 'Диалог неактивен'];
        $telegram->send([
            'text' => '<b>Вы успешно закончили диалог</b>',
            'reply' => [false, $btn]
        ]);
        exit();
    }

    function doJoin($user) {
        global $telegram;

        function checkSession($id) {
            global $memcached;
            $data = $memcached->get('chsp' . $id);
            return $data ? true : false;
        }

        if(getActiveSession($user)) {
            $btn[0][0] = ['text' => '🔒 Закрыть диалог'];
            $btn[0][1] = ['text' => '🔙 Отдать другому оператору'];
            $telegram->send([
               'text' => '⛔ Вы находитесь в диалоге, выйдите из него или передайте другому оператору.',
               'reply' => [false, $btn]
            ]);
            exit();
        }

        $session = R::findOne('sessions', 'id = ? and status = "wait_agent"', [DATA['cmd'][1]]);
        if(!$session || !checkSession($session->id)) {
            $telegram->send([
                'text' => '⚠ Диалог уже взял другой оператор, либо диалога больше не существует.',
                'reply' => [false, []]
            ]);
            exit();
        }

        $cache = new Chat(DATA['cmd'][1]);

        $session = $cache->getSession();
        $session->telegram = DATA['from'];
        $session->status = 'active';
        R::store($session);

        $user->status = 1;
        R::store($user);

        $telegram->send(['from' => CFG['chat_id'], 'message_id' => $session->telegram_id_message, 'text' => '<b>Диалог взял оператор '. DATA['login'] .'</b>']);

        $cache->updateHistory(['type' => 'agent_join', 'name' => $user->name, 'description' => $user->description]);

        $btn[0][0] = ['text' => '🔒 Закрыть диалог'];
        $btn[0][1] = ['text' => '🔙 Отдать другому оператору'];
        $telegram->send([
            'text' => '<b>🚪 Вы успешно подключились к диалогу</b>',
            'reply' => [false, $btn]
        ]);

        foreach ($cache->getHistory()['updates'] as $update) {
            switch ($update['type']) {
                case 'user':
                    $telegram->send(['text' => $update['text']]);
                    break;
                case 'agent':
                    $telegram->send(['text' => '<b>' . $update['name'] . '(Оператор): ' . $update['text'] . '</b>']);
                    break;
            }
        }
        exit();
    }