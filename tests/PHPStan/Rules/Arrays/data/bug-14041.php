<?php declare(strict_types = 1);

namespace Bug14041;

use Pdo\Mysql;

$connection_options['pdo'] = [
	\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
	Mysql::ATTR_USE_BUFFERED_QUERY => TRUE,
	Mysql::ATTR_FOUND_ROWS => TRUE,
	\PDO::ATTR_EMULATE_PREPARES => TRUE,
	Mysql::ATTR_MULTI_STATEMENTS => FALSE,
	\PDO::ATTR_STRINGIFY_FETCHES => TRUE,
];
