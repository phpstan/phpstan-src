<?php

namespace Doctrine\ORM {
	class EntityManagerParamClosureThis
	{

		public function transactional(callable $cb): void {

		}

	}
}

namespace MyFunctionClosureThis {
	function doFoo(callable $cb): void {

	}
}

namespace ParamClosureThisStubs
{

	use Doctrine\ORM\EntityManagerParamClosureThis;
	use function PHPStan\Testing\assertType;

	class Foo
	{
		public function doFoo(EntityManagerParamClosureThis $em): void
		{
			$em->transactional(function () {
				assertType(EntityManagerParamClosureThis::class, $this);
			});
		}

		public function doFoo2(): void
		{
			\MyFunctionClosureThis\doFoo(function () {
				assertType(\MyFunctionClosureThis\Foo::class, $this);
			});
		}

		public function doFoo3(array $a): void
		{
			uksort($a, function () {
				assertType(\stdClass::class, $this);
			});
		}

		/**
		 * @param \Ds\Deque<int> $deque
		 */
		public function doFoo4(\Ds\Deque $deque): void
		{
			$deque->filter(function () {
				assertType('Ds\Deque<int>', $this);
			});
		}
	}

}
