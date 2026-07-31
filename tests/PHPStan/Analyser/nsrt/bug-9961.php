<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug9961;

use function PHPStan\Testing\assertType;

interface Ia {}
interface Ib {}
interface Ic {}
interface Id {}

class A implements Ia, Ib {}

class HelloWorld
{
	/**
	 * @template T of (Ia&Ib)|(Ic&Id)
	 * @param T $a
	 * @return T
	 */
	public function sayHello(Ia|Ic $a): mixed
	{
		if ($a instanceof Ic && $a instanceof Id) {
			assertType('T of Bug9961\Ic&Bug9961\Id (method Bug9961\HelloWorld::sayHello(), argument)', $a);
		} elseif ($a instanceof A) {
			assertType('Bug9961\A&T of Bug9961\Ia&Bug9961\Ib (method Bug9961\HelloWorld::sayHello(), argument)', $a);
		} else {
			throw new \Exception;
		}

		assertType('(Bug9961\A&T of Bug9961\Ia&Bug9961\Ib (method Bug9961\HelloWorld::sayHello(), argument))|T of Bug9961\Ic&Bug9961\Id (method Bug9961\HelloWorld::sayHello(), argument)', $a);

		return $a;
	}
}
