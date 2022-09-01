<?php

namespace SlevomatForeachArrayKeyExistsBug;

use function PHPStan\Testing\assertType;

class Foo
{

	public function doFoo(array $percentageIntervals, array $changes): void
	{
		$intervalResults = [];
		foreach ($percentageIntervals as $percentageInterval) {
			foreach ($changes as $changeInPercents => $itemsCount) {
				if ($percentageInterval->isInInterval((float) $changeInPercents)) {
					$key = $percentageInterval->getFormatted();
					if (array_key_exists($key, $intervalResults)) {
						assertType('aaa', $intervalResults);
						assertType('aaa', $intervalResults[$key]);
						$intervalResults[$key]['itemsCount'] += $itemsCount;
						assertType('aaa', $intervalResults);
						assertType('aaa', $intervalResults[$key]);
					} else {
						assertType('aaa', $intervalResults);
						assertType('aaa', $intervalResults[$key]);
						$intervalResults[$key] = [
							'itemsCount' => $itemsCount,
							'interval' => $percentageInterval,
						];
						assertType('aaa', $intervalResults);
						assertType('aaa', $intervalResults[$key]);
					}
				}
			}
		}

		assertType('aaa', $intervalResults);
		assertType('aaa', $intervalResults);
		foreach ($intervalResults as $data) {
			echo $data['interval'];
		}
	}

}
