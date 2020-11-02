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
        $session = R::findOne('sessions', 'id = ' . $this->ID);
        return $session;
    }

    public function getHistory($ts = 0) {
        $history = $this->memcached->get('chsp' . $this->ID, null, Memcached::GET_EXTENDED)['value']['history'];
        $updates = [];

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
    }

    public function addMessage($type, $text) {
        if($type == 'user') {
            $message = ['type' => 'user', 'text' => $text];
        }

        do {
            $data = $this->memcached->get('chsp' . $this->ID, null, Memcached::GET_EXTENDED);
            $data['value']['history'][] = $message;
            $this->memcached->cas($data['cas'], 'chsp' . $this->ID, $data['value']);
        } while ($this->memcached->getResultCode() != Memcached::RES_SUCCESS);

        $message = R::dispense('history');
        $message->id_session = $this->ID;
        $message->text = $text;
        $message->type = $type;
        $message->time = time();
        R::store($message);
    }

    public function checkSession($id) {
        $data = $this->memcached->get('chsp' . $id);
        return $data ? true : false;
    }
}
