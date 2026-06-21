<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug9444;

class One
{
	private static int $i = 0;

	public static function run(): string
	{
		self::$i++;
		if (self::$i <= 3) {
			throw new \Exception('One:run');
		}

		return 'Ok';
	}
}

class Main
{
	public function process(): string
	{
		for ($i = 0; $i <= 5; $i++) {
			try {
				return One::run();
			} catch (\Throwable $e) {
				$sleep = match ($i) {
					0 => 0.5,
					1 => 1,
					2 => 3,
					3 => 6,
					4 => 9,
					default => throw $e,
				};

				echo $sleep . PHP_EOL;
			}
		}
	}

	public function processWithIf(): string
	{
		for ($i = 0; $i <= 5; $i++) {
			try {
				return One::run();
			} catch (\Throwable $e) {
				if ($i >= 5) {
					throw $e;
				}
				echo $i . PHP_EOL;
			}
		}
	}

	public function processWhile(): string
	{
		$i = 0;
		while ($i <= 5) {
			try {
				return One::run();
			} catch (\Throwable $e) {
				if ($i >= 5) {
					throw $e;
				}
				$i++;
			}
		}
	}
}
