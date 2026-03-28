<?php declare(strict_types = 1);

namespace Bug4918;

class HelloWorld
{
	public function testThrowException(): void
	{
		$tryCounter = 0;

		try {
			$this->runCallback(static function () use (&$tryCounter): void {
				$tryCounter++;
				throw new LogicException('Test exception');
			});
		} catch (LogicException $e) {
			if ($tryCounter === 0) {
				throw new LogicException('Should never happen');
			}
		}
	}

	public function runCallback(callable $callback): void
	{
		$callback();
	}
}
