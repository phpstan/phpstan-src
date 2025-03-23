<?php // lint >= 8.1

namespace ReadonlyPropertyAssign;

class Foo
{

	private readonly int $foo;

	protected readonly int $bar;

	public readonly int $baz;

	public function __construct(int $foo)
	{
		$this->foo = $foo; // constructor - fine
	}

	public function setFoo(int $foo): void
	{
		$this->foo = $foo; // setter - report
	}

}

class Bar extends Foo
{

	public function __construct(int $bar)
	{
		parent::__construct(1);
		$this->foo = $foo; // do not report - private property
		$this->bar = $bar; // report - not in declaring class
		$this->baz = $baz; // report - not in declaring class
	}

	public function setBar(int $bar): void
	{
		$this->bar = $bar; // report - not in declaring class
	}

}

function (Foo $foo): void {
	$foo->foo = 1; // do not report - private property
	$foo->baz = 2; // report - not in declaring class
};

class FooArrays
{

	/**
	 * @var array{name:string,age:int}
	 */
	public readonly array $details;

	public function __construct()
	{
		$this->details = ['name' => 'Foo', 'age' => 25];
	}

	public function doSomething(): void
	{
		$this->details['name'] = 'Bob';
		$this->details['age'] = 42;
	}

}

class NotReadonly
{

	private int $foo;

	public function setFoo(int $foo): void
	{
		$this->foo = $foo; // do not report - not readonly
	}

}

class NotThis
{

	private readonly int $foo;

	public function __construct(int $foo)
	{
		$self = new self(1);
		$self->foo = $foo; // report - not $this
	}

}

class PostInc
{

	private readonly int $foo;

	public function doFoo(): void
	{
		$this->foo++;
		--$this->foo;

		$this->foo += 5;
	}

}

class ListAssign
{

	private readonly int $foo;

	public function __construct()
	{
		[$this->foo] = [1];
	}

	public function setFoo()
	{
		[$this->foo] = [1];
	}

	public function setBar()
	{
		list($this->foo) = [1];
	}

}

enum FooEnum: string
{

	case ONE = 'one';
	case TWO = 'two';

	public function doFoo(): void
	{
		$this->name = 'ONE';
		$this->value = 'one';
	}

}

class TestFooEnum
{

	public function doFoo(FooEnum $foo): void
	{
		$foo->name = 'ONE';
		$foo->value = 'one';
	}

}

class AssignRefOutsideClass
{

	public function doFoo(Foo $foo, int $i)
	{
		$foo->baz = 5;
		$foo->baz = &$i;
	}

}

class Unserialization
{

	private readonly int $foo;

	public function __construct(int $foo)
	{
		$this->foo = $foo; // constructor - fine
	}

	/**
	 * @param array<int, int> $data
	 */
	public function __unserialize(array $data) : void
	{
		[$this->foo] = $data; // __unserialize - fine
	}

}

class TestCase
{

	private readonly int $foo;

	protected function setUp(): void
	{
		$this->foo = 1; // additional constructor - fine
	}

}

class ArrayAccessPropertyFetch
{

	private readonly \ArrayObject $storage;

	public function __construct() {
		$this->storage = new \ArrayObject();
	}

	public function set(\stdClass $class, int $value): void {
		$this->storage[$class] = $value;
		unset($this->storage[$class]);
		$this->storage = new \WeakMap(); // invalid
	}

}
