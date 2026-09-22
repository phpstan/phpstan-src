<?php declare(strict_types = 1);

namespace WalkTraceCallbackScopeMemoReset;

// a rule deriving a scope from its callback scope (invalidateExpression(),
// assignExpression(), mergeWith() ...) must get one that answers afresh: the
// callback scope memoizes asked types by node, and the derived scope is a new
// object in the twin, so the memo must not travel with the native clone
function (\stdClass $o): void {
	if ($o->p === 1) {
		echo $o->p;
	}
};
