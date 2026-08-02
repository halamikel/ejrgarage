<?php
header('Content-Type: text/plain');

$vars = [
    'DB_CONNECTION',
    'DB_HOST',
    'DB_PORT',
    'DB_DATABASE',
    'DB_USERNAME',
    'DB_PASSWORD'
];

foreach ($vars as $var) {
    $value = getenv($var);

    if ($value === false) {
        echo "$var = NOT FOUND\n";
    } elseif ($value === '') {
        echo "$var = EMPTY\n";
    } else {
        echo "$var = FOUND\n";
    }
}