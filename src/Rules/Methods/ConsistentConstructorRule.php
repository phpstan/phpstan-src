<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\Dummy\DummyConstructorReflection;
use PHPStan\Reflection\MethodPrototypeReflection;
use PHPStan\Reflection\Php\PhpMethodReflection;
use PHPStan\Rules\Rule;
use function strtolower;

/** @implements Rule<InClassMethodNode> */
class ConsistentConstructorRule implements Rule
{

	public function __construct(
		private MethodParameterComparisonHelper $methodParameterComparisonHelper,
	)
	{
	}

	public function getNodeType(): string
	{
		return InClassMethodNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$method = $node->getMethodReflection();
		if (strtolower($method->getName()) !== '__construct') {
			return [];
		}

		$parent = $method->getDeclaringClass()->getParentClass();

		if ($parent === null) {
			return [];
		}

		if ($parent->hasConstructor()) {
			$parentConstructor = $parent->getConstructor();
		} else {
			$parentConstructor = $this->getEmptyConstructor($parent);
		}

		if (! $parentConstructor instanceof PhpMethodReflection && ! $parentConstructor instanceof MethodPrototypeReflection) {
			return [];
		}

		if (! $parentConstructor->getDeclaringClass()->hasConsistentConstructor()) {
			return [];
		}

		if (! $parentConstructor instanceof MethodPrototypeReflection) {
			$parentConstructor = $this->getMethodPrototypeReflection($parentConstructor, $parent);
		}

		return $this->methodParameterComparisonHelper->compare($parentConstructor, $method, true);
	}

	private function getMethodPrototypeReflection(PhpMethodReflection $methodReflection, ClassReflection $classReflection): MethodPrototypeReflection
	{
		return new MethodPrototypeReflection(
			$methodReflection->getName(),
			$classReflection,
			$methodReflection->isStatic(),
			$methodReflection->isPrivate(),
			$methodReflection->isPublic(),
			$methodReflection->isAbstract(),
			$methodReflection->isFinal()->yes(),
			$classReflection->getNativeMethod($methodReflection->getName())->getVariants(),
			null,
		);
	}

	private function getEmptyConstructor(ClassReflection $classReflection): MethodPrototypeReflection
	{
		$emptyConstructor = new DummyConstructorReflection($classReflection);

		return new MethodPrototypeReflection(
			$emptyConstructor->getName(),
			$classReflection,
			$emptyConstructor->isStatic(),
			$emptyConstructor->isPrivate(),
			$emptyConstructor->isPublic(),
			false,
			$emptyConstructor->isFinal()->yes(),
			$emptyConstructor->getVariants(),
			null,
		);
	}

}
