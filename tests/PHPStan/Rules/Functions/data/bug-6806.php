<?php declare(strict_types=1);

namespace Bug6806;

function () {
	// to mimic dynamic type containing various "type"s
	$types = explode(',', $_REQUEST['types']);
	// static version throws same issue, though
	//$types = [ 'a', 'b' ];
	$defs = [ 'first' => [ 'type' => 'a' ], 'second' => [ 'type' => 'b' ] ];

	$types = array_fill_keys($types, true);
	$defs = array_filter($defs, function($def) use(&$types) { return isset($types[$def['type']]); });
};
