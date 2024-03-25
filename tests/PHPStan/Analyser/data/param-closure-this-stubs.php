<?php

namespace Doctrine\ORM {
	class EntityManager
	{

		public function transactional(callable $cb): void {

		}

	}
}

namespace MyFunction {
	function doFoo(callable $cb): void {

	}
}

namespace ParamClosureThisStubs
{

	use Doctrine\ORM\EntityManager;
	use function PHPStan\Testing\assertType;

	class Foo
	{
		public function doFoo(EntityManager $em): void
		{
			$em->transactional(function () {
				assertType(EntityManager::class, $this);
			});
		}

		public function doFoo2(): void
		{
			\MyFunction\doFoo(function () {
				assertType(\MyFunction\Foo::class, $this);
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
