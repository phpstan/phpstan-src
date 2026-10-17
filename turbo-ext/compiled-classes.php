<?php declare(strict_types = 1);

/*
 * The shadowed classes whose native implementation is compiled from the PHP
 * twin by turbo-ext/bin/compile-twins.php instead of written by hand: FQCN =>
 * the class-entry variable the rest of the extension uses for the class. The
 * generated file is the one the twin's ShadowedByTurboExtension attribute
 * names.
 */

return [
	'PHPStan\\TrinaryLogic' => 'pt_ce_trinary',
	'PHPStan\\Type\\AcceptsResult' => 'pt_ce_accepts_result',
	'PHPStan\\Type\\IsSuperTypeOfResult' => 'pt_ce_is_super_type_of_result',
];
