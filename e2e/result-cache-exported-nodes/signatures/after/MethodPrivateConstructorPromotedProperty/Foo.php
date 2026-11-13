<?php declare(strict_types = 1);

namespace ResultCacheE2EExportedNodes\MethodPrivateConstructorPromotedProperty;

final class Foo
{

	private function __construct(public string $x)
	{
	}

	public static function create(): self
	{
		return new self('a');
	}

}
