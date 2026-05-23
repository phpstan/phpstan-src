<?php declare(strict_types = 1);

namespace Bug10090;

use function PHPStan\Testing\assertType;
use function PHPStan\Testing\assertVariableCertainty;
use PHPStan\TrinaryLogic;

function doFoo(): void {
	if (rand(0,1)) {
		$shortcut_id = 1;
	}

	$link_mode = isset($shortcut_id) ? "remove" : "add";
	assertType("'add'|'remove'", $link_mode);

	if ($link_mode === "add") {
		assertVariableCertainty(TrinaryLogic::createNo(), $shortcut_id);
	}
	if ($link_mode === "remove") {
		assertVariableCertainty(TrinaryLogic::createYes(), $shortcut_id);
		assertType('1', $shortcut_id);
	}
}

function nullableVariable(): void {
	if (rand(0,1)) {
		$x = rand(0,1) ? 'hello' : null;
	}

	$mode = isset($x) ? "found" : "missing";

	if ($mode === "missing") {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $x);
	}
	if ($mode === "found") {
		assertVariableCertainty(TrinaryLogic::createYes(), $x);
		assertType("'hello'", $x);
	}
}

function definitelyDefined(): void {
	$x = rand(0,1) ? 'hello' : null;

	$mode = isset($x) ? "found" : "missing";

	if ($mode === "missing") {
		assertVariableCertainty(TrinaryLogic::createYes(), $x);
		assertType('null', $x);
	}
	if ($mode === "found") {
		assertVariableCertainty(TrinaryLogic::createYes(), $x);
		assertType("'hello'", $x);
	}
}

function shortTernary(): void {
	if (rand(0,1)) {
		$x = 1;
	}

	$mode = isset($x) ? "remove" : "add";
	if ($mode !== "add") {
		assertVariableCertainty(TrinaryLogic::createYes(), $x);
		assertType('1', $x);
	}
}

function withBoolean(): void {
	if (rand(0,1)) {
		$x = 1;
	}

	$exists = isset($x);
	if (!$exists) {
		assertVariableCertainty(TrinaryLogic::createNo(), $x);
	}
	if ($exists) {
		assertVariableCertainty(TrinaryLogic::createYes(), $x);
		assertType('1', $x);
	}
}
