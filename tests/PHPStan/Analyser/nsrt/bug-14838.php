<?php // lint >= 8.5

namespace Bug14838;

use function PHPStan\Testing\assertType;

readonly class CoffeeBreak
{
	public ?int $durationInMinutes;

	public function __construct(
		public string $name,
	) {
		$this->durationInMinutes = null;
	}

	public function setDuration(): self
	{
		return clone($this, [
			'durationInMinutes' => 15,
		]);
	}

	public function hasDuration(): bool
	{
		// "clone with" may have reinitialized the readonly property, so the value
		// assigned in the constructor is no longer guaranteed here.
		assertType('int|null', $this->durationInMinutes);
		return $this->durationInMinutes !== null;
	}
}
