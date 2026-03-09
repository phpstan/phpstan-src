<?php // lint >= 8.0

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
				$sleep = match($i) {
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
}
