<?php declare(strict_types = 1);

namespace Bug14318;

class HelloWorld5
{
	public function test5(): void
	{
		global $pdo;

		try {
			$this->maybeThrows5($sql = "SELECT * FROM foo");
			$rs = $pdo->query($sql);
			if ($result = $rs->fetch(\PDO::FETCH_ASSOC)) {
				// do something
			}
		} catch (\PDOException $e) {
			var_dump($sql);
		}
	}

	/**
	 * @throws \RuntimeException
	 */
	public function maybeThrows5(string $s): void
	{
		if (random_int(0, 1) === 1) {
			throw new \RuntimeException();
		}
	}
}

class HelloWorld6
{
	public function test6(): void
	{
		global $pdo;

		try {
			$this->maybeThrows6(strlen($sql = "SELECT * FROM foo"));
			$rs = $pdo->query($sql);
			if ($result = $rs->fetch(\PDO::FETCH_ASSOC)) {
				// do something
			}
		} catch (\PDOException $e) {
			var_dump($sql);
		}
	}

	/**
	 * @throws \RuntimeException
	 */
	public function maybeThrows6(int $s): void
	{
		if (random_int(0, 1) === 1) {
			throw new \RuntimeException();
		}
	}
}
