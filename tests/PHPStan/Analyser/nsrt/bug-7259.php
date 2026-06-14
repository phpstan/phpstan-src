<?php // lint >= 8.0
declare(strict_types = 1);
namespace Bug7259;

use function PHPStan\Testing\assertType;

class HelloWorldNullable
{
	public function __construct(
		private ?\DateTimeImmutable $from,
		private ?\DateTimeImmutable $till,
	)
	{
		$newFrom = $this->from;
		$newTill = $this->till;

		if ($newFrom !== null || $newTill !== null) {
			if ($newFrom !== null && $newTill === null) {
				$newFrom = $newFrom->setTime(0, 0);
				$newTill = new \DateTimeImmutable('2300-12-31 23:59:59');
			}

			if ($newTill !== null && $newFrom === null) {
				$newTill = $newTill->setTime(23, 59, 59, 999999);
				$newFrom = new \DateTimeImmutable('1970-01-01 00:00:00');
			}

			assertType('DateTimeImmutable', $newFrom);
			assertType('DateTimeImmutable', $newTill);
			$this->checkDates($newFrom, $newTill);
		}
	}

	private function checkDates(
		\DateTimeImmutable $from,
		\DateTimeImmutable $till,
	): void
	{
	}
}

class HelloWorldStringInt
{
	public function __construct(
		private string|int $from,
		private string|int $till,
	)
	{
		$newFrom = $this->from;
		$newTill = $this->till;

		if (is_string($newFrom) || is_string($newTill)) {
			if (is_string($newFrom) && is_string($newTill) === false) {
				$newTill = 'test';
			}

			if (is_string($newTill) && is_string($newFrom) === false) {
				$newFrom = 'test2';
			}

			assertType('string', $newFrom);
			assertType('string', $newTill);
			$this->checkDates($newFrom, $newTill);
		}
	}

	private function checkDates(
		string $from,
		string $till,
	): void
	{
	}
}
