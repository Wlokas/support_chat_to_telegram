<?php
if(isset($_POST['type']) && $_POST['type'] == "new_message" && isset($_POST['text'])) {
    setcookie('dolbaeb', '123daadsdas');
    exit(json_encode(['status' => 'ok']));
}