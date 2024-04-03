<?php

namespace ReadonlyPropertyAssignPhpDoc;

class Foo
{

	/**
	 * @var int
	 * @readonly
	 */
	private $foo;

	/**
	 * @var int
	 * @readonly
	 */
	protected $bar;

	/**
	 * @var int
	 * @readonly
	 */
	public $baz;

	/**
	 * @var int
	 * @psalm-readonly-allow-private-mutation
	 */
	public $psalm;

	/**
	 * @var int
	 * @phan-read-only
	 */
	public $phan;

	public function __construct(int $foo)
	{
		$this->foo = $foo; // constructor - fine
		$this->psalm = $foo; // constructor - fine
		$this->phan = $foo; // constructor - fine
	}

	public function setFoo(int $foo): void
	{
		$this->foo = $foo; // setter - report
		$this->psalm = $foo; // do not report -allowed private mutation
		$this->phan = $foo; // setter - report
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
		$this->psalm = $bar; // report - not in declaring class
		$this->phan = $bar; // report - not in declaring class
	}

	public function setBar(int $bar): void
	{
		$this->bar = $bar; // report - not in declaring class
		$this->psalm = $bar; // report - not in declaring class
		$this->phan = $bar; // report - not in declaring class
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
	 * @readonly
	 */
	public $details;

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

	/** @var int */
	private $foo;

	public function setFoo(int $foo): void
	{
		$this->foo = $foo; // do not report - not readonly
	}

}

class NotThis
{

	/**
	 * @var int
	 * @readonly
	 */
	private $foo;

	public function __construct(int $foo)
	{
		$self = new self(1);
		$self->foo = $foo; // report - not $this
	}

}

class PostInc
{

	/**
	 * @var int
	 * @readonly
	 */
	private $foo;

	public function doFoo(): void
	{
		$this->foo++;
		--$this->foo;

		$this->foo += 5;
	}

}

class ListAssign
{

	/**
	 * @var int
	 * @readonly
	 */
	private $foo;

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

	/**
	 * @var int
	 * @readonly
	 */
	private $foo;

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

	/**
	 * @var int
	 * @readonly
	 */
	private $foo;

	protected function setUp(): void
	{
		$this->foo = 1; // additional constructor - fine
	}

}

/** @phpstan-immutable */
class Immutable
{

	/** @var int */
	private $foo;

	protected $bar;

	public $baz;

	public function __construct(int $foo)
	{
		$this->foo = $foo; // constructor - fine
	}

	public function setFoo(int $foo): void
	{
		$this->foo = $foo; // setter - report
	}

}

/** @immutable */
class A
{

	/** @var string */
	public $a;

	public function __construct() {
		$this->a = ''; // constructor - fine
	}

}

class B extends A
{

	/** @var string */
	public $b;

	public function __construct()
	{
		parent::__construct();
		$this->b = ''; // constructor - fine
	}

	public function mod()
	{
		$this->b = 'input'; // setter - report
		$this->a = 'input2'; // setter - report
	}

}

class C extends B
{

	/** @var string */
	public $c;

	public function mod()
	{
		$this->c = 'input'; // setter - report
	}

}
