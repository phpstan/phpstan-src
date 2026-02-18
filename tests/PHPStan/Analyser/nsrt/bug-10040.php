<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug10040;

use function PHPStan\Testing\assertType;

class A
{
	public string|null $foo;
}

/**
 * @phpstan-assert-if-true !null $a->foo
 */
function assertStringNotNull(A $a): bool
{
	return true;
}

$a1 = new A();
if(assertStringNotNull($a1)){
	assertType('string', $a1->foo);
}

$a2 = new A();
$stringIsNotNull = assertStringNotNull($a2);
if($stringIsNotNull){
	assertType('string', $a2->foo);
}

$a3 = new A();
if($stringIsNotNull = assertStringNotNull($a3)){
	assertType('string', $a3->foo);
}
