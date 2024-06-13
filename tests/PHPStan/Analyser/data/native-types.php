<?php // onlyif PHP_VERSION_ID >= 70400

namespace NativeTypes;

use function PHPStan\Testing\assertType;
use function PHPStan\Testing\assertNativeType;

class Foo
{

	/**
	 * @param self $foo
	 * @param \DateTimeImmutable $dateTime
	 * @param \DateTimeImmutable $dateTimeMutable
	 * @param string $nullableString
	 * @param string|null $nonNullableString
	 */
	public function doFoo(
		$foo,
		\DateTimeInterface $dateTime,
		\DateTime $dateTimeMutable,
		?string $nullableString,
		string $nonNullableString
	): void
	{
		assertType(Foo::class, $foo);
		assertNativeType('mixed', $foo);

		// change type after assignment
		$foo = new Foo();
		assertType(Foo::class, $foo);
		assertNativeType(Foo::class, $foo);

		assertType(\DateTimeImmutable::class, $dateTime);
		assertNativeType(\DateTimeInterface::class, $dateTime);

		$f = function (Foo $foo) use ($dateTime) {
			assertType(Foo::class, $foo);
			assertNativeType(Foo::class, $foo);

			assertType(\DateTimeImmutable::class, $dateTime);
			assertNativeType(\DateTimeInterface::class, $dateTime);
		};

		assertType(\DateTime::class, $dateTimeMutable);
		assertNativeType(\DateTime::class, $dateTimeMutable);

		assertType('string|null', $nullableString);
		assertNativeType('string|null', $nullableString);

		if (is_string($nullableString)) {
			// change specified type
			assertType('string', $nullableString);
			assertNativeType('string', $nullableString);

			// preserve other variables
			assertType(\DateTimeImmutable::class, $dateTime);
			assertNativeType(\DateTimeInterface::class, $dateTime);
		}

		// preserve after merging scopes
		assertType(\DateTimeImmutable::class, $dateTime);
		assertNativeType(\DateTimeInterface::class, $dateTime);

		assertType('string', $nonNullableString);
		assertNativeType('string', $nonNullableString);

		unset($nonNullableString);
		assertType('*ERROR*', $nonNullableString);
		assertNativeType('*ERROR*', $nonNullableString);

		// preserve other variables
		assertType(\DateTimeImmutable::class, $dateTime);
		assertNativeType(\DateTimeInterface::class, $dateTime);
	}

	/**
	 * @param array<string, int> $array
	 */
	public function doForeach(array $array): void
	{
		assertType('array<string, int>', $array);
		assertNativeType('array', $array);

		foreach ($array as $key => $value) {
			assertType('non-empty-array<string, int>', $array);
			assertNativeType('non-empty-array', $array);

			assertType('string', $key);
			assertNativeType('(int|string)', $key);

			assertType('int', $value);
			assertNativeType('mixed', $value);
		}
	}

	/**
	 * @param self $foo
	 */
	public function doCatch($foo): void
	{
		assertType(Foo::class, $foo);
		assertNativeType('mixed', $foo);

		try {
			throw new \Exception();
		} catch (\InvalidArgumentException $foo) {
			assertType(\InvalidArgumentException::class, $foo);
			assertNativeType(\InvalidArgumentException::class, $foo);
		} catch (\Exception $e) {
			assertType('Exception~InvalidArgumentException', $e);
			assertNativeType('Exception~InvalidArgumentException', $e);

			assertType(Foo::class, $foo);
			assertNativeType('mixed', $foo);
		}
	}

	/**
	 * @param array<string, array{int, string}> $array
	 */
	public function doForeachArrayDestructuring(array $array)
	{
		assertType('array<string, array{int, string}>', $array);
		assertNativeType('array', $array);
		foreach ($array as $key => [$i, $s]) {
			assertType('non-empty-array<string, array{int, string}>', $array);
			assertNativeType('non-empty-array', $array);

			assertType('string', $key);
			assertNativeType('(int|string)', $key);

			assertType('int', $i);
			// assertNativeType('mixed', $i);

			assertType('string', $s);
			// assertNativeType('mixed', $s);
		}
	}

	/**
	 * @param \DateTimeImmutable $date
	 */
	public function doIfElse(\DateTimeInterface $date): void
	{
		if ($date instanceof \DateTimeInterface) {
			assertType(\DateTimeImmutable::class, $date);
			assertNativeType(\DateTimeInterface::class, $date);
		} else {
			assertType('*NEVER*', $date);
			assertNativeType('*NEVER*', $date);
		}

		assertType(\DateTimeImmutable::class, $date);
		assertNativeType(\DateTimeInterface::class, $date);

		if ($date instanceof \DateTimeImmutable) {
			assertType(\DateTimeImmutable::class, $date);
			assertNativeType(\DateTimeImmutable::class, $date);
		} else {
			assertType('*NEVER*', $date);
			assertNativeType('DateTime', $date);
		}

		assertType(\DateTimeImmutable::class, $date);
		assertNativeType(\DateTimeImmutable::class, $date); // could be DateTimeInterface

		if ($date instanceof \DateTime) {

		}
	}

	public function declareStrictTypes(array $array): void
	{
		/** @var array<string> $array */
		assertType('array<string>', $array);
		assertNativeType('array', $array);

		declare(strict_types=1);
		assertType('array<string>', $array);
		assertNativeType('array', $array);
	}

	public function arrowFunction(array $array): void
	{
		/** @var array<string> $array */
		assertType('array<string>', $array);
		assertNativeType('array', $array);

		(fn () => assertNativeType('array', $array))();
	}

	public function closuresUsingCallMethod(array $array, object $object): void
	{
		/** @var \stdClass $object */
		assertType('$this(NativeTypes\Foo)', $this);
		assertNativeType('$this(NativeTypes\Foo)', $this);

		/** @var array<string> $array */
		assertType('array<string>', $array);
		assertNativeType('array', $array);

		(function () use ($array) {
			assertType('stdClass', $this);
			assertNativeType('object', $this);

			assertType('array<string>', $array);
			assertNativeType('array', $array);
		})->call($object);

		assertType('$this(NativeTypes\Foo)', $this);
		assertNativeType('$this(NativeTypes\Foo)', $this);
	}

	public function closureBind(array $array, object $object): void
	{
		/** @var \stdClass $object */
		assertType('$this(NativeTypes\Foo)', $this);
		assertNativeType('$this(NativeTypes\Foo)', $this);

		/** @var array<string> $array */
		assertType('array<string>', $array);
		assertNativeType('array', $array);

		\Closure::bind(function () use ($array) {
			assertType('stdClass', $this);
			assertNativeType('object', $this);

			assertType('array<string>', $array);
			assertNativeType('array', $array);
		}, $object);

		assertType('$this(NativeTypes\Foo)', $this);
		assertNativeType('$this(NativeTypes\Foo)', $this);
	}

}

/**
 * @param Foo $foo
 * @param \DateTimeImmutable $dateTime
 * @param \DateTimeImmutable $dateTimeMutable
 * @param string $nullableString
 * @param string|null $nonNullableString
 */
function fooFunction(
	$foo,
	\DateTimeInterface $dateTime,
	\DateTime $dateTimeMutable,
	?string $nullableString,
	string $nonNullableString
): void
{
	assertType(Foo::class, $foo);
	assertNativeType('mixed', $foo);

	assertType(\DateTimeImmutable::class, $dateTime);
	assertNativeType(\DateTimeInterface::class, $dateTime);

	assertType(\DateTime::class, $dateTimeMutable);
	assertNativeType(\DateTime::class, $dateTimeMutable);

	assertType('string|null', $nullableString);
	assertNativeType('string|null', $nullableString);

	assertType('string', $nonNullableString);
	assertNativeType('string', $nonNullableString);
}

function phpDocDoesNotInfluenceExistingNativeType(): void
{
	$array = [];

	assertType('array{}', $array);
	assertNativeType('array{}', $array);

	/** @var array<string> $array */
	assertType('array<string>', $array);
	assertNativeType('array{}', $array);
}

class NativeStaticCall
{

	public function doFoo()
	{
		assertType('non-empty-string', self::doBar());
		assertNativeType('string', self::doBar());

		$s = new self();
		assertType('non-empty-string', $s::doBar());
		assertNativeType('string', $s::doBar());
	}

	/** @return non-empty-string */
	public static function doBar(): string
	{

	}

}

class TypedProperties
{

	/** @var int */
	private $untyped;

	private int $typed;

	/** @var int */
	private static $untypedStatic;

	private static int $typedStatic;

	public function doFoo(): void
	{
		assertType('int', $this->untyped);
		assertNativeType('mixed', $this->untyped);
		assertType('int', $this->typed);
		assertNativeType('int', $this->typed);
		assertType('int', self::$untypedStatic);
		assertNativeType('mixed', self::$untypedStatic);
		assertType('int', self::$typedStatic);
		assertNativeType('int', self::$typedStatic);
	}

}

/** @return non-empty-string */
function funcWithANativeReturnType(): string
{

}

class TestFuncWithANativeReturnType
{

	public function doFoo(): void
	{
		assertType('non-empty-string', funcWithANativeReturnType());
		assertNativeType('string', funcWithANativeReturnType());

		$f = function (): string {
			return funcWithANativeReturnType();
		};

		assertType('non-empty-string', $f());
		assertNativeType('string', $f());

		assertType('non-empty-string', (function (): string {
			return funcWithANativeReturnType();
		})());
		assertNativeType('string', (function (): string {
			return funcWithANativeReturnType();
		})());

		$g = fn () => funcWithANativeReturnType();

		assertType('non-empty-string', $g());
		assertNativeType('string', $g());

		assertType('non-empty-string', (fn () => funcWithANativeReturnType())());
		assertNativeType('string', (fn () => funcWithANativeReturnType())());
	}

}

class TestPhp8Stubs
{

	public function doFoo(): void
	{
		$a = array_replace([1, 2, 3], [4, 5, 6]);
		assertType('non-empty-array<0|1|2, 1|2|3|4|5|6>', $a);
		assertNativeType('array', $a);
	}

}

class PositiveInt
{

	/**
	 * @param positive-int $i
	 * @return void
	 */
	public function doFoo(int $i): void
	{
		assertType('true', $i > 0);
		assertType('false', $i <= 0);
		assertNativeType('bool', $i > 0);
		assertNativeType('bool', $i <= 0);
	}

}
