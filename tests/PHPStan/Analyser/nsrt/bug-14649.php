<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug14649;

use function PHPStan\Testing\assertType;

enum Role: string
{
    case OWNER = 'OWNER';
    case ADMIN = 'ADMIN';
    case EDITOR = 'EDITOR';

    public function isGreaterThanOrEqual(Role $role): bool
    {
		$map = array_map(
            static fn (Role $role): string => $role->value,
            self::cases()
        );

		assertType("array{'OWNER', 'ADMIN', 'EDITOR'}", $map);

        $hierarchy = array_flip($map);

		assertType("array{OWNER: 0, ADMIN: 1, EDITOR: 2}", $hierarchy);

        return $hierarchy[$this->value] <= $hierarchy[$role->value];
    }
}

function testArrowFunctionArithmetic(): void
{
	$arr = [1, 2, 3];
	$result = array_map(fn(int $x): int => $x * 2, $arr);
	assertType("array{2, 4, 6}", $result);
}

function testClosureArithmetic(): void
{
	$arr = [1, 2, 3];
	$result = array_map(function (int $x): int { return $x * 2; }, $arr);
	assertType("array{2, 4, 6}", $result);
}

function testArrowFunctionStringConcat(): void
{
	$arr = ['a', 'b', 'c'];
	$result = array_map(fn(string $s): string => $s . '_suffix', $arr);
	assertType("array{'a_suffix', 'b_suffix', 'c_suffix'}", $result);
}

function testNamedFunctionCallback(): void
{
	$arr = ['FOO', 'BAR', 'BAZ'];
	$result = array_map('strtolower', $arr);
	assertType("array{'foo', 'bar', 'baz'}", $result);
}

enum IntEnum: int
{
	case A = 10;
	case B = 20;
}

function testIntBackedEnum(): void
{
	$result = array_map(
		static fn (IntEnum $e): int => $e->value,
		IntEnum::cases()
	);
	assertType("array{10, 20}", $result);
}

function testClosureWithStringKeys(): void
{
	$arr = ['x' => 1, 'y' => 2];
	$result = array_map(fn(int $v): string => (string)$v, $arr);
	assertType("array{x: '1', y: '2'}", $result);
}
