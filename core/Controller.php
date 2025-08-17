<?php
class Controller {
    protected function model($name) {
        require_once __DIR__ . "/../app/models/{$name}.php";
        return new $name;
    }

    protected function json($data, $code = 200) {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
