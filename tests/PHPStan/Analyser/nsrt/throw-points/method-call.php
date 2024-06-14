<?php

namespace ThrowPoints\MethodCall;

use PHPStan\TrinaryLogic;
use ThrowPoints\Helpers\ThrowPointTestObject;
use function PHPStan\Testing\assertVariableCertainty;
use function ThrowPoints\Helpers\doesntThrow;
use function ThrowPoints\Helpers\maybeThrows;

function () {
	$obj = new ThrowPointTestObject();
	try {
		$obj->throws();
		$foo = 1;
	} finally {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
	}
};

function () {
	$obj = new ThrowPointTestObject();
	try {
		$obj->maybeThrows();
		$foo = 1;
	} finally {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
	}
};

function () {
	$obj = new ThrowPointTestObject();
	try {
		$obj->doesntThrow();
		$foo = 1;
	} finally {
		assertVariableCertainty(TrinaryLogic::createYes(), $foo);
	}
};

function () {
	try {
		doesntThrow()->{$foo = 1}($bar = 2);
	} finally {
		assertVariableCertainty(TrinaryLogic::createYes(), $foo);
		assertVariableCertainty(TrinaryLogic::createYes(), $bar);
	}
};

function () {
	try {
		maybeThrows()->{$foo = 1}($bar = 2);
	} finally {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
		assertVariableCertainty(TrinaryLogic::createMaybe(), $bar);
	}
};

function () {
	$obj = new ThrowPointTestObject();
	try {
		$obj->{doesntThrow()}($foo = 1);
	} finally {
		assertVariableCertainty(TrinaryLogic::createYes(), $foo);
	}
};

function () {
	$obj = new ThrowPointTestObject();
	try {
		$obj->{maybeThrows()}($foo = 1);
	} finally {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
	}
};

function () {
	$obj = new ThrowPointTestObject();
	try {
		$obj->doesntThrow(doesntThrow(), $foo = 1);
	} finally {
		assertVariableCertainty(TrinaryLogic::createYes(), $foo);
	}
};

function () {
	$obj = new ThrowPointTestObject();
	try {
		$obj->doesntThrow(maybeThrows(), $foo = 1);
	} finally {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
	}
};
