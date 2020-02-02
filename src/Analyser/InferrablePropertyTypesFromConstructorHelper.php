<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PHPStan\Type\MixedType;

class InferrablePropertyTypesFromConstructorHelper
{

	/** @var bool */
	private $hasInferrablePropertyTypesFromConstructor = false;

	public function hasInferrablePropertyTypesFromConstructor(): bool
	{
		return $this->hasInferrablePropertyTypesFromConstructor;
	}

	public function __invoke(Node $node, Scope $scope): void
	{
		if ($this->hasInferrablePropertyTypesFromConstructor) {
			return;
		}

		if (!$node instanceof Node\Stmt\PropertyProperty) {
			return;
		}

		if (!$scope->isInClass()) {
			return;
		}

		$classReflection = $scope->getClassReflection();
		if (!$classReflection->hasConstructor() || $classReflection->getConstructor()->getDeclaringClass()->getName() !== $classReflection->getName()) {
			return;
		}
		$propertyName = $node->name->toString();
		if (!$classReflection->hasNativeProperty($propertyName)) {
			return;
		}
		$propertyReflection = $classReflection->getNativeProperty($propertyName);
		if (!$propertyReflection->isPrivate()) {
			return;
		}
		$propertyType = $propertyReflection->getReadableType();
		if (!$propertyType instanceof MixedType || $propertyType->isExplicitMixed()) {
			return;
		}

		$this->hasInferrablePropertyTypesFromConstructor = true;
	}

}
