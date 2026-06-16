<?php declare(strict_types = 1); // lint >= 8.2

namespace ArrayColumnRuleTest;

class NonFinalObject
{

	/** @var string */
	public $key = 'as';

}

final class FinalObject
{

	public int $id = 1;

	public string $name = 'a';

	private int $secret = 2;

}

class MagicObject
{

	public function __get(string $name): int
	{
		return 1;
	}

	public function __isset(string $name): bool
	{
		return true;
	}

}

#[\AllowDynamicProperties]
class DynamicObject
{

}

enum Suit: string
{

	case Hearts = 'H';

}

/**
 * @param NonFinalObject[] $a
 * @param FinalObject[] $b
 * @param MagicObject[] $c
 * @param DynamicObject[] $d
 * @param Suit[] $e
 * @param array<array<string, int>> $f
 * @param list<FinalObject|array<string, int>> $g
 */
function test(array $a, array $b, array $c, array $d, array $e, array $f, array $g): void
{
	array_column($a, 'key');
	array_column($a, 'wrong_key');

	array_column($b, 'id');
	array_column($b, 'name');
	array_column($b, 'missing');
	array_column($b, 'name', 'id');
	array_column($b, 'name', 'missing');
	array_column($b, 'missing', 'missing2');
	array_column($b, 'secret');

	array_column($c, 'anything');
	array_column($d, 'anything');

	array_column($e, 'value');
	array_column($e, 'name');
	array_column($e, 'missing');

	array_column($f, 'col');
	array_column($g, 'missing');
}

/**
 * @param FinalObject[] $b
 */
function dynamicColumnName(array $b, string $column): void
{
	array_column($b, $column);
}

function bug5101(): void
{
	$ar = [new NonFinalObject(), new NonFinalObject()];
	array_column($ar, 'wrong_key');
	array_column($ar, 'key');
}
