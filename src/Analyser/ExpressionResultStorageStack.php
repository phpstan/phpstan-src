<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\DependencyInjection\AutowiredService;
use function array_key_last;
use function array_pop;
use function count;

/**
 * The ExpressionResultStorage a node callback's type asks resolve against.
 *
 * FiberNodeScopeResolver::callNodeCallback() pushes the emitting walk's
 * storage for the duration of the callback and always pops it in a finally
 * block - the same association a suspended fiber's request had with the
 * frame that would resolve it. Scopes deliberately do not reference the
 * storage directly - it would create a reference cycle (storage -> scopes ->
 * storage) that never gets collected because the cycle collector is disabled
 * in bin/phpstan. An ask outside any running callback simply misses here and
 * resolves on demand.
 */
#[AutowiredService]
final class ExpressionResultStorageStack
{

	/** @var list<ExpressionResultStorage> */
	private array $stack = [];

	public function push(ExpressionResultStorage $storage): void
	{
		$this->stack[] = $storage;
	}

	public function pop(): void
	{
		array_pop($this->stack);
	}

	public function getCurrent(): ?ExpressionResultStorage
	{
		if (count($this->stack) === 0) {
			return null;
		}

		return $this->stack[array_key_last($this->stack)];
	}

}
