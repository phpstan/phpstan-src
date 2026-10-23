<?php // lint >= 8.1

declare(strict_types = 1);

namespace BackedEnumFromParameterTypeOff;

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
	assertType('Closure(int|string): BackedEnumFromParameterTypeOff\NumberEnum', NumberEnum::from(...));
	assertType('Closure(int|string): (BackedEnumFromParameterTypeOff\NumberEnum|null)', NumberEnum::tryFrom(...));
	assertType('Closure(int|string): BackedEnumFromParameterTypeOff\WordEnum', WordEnum::from(...));
	assertType('Closure(int|string): (BackedEnumFromParameterTypeOff\WordEnum|null)', WordEnum::tryFrom(...));
};
