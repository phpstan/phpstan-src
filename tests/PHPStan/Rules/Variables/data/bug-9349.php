<?php declare(strict_types = 1);

namespace Bug9349;

class HelloWorld
{
	public function test(): void
	{
		global $pdo;

		try {
			$this->maybeThrows();
			$sql = "SELECT * FROM foo";
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
	public function maybeThrows(): void
	{
		if (random_int(0, 1) === 1) {
			throw new \RuntimeException();
		}
	}

	public function test2(): void
	{
		global $pdo;

		try {
			$this->maybeThrows2();
			$sql = "SELECT * FROM foo";
			$rs = $pdo->query($sql);
			if ($result = $rs->fetch(\PDO::FETCH_ASSOC)) {
				// do something
			}
		} catch (\PDOException $e) {
			var_dump($sql);
		}
	}

	/**
	 * @throws \LogicException
	 */
	public function maybeThrows2(): void
	{
		if (random_int(0, 1) === 1) {
			throw new \LogicException();
		}
	}
}
