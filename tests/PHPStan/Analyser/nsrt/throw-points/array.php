<?php

namespace ThrowPoints\Array_;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertVariableCertainty;
use function ThrowPoints\Helpers\doesntThrow;
use function ThrowPoints\Helpers\maybeThrows;

function () {
	try {
		[];
		$foo = 1;
	} finally {
		assertVariableCertainty(TrinaryLogic::createYes(), $foo);
	}
};

function () {
	try {
		[doesntThrow()];
		$foo = 1;
	} finally {
		assertVariableCertainty(TrinaryLogic::createYes(), $foo);
	}
};

function () {
	try {
		[maybeThrows()];
		$foo = 1;
	} finally {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
	}
};

function () {
	try {
		[doesntThrow(), $foo = 1];
	} finally {
		assertVariableCertainty(TrinaryLogic::createYes(), $foo);
	}
};

function () {
	try {
		[maybeThrows(), $foo = 1];
	} finally {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
	}
};

function () {
	try {
		[doesntThrow() => 1, $foo = 1];
	} finally {
		assertVariableCertainty(TrinaryLogic::createYes(), $foo);
	}
};

function () {
	try {
		[maybeThrows() => 1, $foo = 1];
	} finally {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
	}
};
