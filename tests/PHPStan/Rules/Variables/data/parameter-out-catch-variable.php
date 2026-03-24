<?php

namespace ParameterOutCatchVariable;

/**
 * @param-out int $p
 */
function foo(&$p): void {
	try {
		throw new \Exception();
	} catch (\Exception $p) {

	}
}

function bar(int &$p): void {
	try {
		throw new \Exception();
	} catch (\Exception $p) {

	}
}
