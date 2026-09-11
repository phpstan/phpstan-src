<?php declare(strict_types = 1);

namespace Bug13833Rule;

$a = (bool) rand(0,1);
$b = (bool) rand(0,1);

if ( $a || $b )
{
	$msg = 'hello';
}

if ( $a )
{
	echo $msg;
}
