<?php declare(strict_types = 1);

namespace PHPStan\PhpStormMeta\TypeMapping;

class MethodCallTypeOverride
{

	public function __construct(
		public readonly string $classlikeName,
		public readonly string $methodName,
		public readonly int $argumentOffset,
		public readonly CallReturnTypeOverride $returnType,
	)
	{
	}

}
