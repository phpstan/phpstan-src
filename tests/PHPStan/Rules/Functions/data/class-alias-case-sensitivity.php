<?php declare(strict_types = 1);

namespace ClassAliasCaseSensitivity;

$callback = function (\ReturnTypes\FooAlias $a): \ReturnTypes\FooAlias {
	return $a;
};
