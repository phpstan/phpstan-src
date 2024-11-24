<?php

namespace DisallowNamedArgumentsInPhpVersionScope;

if (PHP_VERSION_ID >= 80000) {
	class Foo
	{

		public function doFoo(): void
		{
			$this->doBar(i: 1);
		}

		public function doBar(int $i): void
		{

		}

	}
} else {
	class FooBar
	{

		public function doFoo(): void
		{
			$this->doBar(i: 1);
		}

		public function doBar(int $i): void
		{

		}

	}
}
