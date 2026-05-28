<?php

namespace Bug14719;

use PDO;

function (): void {
	$pdo = new PDO(
		dsn: "mysql:host=servername;dbname=dbname;charset=utf8mb4",
		username: "username",
		password: "password"
	);

	$db_stmt = $pdo->prepare("SELECT * FROM table;");
	$db_stmt->fetchAll(\PDO::FETCH_GROUP | \PDO::FETCH_ASSOC);
	$db_stmt->fetchAll(mode: \PDO::FETCH_GROUP | \PDO::FETCH_ASSOC);
	$db_stmt->setFetchMode(\PDO::FETCH_GROUP | \PDO::FETCH_ASSOC);
	$db_stmt->setFetchMode(mode: \PDO::FETCH_GROUP | \PDO::FETCH_ASSOC);
};
