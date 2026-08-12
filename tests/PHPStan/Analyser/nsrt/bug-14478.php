<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug14478;

use function PHPStan\Testing\assertType;

enum SomeEnum: string
{

	case A = 'a';
	case B = 'b';
	case C = 'c';

	public function toInt(): int
	{
		return match ($this) {
			self::A => 1,
			self::B => 2,
			self::C => 3,
		};
	}

	public function narrowedInArm(): int
	{
		return match ($this) {
			self::A => (function (): int {
				assertType('$this(Bug14478\SomeEnum)&Bug14478\SomeEnum::A', $this);
				assertType('Bug14478\SomeEnum::A', self::A);

				return 1;
			})(),
			self::B, self::C => 2,
		};
	}

	public function afterMatch(): void
	{
		$i = match ($this) {
			self::A => 1,
			self::B => 2,
			self::C => 3,
		};

		assertType('1|2|3', $i);
		// the union of per-arm narrowings the arm scopes are merged into - it is this
		// union that the conditional-expression guard checks used to walk once per arm
		assertType('($this(Bug14478\SomeEnum)&Bug14478\SomeEnum::C)|($this(Bug14478\SomeEnum)&Bug14478\SomeEnum::B)|($this(Bug14478\SomeEnum)&Bug14478\SomeEnum::A)', $this);
		assertType('Bug14478\SomeEnum::A', self::A);
	}

}
