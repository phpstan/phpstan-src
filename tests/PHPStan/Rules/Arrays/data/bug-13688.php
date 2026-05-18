<?php declare(strict_types = 1);

namespace Bug13688;

$inputs = [ '', ':' ];

foreach ( $inputs as $input )
{
	$inputLen = \strlen($input);
	$hasTrailingColon = $inputLen > 0 && $input[$inputLen-1] === ':';
	echo $hasTrailingColon ? "{$input} has trailing colon\n" : "{$input} does not have trailing colon\n";
}

/** @var 'a'|'abc' $str */
$str = 'a';
echo $str[0];

/** @var 'a'|'abc' $str2 */
$str2 = 'a';
echo $str2[2];

/** @var ''|'foo'|'barbaz' $str3 */
$str3 = 'foo';
echo $str3[4];

/** @var string|array<int, string> $mixed */
$mixed = 'test';
echo $mixed[0];
