<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Traverser;

use PHPStan\Analyser\Scope;
use PHPStan\Type\StaticType;
use PHPStan\Type\ThisType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverserCallable;

final class TransformStaticTypeTraverser implements TypeTraverserCallable
{

	public function __construct(
		private readonly Scope $scope,
	)
	{
	}

	/**
	 * @param callable(Type): Type $traverse
	 */
	public function traverse(Type $type, callable $traverse): Type
	{
		if (!$this->scope->isInClass()) {
			return $type;
		}
		if ($type instanceof StaticType) {
			$classReflection = $this->scope->getClassReflection();
			$changedType = $type->changeBaseClass($classReflection);
			if ($classReflection->isFinal() && !$type instanceof ThisType) {
				$changedType = $changedType->getStaticObjectType();
			}
			return $traverse($changedType);
		}

		return $traverse($type);
	}

}
