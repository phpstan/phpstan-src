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

	private static ?HelloWorld $instance = null;

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

	public function doStaticClosureGettingThisAsArgument(): void
	{
		$this->foo = new Foo();
		assertType('Bug13735b\Foo', $this->foo);
		$staticClosure = static function (HelloWorld $other): void {
			$other->foo = null;
		};
		$staticClosure($this);
		assertType('Bug13735b\Foo|null', $this->foo);
	}

	public function doStaticArrowFunctionGettingThisAsArgument(): void
	{
		$this->foo = new Foo();
		assertType('Bug13735b\Foo', $this->foo);
		$staticArrowFunction = static fn (HelloWorld $other): ?Foo => $other->foo = null;
		$staticArrowFunction($this);
		assertType('Bug13735b\Foo|null', $this->foo);
	}

	public function doStaticClosureGettingPropertyAsArgument(): void
	{
		$this->foo = new Foo();
		$this->foo->bar = new Bar();
		$staticClosure = static function (Foo $foo): void {
			$foo->bar = null;
		};
		$staticClosure($this->foo);
		// the closure cannot change which Foo $this->foo points at, only what's inside it
		assertType('Bug13735b\Foo', $this->foo);
		assertType('Bug13735b\Bar|null', $this->foo->bar);
	}

	public function doStaticClosureGettingScalarAsArgument(): void
	{
		$this->foo = new Foo();
		assertType('Bug13735b\Foo', $this->foo);
		$staticClosure = static function (int $i): void {
			file_put_contents('log file', (string) $i);
		};
		$staticClosure(1);
		assertType('Bug13735b\Foo', $this->foo);
	}

	public function doStaticMethodGettingThisAsArgument(): void
	{
		$this->foo = new Foo();
		assertType('Bug13735b\Foo', $this->foo);
		self::mutate($this);
		assertType('Bug13735b\Foo|null', $this->foo);
	}

	public function doStaticMethodGettingPropertyAsArgument(): void
	{
		$this->foo = new Foo();
		$this->foo->bar = new Bar();
		self::mutateFoo($this->foo);
		assertType('Bug13735b\Foo', $this->foo);
		assertType('Bug13735b\Bar|null', $this->foo->bar);
	}

	public function doLateStaticBindingGettingThisAsArgument(): void
	{
		$this->foo = new Foo();
		assertType('Bug13735b\Foo', $this->foo);
		static::mutate($this);
		assertType('Bug13735b\Foo|null', $this->foo);
	}

	public function doStaticMethodCalledOnThisGettingThisAsArgument(): void
	{
		$this->foo = new Foo();
		assertType('Bug13735b\Foo', $this->foo);
		$this->mutate($this);
		assertType('Bug13735b\Foo|null', $this->foo);
	}

	public function doStaticMethodCalledOnInstanceGettingThisAsArgument(HelloWorld $other): void
	{
		$this->foo = new Foo();
		assertType('Bug13735b\Foo', $this->foo);
		$other->mutate($this);
		assertType('Bug13735b\Foo|null', $this->foo);
	}

	public function doMethodCallGettingThisAsArgument(HelloWorld $other): void
	{
		$this->foo = new Foo();
		assertType('Bug13735b\Foo', $this->foo);
		$other->nonStaticMutate($this);
		assertType('Bug13735b\Foo|null', $this->foo);
	}

	public function doMethodCallGettingPropertyAsArgument(HelloWorld $other): void
	{
		$this->foo = new Foo();
		$this->foo->bar = new Bar();
		$other->mutateFoo($this->foo);
		// the callee can change what's inside $this->foo, not which Foo it points at
		assertType('Bug13735b\Foo', $this->foo);
		assertType('Bug13735b\Bar|null', $this->foo->bar);
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

	/**
	 * A static method can also reach the object through static state. PHPStan does
	 * not track that for any receiver - 'HelloWorld::$instance = $other; HelloWorld::mutateStored();'
	 * has never invalidated '$other->foo' either - so '$this' is no longer an exception.
	 */
	public function doReachedViaStaticProperty(): void
	{
		self::$instance = $this;
		$this->foo = new Foo();
		assertType('Bug13735b\Foo', $this->foo);
		self::mutateStored();
		assertType('Bug13735b\Foo', $this->foo);
	}

	public function getFoo(): ?Foo
	{
		return $this->foo;
	}

	public static function mutate(HelloWorld $other): void
	{
		$other->foo = null;
	}

	public static function mutateFoo(Foo $foo): void
	{
		$foo->bar = null;
	}

	public static function mutateStored(): void
	{
		if (self::$instance !== null) {
			self::$instance->foo = null;
		}
	}

	public static function sideEffect(): void
	{
		file_put_contents('log file', 'foo');
	}

	public function nonStatic(): void
	{
		file_put_contents('log file', 'foo');
	}

	public function nonStaticMutate(HelloWorld $other): void
	{
		$other->foo = null;
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
