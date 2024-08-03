<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\Neon;

final class OptionalPath
{

	public function __construct(public readonly string $path)
	{
	}

}
