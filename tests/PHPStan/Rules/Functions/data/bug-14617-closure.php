<?php declare(strict_types = 1);

namespace Bug14617Closure;

class MyClass {}

namespace Bug14617Closure\Consumer;

use Bug14617Closure\MyClass as myclass;

$callback = function (myclass $a): myclass {
	return $a;
};
