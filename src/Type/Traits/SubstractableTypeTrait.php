<?php declare(strict_types = 1);

namespace PHPStan\Type\Traits;

use PHPStan\Type\SubtractableType;
use PHPStan\Type\SubtractionAlgebra;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

trait SubstractableTypeTrait
{

	public function tryUnion(Type $otherType): ?Type
	{
		return SubtractionAlgebra::tryUnion($this, $otherType);
	}

	public function tryIntersect(Type $otherType): ?Type
	{
		return SubtractionAlgebra::tryIntersect($this, $otherType);
	}

	public function describeSubtractedType(?Type $subtractedType, VerbosityLevel $level): string
	{
		if ($subtractedType === null) {
			return '';
		}

		if (
			$subtractedType instanceof UnionType
			|| ($subtractedType instanceof SubtractableType && $subtractedType->getSubtractedType() !== null)
		) {
			return sprintf('~(%s)', $subtractedType->describe($level));
		}

		return sprintf('~%s', $subtractedType->describe($level));
	}

}
