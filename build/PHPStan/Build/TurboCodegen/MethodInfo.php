<?php declare(strict_types = 1);

namespace PHPStan\Build\TurboCodegen;

use PhpParser\Node\Stmt\ClassMethod;

/** A method of the compile set and its C++ calling convention. */
final class MethodInfo
{

	/**
	 * @param bool $direct whether callers may bind it statically (final class, private or final method)
	 * @param list<ParamInfo> $params
	 */
	public function __construct(
		public readonly ClassUnit $unit,
		public readonly string $name,
		public readonly bool $isStatic,
		public readonly bool $direct,
		public readonly array $params,
		public readonly ReturnInfo $return,
		public readonly ClassMethod $node,
	)
	{
	}

}
