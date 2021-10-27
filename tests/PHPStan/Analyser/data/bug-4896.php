<?php

declare(strict_types = 1);

namespace Bug4896;

/** @var \DateTime|\DateInterval $command */
$command = new \DateTime();

switch ($command::class) {
	case \DateTime::class:
		var_dump($command->getTimestamp());
		break;
	case \DateInterval::class:
		echo " Hello Date Interval " . $command->format('d');
		break;
}
