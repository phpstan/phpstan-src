<?php declare(strict_types = 1);

namespace PHPStan\Build\TurboCodegen;

use PhpParser\Node\Expr;
use PHPStan\Type\Type;

/** A declared parameter and how it is lowered. */
final class ParamInfo
{

	/**
	 * @param 'long'|'bool'|'zval'|'callable' $kind
	 * @param string $describe the type as PHP's TypeError messages spell it
	 */
	public function __construct(
		public readonly string $name,
		public readonly int $position,
		public readonly string $kind,
		public readonly Type $declared,
		public readonly string $describe,
		public readonly ?string $class,
		public readonly bool $nullable,
		public readonly bool $variadic,
		public readonly ?Expr $default,
		public readonly bool $isArray,
		public readonly bool $promoted,
		public readonly bool $promotedReadonly,
		public readonly bool $byRef = false,
	)
	{
	}

}
