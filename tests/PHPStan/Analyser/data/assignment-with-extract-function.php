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
	'headline2' => 'Test',
	'body' => 'Test',
];

extract($implicitOverwrite);

assertType('\'Test\'', $headline2);
assertType('\'Test\'', $body);


$skip = [
	'headline' => 'Test Skip',
	'does_not_already_exist' => 'Test Skip',
];

extract($skip, EXTR_SKIP);

assertType('\'Test\'', $headline);
assertType('\'Test Skip\'', $does_not_already_exist);
