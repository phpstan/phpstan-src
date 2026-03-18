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
 * @param array<string>|null $reasons
 * @throws \Exception
 */
function check1(array &$reasons = null): void {
	$fileName = time() % 2 ? "abc":null;
	if (!$fileName) {
		$reasons[] = sprintf("Dependency check fail");
		throw new \Exception("check failed");
	}
}

function test1():void {
	try {
		check1($reasons);
		printf("ok\n");
	} catch (\Exception $e) {
		if (!empty($reasons)) {
			$e = new \Exception("Dependency check failed: " . implode(', ', $reasons), 0, $e);
		}
		throw new \Exception("Failed", 0, $e);
	}
}
