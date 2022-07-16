<?php

namespace NonEmptyStringImpossibleType;

class Foo {
	private function isPrefixedInterface(string $shortClassName): bool
	{
		if (strlen($shortClassName) <= 3) {
			return false;
		}

		if (! \str_starts_with($shortClassName, 'I')) {
			return false;
		}

		if (! ctype_upper($shortClassName[1])) {
			return false;
		}

		return ctype_lower($shortClassName[2]);
	}

	public function strContains(string $a, string $b, string $c, string $d, string $e): void
	{
		if (str_contains($a, 'foo')) {
			if (str_contains($a, 'foo')) {
			}
		}

		if (!str_contains($b, 'foo')) {
			if (!str_contains($b, 'foo')) {
			}
		}

		if (str_contains($c, 'foo')) {
			if (!str_contains($c, 'foo')) {
			}
		}

		if (!str_contains($d, 'foo')) {
			if (str_contains($d, 'foo')) {
			}
		}

		if (str_contains($e, 'bar')) {
		}
	}
}
