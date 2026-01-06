<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Traverser;

use PHPStan\Type\IntersectionType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;

final class VoidToNullTraverser
{

	/**
	 * @param callable(Type): Type $traverse
	 */
	public function __invoke(Type $type, callable $traverse): Type
	{
		if ($type instanceof UnionType || $type instanceof IntersectionType) {
			return $traverse($type);
		}

		if ($type->isVoid()->yes()) {
			return new NullType();
		}

		return $type;
	}

}
