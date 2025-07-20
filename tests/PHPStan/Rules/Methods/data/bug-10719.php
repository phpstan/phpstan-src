<?php declare(strict_types = 1);

namespace Bug10719;

use DateTime;

$dt1 = null;
$dt2 = new DateTime();

if (rand(0, 1)) {
	$dt1 = (clone $dt2)->setTimestamp($dt2->getTimestamp() + 1000);
}

if ($dt1 > $dt2) {
	echo $dt1->getTimestamp();
}
