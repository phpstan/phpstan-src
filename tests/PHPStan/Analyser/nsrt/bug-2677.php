<?php

namespace Bug2677;

use function PHPStan\Testing\assertType;

class A {}
class B {}
class C {}
class D {}
class E {}
class F {}
class G {}
class H {}
class I {}
class J {}
class K {}
class L {}
class M {}
class N {}
class O {}
class P {}

function ()
{
	$classes = [
		A::class,
		B::class,
		C::class,
		D::class,
		E::class,
		F::class,
		G::class,
		H::class,
		I::class,
		J::class,
		K::class,
		L::class,
		M::class,
		N::class,
		O::class,
		P::class,
	];
	assertType('array{\'Bug2677\\\\A\', \'Bug2677\\\\B\', \'Bug2677\\\\C\', \'Bug2677\\\\D\', \'Bug2677\\\\E\', \'Bug2677\\\\F\', \'Bug2677\\\\G\', \'Bug2677\\\\H\', \'Bug2677\\\\I\', \'Bug2677\\\\J\', \'Bug2677\\\\K\', \'Bug2677\\\\L\', \'Bug2677\\\\M\', \'Bug2677\\\\N\', \'Bug2677\\\\O\', \'Bug2677\\\\P\'}', $classes);

	foreach ($classes as $class) {
		assertType('\'Bug2677\\\\A\'|\'Bug2677\\\\B\'|\'Bug2677\\\\C\'|\'Bug2677\\\\D\'|\'Bug2677\\\\E\'|\'Bug2677\\\\F\'|\'Bug2677\\\\G\'|\'Bug2677\\\\H\'|\'Bug2677\\\\I\'|\'Bug2677\\\\J\'|\'Bug2677\\\\K\'|\'Bug2677\\\\L\'|\'Bug2677\\\\M\'|\'Bug2677\\\\N\'|\'Bug2677\\\\O\'|\'Bug2677\\\\P\'', $class);
	}
};
