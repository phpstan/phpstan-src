<?php declare(strict_types = 1); // lint >= 8.2

namespace Bug13446;

use function PHPStan\Testing\assertType;

final readonly class MoneyVO
{
	public function __construct(
		public float $value,
		public string $currency,
	) {}

	public function add(self $money): self
	{
		return new self($this->getRoundedValue() + $money->getRoundedValue(), $this->currency);
	}

	public function getRoundedValue(): float
	{
		return round($this->value, 2);
	}
}

final readonly class ContainerCarriageStepPriceVO
{
	public function __construct(
		public ?MoneyVO $mainCarriage,
		public ?MoneyVO $destinationLocals,
	) {}

	public function getSum(): MoneyVO
	{
		if ($this->mainCarriage === null && $this->destinationLocals === null) {
			return new MoneyVO(0.0, 'EUR');
		}

		if ($this->mainCarriage === null) {
			assertType('Bug13446\MoneyVO', $this->destinationLocals);
		}

		if ($this->destinationLocals === null) {
			assertType('Bug13446\MoneyVO', $this->mainCarriage);
		}

		return new MoneyVO(0.0, 'EUR');
	}
}
