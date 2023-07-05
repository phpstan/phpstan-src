<?php // lint >= 7.4

namespace UninitializedProperty;

class Foo
{

	private int $foo;

	private int $bar;

	private int $baz;

	public function __construct()
	{
		$this->foo = 1;
	}

	public function setBaz()
	{
		$this->baz = 1;
	}

}

class Bar
{

	private int $foo;

	public function __construct()
	{
		$this->foo += 1;
		$this->foo = 2;
	}

}

class Baz
{

	private int $foo;

	public function __construct()
	{
		$this->foo = 2;
		$this->foo += 1;
	}

}

class Lorem
{

	private int $foo;

	private int $bar;

	private int $baz;

	private int $lorem;

	private int $ipsum;

	private function assign()
	{
		$this->bar = 2;
		$this->assignAgain();
		self::assignAgainAgain();
	}

	public function __construct()
	{
		$this->foo = 1;
		$this->assign();
	}

	private function assignAgain()
	{
		$this->lorem = 4;
	}

	private function assignAgainAgain()
	{
		$this->ipsum = 5;
	}

	public function notCalled()
	{
		$this->baz = 3;
	}

}

class TestCase
{

	protected function setUp()
	{

	}

}

class MyTestCase extends TestCase
{

	private int $foo;

	protected function setUp()
	{
		$this->foo = 1;
	}

}

class TestExtension
{

	private int $inited;

	private int $uninited;

}

class ImplicitArrayCreation
{

	/** @var mixed[] */
	private array $properties;

	private function __construct(string $message)
	{
		$this->properties['message'] = $message;
	}

}

class ImplicitArrayCreation2
{

	/** @var mixed[] */
	private array $properties;

	private function __construct(string $message)
	{
		$this->properties['foo']['message'] = $message;
	}

}

trait FooTrait
{

	private int $foo;

	private int $bar;

	private int $baz;

	public function setBaz()
	{
		$this->baz = 1;
	}

}

class FooTraitClass
{

	use FooTrait;

	public function __construct()
	{
		$this->foo = 1;
	}

}

class ItemsCrate
{

	/**
	 * @var int[]
	 */
	private array $items;

	/**
	 * @param int[] $items
	 */
	public function __construct(
		array $items
	)
	{
		$this->items = $items;
		$this->sortItems();
	}

	private function sortItems(): void
	{
		usort($this->items, static function ($a, $b): int {
			return $a <=> $b;
		});
	}

	public function addItem(int $i): void
	{
		$this->items[] = $i;
		$this->sortItems();
	}

}

class InitializedInPrivateSetter
{

	private int $foo;

	public function __construct()
	{
		$this->setFoo();
		$this->doSomething();
	}

	private function setFoo()
	{
		$this->foo = 1;
	}

	public function doSomething()
	{
		echo $this->foo;
	}

}

final class InitializedInPublicSetterFinalClass
{

	private int $foo;

	public function __construct()
	{
		$this->setFoo();
		$this->doSomething();
	}

	public function setFoo()
	{
		$this->foo = 1;
	}

	public function doSomething()
	{
		echo $this->foo;
	}

}

class InitializedInPublicSetterNonFinalClass
{

	private int $foo;

	public function __construct()
	{
		$this->setFoo();
		$this->doSomething();
	}

	public function setFoo()
	{
		$this->foo = 1;
	}

	public function doSomething()
	{
		echo $this->foo;
	}

}

class SometimesInitializedInPrivateSetter
{

	private int $foo;

	public function __construct()
	{
		$this->setFoo();
		$this->doSomething();
	}

	private function setFoo()
	{
		if (rand(0, 1)) {
			$this->foo = 1;
		}
	}

	public function doSomething()
	{
		echo $this->foo;
	}

}

class ConfuseNodeScopeResolverWithAnonymousClass
{

	private int $foo;

	public function __construct()
	{
		$this->setFoo();
		$this->doSomething();
	}

	private function setFoo()
	{
		$c = new class () {
			public function setFoo()
			{
			}
		};
		$this->foo = 1;
	}

	public function doSomething()
	{
		echo $this->foo;
	}

}

class ThrowInConstructor1
{

	private int $foo;

	public function __construct()
	{
		if (rand(0, 1)) {
			$this->foo = 1;
			return;
		}

		throw new \Exception;
	}

}

class ThrowInConstructor2
{

	private int $foo;

	public function __construct()
	{
		if (rand(0, 1)) {
			throw new \Exception;
		}

		$this->foo = 1;
	}

}

class EarlyReturn
{

	private int $foo;

	public function __construct()
	{
		if (rand(0, 1)) {
			return;
		}

		$this->foo = 1;
	}

}

class NeverInConstructor
{

	private int $foo;

	public function __construct()
	{
		if (rand(0, 1)) {
			$this->foo = 1;
			return;
		}

		$this->returnNever();
	}

	/**
	 * @return never
	 */
	private function returnNever()
	{
		throw new \Exception();
	}

}
