<?php // lint >= 8.2

namespace FirstClassCallableNativeTemplate;

use PDOStatement;
use WeakReference;
use function PHPStan\Testing\assertType;

class Foo
{

}

function (PDOStatement $stmt, Foo $foo): void {
	assertType('Closure<T of mixed, TArray of array<T>>(TArray, int=): true', sort(...));

	$create = WeakReference::create(...);
	assertType('static-Closure<TIn of object>(TIn): WeakReference<TIn>', $create);
	assertType('WeakReference<FirstClassCallableNativeTemplate\Foo>', $create($foo));

	$fetch = $stmt->fetchObject(...);
	assertType('FirstClassCallableNativeTemplate\Foo|false', $fetch(Foo::class));
};
