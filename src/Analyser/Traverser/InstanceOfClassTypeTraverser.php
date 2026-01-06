<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Traverser;

use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Generic\GenericClassStringType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;

final class InstanceOfClassTypeTraverser
{

	private bool $uncertainty = false;

	/**
	 * @param callable(Type): Type $traverse
	 */
	public function __invoke(Type $type, callable $traverse): Type
	{
		if ($type instanceof UnionType || $type instanceof IntersectionType) {
			return $traverse($type);
		}

		if ($type->getObjectClassNames() !== []) {
			$this->uncertainty = true;
			return $type;
		}
		if ($type instanceof GenericClassStringType) {
			$this->uncertainty = true;
			return $type->getGenericType();
		}
		if ($type instanceof ConstantStringType) {
			return new ObjectType($type->getValue());
		}
		return new MixedType();
	}

	public function getUncertainty(): bool
	{
		return $this->uncertainty;
	}

}
