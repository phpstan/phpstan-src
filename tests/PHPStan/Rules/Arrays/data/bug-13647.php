<?php declare(strict_types = 1);

namespace Bug13647Rule;

$a = [];
$a[0] = [ 0, 1 ];

for ( $i = 1; $i < 6; $i++ )
{
	$a[$i] = [ $i, $i+1 ];
}

for ( $i = 1; $i < 6; $i++ )
{
	$b = $a;

	$b[$i][0] = $a[$i-1][0];
	$b[$i][1] = $a[$i-1][1];

	$a = $b;
}
