<?php declare(strict_types = 0);

namespace Bug12393b;

use function PHPStan\Testing\assertNativeType;
use function PHPStan\Testing\assertType;

class HelloWorld
{
	private string $name;

	/** @var string */
	private $untypedName;

	private float $float;

	/** @var float */
	private $untypedFloat;

	private array $a;

	/**
	 * @param mixed[] $plugin
	 */
	public function __construct(array $plugin){
		$this->name = $plugin["name"];
		assertType('string', $this->name);
	}

	/**
	 * @param mixed[] $plugin
	 */
	public function doFoo(array $plugin){
		$this->untypedName = $plugin["name"];
		assertType('mixed', $this->untypedName);
	}

	public function doBar(int $i){
		$this->float = $i;
		assertType('float', $this->float);
	}

	public function doBaz(int $i){
		$this->untypedFloat = $i;
		assertType('int', $this->untypedFloat);
	}

	public function doLorem(): void
	{
		$this->a = ['a' => 1];
		assertType('array{a: 1}', $this->a);
	}

	public function doFloatTricky(){
		$this->float = 1;
		assertType('float', $this->float);
	}
}

class HelloWorldStatic
{
	private static string $name;

	/** @var string */
	private static $untypedName;

	private static float $float;

	/** @var float */
	private static $untypedFloat;

	private static array $a;

	/**
	 * @param mixed[] $plugin
	 */
	public function __construct(array $plugin){
		self::$name = $plugin["name"];
		assertType('string', self::$name);
	}

	/**
	 * @param mixed[] $plugin
	 */
	public function doFoo(array $plugin){
		self::$untypedName = $plugin["name"];
		assertType('mixed', self::$untypedName);
	}

	public function doBar(int $i){
		self::$float = $i;
		assertType('float', self::$float);
	}

	public function doBaz(int $i){
		self::$untypedFloat = $i;
		assertType('int', self::$untypedFloat);
	}

	public function doLorem(): void
	{
		self::$a = ['a' => 1];
		assertType('array{a: 1}', self::$a);
	}
}

class EntryPointLookup
{

	/** @var array<string, mixed>|null */
	private ?array $entriesData = null;

	/**
	 * @return array<string, mixed>
	 */
	public function doFoo(): void
	{
		if ($this->entriesData !== null) {
			return;
		}

		assertType('null', $this->entriesData);
		assertNativeType('null', $this->entriesData);

		$data = $this->getMixed();
		if ($data !== null) {
			$this->entriesData = $data;
			assertType('array', $this->entriesData);
			assertNativeType('array', $this->entriesData);
			return;
		}

		assertType('null', $this->entriesData);
		assertNativeType('null', $this->entriesData);
	}

	/**
	 * @return mixed
	 */
	public function getMixed()
	{

	}

}

class Foo
{

	public int $foo;

	public function doFoo(string $s): void
	{
		$this->foo = $s;
		assertType('int', $this->foo);
	}

	public function doBar(): void
	{
		$this->foo = 'foo';
		assertType('int', $this->foo);
	}
}

class FooBool
{

	public int $foo;

	public function doFoo(bool $b): void
	{
		$this->foo = $b;
		assertType('int', $this->foo);
	}

	public function doBar(): void
	{
		$this->foo = true;
		assertType('int', $this->foo);
	}
}

class FooBoolString
{

	public string $foo;

	public function doFoo(bool $b): void
	{
		$this->foo = $b;
		assertType('string', $this->foo);
	}

	public function doBar(): void
	{
		$this->foo = true;
		assertType('string', $this->foo);
	}
}

class FooIntString
{

	public string $foo;

	public function doFoo(int $b): void
	{
		$this->foo = $b;
		assertType('string', $this->foo); // could be numeric-string
	}

	public function doBar(): void
	{
		$this->foo = 1;
		assertType('string', $this->foo); // could be numeric-string
	}
}
