<?php declare(strict_types = 1);

namespace PHPStan\Build\TurboCodegen;

use PHPStan\Type\Type;

/** A function's declared return type and how it is lowered. */
final class ReturnInfo
{

	/**
	 * @param 'long'|'bool'|'zval'|'void' $kind
	 * @param string $describe the type as PHP's TypeError messages spell it
	 */
	public function __construct(
		public readonly string $kind,
		public readonly Type $declared,
		public readonly string $describe,
		public readonly ?string $class = null,
		public readonly bool $nullable = false,
	)
	{
	}

}
