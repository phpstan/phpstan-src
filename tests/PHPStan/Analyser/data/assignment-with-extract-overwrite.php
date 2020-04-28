<?php declare(strict_types=1);

namespace Analyser\AssignWithExctractFunction;

use function PHPStan\Analyser\assertType;


$overwrite = [
	'headline' => 'Test',
	'body' => null,
	'items' => [],
];

extract($overwrite, EXTR_OVERWRITE);

assertType('\'Test\'', $headline);
assertType('null', $body);
assertType('array()', $items);


$implicitOverwrite = [
	'headline' => 'Test2',
	'body' => 'Test',
];

extract($implicitOverwrite);

assertType('\'Test2\'', $headline);
assertType('\'Test\'', $body);
