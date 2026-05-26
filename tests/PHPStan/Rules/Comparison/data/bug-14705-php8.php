<?php // lint >= 8.0

namespace Bug14705Php8;

class Foo
{

	/**
	 * str_contains with non-empty-string haystack should not report always-true.
	 *
	 * @param non-empty-string $haystack
	 */
	public function strContainsNonEmpty(string $haystack, string $needle): void
	{
		if (str_contains($haystack, $needle)) {

		}
	}

	/**
	 * str_starts_with with non-empty-string haystack should not report always-true.
	 *
	 * @param non-empty-string $haystack
	 */
	public function strStartsWithNonEmpty(string $haystack, string $needle): void
	{
		if (str_starts_with($haystack, $needle)) {

		}
	}

	/**
	 * str_ends_with with non-empty-string haystack should not report always-true.
	 *
	 * @param non-empty-string $haystack
	 */
	public function strEndsWithNonEmpty(string $haystack, string $needle): void
	{
		if (str_ends_with($haystack, $needle)) {

		}
	}

	/**
	 * @param non-empty-string $needle
	 */
	public function strEndsWithDuplicate(string $haystack, string $needle): void
	{
		if (str_ends_with($haystack, $needle)) {
			if (str_ends_with($haystack, $needle)) { // reported as always-true

			}
		}
	}

	/**
	 * @param non-empty-string $needle
	 */
	public function strContainsDuplicate(string $haystack, string $needle): void
	{
		if (str_contains($haystack, $needle)) {
			if (str_contains($haystack, $needle)) { // reported as always-true

			}
		}
	}

}
