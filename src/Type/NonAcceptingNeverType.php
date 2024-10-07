<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\TrinaryLogic;

/** @api */
class NonAcceptingNeverType extends NeverType
{

	/** @api */
	public function __construct()
	{
		parent::__construct(true);
	}

	public function isSuperTypeOf(Type $type): TrinaryLogic
	{
		return $this->isSuperTypeOfWithReason($type)->result;
	}

	public function isSuperTypeOfWithReason(Type $type): IsSuperTypeOfResult
	{
		if ($type instanceof self) {
			return IsSuperTypeOfResult::createYes();
		}
		if ($type instanceof parent) {
			return IsSuperTypeOfResult::createMaybe();
		}

		return IsSuperTypeOfResult::createNo();
	}

	public function acceptsWithReason(Type $type, bool $strictTypes): AcceptsResult
	{
		if ($type instanceof NeverType) {
			return AcceptsResult::createYes();
		}

		return AcceptsResult::createNo();
	}

	public function describe(VerbosityLevel $level): string
	{
		return 'never';
	}

}
