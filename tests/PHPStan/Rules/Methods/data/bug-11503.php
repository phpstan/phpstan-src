<?php

namespace Bug11503;

class Foo {
	public function test() {
		$date = new \DateTimeImmutable();
		$interval = new \DateInterval('P1M');
		$date->sub($interval);
		$date->add($interval);
		$date->modify('+1 day');
		$date->setDate(2024, 8, 13);
		$date->setISODate(2024, 1);
		$date->setTime(0, 0, 0, 0);
		$date->setTimestamp(1);
		$zone = new \DateTimeZone('UTC');
		$date->setTimezone($zone);
	}
}
