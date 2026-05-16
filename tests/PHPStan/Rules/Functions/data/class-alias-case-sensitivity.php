<?php declare(strict_types = 1);

// Tests that class_alias with a completely different name does not trigger
// "referenced with incorrect case" errors.
// Relies on: class_alias(ReturnTypes\Foo::class, ReturnTypes\FooAlias::class)
// defined in tests/phpstan-bootstrap.php

namespace ClassAliasCaseSensitivity;

$callback = function (\ReturnTypes\FooAlias $a): \ReturnTypes\FooAlias {
	return $a;
};

// Wrong case of the alias name - should NOT report because it's a class_alias
$callback2 = function (\ReturnTypes\FooAliaS $a): \ReturnTypes\fooalias {
	return $a;
};
