<?php // lint >= 8.0

namespace Bug11802b;

class HelloWorld
{
	public function __construct(
	) {}

	private function doBar():void {}

	private function doFooBar():void {}

	public function doFoo(HelloWorld $x, $y): void {
		if ($y !== 'doBar') {
			$s = $x->$y();
		}
	}
}
