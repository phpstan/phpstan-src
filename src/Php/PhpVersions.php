<?php declare(strict_types = 1);

namespace PHPStan\Php;

use PHPStan\TrinaryLogic;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\Type;

/**
 * Range-aware PHP version check that handles version uncertainty.
 *
 * Unlike PhpVersion (which represents a single known version), PhpVersions wraps
 * a Type representing the possible PHP versions. When the exact version is known,
 * queries return Yes/No. When a range of versions is possible (e.g. `int<80000, 80400>`),
 * queries return Maybe.
 *
 * This is the return type of Scope::getPhpVersion(). Rules and extensions use it
 * to query version-dependent features:
 *
 *     $scope->getPhpVersion()->supportsNamedArguments() // TrinaryLogic
 *
 * The underlying type is an integer (range) type representing PHP_VERSION_ID values.
 *
 * @api
 */
final class PhpVersions
{

	/**
	 * @param Type $phpVersions An integer type representing the possible PHP_VERSION_ID values
	 */
	public function __construct(
		private Type $phpVersions,
	)
	{
	}

	/**
	 * Returns the underlying type representing the PHP version range.
	 */
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
