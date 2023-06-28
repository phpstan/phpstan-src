<?php // lint >= 8.0

namespace CoalesceRuleVoid;

/** @return array|void */
function get_post_custom_keys($maybe = false)
{
	if ($maybe) {
		return;
	}

	return [];
}

function foo_bar(): void
{
	return;
}

$test1 = get_post_custom_keys(true) ?? 'foo';

$test2 = foo_bar() ?? 'bar';
