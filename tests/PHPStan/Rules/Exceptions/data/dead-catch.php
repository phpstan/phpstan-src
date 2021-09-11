<?php

namespace DeadCatch;

class Foo
{

	public function doFoo()
	{
		try {
			doFoo();
		} catch (\Exception $e) {

		} catch (\TypeError $e) {

		} catch (\Throwable $t) {

		}
	}

	public function doBar()
	{
		try {
			doFoo();
		} catch (\Throwable $e) {

		} catch (\TypeError $e) {

		}
	}

}
