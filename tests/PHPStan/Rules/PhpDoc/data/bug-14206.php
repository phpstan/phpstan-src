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

	public function test2(PDO $db): void
	{
		/** @var PDOStatement<int,array<string>> */
		$statement = $db->prepare('SELECT foo FROM bar');
		$statement->execute();
	}

	public function test3(PDO $db): void
	{
		/** @var PDOStatement<int,object> */
		$statement = $db->prepare('SELECT foo FROM bar');
		$statement->setFetchMode(PDO::FETCH_OBJ, 0);
		$statement->execute();
	}
}
