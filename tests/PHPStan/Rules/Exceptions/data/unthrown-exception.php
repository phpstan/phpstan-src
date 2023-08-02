<?php

namespace UnthrownException;

class Foo
{

	public function doFoo(): void
	{
		try {
			$foo = 1;
		} catch (\Throwable $e) {
			// pass
		}
	}

	public function doBar(): void
	{
		try {
			$foo = 1;
		} catch (\Exception $e) {
			// pass
		}
	}

	/** @throws \InvalidArgumentException */
	public function throwIae(): void
	{

	}

	public function doBaz(): void
	{
		try {
			$this->throwIae();
		} catch (\InvalidArgumentException $e) {

		} catch (\Exception $e) {
			// dead
		} catch (\Throwable $e) {
			// not dead
		}
	}

	public function doLorem(): void
	{
		try {
			$this->throwIae();
		} catch (\RuntimeException $e) {
 			// dead
		} catch (\Throwable $e) {

		}
	}

	public function doIpsum(): void
	{
		try {
			$this->throwIae();
		} catch (\Throwable $e) {

		}
	}

	public function doDolor(): void
	{
		try {
			throw new \InvalidArgumentException();
		} catch (\InvalidArgumentException $e) {

		} catch (\Throwable $e) {

		}
	}

	public function doSit(): void
	{
		try {
			try {
				\ThrowPoints\Helpers\maybeThrows();
			} catch (\InvalidArgumentException $e) {

			}
		} catch (\InvalidArgumentException $e) {

		}
	}

	/**
	 * @throws \InvalidArgumentException
	 * @throws \DomainException
	 */
	public function doAmet()
	{

	}

	public function doAmet1()
	{
		try {
			$this->doAmet();
		} catch (\InvalidArgumentException $e) {

		} catch (\DomainException $e) {

		} catch (\Throwable $e) {
			// not dead
		}
	}

	public function doAmet2()
	{
		try {
			throw new \InvalidArgumentException();
		} catch (\InvalidArgumentException $e) {

		} catch (\DomainException $e) {
			// dead
		} catch (\Throwable $e) {
			// dead
		}
	}

	public function doConsecteur()
	{
		try {
			if (false) {

			} elseif ($this->doAmet()) {

			}
		} catch (\InvalidArgumentException $e) {

		}
	}

}

class InlineThrows
{

	public function doFoo()
	{
		try {
			/** @throws \InvalidArgumentException */
			echo 1;
		} catch (\InvalidArgumentException $e) {

		}
	}

	public function doBar()
	{
		try {
			/** @throws \InvalidArgumentException */
			$i = 1;
		} catch (\InvalidArgumentException $e) {

		}
	}

}

class TestDateTime
{

	public function doFoo(): void
	{
		try {
			new \DateTime();
		} catch (\Exception $e) {

		}
	}

	public function doBar(): void
	{
		try {
			new \DateTime('now');
		} catch (\Exception $e) {

		}
	}

	public function doBaz(string $s): void
	{
		try {
			new \DateTime($s);
		} catch (\Exception $e) {

		}
	}

	/**
	 * @phpstan-param 'now'|class-string $s
	 */
	public function doSuperBaz(string $s): void
	{
		try {
			new \DateTime($s);
		} catch (\Exception $e) {

		}
	}

}

class TestDateInterval
{

	public function doFoo(): void
	{
		try {
			new \DateInterval('invalid format');
		} catch (\Exception $e) {

		}
	}

	public function doBar(): void
	{
		try {
			new \DateInterval('P10D');
		} catch (\Exception $e) {

		}
	}

	public function doBaz(string $s): void
	{
		try {
			new \DateInterval($s);
		} catch (\Exception $e) {

		}
	}

	/**
	 * @phpstan-param 'P10D'|class-string $s
	 */
	public function doSuperBaz(string $s): void
	{
		try {
			new \DateInterval($s);
		} catch (\Exception $e) {

		}
	}

}

class TestIntdiv
{

	public function doFoo(): void
	{
		try {
			intdiv(1, 1);
			intdiv(1, -1);
		} catch (\ArithmeticError $e) {

		}
		try {
			intdiv(PHP_INT_MIN, -1);
		} catch (\ArithmeticError $e) {

		}
		try {
			intdiv(1, 0);
		} catch (\ArithmeticError $e) {

		}
	}

	public function doBar(int $int): void
	{
		try {
			intdiv($int, 1);
		} catch (\ArithmeticError $e) {

		}
		try {
			intdiv($int, -1);
		} catch (\ArithmeticError $e) {

		}
	}

	public function doBaz(int $int): void
	{
		try {
			intdiv(1, $int);
		} catch (\ArithmeticError $e) {

		}
		try {
			intdiv(PHP_INT_MIN, $int);
		} catch (\ArithmeticError $e) {

		}
	}

}

class TestSimpleXMLElement
{

	public function doFoo(): void
	{
		try {
			new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><root></root>');
		} catch (\Exception $e) {

		}
	}

	public function doBar(): void
	{
		try {
			new \SimpleXMLElement('foo');
		} catch (\Exception $e) {

		}
	}

	public function doBaz(string $string): void
	{
		try {
			new \SimpleXMLElement($string);
		} catch (\Exception $e) {

		}
	}

}

class TestReflectionClass
{

	public function doFoo(): void
	{
		try {
			new \ReflectionClass(\DateTime::class);
		} catch (\Exception $e) {

		}
	}

	public function doBar(): void
	{
		try {
			new \ReflectionClass('ThisIsNotARealClass');
		} catch (\Exception $e) {

		}
	}

	public function doBaz(string $string): void
	{
		try {
			new \ReflectionClass($string);
		} catch (\Exception $e) {

		}
	}

	/**
	 * @param \DateTime|\DateTimeImmutable|class-string<\DateTime> $rightClassOrObject
	 * @param \DateTime|\DateTimeImmutable|string $wrongClassOrObject
	 */
	public function doThing(object $foo, $rightClassOrObject, $wrongClassOrObject): void
	{
		try {
			new \ReflectionClass($foo);
		} catch (\Exception $e) {

		}
		try {
			new \ReflectionClass($rightClassOrObject);
		} catch (\Exception $e) {

		}
		try {
			new \ReflectionClass($wrongClassOrObject);
		} catch (\Exception $e) {

		}
	}
}

class TestReflectionFunction
{

	public function doFoo(): void
	{
		try {
			new \ReflectionFunction('is_string');
		} catch (\Exception $e) {

		}
	}

	public function doBar(): void
	{
		try {
			new \ReflectionFunction('foo');
		} catch (\Exception $e) {

		}
	}

	public function doBaz(string $string): void
	{
		try {
			new \ReflectionFunction($string);
		} catch (\Exception $e) {

		}
	}

}

class TestReflectionMethod
{
	/**
	 * @param class-string<\DateTimeInterface> $foo
	 */
	public function doFoo($foo): void
	{
		try {
			new \ReflectionMethod(\DateTime::class, 'format');
		} catch (\Exception $e) {

		}
		try {
			new \ReflectionMethod($foo, 'format');
		} catch (\Exception $e) {

		}
	}

	public function doBar(): void
	{
		try {
			new \ReflectionMethod('foo', 'format');
		} catch (\Exception $e) {

		}
		try {
			new \ReflectionMethod(\DateTime::class, 'foo');
		} catch (\Exception $e) {

		}
	}

	public function doBaz(string $string): void
	{
		try {
			new \ReflectionMethod($string, $string);
		} catch (\Exception $e) {

		}
		try {
			new \ReflectionMethod(\DateTime::class, $string);
		} catch (\Exception $e) {

		}
		try {
			new \ReflectionMethod($string, 'foo');
		} catch (\Exception $e) {

		}
	}

}

class TestReflectionProperty
{
	public $foo;

	public function doFoo(): void
	{
		try {
			new \ReflectionProperty(self::class, 'foo');
		} catch (\Exception $e) {

		}
	}

	public function doBar(): void
	{
		try {
			new \ReflectionProperty(self::class, 'bar');
		} catch (\Exception $e) {

		}
		try {
			new \ReflectionProperty(\DateTime::class, 'bar');
		} catch (\Exception $e) {

		}
	}

	public function doBaz(string $string): void
	{
		try {
			new \ReflectionProperty($string, $string);
		} catch (\Exception $e) {

		}
		try {
			new \ReflectionProperty(self::class, $string);
		} catch (\Exception $e) {

		}
		try {
			new \ReflectionProperty($string, 'foo');
		} catch (\Exception $e) {

		}
	}

}

class ExceptionGetMessage
{

	public function doFoo(\Exception $e)
	{
		try {
			echo $e->getMessage();
		} catch (\Exception $t) {

		}
	}

	public function doBar(string $s)
	{
		try {
			$this->{'doFoo' . $s}();
		} catch (\InvalidArgumentException $e) {

		}
	}

}

class TestCaseInsensitiveClassNames
{

	public function doFoo(): void
	{
		try {
			new \SimpleXmlElement('<?xml version="1.0" encoding="UTF-8"?><root></root>');
		} catch (\Exception $e) {

		}
	}
}
