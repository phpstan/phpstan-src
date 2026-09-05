<?php declare(strict_types = 1);

namespace Bug13833;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertType;
use function PHPStan\Testing\assertVariableCertainty;

$a = (bool) rand(0,1);
$b = (bool) rand(0,1);

if ( $a || $b )
{
	$msg = 'hello';
}

assertVariableCertainty(TrinaryLogic::createMaybe(), $msg);

if ( $a )
{
	assertVariableCertainty(TrinaryLogic::createYes(), $msg);
	assertType("'hello'", $msg);
	echo $msg;
}
