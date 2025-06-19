<?php // lint >= 8.0

namespace NamedArgumentRuleNoErrors;

use Exception;

function (): void {
	new Exception('foo', 0);
	new Exception('foo', 0, null);
};
