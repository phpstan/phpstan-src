<?php

namespace Generics\Bug2577;

class A {}
class A1 extends A {}
class A2 extends A {}

/**
 * @template T of A
 *
 * @param \Closure():T $t1
 * @param T $t2
 */
function echoOneOrOther(\Closure $t1, A $t2) : void {
	echo get_class($t1());
	echo get_class($t2);
}

function test(): void {
	echoOneOrOther(
		function () : A1 {
			return new A1;
		},
		new A2
	);
}
