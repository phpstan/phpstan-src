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

	public function getType(): Type
	{
		return $this->phpVersions;
	}

	public function supportsNoncapturingCatches(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80000, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function producesWarningForFinalPrivateMethods(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80000, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function supportsNamedArguments(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80000, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function supportsNamedArgumentAfterUnpackedArgument(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80100, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function supportsTrueAndFalseStandaloneType(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80200, null)->isSuperTypeOf($this->phpVersions)->result;
	}

	public function supportsMaxMemoryLimit(): TrinaryLogic
	{
		return IntegerRangeType::fromInterval(80500, null)->isSuperTypeOf($this->phpVersions)->result;
	}

}
