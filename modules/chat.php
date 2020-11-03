<?php

    function chat() {
        global $telegram;

        $user = R::findOne('accounts', 'telegram = ?', [DATA['from']]);
        if(!$user) {
            $user = newMember();
        }

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
        if(DATA['member']) doJoin();
        if(DATA['cmd'][0] == "/help") doHelp();
        if(DATA['cmd'][0] == "/work") doWork($user);
        if(DATA['cmd'][0] == "/supports") doSupports();
        if(DATA['cmd'][0] == "/chats") doChats();
    }

    function doChats() {
        global $memcached, $telegram;
        $keys_sessions = $memcached->getAllKeys();
        $sessions_data = R::getAll('SELECT * FROM `sessions` WHERE `status` = "wait_agent"');
        $sessions = [];
        $btns = [];
        $index_line = 0;
        $index_button = 0;
        foreach ($sessions_data as $key => $session) {
            $session_id = $session['id'];
            if(array_search('chsp' . $session_id, $keys_sessions) !== FALSE) {
                if($index_button > 4) { $index_button = 0; $index_line++; }
                $sessions[] = date($key + 1 . '. ⌛ Ожидает оператора.. | H:i', $session['last_update']);
                $btns[$index_line][$index_button] = ['text' => $key + 1, 'url' => 't.me/' . $telegram->getMe()->username . '?start=' . $session_id];
                $index_button++;
            }
        }
        if(count($sessions) >= 1) {
            $text = array_merge(['🎧 <b>Чаты ожидающие оператора:</b>', ''], $sessions);
            $telegram->send(['from' => CFG['chat_id'],
                'text' => $text,
                'reply' => [true, $btns]]);
        } else {
            $telegram->send(['from' => CFG['chat_id'],
                'text' => [
                    '🎧 <b>Чатов ожидающих операторов в данный момент нет</b>'
                ]]);
        }
    }

    function doSupports() {
        global $telegram;
        $supports = R::getAll('SELECT * FROM accounts WHERE status = 1');
        $message = [];
        foreach ($supports as $support) {
            $message[] = $support['username'];
        }
        if(count($message) >= 1) {
            $text = array_merge(['🦺 <b>Активные воркеры:</b>', ''], $message);
            $telegram->send(['from' => CFG['chat_id'],
                'text' => $text]);
        } else {
            $telegram->send(['from' => CFG['chat_id'],
                'text' => [
                    '🩸 <b>Активных воркеров в данный момент нет</b>'
                ]]);
        }
    }

    function doWork($user) {
        global $telegram;
        if($user->status == 0) {
            $user->status = 1;
            $telegram->send(['from' => CFG['chat_id'],
                'text' => [
                    '📗 <b>Вы успешно заступили на службу</b>'
                ]]);
        } elseif($user->status == 1) {
            $user->status = 0;
            $telegram->send(['from' => CFG['chat_id'],
                'text' => [
                    '📕 <b>Вы вышли со службы</b>'
                ]]);
        }

        R::store($user);
    }

    function doHelp() {
        global $telegram;
        $telegram->send(['from' => CFG['chat_id'],
            'text' => [
                '<b>Команды:</b>',
                '/help - Основные команды',
                '/work - начать/закончить работу',
                '/supports - узнать активных работников',
                '/chats - вывести свободные активные чаты'
            ]]);
    }

    function doJoin() {
        global $telegram;
        $btn[0][0] = ['text' => '🎲 Сгенерировать рандомные', 'callback_data' => 'generateRandom'];
        $telegram->send(['from' => CFG['chat_id'],
            'text' => [
                '🎈 <b>Добро пожаловать в чат поддержки</b>',
                '',
                'Напиши ниже <b>Имя</b> и <b>Должность</b> которые будут отображены в чате',
                'Пример: Андрей Отдел Продаж',
                '',
                '<b>Команды:</b>',
                '/help - Основные команды',
                '/work - начать/закончить работу',
                '/supports - узнать активных работников',
                '/chats - вывести свободные активные чаты'
            ], 'reply' => [true, $btn]]);
    }

    function doGenerateInfo($user) {
        global $telegram;
        switch (rand(0, 5)) {
            case 0: $name = "Андрей"; break;
            case 1: $name = "Виктор"; break;
            case 2: $name = "Алексей"; break;
            case 3: $name = "Виталий"; break;
            case 4: $name = "Даниил"; break;
            case 5: $name = "Егор"; break;
        }
        $user->name = $name;
        $user->description = "Оператор";
        R::store($user);

        $telegram->send([
            'from' => DATA['chat'],
            'text' => [
                '<b>Вы установили новые параметры:</b>',
                '👔 Имя: ' . $name,
                '👖 Должность: ' . 'Оператор',
            ]
        ]);
    }

    function newMember() {
        $account = R::dispense('accounts');
        $account->telegram = DATA['from'];
        $account->username = DATA['login'];
        $account->time_reg = time();
        $account->status = 0;
        R::store($account);
        return $account;
    }