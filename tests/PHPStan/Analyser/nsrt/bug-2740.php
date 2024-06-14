<?php

namespace Bug2740;

use function PHPStan\Testing\assertType;

function (Member $member): void
{
	foreach ($member as $i) {
		assertType('Bug2740\Member', $i);
	}
};
