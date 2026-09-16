<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Turbo\ShadowedByTurboExtension;

#[AutowiredService]
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../turbo-ext/src/ParametersProcessor.cpp')]
final class ParametersProcessor
{

	public function __construct(
		private AttributesHandler $attributesHandler,
	)
	{
	}

	/**
	 * @param Node\Param[] $params
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	public function processParams(
		NodeScopeResolver $nodeScopeResolver,
		Node\Stmt $stmt,
		array $params,
		MutatingScope $scope,
		ExpressionResultStorage $storage,
		callable $nodeCallback,
	): void
	{
		foreach ($params as $param) {
			$this->attributesHandler->processAttributeGroups($nodeScopeResolver, $stmt, $param->attrGroups, $scope, $storage, $nodeCallback);
			$nodeScopeResolver->callNodeCallback($nodeCallback, $param, $scope, $storage);
			if ($param->type !== null) {
				$nodeScopeResolver->callNodeCallback($nodeCallback, $param->type, $scope, $storage);
			}
			if ($param->default === null) {
				continue;
			}

			$nodeScopeResolver->processExprNode($stmt, $param->default, $scope, $storage, $nodeCallback, ExpressionContext::createDeep());
		}
	}

}
