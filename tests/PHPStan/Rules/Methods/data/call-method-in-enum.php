<?php // lint >= 8.1

namespace CallMethodInEnum;

enum Foo
{

	public function doFoo()
	{
		$this->doFoo();
		$this->doNonexistent();
	}

}

trait FooTrait
{

	public function doFoo()
	{
		$this->doFoo();
		$this->doNonexistent();
	}

}

enum Bar
{

	use FooTrait;

}

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

enum FooCall {
	/**
	 * @param value-of<Country> $countryName
	 */
	function hello(string $countryName): void
	{
		// ...
	}

	/**
	 * @param array<value-of<Country>, bool> $countryMap
	 */
	function helloArray(array $countryMap): void {
		// ...
	}

	function doFooArray() {
		$this->hello(CountryNo::NL);

		// 'abc' does not match value-of<Country>
		$this->helloArray(['abc' => true]);
		$this->helloArray(['abc' => 123]);

		// wrong key type
		$this->helloArray([true]);
	}
}

enum TestPassingEnums {
	case ONE;
	case TWO;

	/**
	 * @param self::ONE $one
	 * @return void
	 */
	public function requireOne(self $one): void
	{

	}

	public function doFoo(): void
	{
		match ($this) {
			self::ONE => $this->requireOne($this),
			self::TWO => $this->requireOne($this),
		};
	}

	public function doFoo2(): void
	{
		match ($this) {
			self::ONE => $this->requireOne($this),
			default => $this->requireOne($this),
		};
	}

	public function doFoo3(): void
	{
		match ($this) {
			self::TWO => $this->requireOne($this),
			default => $this->requireOne($this),
		};
	}
}
