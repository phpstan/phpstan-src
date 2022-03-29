<?php

namespace TryCatchType;

class Foo
{

	public function doFoo(): void
	{
		echo 'foo';
		try {
			echo 'foo';
			function (): void {
				echo 'foo';
			};
			try {
				echo 'foo';
			} catch (\TypeError $e) {

			}
			echo 'foo';
		} catch (\RuntimeException $e) {

		} catch (\LogicException $e) {

		}
	}

}
