<?php

namespace Bug13310;

/**
 * @return array<mixed>
 */
function get_array(): array
{
	return [];
}

$tests = get_array();

foreach ($tests as $test) {

// information fichiers
	$test['information'] = '';
	$test['information'] .= $test['a'] ? 'test' : '';
	$test['information'] .= $test['b'] ? 'test' : '';
	$test['information'] .= $test['c'] ? 'test' : '';
	$test['information'] .= $test['d'] ? 'test' : '';
	$test['information'] .= $test['e'] ? 'test' : '';
	$test['information'] .= $test['f'] ? 'test' : '';
	$test['information'] .= $test['g'] ? 'test' : '';
	$test['information'] .= $test['h'] ? 'test' : '';

}
