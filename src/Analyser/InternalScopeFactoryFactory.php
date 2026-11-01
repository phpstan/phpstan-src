<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;

interface InternalScopeFactoryFactory
{

	/**
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	public function create(
		?callable $nodeCallback,
		bool $createsNodeCallbackScopes,
		?ExpressionResultStorageStack $expressionResultStorageStack,
	): InternalScopeFactory;

}
