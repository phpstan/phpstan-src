<?php declare(strict_types = 1);

namespace PHPStan\Type;

/** @api */
class NonAcceptingNeverType extends NeverType
{

	/** @api */
	public function __construct()
	{
		parent::__construct(true);
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
