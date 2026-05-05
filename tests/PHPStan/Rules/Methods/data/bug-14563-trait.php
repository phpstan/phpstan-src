<?php declare(strict_types = 1);

namespace Bug14563Trait;

trait PureTrait
{

	/** @phpstan-pure */
	abstract public function pureTraitMethod(): int;

}

class ImpureTraitUser
{

	use PureTrait;

	/** @phpstan-impure */
	public function pureTraitMethod(): int
	{
		return random_int(0, 1);
	}

}
