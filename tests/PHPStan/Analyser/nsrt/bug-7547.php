<?php

namespace Bug7547;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertVariableCertainty;

function (): void {
	$random = rand(1, 2);

	if($random === 1)
		$input = "foo bar";
	elseif($random === 2)
		$input = array("foo bar");

	assertVariableCertainty(TrinaryLogic::createYes(), $input);
};
