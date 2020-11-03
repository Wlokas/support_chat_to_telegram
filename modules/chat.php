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