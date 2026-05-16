<?php declare(strict_types = 1);

namespace Bug14617GroupUse;

class MyClass {}
class AnotherClass {}

namespace Bug14617GroupUse\Consumer;

use Bug14617GroupUse\{MyClass as myclass, AnotherClass as anotherclass};

$callback = function (myclass $a): anotherclass {
	return new anotherclass();
};
