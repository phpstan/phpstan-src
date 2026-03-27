<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug9444;

class HelloWorld
{

	/** @throws \Exception */
	public static function riskyOp(): string
	{
		return 'ok';
	}

	public function gotoRetry(): string
	{
		$i = 0;
		beginning:
		try {
			return self::riskyOp();
		} catch (\Throwable $e) {
			if (++$i < 5) {
				goto beginning;
			} else {
				throw $e;
			}
		}
	}

	public function gotoRetrySimple(): string
	{
		beginning:
		try {
			return self::riskyOp();
		} catch (\Throwable $e) {
			goto beginning;
		}
	}

	public function gotoInCatch(): int
	{
		$result = 0;
		beginning:
		try {
			$result = random_int(1, 100);
		} catch (\Throwable $e) {
			goto beginning;
		}
		return $result;
	}

}
