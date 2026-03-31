<?php declare(strict_types = 1);

namespace Bug13688;

$inputs = [ '', ':' ];

foreach ( $inputs as $input )
{
	$inputLen = \strlen($input);
	$hasTrailingColon = $inputLen > 0 && $input[$inputLen-1] === ':';
	echo $hasTrailingColon ? "{$input} has trailing colon\n" : "{$input} does not have trailing colon\n";
}
