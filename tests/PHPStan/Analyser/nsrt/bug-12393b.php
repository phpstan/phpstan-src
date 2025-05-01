<?php // lint >= 8.0

declare(strict_types = 0);

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

class FooStringInt
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
		assertType('*NEVER*', $this->foo);
		$this->foo = '123';
		assertType('123', $this->foo);
	}

	/**
	 * @param non-empty-string $nonEmpty
	 * @param non-falsy-string $nonFalsy
	 * @param numeric-string $numeric
	 * @param literal-string $literal
	 * @param lowercase-string $lower
	 * @param uppercase-string $upper
	 */
	function doStrings($nonEmpty, $nonFalsy, $numeric, $literal, $lower, $upper) {
		$this->foo = $nonEmpty;
		assertType('int', $this->foo);
		$this->foo = $nonFalsy;
		assertType('int<min, -1>|int<1, max>', $this->foo);
		$this->foo = $numeric;
		assertType('int', $this->foo);
		$this->foo = $literal;
		assertType('int', $this->foo);
		$this->foo = $lower;
		assertType('int', $this->foo);
		$this->foo = $upper;
		assertType('int', $this->foo);
	}
}

class FooStringFloat
{

	public float $foo;

	public function doFoo(string $s): void
	{
		$this->foo = $s;
		assertType('float', $this->foo);
	}

	public function doBar(): void
	{
		$this->foo = 'foo';
		assertType('*NEVER*', $this->foo);
		$this->foo = '123';
		assertType('123.0', $this->foo);
	}

	/**
	 * @param non-empty-string $nonEmpty
	 * @param non-falsy-string $nonFalsy
	 * @param numeric-string $numeric
	 * @param literal-string $literal
	 * @param lowercase-string $lower
	 * @param uppercase-string $upper
	 */
	function doStrings($nonEmpty, $nonFalsy, $numeric, $literal, $lower, $upper) {
		$this->foo = $nonEmpty;
		assertType('float', $this->foo);
		$this->foo = $nonFalsy;
		assertType('float', $this->foo);
		$this->foo = $numeric;
		assertType('float', $this->foo);
		$this->foo = $literal;
		assertType('float', $this->foo);
		$this->foo = $lower;
		assertType('float', $this->foo);
		$this->foo = $upper;
		assertType('float', $this->foo);
	}
}

class FooStringBool
{

	public bool $foo;

	public function doFoo(string $s): void
	{
		$this->foo = $s;
		assertType('bool', $this->foo);
	}

	public function doBar(): void
	{
		$this->foo = '0';
		assertType('false', $this->foo);
		$this->foo = 'foo';
		assertType('true', $this->foo);
		$this->foo = '123';
		assertType('true', $this->foo);
	}

	/**
	 * @param non-empty-string $nonEmpty
	 * @param non-falsy-string $nonFalsy
	 * @param numeric-string $numeric
	 * @param literal-string $literal
	 * @param lowercase-string $lower
	 * @param uppercase-string $upper
	 */
	function doStrings($nonEmpty, $nonFalsy, $numeric, $literal, $lower, $upper) {
		$this->foo = $nonEmpty;
		assertType('bool', $this->foo);
		$this->foo = $nonFalsy;
		assertType('true', $this->foo);
		$this->foo = $numeric;
		assertType('bool', $this->foo);
		$this->foo = $literal;
		assertType('bool', $this->foo);
		$this->foo = $lower;
		assertType('bool', $this->foo);
		$this->foo = $upper;
		assertType('bool', $this->foo);
	}
}

class FooBoolInt
{

	public int $foo;

	public function doFoo(bool $b): void
	{
		$this->foo = $b;
		assertType('0|1', $this->foo);
	}

	public function doBar(): void
	{
		$this->foo = true;
		assertType('1', $this->foo);
		$this->foo = false;
		assertType('0', $this->foo);
	}
}

class FooVoidInt {
    private ?int $foo;
    private int $fooNonNull;

    public function doFoo(): void {
        $this->foo = $this->returnVoid();
        assertType('null', $this->foo);

        $this->fooNonNull = $this->returnVoid();
        assertType('int|null', $this->foo); // should be *NEVER*
    }

    public function returnVoid(): void {
        return;
    }
}


class FooBoolString
{

	public string $foo;

	public function doFoo(bool $b): void
	{
		$this->foo = $b;
		assertType("''|'1'", $this->foo);
	}

	public function doBar(): void
	{
		$this->foo = true;
		assertType("'1'", $this->foo);
		$this->foo = false;
		assertType("''", $this->foo);
	}
}

class FooIntString
{

	public string $foo;

	public function doFoo(int $b): void
	{
		$this->foo = $b;
		assertType('lowercase-string&numeric-string&uppercase-string', $this->foo);
	}

	public function doBar(): void
	{
		$this->foo = -1;
		assertType("'-1'", $this->foo);
		$this->foo = 1;
		assertType("'1'", $this->foo);
		$this->foo = 0;
		assertType("'0'", $this->foo);
	}
}

class FooIntBool
{

	public bool $foo;

	public function doFoo(int $b): void
	{
		$this->foo = $b;
		assertType('bool', $this->foo);

		if ($b !== 0) {
			$this->foo = $b;
			assertType('true', $this->foo);
		}
		if ($b !== 1) {
			$this->foo = $b;
			assertType('bool', $this->foo);
		}
	}

	public function doBar(): void
	{
		$this->foo = -1;
		assertType("true", $this->foo);
		$this->foo = 1;
		assertType("true", $this->foo);
		$this->foo = 0;
		assertType("false", $this->foo);
	}
}

class FooIntRangeString
{

	public string $foo;

	/**
	 * @param int<5, 10> $b
	 */
	public function doFoo(int $b): void
	{
		$this->foo = $b;
		assertType("'10'|'5'|'6'|'7'|'8'|'9'", $this->foo);
	}

	public function doBar(): void
	{
		$i = rand(5, 10);
		$this->foo = $i;
		assertType("'10'|'5'|'6'|'7'|'8'|'9'", $this->foo);
	}
}

class FooNullableIntString
{

	public string $foo;

	public function doFoo(?int $b): void
	{
		$this->foo = $b;
		assertType('lowercase-string&numeric-string&uppercase-string', $this->foo);
	}

	public function doBar(): void
	{
		$this->foo = null;
		assertType('*NEVER*', $this->foo); // null cannot be coerced to string, see https://3v4l.org/5k1Dl
	}
}

class FooFloatString
{

	public string $foo;

	public function doFoo(float $b): void
	{
		$this->foo = $b;
		assertType('numeric-string&uppercase-string', $this->foo);
	}

	public function doBar(): void
	{
		$this->foo = 1.0;
		assertType("'1'", $this->foo);
	}
}

class FooStringToUnion
{

	public int|float $foo;

	public function doFoo(string $b): void
	{
		$this->foo = $b;
		assertType('float|int', $this->foo);
	}

	public function doBar(): void
	{
		$this->foo = "1.0";
		assertType('1|1.0', $this->foo);
	}
}

class FooNumericToString
{

	public string $foo;

	public function doFoo(float|int $b): void
	{
		$this->foo = $b;
		assertType('numeric-string&uppercase-string', $this->foo);
	}

}

class FooMixedToInt
{

	public int $foo;

	public function doFoo(mixed $b): void
	{
		$this->foo = $b;
		assertType('int', $this->foo);
	}

}


class FooArrayToInt
{
	public int $foo;

	public function doFoo(array $arr): void
	{
		$this->foo = $arr;
		assertType('*NEVER*', $this->foo);
	}

    /**
     * @param non-empty-array $arr
     */
    public function doBar(array $arr): void
    {
        $this->foo = $arr;
        assertType('*NEVER*', $this->foo);
    }

    /**
     * @param non-empty-list $list
     */
    public function doBaz(array $list): void
    {
        $this->foo = $list;
        assertType('*NEVER*', $this->foo);
    }
}

class FooArrayToFloat
{
    public float $foo;

    public function doFoo(array $arr): void
    {
        $this->foo = $arr;
        assertType('*NEVER*', $this->foo);
    }

    /**
     * @param non-empty-array $arr
     */
    public function doBar(array $arr): void
    {
        $this->foo = $arr;
        assertType('*NEVER*', $this->foo);
    }

    /**
     * @param non-empty-list $list
     */
    public function doBaz(array $list): void
    {
        $this->foo = $list;
        assertType('*NEVER*', $this->foo);
    }
}

class FooArrayToString
{
    public string $foo;

    public function doFoo(array $arr): void
    {
        $this->foo = $arr;
        assertType('*NEVER*', $this->foo);
    }

    /**
     * @param non-empty-array $arr
     */
    public function doBar(array $arr): void
    {
        $this->foo = $arr;
        assertType('*NEVER*', $this->foo);
    }

    /**
     * @param non-empty-list $list
     */
    public function doBaz(array $list): void
    {
        $this->foo = $list;
        assertType('*NEVER*', $this->foo);
    }
}

class FooArray
{
    public array $foo;

    /**
     * @param non-empty-array $arr
     */
    public function doFoo(array $arr): void
    {
        $this->foo = $arr;
        assertType('non-empty-array', $this->foo);

        if (array_key_exists('foo', $arr)) {
            $this->foo = $arr;
            assertType("non-empty-array&hasOffset('foo')", $this->foo);
        }

        if (array_key_exists('foo', $arr) && $arr['foo'] === 'bar') {
            $this->foo = $arr;
            assertType("non-empty-array&hasOffsetValue('foo', 'bar')", $this->foo);
        }
    }
}

class FooTypedArray
{
    /**
     * @var array<int>
     */
    public array $foo;

    /**
     * @param array<float> $arr
     */
    public function doFoo(array $arr): void
    {
        $this->foo = $arr;
        assertType('array<float>', $this->foo);
    }

    /**
     * @param array<string> $arr
     */
    public function doBar(array $arr): void
    {
        $this->foo = $arr;
        assertType('array<string>', $this->foo);
    }
}

class FooList
{
    public array $foo;

    /**
     * @param non-empty-list $list
     */
    public function doFoo(array $list): void
    {
        $this->foo = $list;
        assertType('non-empty-list', $this->foo);

        if (array_key_exists(3, $list)) {
            $this->foo = $list;
            assertType("non-empty-list&hasOffset(3)", $this->foo);
        }

        if (array_key_exists(3, $list) && is_string($list[3])) {
            $this->foo = $list;
            assertType("non-empty-list&hasOffsetValue(3, string)", $this->foo);
        }
    }

}

// https://3v4l.org/LJiRB
class CallableString {
    private string $foo;

    public function doFoo(callable $foo): void {
        $this->foo = $foo;
        assertType('callable-string|non-empty-string', $this->foo);
    }
}

// https://3v4l.org/VvUsp
class CallableArray {
    private array $foo;

    public function doFoo(callable $foo): void {
        $this->foo = $foo;
        assertType('array', $this->foo); // could be non-empty-array
    }
}

class StringableFoo {
    private string $foo;

    public function doFoo(StringableFoo $foo): void {
        $this->foo = $foo;
        assertType('string', $this->foo);
    }

    public function doFoo2(NotStringable $foo): void {
        $this->foo = $foo;
        assertType('*NEVER*', $this->foo);
    }

    public function __toString(): string {
        return 'Foo';
    }
}

final class NotStringable {}

class ObjectWithToStringMethod {
    private string $foo;

    public function doFoo(object $foo): void {
        if (method_exists($foo, '__toString')) {
            $this->foo = $foo;
            assertType('string', $this->foo);
        }
    }
    public function __toString(): string {
        return 'Foo';
    }
}

