<?php declare(strict_types = 1);

namespace Bug7385;

use function PHPStan\Testing\assertType;

class Model
{
	public function assertHasIface(): void
	{
		throw new \Error('For static analysis only, $this type is narrowed by MethodTypeSpecifyingExtension');
	}
}

interface Iface
{
}

function () {
	$m = random_int(0, 1) === 0 ? new Model() : new class() extends Model implements Iface {};
	assertType('Bug7385\Model', $m);
	$m->assertHasIface();
	assertType('Bug7385\Iface&Bug7385\Model', $m);
};
