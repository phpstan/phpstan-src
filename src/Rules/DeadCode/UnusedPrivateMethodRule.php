<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PhpParser\Node;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClassMethodsNode;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use function array_map;
use function count;
use function sprintf;
use function strtolower;

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
		if (!$node->getClass() instanceof Node\Stmt\Class_ && !$node->getClass() instanceof Node\Stmt\Enum_) {
			return [];
		}
		if (!$scope->isInClass()) {
			throw new ShouldNotHappenException();
		}
		$classReflection = $scope->getClassReflection();
		$constructor = null;
		if ($classReflection->hasConstructor()) {
			$constructor = $classReflection->getConstructor();
		}
		$classType = new ObjectType($classReflection->getName());

		$methods = [];
		foreach ($node->getMethods() as $method) {
			if (!$method->getNode()->isPrivate()) {
				continue;
			}
			if ($method->isDeclaredInTrait()) {
				continue;
			}
			$methodName = $method->getNode()->name->toString();
			if ($constructor !== null && $constructor->getName() === $methodName) {
				continue;
			}
			if (strtolower($methodName) === '__clone') {
				continue;
			}
			$methods[$methodName] = $method;
		}

		$arrayCalls = [];
		foreach ($node->getMethodCalls() as $methodCall) {
			$methodCallNode = $methodCall->getNode();
			if ($methodCallNode instanceof Node\Expr\Array_) {
				$arrayCalls[] = $methodCall;
				continue;
			}
			$callScope = $methodCall->getScope();
			if ($methodCallNode->name instanceof Identifier) {
				$methodNames = [$methodCallNode->name->toString()];
			} else {
				$methodNameType = $callScope->getType($methodCallNode->name);
				$strings = $methodNameType->getConstantStrings();
				if (count($strings) === 0) {
					return [];
				}

				$methodNames = array_map(static fn (ConstantStringType $type): string => $type->getValue(), $strings);
			}

			if ($methodCallNode instanceof Node\Expr\MethodCall) {
				$calledOnType = $callScope->getType($methodCallNode->var);
			} else {
				if (!$methodCallNode->class instanceof Node\Name) {
					continue;
				}
				$calledOnType = $scope->resolveTypeByName($methodCallNode->class);
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

			foreach ($methodNames as $methodName) {
				if ($inMethod->getName() === $methodName) {
					continue;
				}
				unset($methods[$methodName]);
			}
		}

		if (count($methods) > 0) {
			foreach ($arrayCalls as $arrayCall) {
				/** @var Node\Expr\Array_ $array */
				$array = $arrayCall->getNode();
				$arrayScope = $arrayCall->getScope();
				$arrayType = $arrayScope->getType($array);
				if (!$arrayType->isCallable()->yes()) {
					continue;
				}
				foreach ($arrayType->getConstantArrays() as $constantArray) {
					foreach ($constantArray->findTypeAndMethodNames() as $typeAndMethod) {
						if ($typeAndMethod->isUnknown()) {
							return [];
						}
						if (!$typeAndMethod->getCertainty()->yes()) {
							return [];
						}
						$calledOnType = $typeAndMethod->getType();
						if ($classType->isSuperTypeOf($calledOnType)->no()) {
							continue;
						}
						if ($calledOnType instanceof MixedType) {
							continue;
						}
						$inMethod = $arrayScope->getFunction();
						if (!$inMethod instanceof MethodReflection) {
							continue;
						}
						if ($inMethod->getName() === $typeAndMethod->getMethod()) {
							continue;
						}
						unset($methods[$typeAndMethod->getMethod()]);
					}
				}
			}
		}

		$errors = [];
		foreach ($methods as $methodName => $method) {
			$methodType = 'Method';
			if ($method->getNode()->isStatic()) {
				$methodType = 'Static method';
			}
			$errors[] = RuleErrorBuilder::message(sprintf('%s %s::%s() is unused.', $methodType, $classReflection->getDisplayName(), $methodName))
				->line($method->getNode()->getLine())
				->identifier('method.unused')
				->build();
		}

		return $errors;
	}

}
