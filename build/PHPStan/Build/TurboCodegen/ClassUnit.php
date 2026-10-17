<?php declare(strict_types = 1);

namespace PHPStan\Build\TurboCodegen;

use PhpParser\Node\Stmt\Class_;
use ReflectionClass;

/** One class of the compile set. */
final class ClassUnit
{

	/** @var array<string, MethodInfo> lowercase name => method */
	public array $methods = [];

	/**
	 * @param ReflectionClass<object> $reflection
	 */
	public function __construct(
		public readonly string $className,
		public readonly string $stem,
		public readonly string $ce,
		public readonly ReflectionClass $reflection,
		public readonly TypeOracle $oracle,
		public readonly Class_ $node,
		public readonly string $implementation,
	)
	{
	}

}
