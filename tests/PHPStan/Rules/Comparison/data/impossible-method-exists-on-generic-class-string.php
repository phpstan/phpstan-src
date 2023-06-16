<?php

namespace ImpossibleMethodExistsOnGenericClassString;

class HelloWorld
{
	/**
	 * @param class-string<S>&literal-string $s
	 */
	public function sayGenericHello(string $s): void
	{
		// no errors on non-final class
		if (method_exists($s, 'nonExistent')) {
			$s->nonExistent();
			$s::nonExistent();
		}

		if (method_exists($s, 'staticAbc')) {
			$s::staticAbc();
			$s->staticAbc();
		}

		if (method_exists($s, 'nonStaticAbc')) {
			$s::nonStaticAbc();
			$s->nonStaticAbc();
		}
	}

	/**
	 * @param class-string<FinalS>&literal-string $s
	 */
	public function sayFinalGenericHello(string $s): void
	{
		if (method_exists($s, 'nonExistent')) {
			$s->nonExistent();
			$s::nonExistent();
		}

		if (method_exists($s, 'staticAbc')) {
			$s::staticAbc();
			$s->staticAbc();
		}

		if (method_exists($s, 'nonStaticAbc')) {
			$s::nonStaticAbc();
			$s->nonStaticAbc();
		}
	}
}

class S {
	public static function staticAbc():void {}

	public function nonStaticAbc():void {}
}

final class FinalS {
	public static function staticAbc():void {}

	public function nonStaticAbc():void {}
}
