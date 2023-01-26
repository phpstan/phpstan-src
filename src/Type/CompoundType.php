<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\TrinaryLogic;

/** @api */
interface CompoundType extends Type
{

	public function isSubTypeOf(Type $otherType): TrinaryLogic;

	public function isAcceptedBy(Type $acceptingType, bool $strictTypes): TrinaryLogic;

	public function isAcceptedWithReasonBy(Type $acceptingType, bool $strictTypes): AcceptsResult;

	public function isGreaterThan(Type $otherType): TrinaryLogic;

	public function isGreaterThanOrEqual(Type $otherType): TrinaryLogic;

}
