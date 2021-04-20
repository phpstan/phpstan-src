<?php

namespace EarlyTermination;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertVariableCertainty;

function(): void {
	if (rand(0, 1)) {
		Foo::doBarPhpDoc();
	} else {
		$a = 1;
	}

	assertVariableCertainty(TrinaryLogic::createYes(), $a);
};

function(): void {
	if (rand(0, 1)) {
		(new Foo)->doFooPhpDoc();
	} else {
		$a = 1;
	}

	assertVariableCertainty(TrinaryLogic::createYes(), $a);
};

function(): void {
	if (rand(0, 1)) {
		bazPhpDoc();
	} else {
		$a = 1;
	}

	assertVariableCertainty(TrinaryLogic::createYes(), $a);
};

function(): void {
	if (rand(0, 1)) {
		bazPhpDoc2();
	} else {
		$a = 1;
	}

	assertVariableCertainty(TrinaryLogic::createYes(), $a);
};
