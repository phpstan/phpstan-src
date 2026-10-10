<?php

// test file for ExpressionTypeResolverExtensionTest

namespace ExpressionTypeResolverExtensionTest;

use function PHPStan\Testing\assertType;

class WhateverClass {
	public function methodReturningBoolNoMatterTheCallerUnlessReturnsString() { return true; }
}
class WhateverClass2 {
	public function methodReturningBoolNoMatterTheCallerUnlessReturnsString() { return true; }
}
class WhateverClass3 {
	public function methodReturningBoolNoMatterTheCallerUnlessReturnsString(): string { return ''; }
}

assertType('bool', (new WhateverClass)->methodReturningBoolNoMatterTheCallerUnlessReturnsString());
assertType('bool', (new WhateverClass2)->methodReturningBoolNoMatterTheCallerUnlessReturnsString());
assertType('string', (new WhateverClass3)->methodReturningBoolNoMatterTheCallerUnlessReturnsString());

// the extension's answer must survive into the assigned variable's holder
$assigned = (new WhateverClass)->methodReturningBoolNoMatterTheCallerUnlessReturnsString();
assertType('bool', $assigned);

$closure = static function () use ($assigned): void {
	assertType('bool', $assigned);
};

// the extension answers a property PHPStan itself does not know - narrowing it must start from that answer
class ClassWithVirtualProperty {}

function (ClassWithVirtualProperty $o): void {
	assertType('string|null', $o->virtualProperty);
	if ($o->virtualProperty !== null) {
		assertType('string', $o->virtualProperty);
	}
	if ($o->virtualProperty === null) {
		return;
	}
	assertType('string', $o->virtualProperty);
};
