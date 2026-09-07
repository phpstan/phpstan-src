<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug13735b;

use function PHPStan\Testing\assertType;

class Foo
{
	public ?Bar $bar = null;
}

class Bar
{
}

class HelloWorld extends ParentClass
{
	public ?Foo $foo = null;

	/** @var array<string, string> */
	private array $arr = [];

	private static ?Foo $staticFoo = null;

	public function doNestedPropertyFetch(): void
	{
		$this->foo = new Foo();
		$this->foo->bar = new Bar();
		assertType('Bug13735b\Bar', $this->foo->bar);
		self::sideEffect();
		assertType('Bug13735b\Bar', $this->foo->bar);
	}

	public function doNullsafePropertyFetch(): void
	{
		if ($this->foo?->bar !== null) {
			assertType('Bug13735b\Bar', $this->foo?->bar);
			self::sideEffect();
			assertType('Bug13735b\Bar', $this->foo?->bar);
		}
	}

	public function doDynamicPropertyName(string $name): void
	{
		if ($this->{$name} instanceof Foo) {
			assertType('Bug13735b\Foo', $this->{$name});
			self::sideEffect();
			assertType('Bug13735b\Foo', $this->{$name});
		}
	}

	public function doArrayProperty(): void
	{
		$this->arr['x'] = 'y';
		assertType("non-empty-array<string, string>&hasOffsetValue('x', 'y')", $this->arr);
		assertType("'y'", $this->arr['x']);
		self::sideEffect();
		assertType("non-empty-array<string, string>&hasOffsetValue('x', 'y')", $this->arr);
		assertType("'y'", $this->arr['x']);
	}

	public function doStaticMethodCalledOnInstance(HelloWorld $other): void
	{
		$other->foo = new Foo();
		assertType('Bug13735b\Foo', $other->foo);
		$other->sideEffect();
		assertType('Bug13735b\Foo', $other->foo);
	}

	public function doStaticClosure(): void
	{
		$this->foo = new Foo();
		assertType('Bug13735b\Foo', $this->foo);
		$staticClosure = static function (): void {
			file_put_contents('log file', 'foo');
		};
		$staticClosure();
		assertType('Bug13735b\Foo', $this->foo);
	}

	public function doNonStaticClosure(): void
	{
		$this->foo = new Foo();
		assertType('Bug13735b\Foo', $this->foo);
		$closure = function (): void {
			file_put_contents('log file', 'foo');
		};
		$closure();
		assertType('Bug13735b\Foo|null', $this->foo);
	}

	public function doMethodCallOnThis(): void
	{
		if ($this->getFoo() !== null) {
			assertType('Bug13735b\Foo', $this->getFoo());
			self::sideEffect();
			assertType('Bug13735b\Foo|null', $this->getFoo());
		}
	}

	public function doStaticProperty(): void
	{
		self::$staticFoo = new Foo();
		assertType('Bug13735b\Foo', self::$staticFoo);
		self::sideEffect();
		assertType('Bug13735b\Foo|null', self::$staticFoo);
	}

	public function doNonStaticMethod(): void
	{
		$this->foo = new Foo();
		assertType('Bug13735b\Foo', $this->foo);
		self::nonStatic();
		assertType('Bug13735b\Foo|null', $this->foo);
	}

	public function doLateStaticBinding(): void
	{
		$this->foo = new Foo();
		assertType('Bug13735b\Foo', $this->foo);
		static::sideEffect();
		assertType('Bug13735b\Foo', $this->foo);
	}

	public function doLateStaticBindingNonStaticMethod(): void
	{
		$this->foo = new Foo();
		assertType('Bug13735b\Foo', $this->foo);
		static::nonStatic();
		assertType('Bug13735b\Foo|null', $this->foo);
	}

	public function doParentMethod(): void
	{
		$this->publicFoo = new Foo();
		assertType('Bug13735b\Foo', $this->publicFoo);
		parent::nonStaticParent();
		assertType('Bug13735b\Foo|null', $this->publicFoo);
	}

	public function getFoo(): ?Foo
	{
		return $this->foo;
	}

	public static function sideEffect(): void
	{
		file_put_contents('log file', 'foo');
	}

	public function nonStatic(): void
	{
		file_put_contents('log file', 'foo');
	}
}

class ParentClass
{
	public ?Foo $publicFoo = null;

	public function nonStaticParent(): void
	{
		file_put_contents('log file', 'foo');
	}
}
