<?php

namespace UnsetOffsetMaybeAccessibleTemplate;

/**
 * @template T of array<string, int>|false
 * @param array<string, int>|T $value
 */
function foo($value): void
{
	unset($value['port']);
}
