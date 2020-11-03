<?php

class Chat {
    public $ID;
    public $memcached;

    public function __construct($id, $is_create = true)
    {
        $this->memcached = new Memcached;;
        $this->memcached->addServer('localhost', 11211);
        if(($id == NULL || !$this->checkSession($id)) && $is_create) {
            $session = R::dispense('sessions');
            $session->status = "created";
            $session->last_update = time();
            $id = R::store($session);

            $data = ['last_update' => time(), 'history' => []];
            setcookie('chsp_id', $id);
            $this->memcached->set('chsp' . $id, $data);
        }

        $this->ID = $id;
    }

    public function getSession() {
        return R::findOne('sessions', 'id = ' . $this->ID);
    }

    public function closeSession() {
        $this->memcached->delete('chsp' . $this->ID);
        $session = $this->getSession();
        $session->status = 'close';
        R::store($session);
    }

    public function getHistory($ts = 0) {
        $history = $this->memcached->get('chsp' . $this->ID, null, Memcached::GET_EXTENDED)['value']['history'];
        $updates = [];

        if($history) {
            foreach ($history as $key => $value) {
                $key++;
                if($key > $ts) {
                    $updates[] = $value;
                }
            }

            if(count($updates) > 0) {
                return ['ts' => count($history), 'updates' => $updates];
            } else {
                return false;
            }
        } else {
            return ['error' => 'not_found'];
        }
    }

    public function addMessage($type, $text) {
        $telegram_id = NULL;
        if($type == 'user') {
            $message = ['type' => 'user', 'text' => $text];
        } elseif($type == 'agent') {
            $session = $this->getSession();
            $user = R::findOne('accounts', 'telegram = ?', [$session->telegram]);
            $message = ['type' => 'agent', 'text' => $text, 'name' => $user->name, 'description' => $user->description];
            $telegram_id = $user->telegram;
        }

        $this->updateHistory($message);

        $message = R::dispense('history');
        $message->id_session = $this->ID;
        $message->text = $text;
        $message->telegram = $telegram_id;
        $message->type = $type;
        $message->time = time();
        R::store($message);
    }

    public function checkSession($id) {
        $data = $this->memcached->get('chsp' . $id);
        return $data ? true : false;
    }

    public function updateTime() {
        do {
            $data = $this->memcached->get('chsp' . $this->ID, null, Memcached::GET_EXTENDED);
            $data['value']['last_update'] = time();
            $this->memcached->cas($data['cas'], 'chsp' . $this->ID, $data['value']);
        } while ($this->memcached->getResultCode() != Memcached::RES_SUCCESS);

        $session = $this->getSession();
        $session->last_update = time();
        R::store($session);
    }

    public function updateHistory($value) {
        do {
            $data = $this->memcached->get('chsp' . $this->ID, null, Memcached::GET_EXTENDED);
            $data['value']['history'][] = $value;
            $this->memcached->cas($data['cas'], 'chsp' . $this->ID, $data['value']);
        } while ($this->memcached->getResultCode() != Memcached::RES_SUCCESS);
    }
}

class telegram {
    public $token;
    public $user_id;

    public function __construct($token, $user_id = NULL)
    {
        $this->token = $token;
        $this->user_id = $user_id;
    }


    public function getMe() {
        $need = ['url' => 'https://api.telegram.org/bot'.$this->token.'/getMe'];
        $response = json_decode($this->curl($need, false));
        return $response->ok ? $response->result : false;
    }

    public function send($need){
        $method = 'sendMessage';
        $query = [
            'chat_id' => ($need['from']) ? $need['from'] : $this->user_id,
            'text' => (is_array($need['text'])) ? implode("\n", $need['text']) : $need['text'],
            'parse_mode' => 'html',
            'disable_web_page_preview' => 'true'
        ];

        if (count($need['reply'])>0) $query['reply_markup'] = json_encode($this->reply($need['reply']));

        if ($need['message_id']) {
            $method = 'editMessageText';
            $query['message_id'] = $need['message_id'];
        }

        if ($need['method'] == 'kickChatMember') {
            $method = 'kickChatMember';
            $query = [
                'chat_id' => $need['chat'],
                'user_id' => $need['from']
            ];
        }

        if ($need['token']) {
            $token = $need['token'];
        } else {
            $token = $this->token;
        }

        $a = ['url' => 'https://api.telegram.org/bot'.$token.'/'.$method, 'query' => $query];
        $response = json_decode($this->curl($a, false));
        return $response->ok ? $response->result->message_id : false;
    }

    private function reply ($v) {
        if ($v[0]) return ['inline_keyboard' => $v[1]]; else return ['keyboard' => $v[1], 'resize_keyboard' => true, 'one_time_keyboard' => false];
    }

    private function curl($need, $proxy = true) {
        $curl = curl_init($need['url']);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        if ($proxy) {
            curl_setopt($curl, CURLOPT_PROXY, CFG['proxy']['host']);
            curl_setopt($curl, CURLOPT_PROXYUSERPWD, CFG['proxy']['auth']);
        }
        if ($need['query']) {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($need['query']));
        }
        $result = curl_exec($curl);
        curl_close($curl);
        return $result;
    }
}
