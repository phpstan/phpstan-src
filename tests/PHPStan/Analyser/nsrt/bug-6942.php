<?php declare(strict_types = 1);

namespace Bug6942;

use function PHPStan\Testing\assertType;

$adminClasses = getClassesBySubject (1);
assertType('array<int, array<string, int>>', $adminClasses);
$adminClasses [] ['class_id'] = -1;
assertType('non-empty-array<int, array<string, int>>', $adminClasses);

foreach ($adminClasses as $class)
{
    if ($class ['class_id'] != -1)
    {
		echo "hi";
	}
}

/**
 * @param int $subject
 *
 * @return array<int,array<string,int>>
 */
function getClassesBySubject (int $subject): array
{
	// some database stuff goes here...
	return [
		[
			'class_id' => 3,
		]
	];
}
