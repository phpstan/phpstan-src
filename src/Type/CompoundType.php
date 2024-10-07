<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Php\PhpVersion;
use PHPStan\TrinaryLogic;

/** @api */
interface CompoundType extends Type
{

	public function isSubTypeOf(Type $otherType): TrinaryLogic;

	public function isAcceptedBy(Type $acceptingType, bool $strictTypes): AcceptsResult;

	/**
	 * This is like isSubTypeOf() but gives reasons
	 * why the type was not/might not be accepted in some non-intuitive scenarios.
	 *
	 * In PHPStan 2.0 this method will be removed and the return type of isSubTypeOf()
	 * will change to IsSuperTypeOfResult.
	 */
	public function isSubTypeOfWithReason(Type $otherType): IsSuperTypeOfResult;

	public function isGreaterThan(Type $otherType, PhpVersion $phpVersion): TrinaryLogic;

	public function isGreaterThanOrEqual(Type $otherType, PhpVersion $phpVersion): TrinaryLogic;

}
