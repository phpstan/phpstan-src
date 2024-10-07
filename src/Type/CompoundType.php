<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Php\PhpVersion;
use PHPStan\TrinaryLogic;

/** @api */
interface CompoundType extends Type
{

	public function isAcceptedBy(Type $acceptingType, bool $strictTypes): AcceptsResult;

	public function isSubTypeOf(Type $otherType): IsSuperTypeOfResult;

	public function isGreaterThan(Type $otherType, PhpVersion $phpVersion): TrinaryLogic;

	public function isGreaterThanOrEqual(Type $otherType, PhpVersion $phpVersion): TrinaryLogic;

}
