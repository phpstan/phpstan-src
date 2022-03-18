<?php // lint >= 8.1

declare(strict_types=1);

namespace ValueOfEnum;

enum Country: string
{
	case NL = 'The Netherlands';
	case US = 'United States';
}

class Foo {
	/**
	 * @param value-of<Country> $countryName
	 */
	function hello(string $countryName): void
	{
		// ...
	}

	function doFoo() {
		$this->hello(Country::NL);
	}
}

