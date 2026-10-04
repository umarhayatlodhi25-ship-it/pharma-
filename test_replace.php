<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
try {
    echo \Illuminate\Support\Str::replaceArray('?', [['1']], 'where id = ?');
} catch (\Throwable $e) {
    echo $e->getMessage();
}
