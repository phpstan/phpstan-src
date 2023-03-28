<?php declare(strict_types = 1);

namespace PHPStan\PhpStormMeta\TypeMapping;

final class PassedArgumentType implements CallReturnTypeOverride
{

	public function __construct(
		public readonly int $argumentOffset,
	)
	{
	}

}
