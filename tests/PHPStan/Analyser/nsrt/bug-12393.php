<?php declare(strict_types = 1);

namespace Bug12393;

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
		assertType('1.0', $this->float);
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

// https://3v4l.org/LK6Rh
class CallableString {
	private string $foo;

	public function doFoo(callable $foo): void {
		$this->foo = $foo; // PHPStorm wrongly reports an error on this line
		assertType('callable-string|non-empty-string', $this->foo);
	}
}

// https://3v4l.org/WJ8NW
class CallableArray {
	private array $foo;

	public function doFoo(callable $foo): void {
		$this->foo = $foo;
		assertType('array', $this->foo); // could be non-empty-array
	}
}

class StringableFoo {
	private string $foo;

	// https://3v4l.org/DQSgA#v8.4.6
	public function doFoo(StringableFoo $foo): void {
		$this->foo = $foo;
		assertType('*NEVER*', $this->foo);
	}

	public function doFoo2(NotStringable $foo): void {
		$this->foo = $foo;
		assertType('*NEVER*', $this->foo);
	}

	public function __toString(): string {
		return 'Foo';
	}
}
