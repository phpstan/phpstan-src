<?php

namespace InstanceOfNamespace;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use function PHPStan\Testing\assertType;

interface BarInterface
{

}

abstract class BarParent
{

}

class Foo extends BarParent
{

	public function someMethod(Expr $foo)
	{
		$bar = $foo;
		$baz = doFoo();
		$intersected = new Foo();
		$parent = doFoo();

		if ($baz instanceof Foo) {
			// ...
		} else {
			while ($foo instanceof ArrayDimFetch) {
				assert($lorem instanceof Lorem);
				if ($dolor instanceof Dolor && $sit instanceof Sit) {
					if ($static instanceof static) {
						if ($self instanceof self) {
							if ($intersected instanceof BarInterface) {
								if ($this instanceof BarInterface) {
									if ($parent instanceof parent) {
										assertType('PhpParser\Node\Expr\ArrayDimFetch', $foo);
										assertType('PhpParser\Node\Expr', $bar);
										assertType('*ERROR*', $baz);
										assertType('InstanceOfNamespace\Lorem', $lorem);
										assertType('InstanceOfNamespace\Dolor', $dolor);
										assertType('InstanceOfNamespace\Sit', $sit);
										assertType('InstanceOfNamespace\Foo', $self);
										assertType('static(InstanceOfNamespace\Foo)', $static);
										assertType('static(InstanceOfNamespace\Foo)', clone $static);
										assertType('InstanceOfNamespace\BarInterface&InstanceOfNamespace\Foo', $intersected);
										assertType('$this(InstanceOfNamespace\Foo)&InstanceOfNamespace\BarInterface', $this);
										assertType('InstanceOfNamespace\BarParent', $parent);
									}
								}
							}
						}
					}
				}
			}
		}
	}

	/**
	 * @template ObjectT of BarInterface
	 * @template MixedT
	 *
	 * @param class-string<Foo> $classString
	 * @param class-string<Foo>|class-string<BarInterface> $union
	 * @param class-string<Foo>&class-string<BarInterface> $intersection
	 * @param BarInterface $instance
	 * @param ObjectT $objectT
	 * @param class-string<ObjectT> $objectTString
	 * @param class-string<MixedT> $mixedTString
	 * @param object $object
	 */
	public function testExprInstanceof($subject, string $classString, $union, $intersection, string $other, $instance, $objectT, $objectTString, $mixedTString, string $string, $object)
	{
		assertType('bool', $subject instanceof $classString);
		if ($subject instanceof $classString) {
			assertType('InstanceOfNamespace\Foo', $subject);
			assertType('true', $subject instanceof Foo);
			assertType('bool', $subject instanceof $classString);
		} else {
			assertType('mixed~InstanceOfNamespace\Foo', $subject);
			assertType('false', $subject instanceof Foo);
			assertType('false', $subject instanceof $classString);
		}

		$constantString = 'InstanceOfNamespace\BarParent';

		assertType('bool', $subject instanceof $constantString);
		if ($subject instanceof $constantString) {
			assertType('InstanceOfNamespace\BarParent', $subject);
			assertType('true', $subject instanceof BarParent);
			assertType('true', $subject instanceof $constantString);
		} else {
			assertType('mixed~InstanceOfNamespace\BarParent', $subject);
			assertType('false', $subject instanceof BarParent);
			assertType('false', $subject instanceof $constantString);
		}

		assertType('bool', $subject instanceof $union);
		if ($subject instanceof $union) {
			assertType('InstanceOfNamespace\BarInterface|InstanceOfNamespace\Foo', $subject);
			assertType('bool', $subject instanceof $union);
			assertType('bool', $subject instanceof BarInterface);
			assertType('bool', $subject instanceof Foo);
			assertType('true', $subject instanceof Foo || $subject instanceof BarInterface);
		}

		if ($subject instanceof $intersection) {
			assertType('InstanceOfNamespace\BarInterface&InstanceOfNamespace\Foo', $subject);
			assertType('bool', $subject instanceof $intersection);
			assertType('true', $subject instanceof BarInterface);
			assertType('true', $subject instanceof Foo);
		}

		if ($subject instanceof $instance) {
			assertType('InstanceOfNamespace\BarInterface', $subject);
			assertType('bool', $subject instanceof $instance);
			assertType('true', $subject instanceof BarInterface);
		}

		if ($subject instanceof $other) {
			assertType('object', $subject);
			assertType('bool', $subject instanceof $other);
		} else {
			assertType('mixed', $subject);
			assertType('bool', $subject instanceof $other);
		}

		if ($subject instanceof $objectT) {
			assertType('ObjectT of InstanceOfNamespace\BarInterface (method InstanceOfNamespace\Foo::testExprInstanceof(), argument)', $subject);
			assertType('bool', $subject instanceof $objectT);
		} else {
			assertType('mixed~ObjectT of InstanceOfNamespace\BarInterface (method InstanceOfNamespace\Foo::testExprInstanceof(), argument)', $subject);
			assertType('bool', $subject instanceof $objectT); // can be false
		}

		if ($subject instanceof $objectTString) {
			assertType('ObjectT of InstanceOfNamespace\BarInterface (method InstanceOfNamespace\Foo::testExprInstanceof(), argument)', $subject);
			assertType('bool', $subject instanceof $objectTString);
		} else {
			assertType('mixed~ObjectT of InstanceOfNamespace\BarInterface (method InstanceOfNamespace\Foo::testExprInstanceof(), argument)', $subject);
			assertType('bool', $subject instanceof $objectTString); // can be false
		}

		if ($subject instanceof $mixedTString) {
			assertType('MixedT (method InstanceOfNamespace\Foo::testExprInstanceof(), argument)&object', $subject);
			assertType('bool', $subject instanceof $mixedTString);
		} else {
			assertType('mixed~MixedT (method InstanceOfNamespace\Foo::testExprInstanceof(), argument)', $subject);
			assertType('bool', $subject instanceof $mixedTString); // can be false
		}

		if ($subject instanceof $string) {
			assertType('object', $subject);
			assertType('bool', $subject instanceof $string);
		} else {
			assertType('mixed', $subject);
			assertType('bool', $subject instanceof $string);
		}

		if ($object instanceof $string) {
			assertType('object', $object);
			assertType('bool', $object instanceof $string);
		} else {
			assertType('object', $object);
			assertType('bool', $object instanceof $string);
		}

		if ($object instanceof $object) {
			assertType('object', $object);
			assertType('bool', $object instanceof $object);
		} else {
			assertType('object', $object);
			assertType('bool', $object instanceof $object);
		}

		if ($object instanceof $classString) {
			assertType('InstanceOfNamespace\Foo', $object);
			assertType('bool', $object instanceof $classString);
		} else {
			assertType('object~InstanceOfNamespace\Foo', $object);
			assertType('false', $object instanceof $classString);
		}

		if ($instance instanceof $string) {
			assertType('InstanceOfNamespace\BarInterface', $instance);
			assertType('bool', $instance instanceof $string);
		} else {
			assertType('InstanceOfNamespace\BarInterface', $instance);
			assertType('bool', $instance instanceof $string);
		}

		if ($instance instanceof $object) {
			assertType('InstanceOfNamespace\BarInterface', $instance);
			assertType('bool', $instance instanceof $object);
		} else {
			assertType('InstanceOfNamespace\BarInterface', $instance);
			assertType('bool', $object instanceof $object);
		}

		if ($instance instanceof $classString) {
			assertType('InstanceOfNamespace\BarInterface&InstanceOfNamespace\Foo', $instance);
			assertType('bool', $instance instanceof $classString);
		} else {
			assertType('InstanceOfNamespace\BarInterface', $instance);
			assertType('bool', $instance instanceof $classString);
		}
	}

}
