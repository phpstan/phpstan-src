<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\ShouldNotHappenException;
use function array_pop;
use function count;

/**
 * The ExpressionResultStorage of the analysis currently in progress.
 *
 * Not a service - the internal scope factory creates one instance and injects
 * it into every scope it creates, so all scopes of one analysis share it by
 * construction. Scopes deliberately do not reference the storage directly -
 * it would create a reference cycle (storage -> results -> scopes -> storage)
 * that never gets collected because the cycle collector is disabled
 * in bin/phpstan.
 *
 * NodeScopeResolver pushes a storage for the duration of an analysis (file,
 * statement list, trait pass, on-demand expression) through
 * MutatingScope::pushExpressionResultStorage() and must always pop it
 * in a finally block. Old-world type questions about an expression are answered
 * from the current storage (see MutatingScope::resolveTypeOfNewWorldHandlerNode()).
 * A scope used outside any running analysis simply misses here and resolves
 * on demand with a throwaway storage.
 */
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
		if (count($this->stack) === 0) {
			throw new ShouldNotHappenException('Unbalanced ExpressionResultStorageStack pop.');
		}

		array_pop($this->stack);
	}

	public function getCurrent(): ?ExpressionResultStorage
	{
		if (count($this->stack) === 0) {
			return null;
		}

		return $this->stack[count($this->stack) - 1];
	}

}
