<?php // lint >= 8.1

namespace ValueOfEnumPhpdoc;

enum Country: string
{
	case NL = 'The Netherlands';
	case US = 'United States';
}

enum CountryNo: int
{
	case NL = 1;
	case US = 2;
}

class Foo {
	/**
	 * @param value-of<Country> $countryName
	 */
	function hello(string $countryName): void
	{
		// ...
	}

	/**
	 * @param value-of<Country> $shouldError
	 */
	function helloError(int $shouldError): void
	{
		// ...
	}
	/**
	 * @param value-of<CountryNo> $shouldError
	 */
	function helloError2(string $shouldError): void
	{
		// ...
	}

	function doFoo() {
		$this->hello(Country::NL);
	}
}

