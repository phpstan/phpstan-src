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

	public function producesWarningForFinalPrivateMethods(): TrinaryLogic
	{
		return $this->phpVersions->isSuperTypeOf(IntegerRangeType::fromInterval(80000, null))->result;
	}

}
