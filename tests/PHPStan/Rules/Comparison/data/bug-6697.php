<?php // lint >= 8.1

declare(strict_types=1);

namespace Bug6697;

function foo() {
	$result = \is_subclass_of( '\\My\\Namespace\\MyClass', '\\My\\Namespace\\MyBaseClass', true);
}
