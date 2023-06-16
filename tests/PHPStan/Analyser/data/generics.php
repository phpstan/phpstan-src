<?php

namespace PHPStan\Generics\FunctionsAssertType;

use IteratorAggregate;
use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PHPStan\Generics\FunctionsAssertType\GenericRule;
use function PHPStan\Testing\assertType;

/**
 * @template T
 * @param T $a
 * @return T
 */
function a($a)
{
	assertType('T (function PHPStan\Generics\FunctionsAssertType\a(), argument)', $a);
	return $a;
}

/**
 * @param int $int
 * @param int|float $intFloat
 * @param mixed $mixed
 */
function testA($int, $intFloat, $mixed)
{
	assertType('int', a($int));
	assertType('float|int', a($intFloat));
	assertType('DateTime', a(new \DateTime()));
	assertType('mixed', a($mixed));
}

/**
 * @template T of \DateTimeInterface
 * @param T $a
 * @return T
 */
function b($a)
{
	assertType('T of DateTimeInterface (function PHPStan\Generics\FunctionsAssertType\b(), argument)', $a);
	assertType('T of DateTimeInterface (function PHPStan\Generics\FunctionsAssertType\b(), argument)', b($a));
	return $a;
}

/**
 * @param \DateTimeInterface $dateTimeInterface
 */
function assertTypeTest($dateTimeInterface)
{
	assertType('DateTime', b(new \DateTime()));
	assertType('DateTimeImmutable', b(new \DateTimeImmutable()));
	assertType('DateTimeInterface', b($dateTimeInterface));
}

/**
 * @template K
 * @template V
 * @param array<K,V> $a
 * @return array<K,V>
 */
function c($a)
{
	return $a;
}

/**
 * @param array<int, string> $arrayOfString
 */
function testC($arrayOfString)
{
	assertType('array<int, string>', c($arrayOfString));
}

/**
 * @template T
 * @param T $a
 * @param T $b
 * @return T
 */
function d($a, $b)
{
	return $a;
}

/**
 * @param int $int
 * @param float $float
 * @param int|float $intFloat
 */
function testD($int, $float, $intFloat)
{
	assertType('int', d($int, $int));
	assertType('float|int', d($int, $float));
	assertType('DateTime|int', d($int, new \DateTime()));
	assertType('DateTime|float|int', d($intFloat, new \DateTime()));
	assertType('array{}|DateTime', d([], new \DateTime()));
	assertType('array{blabla: string}|DateTime', d(['blabla' => 'barrrr'], new \DateTime()));
}

/**
 * @template T
 * @param array<\DateTime|array<T>> $a
 * @return T
 */
function e($a)
{
	throw new \Exception();
}

/**
 * @param int $int
 */
function testE($int)
{
	assertType('int', e([[$int]]));
}

/**
 * @template A
 * @template B
 *
 * @param array<A> $a
 * @param callable(A):B $b
 *
 * @return array<B>
 */
function f($a, $b)
{
	$result = [];
	assertType('array<A (function PHPStan\Generics\FunctionsAssertType\f(), argument)>', $a);
	assertType('callable(A): B', $b);
	foreach ($a as $k => $v) {
		assertType('A (function PHPStan\Generics\FunctionsAssertType\f(), argument)', $v);
		$newV = $b($v);
		assertType('B (function PHPStan\Generics\FunctionsAssertType\f(), argument)', $newV);
		$result[$k] = $newV;
	}
	return $result;
}

/**
 * @param array<int> $arrayOfInt
 * @param null|(callable(int):string) $callableOrNull
 */
function testF($arrayOfInt, $callableOrNull)
{
	assertType('Closure(int): numeric-string', function (int $a): string {
		return (string)$a;
	});
	assertType('array<string>', f($arrayOfInt, function (int $a): string {
		return (string)$a;
	}));
	assertType('Closure(mixed): string', function ($a): string {
		return (string)$a;
	});
	assertType('array<string>', f($arrayOfInt, function ($a): string {
		return (string)$a;
	}));
	assertType('array', f($arrayOfInt, function ($a) {
		return $a;
	}));
	assertType('array<string>', f($arrayOfInt, $callableOrNull));
	assertType('array', f($arrayOfInt, null));
	assertType('array', f($arrayOfInt, ''));
}

/**
 * @template T
 * @param T $a
 * @return array<T>
 */
function g($a)
{
	return [$a];
}

/**
 * @param int $int
 */
function testG($int)
{
	assertType('array<int>', g($int));
}

class Foo
{

	/** @var static */
	public static $staticProp;

	/** @return static */
	public static function returnsStatic()
	{
		return new static();
	}

	/** @return static */
	public function instanceReturnsStatic()
	{
		return new static();
	}
}

/**
 * @template T of Foo
 * @param T $foo
 */
function testReturnsStatic($foo)
{
	assertType('T of PHPStan\Generics\FunctionsAssertType\Foo (function PHPStan\Generics\FunctionsAssertType\testReturnsStatic(), argument)', $foo::returnsStatic());
	assertType('T of PHPStan\Generics\FunctionsAssertType\Foo (function PHPStan\Generics\FunctionsAssertType\testReturnsStatic(), argument)', $foo->instanceReturnsStatic());
}

/**
 * @param int[] $listOfIntegers
 */
function testArrayMap(array $listOfIntegers)
{
	$strings = array_map(function ($int): string {
		assertType('int', $int);

		return (string) $int;
	}, $listOfIntegers);
	assertType('array<numeric-string>', $strings);
}

/**
 * @param int[] $listOfIntegers
 */
function testArrayFilter(array $listOfIntegers)
{
	$integers = array_filter($listOfIntegers, function ($int): bool {
		assertType('int', $int);

		return true;
	});
	assertType('array<int>', $integers);
}

/**
 * @template K
 * @template V
 * @param iterable<K, V> $it
 * @return array<K, V>
 */
function iterableToArray($it)
{
	$ret = [];
	foreach ($it as $k => $v) {
		$ret[$k] = $v;
	}
	return $ret;
}

/**
 * @param iterable<string, Foo> $it
 */
function testIterable(iterable $it)
{
	assertType('array<string, PHPStan\Generics\FunctionsAssertType\Foo>', iterableToArray($it));
}

/**
 * @template T
 * @template U
 * @param array{a: T, b: U, c: int} $a
 * @return array{T, U}
 */
function constantArray($a): array
{
	return [$a['a'], $a['b']];
}

function testConstantArray(int $int, string $str)
{
	[$a, $b] = constantArray(['a' => $int, 'b' => $str, 'c' => 1]);
	assertType('int', $a);
	assertType('string', $b);
}

/**
 * @template U of \DateTimeInterface
 * @param U $a
 * @return U
 */
function typeHints(\DateTimeInterface $a): \DateTimeInterface
{
	assertType('U of DateTimeInterface (function PHPStan\Generics\FunctionsAssertType\typeHints(), argument)', $a);
	return $a;
}

/**
 * @template U of \DateTime
 * @param U $a
 * @return U
 */
function typeHintsSuperType(\DateTimeInterface $a): \DateTimeInterface
{
	assertType('U of DateTime (function PHPStan\Generics\FunctionsAssertType\typeHintsSuperType(), argument)', $a);
	return $a;
}

/**
 * @template U of \DateTimeInterface
 * @param U $a
 * @return U
 */
function typeHintsSubType(\DateTime $a): \DateTimeInterface
{
	assertType('DateTime', $a);
	return $a;
}

function testTypeHints(): void
{
	assertType('DateTime', typeHints(new \DateTime()));
	assertType('DateTime', typeHintsSuperType(new \DateTime()));
	assertType('DateTimeInterface', typeHintsSubType(new \DateTime()));
}

/**
 * @template T of \Exception
 * @param T $a
 * @param T $b
 * @return T
 */
function expectsException($a, $b)
{
	return $b;
}

function testUpperBounds(\Throwable $t)
{
	assertType('Exception', expectsException(new \Exception(), $t));
}

/**
 * @template T
 * @param callable $cb
 * @return T
 */
function varAnnotation($cb)
{
	/** @var T */
	$v = $cb();

	assertType('T (function PHPStan\Generics\FunctionsAssertType\varAnnotation(), parameter)', $v);

	return $v;
}

/**
 * @template T
 */
class C
{
	/** @var T */
	private $a;

	/**
	 * @param T $p
	 * @param callable $cb
	 */
	public function f($p, $cb)
	{
		assertType('T (class PHPStan\Generics\FunctionsAssertType\C, argument)', $p);

		/** @var T */
		$v = $cb();

		assertType('T (class PHPStan\Generics\FunctionsAssertType\C, parameter)', $v); // should be argument

		assertType('T (class PHPStan\Generics\FunctionsAssertType\C, argument)', $this->a);

		$a = new class
		{
			/** @return T */
			public function g()
			{
				throw new \Exception();
			}
		};

		assertType('T (class PHPStan\Generics\FunctionsAssertType\C, parameter)', $a->g());
	}
}

/**
 * @template T
 */
class A
{
	/** @var T */
	private $a;

	/** @var T */
	public $b;

	/**
	 * A::__construct()
	 *
	 * @param T $a
	 */
	public function __construct($a)
	{
		$this->a = $a;
		$this->b = $a;
	}

	/**
	 * @return T
	 */
	public function get()
	{
		assertType('T (class PHPStan\Generics\FunctionsAssertType\A, argument)', $this->a);
		assertType('T (class PHPStan\Generics\FunctionsAssertType\A, argument)', $this->b);
		return $this->a;
	}

	/**
	 * @param T $a
	 */
	public function set($a)
	{
		$this->a = $a;
	}

}

/**
 * @extends A<\DateTime>
 */
class AOfDateTime extends A
{
	public function __construct()
	{
		parent::__construct(new \DateTime());
	}
}

/**
 * @template T
 *
 * @extends A<T>
 */
class B extends A
{
	/**
	 * B::__construct()
	 *
	 * @param T $a
	 */
	public function __construct($a)
	{
		parent::__construct($a);
	}
}

/**
 * @template T
 */
interface I
{
	/**
	 * I::get()
	 *
	 * @return T
	 */
	function get();

	/**
	 * I::getInheritdoc()
	 *
	 * @return T
	 */
	function getInheritdoc();
}

/**
 * @implements I<int>
 */
class CofI implements I
{
	public function get()
	{
	}

	/** @inheritdoc */
	public function getInheritdoc()
	{
	}
}

/**
 * Interface SuperIfaceA
 *
 * @template A
 */
interface SuperIfaceA
{
	/**
	 * SuperIfaceA::get()
	 *
	 * @param A $a
	 * @return A
	 */
	public function getA($a);
}

/**
 * Interface SuperIfaceB
 *
 * @template B
 */
interface SuperIfaceB
{
	/**
	 * SuperIfaceB::get()
	 *
	 * @param B $b
	 * @return B
	 */
	public function getB($b);
}

/**
 * IfaceAB
 *
 * @template T
 *
 * @extends SuperIfaceA<int>
 * @extends SuperIfaceB<T>
 */
interface IfaceAB extends SuperIfaceA, SuperIfaceB
{
}

/**
 * ABImpl
 *
 * @implements IfaceAB<\DateTime>
 */
class ABImpl implements IfaceAB
{
	public function getA($a)
	{
		assertType('int', $a);
		return 1;
	}

	public function getB($b)
	{
		assertType('DateTime', $b);
		return new \DateTime();
	}
}

/**
 * @implements SuperIfaceA<int>
 */
class X implements SuperIfaceA
{
	public function getA($a)
	{
		assertType('int', $a);
		return 1;
	}
}

/**
 * @template T
 *
 * @extends A<T>
 */
class NoConstructor extends A
{
}

/**
 * @template T
 * @param class-string<T> $s
 * @return T
 */
function acceptsClassString(string $s)
{
	return new $s;
}

/**
 * @template U
 * @param class-string<U> $s
 * @return class-string<U>
 */
function anotherAcceptsClassString(string $s)
{
	assertType('U (function PHPStan\Generics\FunctionsAssertType\anotherAcceptsClassString(), argument)', acceptsClassString($s));
}

/**
 * @template T
 * @param T $object
 * @return class-string<T>
 */
function returnsClassString($object)
{
	return get_class($object);
}

/**
 * @template T of \Exception
 * @param class-string<T> $string
 * @return T
 */
function acceptsClassStringUpperBound($string)
{
	return new $string;
}


/**
 * @template TNodeType of \PhpParser\Node
 */
interface GenericRule
{
	/**
	 * @return TNodeType
	 */
	public function getNodeInstance(): Node;

	/**
	 * @return class-string<TNodeType>
	 */
	public function getNodeType(): string;
}

/**
 * @implements GenericRule<\PhpParser\Node\Expr\StaticCall>
 */
class SomeRule implements GenericRule
{
	public function getNodeInstance(): Node
	{
		return new StaticCall(new Name(\stdClass::class), '__construct');
	}

	public function getNodeType(): string
	{
		return StaticCall::class;
	}
}

class SomeRule2 implements GenericRule
{
	public function getNodeInstance(): Node
	{
		return new StaticCall(new Name(\stdClass::class), '__construct');
	}

	public function getNodeType(): string
	{
		return Node::class;
	}
}

/**
 * Infer from generic
 *
 * @template T of \DateTimeInterface
 *
 * @param A<A<T>> $x
 *
 * @return A<T>
 */
function inferFromGeneric($x)
{
	return $x->get();
}

/**
 * @template A
 * @template B
 */
class Factory
{
	private $a;
	private $b;

	/**
	 * @param A $a
	 * @param B $b
	 */
	public function __construct($a, $b)
	{
		$this->a = $a;
		$this->b = $b;
	}

	/**
	 * @template C
	 * @template D
	 *
	 * @param A $a
	 * @param C $c
	 * @param D $d
	 *
	 * @return array{A, B, C, D}
	 */
	public function create($a, $c, $d): array
	{
		return [$a, $this->b, $c, $d];
	}
}

function testClasses()
{
	$a = new A(1);
	assertType('PHPStan\Generics\FunctionsAssertType\A<int>', $a);
	assertType('int', $a->get());
	assertType('int', $a->b);

	$a = new AOfDateTime();
	assertType('PHPStan\Generics\FunctionsAssertType\AOfDateTime', $a);
	assertType('DateTime', $a->get());
	assertType('DateTime', $a->b);

	$b = new B(1);
	assertType('PHPStan\Generics\FunctionsAssertType\B<int>', $b);
	assertType('int', $b->get());
	assertType('int', $b->b);

	$c = new CofI();
	assertType('PHPStan\Generics\FunctionsAssertType\CofI', $c);
	assertType('int', $c->get());
	assertType('int', $c->getInheritdoc());

	$ab = new ABImpl();
	assertType('int', $ab->getA(0));
	assertType('DateTime', $ab->getB(new \DateTime()));

	$noConstructor = new NoConstructor(1);
	assertType('PHPStan\Generics\FunctionsAssertType\NoConstructor<mixed>', $noConstructor);

	assertType('stdClass', acceptsClassString(\stdClass::class));
	assertType('class-string<stdClass>', returnsClassString(new \stdClass()));

	assertType('Exception', acceptsClassStringUpperBound(\Exception::class));
	assertType('Exception', acceptsClassStringUpperBound(\Throwable::class));
	assertType('InvalidArgumentException', acceptsClassStringUpperBound(\InvalidArgumentException::class));

	$rule = new SomeRule();
	assertType(StaticCall::class, $rule->getNodeInstance());
	assertType('class-string<' . StaticCall::class . '>', $rule->getNodeType());

	$rule2 = new SomeRule2();
	assertType(Node::class, $rule2->getNodeInstance());
	assertType('class-string<' . Node::class . '>', $rule2->getNodeType());

	$a = inferFromGeneric(new A(new A(new \DateTime())));
	assertType('PHPStan\Generics\FunctionsAssertType\A<DateTime>', $a);

	$factory = new Factory(new \DateTime(), new A(1));
	assertType(
		'array{DateTime, PHPStan\\Generics\\FunctionsAssertType\\A<int>, string, PHPStan\\Generics\\FunctionsAssertType\\A<DateTime>}',
		$factory->create(new \DateTime(), '', new A(new \DateTime()))
	);
}

/**
 * @template T
 */
interface GenericIterator extends IteratorAggregate
{

	/**
	 * @return \Iterator<T>
	 */
	public function getIterator(): \Iterator;

}

function () {
	/** @var GenericIterator<int> $iterator */
	$iterator = doFoo();
	assertType('PHPStan\Generics\FunctionsAssertType\GenericIterator<int>', $iterator);
	assertType('Iterator<mixed, int>', $iterator->getIterator());

	foreach ($iterator as $int) {
		assertType('int', $int);
	}
};

function (GenericRule $rule): void {
	assertType('class-string<PhpParser\Node>', $rule->getNodeType());
	assertType(Node::class, $rule->getNodeInstance());
};

/**
 * @template T of \PhpParser\Node
 */
class GenericClassWithProperty
{

	/** @var T */
	public $a;

}

function (GenericClassWithProperty $obj): void
{
	assertType(Node::class, $obj->a);
};

class ClassThatExtendsGenericClassWithPropertyWithoutSpecifyingTemplateType extends GenericClassWithProperty
{

}

function (ClassThatExtendsGenericClassWithPropertyWithoutSpecifyingTemplateType $obj): void
{
	assertType(Node::class, $obj->a);
};

/**
 * @template T of \DateTimeInterface
 */
class GenericThis
{
	/** @param T $foo */
	public function __construct(\DateTimeInterface $foo)
	{
		assertType('T of DateTimeInterface (class PHPStan\Generics\FunctionsAssertType\GenericThis, argument)', $this->getFoo());
	}

	/** @return T */
	public function getFoo()
	{
	}
}

/**
 * @template T
 */
class Cache
{
	/**
	 * @param T $t
	 */
	public function __construct($t)
	{
	}

	/**
	 * Function Cache::get
	 *
	 * @return T
	 */
	public function get()
	{
	}
}

/**
 * Function cache0
 *
 * @template T
 *
 * @param T $t
 */
function cache0($t): void {
	$c = new Cache($t);
	assertType('T (function PHPStan\Generics\FunctionsAssertType\cache0(), argument)', $c->get());
}

/**
 * Function cache1
 *
 * @template T
 *
 * @param T $t
 */
function cache1($t): void {
	$c = new Cache($t);
	assertType('T (function PHPStan\Generics\FunctionsAssertType\cache1(), argument)', $c->get());
}

function newHandling(): void {
	assertType('PHPStan\Generics\FunctionsAssertType\C<mixed>', new C());
	assertType('PHPStan\Generics\FunctionsAssertType\A<stdClass>', new A(new \stdClass()));
	assertType('PHPStan\Generics\FunctionsAssertType\A<mixed>', new A());
}

/**
 * @template TKey of array-key
 * @template TValue of \stdClass
 */
class StdClassCollection
{

	/** @var array<TKey, TValue> */
	private $list;

	/**
	 * @param array<TKey, TValue> $list
	 */
	public function __construct(array $list)
	{

	}

	/**
	 * @return array<TKey, TValue>
	 */
	public function getAll(): array
	{
		return $this->list;
	}

	/**
	 * @return static
	 */
	public function returnStatic(): self
	{

	}

}

function () {
	$stdEmpty = new StdClassCollection([]);
	assertType('PHPStan\Generics\FunctionsAssertType\StdClassCollection<*NEVER*, *NEVER*>', $stdEmpty);
	assertType('array{}', $stdEmpty->getAll());

	$std = new StdClassCollection([new \stdClass()]);
	assertType('PHPStan\Generics\FunctionsAssertType\StdClassCollection<int, stdClass>', $std);
	assertType('PHPStan\Generics\FunctionsAssertType\StdClassCollection<int, stdClass>', $std->returnStatic());
	assertType('array<int, stdClass>', $std->getAll());
};

class ClassWithMethodCachingIssue
{

	/**
	 * @template T
	 * @param T $a
	 */
	public function doFoo($a)
	{
		assertType('T (method PHPStan\Generics\FunctionsAssertType\ClassWithMethodCachingIssue::doFoo(), argument)', $a);

		/** @var T $b */
		$b = doFoo();
		assertType('T (method PHPStan\Generics\FunctionsAssertType\ClassWithMethodCachingIssue::doFoo(), parameter)', $b);
	}

	/**
	 * @template T
	 * @param T $a
	 */
	public function doBar($a)
	{
		assertType('T (method PHPStan\Generics\FunctionsAssertType\ClassWithMethodCachingIssue::doBar(), argument)', $a);

		/** @var T $b */
		$b = doFoo();
		assertType('T (method PHPStan\Generics\FunctionsAssertType\ClassWithMethodCachingIssue::doBar(), parameter)', $b);
	}

}

/**
 * @param \ReflectionClass<Foo> $ref
 */
function testReflectionClass($ref)
{
	assertType('class-string<PHPStan\Generics\FunctionsAssertType\Foo>', $ref->name);
	assertType('class-string<PHPStan\Generics\FunctionsAssertType\Foo>', $ref->getName());
	assertType('PHPStan\Generics\FunctionsAssertType\Foo', $ref->newInstanceWithoutConstructor());

	assertType('ReflectionClass<PHPStan\Generics\FunctionsAssertType\Foo>', new \ReflectionClass(Foo::class));
}

class CreateClassReflectionOfStaticClass
{

	public function doFoo()
	{
		assertType('PHPStan\Generics\FunctionsAssertType\CreateClassReflectionOfStaticClass', (new \ReflectionClass(self::class))->newInstanceWithoutConstructor());
		assertType('static(PHPStan\Generics\FunctionsAssertType\CreateClassReflectionOfStaticClass)', (new \ReflectionClass(static::class))->newInstanceWithoutConstructor());
		assertType('class-string<static(PHPStan\Generics\FunctionsAssertType\CreateClassReflectionOfStaticClass)>', (new \ReflectionClass(static::class))->name);
	}

}

/**
 * @param \Traversable<int> $t1
 * @param \Traversable<int, \stdClass> $t2
 */
function testIterateOverTraversable($t1, $t2)
{
	foreach ($t1 as $int) {
		assertType('int', $int);
	}

	foreach ($t2 as $key => $value) {
		assertType('int', $key);
		assertType('stdClass', $value);
	}
}

/**
 * @return \Generator<int, string, \stdClass, \Exception>
 */
function getGenerator(): \Generator
{
	$stdClass = yield 'foo';
	assertType(\stdClass::class, $stdClass);
}

function testYieldFrom()
{
	$yield = yield from getGenerator();
	assertType('Exception', $yield);
}

/**
 * @template T
 */
class StaticClassConstant
{

	public function doFoo()
	{
		$staticClassName = static::class;
		assertType('class-string<static(PHPStan\Generics\FunctionsAssertType\StaticClassConstant<T (class PHPStan\Generics\FunctionsAssertType\StaticClassConstant, argument)>)>', $staticClassName);
		assertType('static(PHPStan\Generics\FunctionsAssertType\StaticClassConstant<T (class PHPStan\Generics\FunctionsAssertType\StaticClassConstant, argument)>)', new $staticClassName);
	}

	/**
	 * @param class-string<T> $type
	 */
	public function doBar(string $type)
	{
		if ($type !== Foo::class && $type !== C::class) {
			assertType('class-string<T (class PHPStan\Generics\FunctionsAssertType\StaticClassConstant, argument)>', $type);
			throw new \InvalidArgumentException;
		}

		assertType('\'PHPStan\\\\Generics\\\\FunctionsAssertType\\\\C\'|\'PHPStan\\\\Generics\\\\FunctionsAssertType\\\\Foo\'', $type);
	}

}

/**
 * @template T of \DateTime
 * @template U as \DateTime
 * @param T $a
 * @param U $b
 */
function testBounds($a, $b): void
{
	assertType('T of DateTime (function PHPStan\Generics\FunctionsAssertType\testBounds(), argument)', $a);
	assertType('U of DateTime (function PHPStan\Generics\FunctionsAssertType\testBounds(), argument)', $b);
}

/**
 * @template T of object
 * @param T $a
 * @return T
 */
function testGenericObjectWithoutClassType($a)
{
	return $a;
}

/**
 * @template T of object
 * @param T $a
 * @return T
 */
function testGenericObjectWithoutClassType2($a)
{
	assertType('T of object (function PHPStan\Generics\FunctionsAssertType\testGenericObjectWithoutClassType2(), argument)', $a);
	assertType('T of object (function PHPStan\Generics\FunctionsAssertType\testGenericObjectWithoutClassType2(), argument)', testGenericObjectWithoutClassType($a));
	$b = $a;
	if ($b instanceof \stdClass) {
		return $a;
	}

	assertType('T of object~stdClass (function PHPStan\Generics\FunctionsAssertType\testGenericObjectWithoutClassType2(), argument)', $b);

	return $a;
}

function () {
	$a = new \stdClass();
	assertType('stdClass', testGenericObjectWithoutClassType($a));
	assertType('stdClass', testGenericObjectWithoutClassType(testGenericObjectWithoutClassType($a)));
	assertType('stdClass', testGenericObjectWithoutClassType2(testGenericObjectWithoutClassType($a)));
};

/**
 * @template T of object
 * @extends \ReflectionClass<T>
 */
class GenericReflectionClass extends \ReflectionClass
{

	#[\ReturnTypeWillChange]
	public function newInstanceWithoutConstructor()
	{
		return parent::newInstanceWithoutConstructor();
	}

}

/**
 * @extends \ReflectionClass<\stdClass>
 */
class SpecificReflectionClass extends \ReflectionClass
{

	#[\ReturnTypeWillChange]
	public function newInstanceWithoutConstructor()
	{
		return parent::newInstanceWithoutConstructor();
	}

}

/**
 * @param GenericReflectionClass<\stdClass> $ref
 */
function testGenericReflectionClass($ref)
{
	assertType('class-string<stdClass>', $ref->name);
	assertType('stdClass', $ref->newInstanceWithoutConstructor());
};

/**
 * @param SpecificReflectionClass $ref
 */
function testSpecificReflectionClass($ref)
{
	assertType('class-string<stdClass>', $ref->name);
	assertType('stdClass', $ref->newInstanceWithoutConstructor());
};

/**
 * @template T of Foo
 * @phpstan-template T of Bar
 */
class PrefixedTemplateWins
{

	/** @var T */
	public $name;

}

/**
 * @phpstan-template T of Bar
 * @template T of Foo
 */
class PrefixedTemplateWins2
{

	/** @var T */
	public $name;

}

/**
 * @template T of Foo
 * @phpstan-template T of Bar
 * @psalm-template T of Baz
 */
class PrefixedTemplateWins3
{

	/** @var T */
	public $name;

}

/**
 * @template T of Foo
 * @psalm-template T of Bar
 */
class PrefixedTemplateWins4
{

	/** @var T */
	public $name;

}

/**
 * @psalm-template T of Foo
 * @phpstan-template T of Bar
 */
class PrefixedTemplateWins5
{

	/** @var T */
	public $name;

}

function testPrefixed(
	PrefixedTemplateWins $a,
	PrefixedTemplateWins2 $b,
	PrefixedTemplateWins3 $c,
	PrefixedTemplateWins4 $d,
	PrefixedTemplateWins5 $e

) {
	assertType('PHPStan\Generics\FunctionsAssertType\Bar', $a->name);
	assertType('PHPStan\Generics\FunctionsAssertType\Bar', $b->name);
	assertType('PHPStan\Generics\FunctionsAssertType\Bar', $c->name);
	assertType('PHPStan\Generics\FunctionsAssertType\Bar', $d->name);
	assertType('PHPStan\Generics\FunctionsAssertType\Bar', $e->name);
};

/**
 * @template T of object
 * @param class-string<T> $classString
 * @return T
 */
function acceptsClassStringOfObject(string $classString)
{

}

/**
 * @param class-string $classString
 */
function testClassString(
	string $classString
)
{
	assertType('class-string', $classString);
	assertType('object', acceptsClassString($classString));
	assertType('Exception', acceptsClassStringUpperBound($classString));
	assertType('object', acceptsClassStringOfObject($classString));
}

class Bar extends Foo
{

}

/**
 * @template T of Foo
 * @param class-string<T> $classString
 * @param class-string<Foo> $anotherClassString
 * @param class-string<Bar> $yetAnotherClassString
 */
function returnStaticOnClassString(
	string $classString,
	string $anotherClassString,
	string $yetAnotherClassString
)
{
	assertType('T of PHPStan\Generics\FunctionsAssertType\Foo (function PHPStan\Generics\FunctionsAssertType\returnStaticOnClassString(), argument)', $classString::returnsStatic());
	assertType('T of PHPStan\Generics\FunctionsAssertType\Foo (function PHPStan\Generics\FunctionsAssertType\returnStaticOnClassString(), argument)', $classString::instanceReturnsStatic());
	assertType('T of PHPStan\Generics\FunctionsAssertType\Foo (function PHPStan\Generics\FunctionsAssertType\returnStaticOnClassString(), argument)', $classString::$staticProp);

	assertType('PHPStan\Generics\FunctionsAssertType\Foo', $anotherClassString::instanceReturnsStatic());
	assertType('PHPStan\Generics\FunctionsAssertType\Foo', $anotherClassString::returnsStatic());
	assertType('PHPStan\Generics\FunctionsAssertType\Foo', $anotherClassString::$staticProp);

	assertType('PHPStan\Generics\FunctionsAssertType\Bar', $yetAnotherClassString::instanceReturnsStatic());
	assertType('PHPStan\Generics\FunctionsAssertType\Bar', $yetAnotherClassString::returnsStatic());
	assertType('PHPStan\Generics\FunctionsAssertType\Bar', $yetAnotherClassString::$staticProp);
}

/**
 * @param class-string<Foo>[] $a
 */
function arrayOfGenericClassStrings(array $a): void
{
	assertType('array<class-string<PHPStan\Generics\FunctionsAssertType\Foo>>', $a);
}

/**
 * @template T
 * @template U of \Exception
 * @template V of \DateTimeInterface
 * @template W of object
 * @param T $a
 * @param U $b
 * @param U|V $c
 * @param \Iterator<\DateTimeInterface> $d
 * @param object $object
 * @param mixed $mixed
 * @param W $tObject
 */
function getClassOnTemplateType($a, $b, $c, $d, $object, $mixed, $tObject)
{
	assertType(
		'class-string<T (function PHPStan\Generics\FunctionsAssertType\getClassOnTemplateType(), argument)>|false',
		get_class($a)
	);
	assertType(
		'class-string<U of Exception (function PHPStan\Generics\FunctionsAssertType\getClassOnTemplateType(), argument)>',
		get_class($b)
	);
	assertType(
		'class-string<U of Exception (function PHPStan\Generics\FunctionsAssertType\getClassOnTemplateType(), argument)>|' .
		'class-string<V of DateTimeInterface (function PHPStan\Generics\FunctionsAssertType\getClassOnTemplateType(), argument)>',
		get_class($c)
	);
	assertType('class-string<Iterator<mixed, DateTimeInterface>>', get_class($d));

	$objectB = new $b;
	assertType(
		'U of Exception (function PHPStan\Generics\FunctionsAssertType\getClassOnTemplateType(), argument)',
		$objectB
	);
	assertType(
		'class-string<U of Exception (function PHPStan\Generics\FunctionsAssertType\getClassOnTemplateType(), argument)>',
		get_class($objectB)
	);

	assertType('class-string', get_class($object));
	assertType('class-string|false', get_class($mixed));
	assertType('class-string<W of object (function PHPStan\Generics\FunctionsAssertType\getClassOnTemplateType(), argument)>', get_class($tObject));
}

/**
 * @template T
 */
class TagMergingGrandparent
{
	/** @var T */
	public $property;

	/**
	 * @param T $one
	 * @param int $two
	 */
	public function method($one, $two): void {}
}

/**
 * @template TT
 * @extends TagMergingGrandparent<TT>
 */
class TagMergingParent extends TagMergingGrandparent
{
	/**
	 * @param TT $one
	 */
	public function method($one, $two): void {}
}

/**
 * @extends TagMergingParent<float>
 */
class TagMergingChild extends TagMergingParent
{
	public function method($one, $two): void
	{
		assertType('float', $one);
		assertType('int', $two);
		assertType('float', $this->property);
	}
}

/**
 * @template T
 */
interface GeneralFactoryInterface {
	/**
	 * @return T
	 */
	public static function create();
}

class Car {}

/**
 * @implements GeneralFactoryInterface<Car>
 */
class CarFactory implements GeneralFactoryInterface {
	public static function create() { return new Car(); }
}

class CarFactoryProcessor {
	/**
	 * @param class-string<CarFactory> $class
	 */
	public function process($class): void {
		$car = $class::create();
		assertType(Car::class, $car);
	}
}

function (\Throwable $e): void {
	assertType('(int|string)', $e->getCode());
};

function (): void {
	$array = ['a' => 1, 'b' => 2];
	assertType('array{a: int, b: int}', a($array));
};


/**
 * @template T of bool
 * @param T $b
 * @return T
 */
function boolBound(bool $b): bool
{
	return $b;
}

function (bool $b): void {
	assertType('true', boolBound(true));
	assertType('false', boolBound(false));
	assertType('bool', boolBound($b));
};

/**
 * @template T of float
 * @param T $f
 * @return T
 */
function floatBound(float $f): float
{
	return $f;
}

function (float $f): void {
	assertType('1.0', floatBound(1.0));
	assertType('float', floatBound($f));
};

/**
 * @template T of string|int|float|bool
 */
class UnionT
{

	/**
	 * @param T|null $t
	 * @return T|null
	 */
	public function doFoo($t)
	{
		return $t;
	}

}

/**
 * @param UnionT<string> $foo
 */
function foooo(UnionT $foo): void
{
	assertType('string|null', $foo->doFoo('a'));
}

/**
 * @template T1 of object
 * @param T1 $type
 * @return T1
 */
function newObject($type): void
{
	assertType('T1 of object (function PHPStan\Generics\FunctionsAssertType\newObject(), argument)', new $type);
}

function newStdClass(\stdClass $std): void
{
	assertType('stdClass', new $std);
}

/**
 * @template T1 of object
 * @param class-string<T1> $type
 * @return T1
 */
function newClassString($type): void
{
	assertType('T1 of object (function PHPStan\Generics\FunctionsAssertType\newClassString(), argument)', new $type);
}

/**
 * @template T of array<int, bool>
 * @param T $a
 * @return T
 */
function arrayBound1(array $a): array
{
   return $a;
}

/**
 * @template T of array<string>
 * @param T $a
 * @return T
 */
function arrayBound2(array $a): array
{
	return $a;
}

/**
 * @template T of list<bool>
 * @param T $a
 * @return T
 */
function arrayBound3(array $a): array
{
	return $a;
}

/**
 * @template T of list<array<string, string>>
 * @param T $a
 * @return T
 */
function arrayBound4(array $a): array
{
	return $a;
}

/**
 * @template T of array<string>
 * @param T $a
 * @return array<string>
 */
function arrayBound5(array $a): array
{
	return $a;
}

function (): void {
	assertType('array{1: true}', arrayBound1([1 => true]));
	assertType('array{\'a\', \'b\', \'c\'}', arrayBound2(range('a', 'c')));
	assertType('array<string>', arrayBound2([1, 2, 3]));
	assertType('array{bool, bool, bool}', arrayBound3([true, false, true]));
	assertType('array{array{a: string}, array{b: string}, array{c: string}}', arrayBound4([['a' => 'a'], ['b' => 'b'], ['c' => 'c']]));
	assertType('array<string>', arrayBound5(range('a', 'c')));
};

/**
 * @template T of array{0: string, 1: bool}
 * @param T $a
 * @return T
 */
function constantArrayBound(array $a): array
{
	return $a;
}

function (): void {
	assertType('array{\'string\', true}', constantArrayBound(['string', true]));
};
