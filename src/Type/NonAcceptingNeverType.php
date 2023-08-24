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
		if ($type instanceof self) {
			return TrinaryLogic::createYes();
		}
		if ($type instanceof parent) {
			return TrinaryLogic::createMaybe();
		}

		return TrinaryLogic::createNo();
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
