<?php declare(strict_types = 1);

namespace PHPStan\Command\Neon2Attributes;

/**
 * Written as `ClassName::class`.
 */
final class ClassReference
{

	public function __construct(public string $className)
	{
	}

}
