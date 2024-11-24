<?php declare(strict_types = 1);

namespace PHPStan\Php;

use PHPStan\TrinaryLogic;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\Type;

/**
 * @api
 */
final class PhpVersions
{

	public function __construct(
		private Type $phpVersions,
	)
	{
	}

	public function supportsNoncapturingCatches(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80000, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function producesWarningForFinalPrivateMethods(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80000, null)->isSuperTypeOf($this->phpVersions)->result;
	}

}
