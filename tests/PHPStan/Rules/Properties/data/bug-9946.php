<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug9946;

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
	$test = $bObj->valA->value;
}
