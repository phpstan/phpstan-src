<?php // lint >= 8.0

namespace WrongAssertNativeNamespace;

use function SomeWrong\Namespace\assertNativeType;

function doFoo(string $s) {
	assertNativeType('string', $s);
}
