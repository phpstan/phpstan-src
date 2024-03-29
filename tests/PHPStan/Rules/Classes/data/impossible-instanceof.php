<?php

namespace ImpossibleInstanceOf;

interface Foo
{

}

interface Bar
{

}

interface BarChild extends Bar
{

}

class Lorem
{

}

class Ipsum extends Lorem
{

}

class Dolor
{

}

class FooImpl implements Foo
{

}

class Test
{

	public function doTest(
		Foo $foo,
		Bar $bar,
		Lorem $lorem,
		Ipsum $ipsum,
		Dolor $dolor,
		FooImpl $fooImpl,
		BarChild $barChild
	)
	{
		if ($foo instanceof Bar) {

		}
		if ($bar instanceof Foo) {

		}
		if ($lorem instanceof Lorem) {

		}
		if ($lorem instanceof Ipsum) {

		}
		if ($ipsum instanceof Lorem) {

		}
		if ($ipsum instanceof Ipsum) {

		}
		if ($dolor instanceof Lorem) {

		}
		if ($fooImpl instanceof Foo) {

		}
		if ($barChild instanceof Bar) {

		}

		/** @var Collection|mixed[] $collection */
		$collection = doFoo();
		if ($collection instanceof Foo) {

		}

		/** @var object $object */
		$object = doFoo();
		if ($object instanceof Foo) {

		}

		$str = 'str';
		if ($str instanceof Foo) {

		}

		if ($str instanceof $str) {

		}

		if ($foo instanceof $str) {

		}

		$self = new self();
		if ($self instanceof self) {

		}
	}

	public function foreachWithTypeChange()
	{
		$foo = null;
		foreach ([] as $val) {
			if ($foo instanceof self) {

			}
			if ($foo instanceof Lorem) {

			}

			$foo = new self();
			if ($foo instanceof self) {

			}
		}
	}

	public function whileWithTypeChange()
	{
		$foo = null;
		while (fetch()) {
			if ($foo instanceof self) {

			}
			if ($foo instanceof Lorem) {

			}

			$foo = new self();
			if ($foo instanceof self) {

			}
		}
	}

	public function forWithTypeChange()
	{
		$foo = null;
		for (;;) {
			if ($foo instanceof self) {

			}
			if ($foo instanceof Lorem) {

			}

			$foo = new self();
			if ($foo instanceof self) {

			}
		}
	}

}

interface Collection extends \IteratorAggregate
{

}

final class FinalClassWithInvoke
{

	public function __invoke()
	{

	}

}

final class FinalClassWithoutInvoke
{

}

class ClassWithInvoke
{

	public function __invoke()
	{

	}

	public function doFoo(callable $callable, Foo $foo)
	{
		if ($callable instanceof self) {

		}
		if ($callable instanceof FinalClassWithInvoke) {

		}
		if ($callable instanceof FinalClassWithoutInvoke) {

		}
		if ($callable instanceof Foo) {

		}
		if ($callable instanceof Lorem) {

		}
	}

}

class EliminateCompoundTypes
{

	/**
	 * @param Lorem|Dolor $union
	 * @param Foo&Bar $intersection
	 */
	public function doFoo($union, $intersection)
	{
		if ($union instanceof Lorem || $union instanceof Dolor) {

		} elseif ($union instanceof Lorem) {

		}

		if ($intersection instanceof Foo && $intersection instanceof Bar) {

		} elseif ($intersection instanceof Foo) {

		}

		if ($intersection instanceof Foo) {

		} elseif ($intersection instanceof Bar) {

		}
	}

}

class InstanceOfString
{

	/**
	 * @param Foo|Bar|null $fooBarNull
	 */
	public function doFoo($fooBarNull)
	{
		$string = 'Foo';
		if (rand(0, 1) === 1) {
			$string = 'Bar';
		}
		if ($fooBarNull instanceof $string) {
			return;
		}
	}

}

trait TraitWithInstanceOfThis
{

	public function doFoo()
	{
		if ($this instanceof Foo) {

		}
	}

}

class ClassUsingTrait implements Foo
{

	use TraitWithInstanceOfThis;

}

function (\Iterator $arg) {
    foreach ($arg as $key => $value) {
        assert($key instanceof Foo);
    }
};

class ObjectSubtracted
{

	/**
	 * @param object $object
	 */
	public function doBar($object)
	{
		if ($object instanceof \Exception) {
			return;
		}

		if ($object instanceof \Exception) {

		}

		if ($object instanceof \InvalidArgumentException) {

		}
	}

	public function doBaz(Bar $bar)
	{
		if ($bar instanceof BarChild) {
			return;
		}

		if ($bar instanceof BarChild) {

		}

		if ($bar instanceof BarGrandChild) {

		}
	}

}

class BarGrandChild implements BarChild
{

}

class InvalidTypeTest
{
	/**
	 * @template ObjectT of InvalidTypeTest
	 * @template MixedT
	 *
	 * @param mixed $subject
	 * @param int $int
	 * @param object $objectWithoutClass
	 * @param InvalidTypeTest $object
	 * @param int|InvalidTypeTest $intOrObject
	 * @param string $string
	 * @param mixed $mixed
	 * @param mixed $mixed
	 * @param ObjectT $objectT
	 * @param MixedT $mixedT
	 */
	public function doTest($int, $objectWithoutClass, $object, $intOrObject, $string, $mixed, $objectT, $mixedT)
	{
		if ($mixed instanceof $int) {
		}

		if ($mixed instanceof $objectWithoutClass) {
		}

		if ($mixed instanceof $object) {
		}

		if ($mixed instanceof $intOrObject) {
		}

		if ($mixed instanceof $string) {
		}

		if ($mixed instanceof $mixed) {
		}

		if ($mixed instanceof $objectT) {
		}

		if ($mixed instanceof $mixedT) {
		}
	}
}

class CheckInstanceofInIterableForeach
{

	/**
	 * @param iterable<Foo> $items
	 */
	public function test(iterable $items): void
	{
		foreach ($items as $item) {
			if (!$item instanceof Foo) {
				throw new \Exception('Unsupported');
			}
		}
	}

}

class CheckInstanceofWithTemplates
{
	/**
	 * @template T of \Exception
	 * @param T $e
	 */
	public function test(\Throwable $t, $e): void {
		if ($t instanceof $e) return;
		if ($e instanceof $t) return;
	}
}

class CheckGenericClassString
{
	/**
	 * @param \DateTimeInterface $a
	 * @param class-string<\DateTimeInterface> $b
	 * @param class-string<\DateTimeInterface> $c
	 */
	function test($a, $b, $c): void
	{
		if ($a instanceof $b) return;
		if ($b instanceof $a) return;
		if ($b instanceof $c) return;
	}
}

class CheckGenericClassStringWithConstantString
{
	/**
	 * @param class-string<\DateTimeInterface> $a
	 * @param \DateTimeInterface $b
	 */
	function test($a, $b): void
	{
		$t = \DateTimeInterface::class;
		if ($a instanceof $t) return;
		if ($b instanceof $t) return;
	}
}

class CheckInstanceOfLsp
{
	function test(\DateTimeInterface $a, \DateTimeInterface $b): void {
		if ($a instanceof $b) return;
		if ($b instanceof $a) return;
	}
}

class InstanceofBenevolentUnionType
{
	public function doFoo(\SimpleXMLElement $xml)
	{
		echo $xml->branch1 instanceof \SimpleXMLElement;
		echo $xml->branch2->branch3 instanceof \SimpleXMLElement;
	}

}
