<?php declare(strict_types = 1);

namespace Bug14206;

use PDO;
use PDOStatement;

class Test
{
	public function test(PDO $db): void
	{
		/** @var PDOStatement<int,string> */
		$statement = $db->prepare('SELECT foo FROM bar');
		$statement->setFetchMode(PDO::FETCH_COLUMN, 0);
		$statement->execute();
	}
}
