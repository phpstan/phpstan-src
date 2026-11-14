<?php // lint >= 8.4

namespace ArrayAllAnyUndecided;

class Foo
{

	/**
	 * An already non-empty array does not make array_any() always true: the
	 * predicate may still reject every element.
	 *
	 * @param non-empty-array<mixed> $array
	 */
	public function anyOnNonEmptyArray(array $array): void
	{
		if (array_any($array, fn ($value) => is_int($value))) {
			echo 'maybe';
		}
	}

	/**
	 * @param array{0: string, 1?: string} $array
	 */
	public function anyOnShape(array $array): void
	{
		if (array_any($array, fn (string $value): bool => $value !== '')) {
			echo 'maybe';
		}
	}

	/**
	 * A predicate that does not narrow the elements leaves the array type
	 * unchanged, which does not make array_all() always true.
	 *
	 * @param array<mixed> $array
	 */
	public function allWithoutNarrowing(array $array): void
	{
		if (array_all($array, fn ($value) => self::check($value))) {
			echo 'maybe';
		}
	}

	/**
	 * @param non-empty-list<string> $array
	 */
	public function allOnNonEmptyList(array $array): void
	{
		if (array_all($array, fn (string $value): bool => self::check($value))) {
			echo 'maybe';
		}
	}

	/**
	 * A genuinely impossible call is still reported.
	 *
	 * @param array{} $array
	 */
	public function anyOnEmptyArray(array $array): void
	{
		if (array_any($array, fn ($value) => self::check($value))) {
			echo 'never';
		}
	}

	private static function check(mixed $value): bool
	{
		return $value !== null && rand() > 0;
	}

}
