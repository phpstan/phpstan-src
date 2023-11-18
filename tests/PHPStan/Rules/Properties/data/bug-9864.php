<?php // lint >= 8.1

namespace Bug9864;

abstract class UuidValueObject
{
	public function __construct(public readonly string $value)
	{
	}
}

final class ProductId extends UuidValueObject
{
	public readonly string $value;

	public function __construct(string $value)
	{
		parent::__construct($value);
	}
}
