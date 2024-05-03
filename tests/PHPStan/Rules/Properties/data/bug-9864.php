<?php declare(strict_types = 1); // lint >= 8.2

namespace Bug9864;

readonly abstract class UuidValueObject
{
	public function __construct(public string $value)
	{
		$this->ensureIsValidUuid($value);
	}

	private function ensureIsValidUuid(string $value): void
	{
	}
}


final readonly class ProductId extends UuidValueObject
{
	public string $value;

	public function __construct(
		string $value
	) {
		parent::__construct($value);
	}
}

var_dump(new ProductId('test'));

// property is assigned on parent class, no need to reassing, specially for readonly properties
