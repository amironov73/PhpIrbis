<?php

$client = new Irbis\Connection();
$client->host = 'irbis.server';
$client->port = 5555;
$client->username = 'ninja';
$client->password = 'i_am_invisible';
$client->connect();
$expression = Irbis\author('Пушкин$')->and_(Irbis\title('СКАЗКИ$'));
$found = $client->searchCount($expression);
echo "Найдено: ", $found;
$client->disconnect();
