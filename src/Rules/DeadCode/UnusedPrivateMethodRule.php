<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PhpParser\Node;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClassMethodsNode;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Rules\Methods\AlwaysUsedMethodExtensionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantStringType;
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

	public function __construct(private AlwaysUsedMethodExtensionProvider $extensionProvider)
	{
	}

	public function getNodeType(): string
	{
		return ClassMethodsNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->getClass() instanceof Node\Stmt\Class_ && !$node->getClass() instanceof Node\Stmt\Enum_) {
			return [];
		}
		$classReflection = $node->getClassReflection();
		$classType = new ObjectType($classReflection->getName());
		$constructor = null;
		if ($classReflection->hasConstructor()) {
			$constructor = $classReflection->getConstructor();
		}

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

			$methodReflection = $classType->getMethod($methodName, $scope);
			foreach ($this->extensionProvider->getExtensions() as $extension) {
				if ($extension->isAlwaysUsed($methodReflection)) {
					continue 2;
				}
			}

			$methods[strtolower($methodName)] = $method;
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
				if ($methodCallNode->class instanceof Node\Name) {
					$calledOnType = $callScope->resolveTypeByName($methodCallNode->class);
				} else {
					$calledOnType = $callScope->getType($methodCallNode->class);
				}
			}

			$inMethod = $callScope->getFunction();
			if (!$inMethod instanceof MethodReflection) {
				continue;
			}

			foreach ($methodNames as $methodName) {
				$methodReflection = $callScope->getMethodReflection($calledOnType, $methodName);
				if ($methodReflection === null) {
					if (!$classType->isSuperTypeOf($calledOnType)->no()) {
						unset($methods[strtolower($methodName)]);
					}
					continue;
				}
				if ($methodReflection->getDeclaringClass()->getName() !== $classReflection->getName()) {
					if (!$classType->isSuperTypeOf($calledOnType)->no()) {
						unset($methods[strtolower($methodName)]);
					}
					continue;
				}
				if ($inMethod->getName() === $methodName) {
					continue;
				}
				unset($methods[strtolower($methodName)]);
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
						$methodReflection = $arrayScope->getMethodReflection($calledOnType, $typeAndMethod->getMethod());
						if ($methodReflection === null) {
							continue;
						}

						if ($methodReflection->getDeclaringClass()->getName() !== $classReflection->getName()) {
							continue;
						}

						$inMethod = $arrayScope->getFunction();
						if (!$inMethod instanceof MethodReflection) {
							continue;
						}
						if ($inMethod->getName() === $typeAndMethod->getMethod()) {
							continue;
						}
						unset($methods[strtolower($typeAndMethod->getMethod())]);
					}
				}
			}
		}

		$errors = [];
		foreach ($methods as $method) {
			$originalMethodName = $method->getNode()->name->toString();
			$methodType = 'Method';
			if ($method->getNode()->isStatic()) {
				$methodType = 'Static method';
			}
			$errors[] = RuleErrorBuilder::message(sprintf('%s %s::%s() is unused.', $methodType, $classReflection->getDisplayName(), $originalMethodName))
				->line($method->getNode()->getLine())
				->identifier('deadCode.unusedMethod')
				->metadata([
					'classOrder' => $node->getClass()->getAttribute('statementOrder'),
					'classDepth' => $node->getClass()->getAttribute('statementDepth'),
					'classStartLine' => $node->getClass()->getStartLine(),
					'methodName' => $originalMethodName,
				])
				->build();
		}

		return $errors;
	}

}
