<?php declare(strict_types = 1); // lint >= 8.4

namespace Bug13001;

$bcNumber = new \BcMath\Number(-1);

// invalid

var_dump(
	$bcNumber > 0.2,
	0.2 <=> $bcNumber,
);

// probably invalid, but PHPStan currently allows these comparisons:

var_dump(
	$bcNumber < true,
	null <= $bcNumber,
	fopen('php://stdin', 'r') >= $bcNumber,
	new \stdClass() == $bcNumber,
);

// valid

var_dump(
	$bcNumber < 0,
	$bcNumber < '0.2',
	$bcNumber < new \BcMath\Number(3),
);
var_dump(
	$bcNumber <= 0,
	$bcNumber <= '0.2',
	$bcNumber <= new \BcMath\Number(3),
);
var_dump(
	$bcNumber > 0,
	$bcNumber > '0.2',
	$bcNumber > new \BcMath\Number(3),
);
var_dump(
	$bcNumber >= 0,
	$bcNumber >= '0.2',
	$bcNumber >= new \BcMath\Number(3),
);
var_dump(
	$bcNumber == 0,
	$bcNumber == '0.2',
	$bcNumber == new \BcMath\Number(3),
);
var_dump(
	$bcNumber != 0,
	$bcNumber != '0.2',
	$bcNumber != new \BcMath\Number(3),
);
var_dump(
	$bcNumber <=> 0,
	$bcNumber <=> '0.2',
	$bcNumber <=> new \BcMath\Number(3),
);
var_dump(
	0 < $bcNumber,
	'0.2' < $bcNumber,
);
var_dump(
	0 <= $bcNumber,
	'0.2' <= $bcNumber,
);
var_dump(
	0 > $bcNumber,
	'0.2' > $bcNumber,
);
var_dump(
	0 >= $bcNumber,
	'0.2' >= $bcNumber,
);
var_dump(
	0 == $bcNumber,
	'0.2' == $bcNumber,
);
var_dump(
	0 != $bcNumber,
	'0.2' != $bcNumber,
);
var_dump(
	0 <=> $bcNumber,
	'0.2' <=> $bcNumber,
);
