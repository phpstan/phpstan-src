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
}
