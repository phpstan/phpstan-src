<?php declare(strict_types = 1);

namespace Bug13446;

final readonly class ContainerCarriageStepPriceVO
{
	public function __construct(
		public ?MoneyVO $mainCarriage,
		public ?MoneyVO $destinationLocals,
	)
	{

	}

	public function getSum(): MoneyVO
	{
		if ($this->mainCarriage === null && $this->destinationLocals === null) {
			return new MoneyVO(0.0, 'EUR');
		}

		if ($this->mainCarriage === null && $this->destinationLocals !== null) {
			return $this->destinationLocals;
		}

		if ($this->mainCarriage !== null && $this->destinationLocals === null) {
			return $this->mainCarriage;
		}

		return $this->mainCarriage->add($this->destinationLocals);
	}
}

final readonly class MoneyVO
{
	public function __construct(
		public float $value,
		public string $currency,
	) {
	}

	public function add(self $money): self
	{
		return new self($this->getRoundedValue() + $money->getRoundedValue(), $this->currency);
	}

	public function getRoundedValue(): float
	{
		return round($this->value, 2);
	}
}
