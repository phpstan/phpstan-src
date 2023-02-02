<?php // lint >= 8.0

namespace Bug8713;

class Foo
{
	public function foo(): void
	{
		$query = "SELECT * FROM `foo`";
		$pdo = new \PDO("dsn");
		$pdo->query(query: $query);
	}
}
