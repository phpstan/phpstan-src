<?php declare(strict_types = 1);

namespace Bug7851;

use function PHPStan\Testing\assertType;

class HelloWorld
{

	public int $v = 0;

	public static int $sv = 0;

	public function test(): void
	{
		$this->v = 4;
		assertType('4', $this->v);
		$this->{'v'} = 5;
		assertType('5', $this->v);
		$n = 'v';
		$this->{$n} = 6;
		assertType('6', $this->v);
		$this->$n = 7;
		assertType('7', $this->v);
		assertType('7', $this->{'v'});
		assertType('7', $this->{$n});
	}

	public function testStatic(): void
	{
		self::$sv = 4;
		assertType('4', self::$sv);
		$s = 'sv';
		self::${$s} = 5;
		assertType('5', self::$sv);
		assertType('5', self::${'sv'});
		assertType('5', self::${$s});
	}

}

class WithNullable
{

	public ?string $n = null;

	public static ?string $s = null;

	public function narrowInstance(): void
	{
		$name = 'n';
		if ($this->{$name} !== null) {
			assertType('string', $this->n);
			assertType('string', $this->{'n'});
			assertType('string', $this->{$name});
		}
	}

	public function narrowStatic(): void
	{
		$name = 's';
		if (self::${$name} !== null) {
			assertType('string', self::$s);
			assertType('string', self::${'s'});
		}
	}

}

class WithMethods
{

	public function count(): int
	{
		return 0;
	}

	public function getName(): ?string
	{
		return null;
	}

	public function memberAccessViaConstantName(): void
	{
		$method = 'getName';
		// constant-string method calls resolve the same member as the bareword form
		assertType('int', $this->{'count'}());
		assertType('string|null', $this->{$method}());
	}

	public function narrowMethodCall(): void
	{
		$method = 'getName';
		// narrowing a constant-string method call carries over to the bareword form
		if ($this->{$method}() !== null) {
			assertType('string', $this->getName());
			assertType('string', $this->{'getName'}());
		}
	}

}
