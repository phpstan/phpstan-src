<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug5755;

use function PHPStan\Testing\assertType;

/**
 * @param list<mixed> $ids
 * @return list<string>|null
 */
function validate(array $ids): array|null
{
	foreach ($ids as $id) {
		if (!is_string($id)) {
			return null;
		}
	}

	assertType('list<string>', $ids);

	return $ids;
}

class Test
{

	/**
	 * @return array<int|string>|float|int|string|false|null
	 */
	private function value(): mixed
	{
		$values = ['a', 1, false, null, 3.4, [], [2, 3, 4], ['a', 'b', 'c']];
		$index = rand(0, 7);
		return $values[$index];
	}

	/**
	 * @return array<string>|null
	 */
	public function strings(): ?array
	{
		$values = $this->value();
		if (!is_array($values)) {
			return null;
		}
		foreach ($values as $value) {
			if (!is_string($value)) {
				return null;
			}
		}

		assertType('array<string>', $values);

		return $values;
	}

}

/**
 * @param array<string, int|string> $map
 */
function withGuardContinue(array $map): void
{
	foreach ($map as $value) {
		if (is_int($value)) {
			continue;
		}
	}

	// The continue keeps int around, no narrowing happens.
	assertType('array<string, int|string>', $map);
}

/**
 * @param array<string, int|string> $map
 */
function narrowKeyless(array $map): void
{
	foreach ($map as $value) {
		if (!is_string($value)) {
			return;
		}
	}

	assertType('array<string, string>', $map);
}

/**
 * @param list<int|string> $list
 */
function reassignValueVarKeyless(array $list): void
{
	foreach ($list as $value) {
		// Reassigning the value variable must not narrow the array element type.
		$value = 'foo';
	}

	assertType('list<int|string>', $list);
}

interface Foo
{
}

/**
 * @param list<Foo|string> $list
 */
function instanceofKeyless(array $list): void
{
	foreach ($list as $value) {
		if (!$value instanceof Foo) {
			return;
		}
	}

	assertType('list<Bug5755\Foo>', $list);
}

/**
 * @param list<int|string> $list
 */
function throwKeyless(array $list): void
{
	foreach ($list as $value) {
		if (!is_string($value)) {
			throw new \Exception();
		}
	}

	assertType('list<string>', $list);
}

/**
 * @param list<int|string> $list
 */
function byRefKeyless(array $list): void
{
	foreach ($list as &$value) {
		if (!is_string($value)) {
			return;
		}
	}

	assertType('list<string>', $list);
}

/**
 * @param array<int|string, array{int}> $a
 */
function keyedWithDestructuredValue(array $a): void
{
	// The value is destructured, so there is no value variable to track, but a key
	// variable is still present. Key narrowing must keep working through the key path.
	foreach ($a as $k => [$v]) {
		if (!is_int($k)) {
			return;
		}
	}

	assertType('array<int, array{int}>', $a);
}
