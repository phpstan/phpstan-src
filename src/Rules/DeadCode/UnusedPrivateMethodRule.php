<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PhpParser\Node;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClassMethodsNode;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;

/**
 * @implements Rule<ClassMethodsNode>
 */
class UnusedPrivateMethodRule implements Rule
{

	public function getNodeType(): string
	{
		return ClassMethodsNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->getClass() instanceof Node\Stmt\Class_) {
			return [];
		}
		if (!$scope->isInClass()) {
			throw new \PHPStan\ShouldNotHappenException();
		}
		$classReflection = $scope->getClassReflection();
		$constructor = null;
		if ($classReflection->hasConstructor()) {
			$constructor = $classReflection->getConstructor();
		}
		$classType = new ObjectType($classReflection->getName());

		$methods = [];
		foreach ($node->getMethods() as $method) {
			if (!$method->isPrivate()) {
				continue;
			}
			if ($constructor !== null && $constructor->getName() === $method->name->toString()) {
				continue;
			}
			$methods[$method->name->toString()] = $method;
		}

		foreach ($node->getMethodCalls() as $methodCall) {
			$methodCallNode = $methodCall->getNode();
			if (!$methodCallNode->name instanceof Identifier) {
				continue;
			}
			$methodName = $methodCallNode->name->toString();
			$callScope = $methodCall->getScope();
			if ($methodCallNode instanceof Node\Expr\MethodCall) {
				$calledOnType = $callScope->getType($methodCallNode->var);
			} else {
				if (!$methodCallNode->class instanceof Node\Name) {
					continue;
				}
				$calledOnType = new ObjectType($callScope->resolveName($methodCallNode->class));
			}
			if ($classType->isSuperTypeOf($calledOnType)->no()) {
				continue;
			}
			if ($calledOnType instanceof MixedType) {
				continue;
			}
			$inMethod = $callScope->getFunction();
			if (!$inMethod instanceof MethodReflection) {
				continue;
			}
			if ($inMethod->getName() === $methodName) {
				continue;
			}
			unset($methods[$methodName]);
		}

		$errors = [];
		foreach ($methods as $methodName => $methodNode) {
			$errors[] = RuleErrorBuilder::message(sprintf('Class %s has an unused method %s().', $classReflection->getDisplayName(), $methodName))->line($methodNode->getLine())->build();
		}

		return $errors;
	}

}
