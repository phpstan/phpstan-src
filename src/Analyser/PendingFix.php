<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use Closure;
use PhpParser\Node;

/**
 * @internal
 */
final class PendingFix
{

	/**
	 * @param Closure(Node): Node $newNodeCallable
	 * @param class-string|null $consumerClass
	 */
	public function __construct(
		public readonly Error $error,
		public readonly Node $originalNode,
		public readonly Closure $newNodeCallable,
		public readonly string $fixingFilePath,
		public readonly ?string $consumerClass = null,
	)
	{
	}

}
