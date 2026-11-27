<?php

namespace WeirdArrayKeyExistsIssue;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	/**
	 * @param int[] $data
	 * @return array<int|string, array<string, float|int>>
	 */
	public function doFoo(array $data): array
	{
		if (count($data) === 0) {
			return [];
		}

		arsort($data);
		$locationData = [];
		$otherData = [];
		$i = 0;
		$total = array_sum($data);
		foreach ($data as $location => $count) {
			assertType('int<0, max>', $i);
			if ($i < 5) {
				$locationData[$location] = [
					'abs' => $count,
					'rel' => $count / $total * 100,
				];
			} else {
				$key = 'Ostatní';
				assertType('bool', array_key_exists($key, $otherData));
				assertType('array<\'Ostatní\', array{abs: int, rel: (float|int)}>', $otherData);
				if (!array_key_exists($key, $otherData)) {
					$otherData[$key] = [
						'abs' => 0,
						'rel' => 0,
					];
					assertType('array{Ostatní: array{abs: 0, rel: 0}}', $otherData);
				}
				$otherData[$key]['abs'] += $count;
				$otherData[$key]['rel'] += $count / $total * 100;
				assertType('array{Ostatní: array{abs: int, rel: (float|int)}}', $otherData);
			}
			$i++;
		}

		return array_merge($locationData, $otherData);
	}
}
