<?php

namespace Bug12273;

function doFoo():void {
	$map = [
		'datetime' => \DateTime::class,
		'stdclass' => \stdClass::class,
	];

	$settings = json_decode('{"class": "datetim"}');

	\PHPStan\dumpType($map);
	\PHPStan\dumpType($settings->class);

	new ($map[$settings->class])();
}
