<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Expr\New_;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;

/**
 * Processes attribute arguments for NodeScopeResolver - as constructor
 * arguments when the attribute class has a constructor, as plain expressions
 * otherwise.
 */
#[AutowiredService]
final class AttributesHandler
{

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private ArgumentsHandler $argumentsHandler,
	)
	{
	}

	/**
	 * @param AttributeGroup[] $attrGroups
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	public function processAttributeGroups(
		NodeScopeResolver $nodeScopeResolver,
		Node\Stmt $stmt,
		array $attrGroups,
		MutatingScope $scope,
		ExpressionResultStorage $storage,
		callable $nodeCallback,
	): void
	{
		foreach ($attrGroups as $attrGroup) {
			foreach ($attrGroup->attrs as $attr) {
				$className = $scope->resolveName($attr->name);
				if ($this->reflectionProvider->hasClass($className)) {
					$classReflection = $this->reflectionProvider->getClass($className);
					if ($classReflection->hasConstructor()) {
						$constructorReflection = $classReflection->getConstructor();
						$parametersAcceptor = ParametersAcceptorSelector::combineVariantsForNormalization(
							$attr->args,
							$constructorReflection->getVariants(),
							$constructorReflection->getNamedArgumentsVariants(),
						);
						$expr = new New_($attr->name, $attr->args);
						$expr = ArgumentsNormalizer::reorderNewArguments($parametersAcceptor, $expr) ?? $expr;
						$this->argumentsHandler->processArgs($nodeScopeResolver, $stmt, $constructorReflection, null, $constructorReflection->getVariants(), $constructorReflection->getNamedArgumentsVariants(), $expr, $scope, $storage, $nodeCallback, ExpressionContext::createDeep());
						$nodeScopeResolver->callNodeCallback($nodeCallback, $attr, $scope, $storage);
						continue;
					}
				}

				foreach ($attr->args as $arg) {
					$nodeScopeResolver->processExprNode($stmt, $arg->value, $scope, $storage, $nodeCallback, ExpressionContext::createDeep());
					$nodeScopeResolver->callNodeCallback($nodeCallback, $arg, $scope, $storage);
				}
				$nodeScopeResolver->callNodeCallback($nodeCallback, $attr, $scope, $storage);
			}
			$nodeScopeResolver->callNodeCallback($nodeCallback, $attrGroup, $scope, $storage);
		}
	}

}
