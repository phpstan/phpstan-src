<?php

namespace ConstantConditionFunctionCall;

function retObj(): object
{
	return new \stdClass();
}

function doFoo(int $int): void
{
	// reported by the ImpossibleCheckType rule, NOT by the constant-condition rule
	if (is_int($int)) {
		echo 'always';
	}

	// reported by the constant-condition rule: an always-truthy return
	// that is not a type-check
	if (retObj()) {
		echo 'always';
	}
}
