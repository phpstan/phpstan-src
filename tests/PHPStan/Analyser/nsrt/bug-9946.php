<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug9946;

use function PHPStan\Testing\assertType;

class A {
	public null|int $value = null;
}

class B
{
	public null|A $valA = null;
}

$aObj = new A();
$bObj = new B();

if (($bObj->valA?->value ?? 0) !== 0) {
	assertType('Bug9946\A', $bObj->valA);
	assertType('int<min, -1>|int<1, max>', $bObj->valA->value);
	$test = $bObj->valA->value;
}
