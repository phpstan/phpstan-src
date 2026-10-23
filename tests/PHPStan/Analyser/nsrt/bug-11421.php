<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug11421Types;

use function PHPStan\Testing\assertNativeType;
use function PHPStan\Testing\assertType;

enum NumberEnum: int
{
	case One = 1;
}

enum WordEnum: string
{
	case One = 'one';
}

function (): void {
	assertType('Closure(int): Bug11421Types\NumberEnum', NumberEnum::from(...));
	assertNativeType('Closure(int): mixed', NumberEnum::from(...));
	assertType('Closure(int): (Bug11421Types\NumberEnum|null)', NumberEnum::tryFrom(...));
	assertType('Closure(string): Bug11421Types\WordEnum', WordEnum::from(...));
	assertType('Closure(string): (Bug11421Types\WordEnum|null)', WordEnum::tryFrom(...));
};
