<?php

namespace ScopeInEnumMatchArmBody;

use function PHPStan\Testing\assertType;

enum Foo: int
{
	case ALLOW_NULLABLE_INT = 1;
	case ALLOW_ONLY_INT = 2;

	public function bar(?int $nullable): void
	{
		if ($nullable === null && $this === self::ALLOW_ONLY_INT) {
			throw new \LogicException('Cannot be null');
		}

		match ($this) {
			self::ALLOW_ONLY_INT => assertType('int', $nullable),
			self::ALLOW_NULLABLE_INT => assertType('int|null', $nullable),
		};
	}
}



