<?php

namespace CallMethodsDeadCatch;

class Foo
{

	public function doFoo(): void
	{
		try {
			$a = 1;
		} catch (\InvalidArgumentException $e) {
			$e->getMessage();
			$e->nonExistentMethod();
			$this->nonExistentMethod();
		}
	}

	public function doBar(): void
	{
		try {
			$this->doFoo();
		} catch (\Throwable $e) {

		} catch (\InvalidArgumentException $e) {
			$e->nonExistentMethod();
		}
	}

}
