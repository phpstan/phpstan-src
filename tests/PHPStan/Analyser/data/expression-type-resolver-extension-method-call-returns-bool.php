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
