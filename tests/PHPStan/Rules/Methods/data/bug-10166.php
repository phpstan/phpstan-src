<?php

namespace Bug10166;

trait ReturnTypeTrait {
	abstract public static function createSelf(): self;
}

final class ReturnTypeClass
{
	use ReturnTypeTrait;

	public static function createSelf(): self
	{
		return new self();
	}
}

final class ReturnTypeClass2
{
	use ReturnTypeTrait;

	public static function createSelf(): ?self
	{
		return new self();
	}
}
