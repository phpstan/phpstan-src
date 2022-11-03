<?php declare(strict_types = 1);

namespace ArrayCastListTypes;

/**
 * @param list<mixed> $var
 */
function foo($var): void {}

/**
 * @var non-empty-string $nonEmptyString
 * @var non-falsy-string $nonFalsyString
 * @var numeric-string $numericString
 * @var resource $resource
 */

foo((array) true);
foo((array) 'literal');
foo((array) 1.0);
foo((array) 1);
foo((array) $resource);
foo((array) (fn () => 'closure'));
foo((array) $nonEmptyString);
foo((array) $nonFalsyString);
foo((array) $numericString);
