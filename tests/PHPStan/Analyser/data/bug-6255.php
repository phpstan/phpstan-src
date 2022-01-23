<?php

namespace Bug6255;

$pdo = new \PDO('');
$pdo->pgsqlGetNotify(\PDO::FETCH_ASSOC);
