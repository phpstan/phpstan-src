<?php

declare(strict_types = 1);

namespace Bug4896;

use function PHPStan\Testing\assertType;

/** @var \DateTime|\DateInterval $command */
$command = new \DateTime();

switch ($command::class) {
	case \DateTime::class:
		assertType(\DateTime::class, $command);
		var_dump($command->getTimestamp());
		break;
	case \DateInterval::class:
		assertType(\DateInterval::class, $command);
		echo " Hello Date Interval " . $command->format('d');
		break;
}
