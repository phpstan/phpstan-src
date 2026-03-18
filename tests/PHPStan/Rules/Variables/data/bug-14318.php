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

class HelloWorld7
{
	public function test7(): void
	{
		global $pdo;

		try {
			self::maybeThrows7($sql = "SELECT * FROM foo");
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
	public static function maybeThrows7(string $s): void
	{
		if (random_int(0, 1) === 1) {
			throw new \RuntimeException();
		}
	}
}

class HelloWorld8
{
	public function test8(): void
	{
		global $pdo;

		try {
			self::maybeThrows8(strlen($sql = "SELECT * FROM foo"));
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
	public static function maybeThrows8(int $s): void
	{
		if (random_int(0, 1) === 1) {
			throw new \RuntimeException();
		}
	}
}

/**
 * @throws \RuntimeException
 */
function maybeThrows9(string $s): void
{
	if (random_int(0, 1) === 1) {
		throw new \RuntimeException();
	}
}

/**
 * @throws \RuntimeException
 */
function maybeThrows10(int $s): void
{
	if (random_int(0, 1) === 1) {
		throw new \RuntimeException();
	}
}

class HelloWorld9
{
	public function test9(): void
	{
		global $pdo;

		try {
			maybeThrows9($sql = "SELECT * FROM foo");
			$rs = $pdo->query($sql);
			if ($result = $rs->fetch(\PDO::FETCH_ASSOC)) {
				// do something
			}
		} catch (\PDOException $e) {
			var_dump($sql);
		}
	}
}

class HelloWorld10
{
	public function test10(): void
	{
		global $pdo;

		try {
			maybeThrows10(strlen($sql = "SELECT * FROM foo"));
			$rs = $pdo->query($sql);
			if ($result = $rs->fetch(\PDO::FETCH_ASSOC)) {
				// do something
			}
		} catch (\PDOException $e) {
			var_dump($sql);
		}
	}
}
