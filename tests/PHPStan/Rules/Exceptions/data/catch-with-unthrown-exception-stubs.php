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

namespace CatchWithUnthrownExceptionStubs
{

	use Doctrine\ORM\EntityManager;

	class Foo
	{
		public function doFoo(EntityManager $em): void
		{
			try {
				$em->transactional(function () {
					throw new \InvalidArgumentException();
				});
			} catch (\InvalidArgumentException $e) {

			}
		}

		public function doFoo2(): void
		{
			try {
				\MyFunction\doFoo(function () {
					throw new \InvalidArgumentException();
				});
			} catch (\InvalidArgumentException $e) {

			}
		}

		public function doFoo3(array $a): void
		{
			try {
				uksort($a, function () {
					throw new \InvalidArgumentException();
				});
			} catch (\InvalidArgumentException $e) {

			}
		}

		public function doFoo4(\SQLite3 $sqlite): void
		{
			try {
				$sqlite->createFunction('foo', function () {
					throw new \InvalidArgumentException();
				});
			} catch (\InvalidArgumentException $e) {

			}
		}
	}

}
