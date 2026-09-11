<?php declare(strict_types = 1);

namespace UnusedVariableFlowMessagesCatch;

/** @return mixed */
function source()
{
	return rand();
}

function catchVariable(): void
{
	try {
		source();
	} catch (\Exception $e) {
		while (rand(0, 1)) {
			$e = rand(0, 1) ? $e : null;
		}
	}
}

function catchVariableCovered(): void
{
	try {
		source();
	} catch (\Exception $e) {
		$copy = $e;
	}
}
