<?php declare(strict_types = 1);

namespace Bug10064;

class HelloWorld
{
	public function sayHello(int $check): bool
	{
		$a = random_int(0, 10) > 5 ? 42: null;  // a possibly null var
		$b = random_int(0, 10) > 6 ? 47: null;  // a possibly null var
		if (isset($a, $b)) {
			return $check > $a && $check < $b;
		}
		if (isset($a)) {
			return $check > $a;
		}
		if (isset($b)) {
			return $check < $b;
		}

		return false;
	}
}
