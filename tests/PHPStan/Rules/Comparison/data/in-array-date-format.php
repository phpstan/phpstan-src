<?php

namespace InArrayDateFormat;

class Foo
{

	public function doFoo(array $a, \DateTimeImmutable $dt)
	{
		$result = [];
		foreach ($a as $k => $v) {
			$result[$k] = $dt->format('d');
		}

		$d = new \DateTimeImmutable();
		if (in_array($d->format('d'), $result, true)) {

		}

		if (in_array('01', $result, true)) {

		}

		$day = $d->format('d');
		if (rand(0, 1)) {
			$day = '32';
		}

		if (in_array($day, $result, true)) {

		}
	}

	/**
	 * @param non-empty-array<int, 'a'> $a
	 */
	public function doBar(array $a, int $i)
	{
		if (in_array('a', $a, true)) {

		}

		if (in_array('b', $a, true)) {

		}

		if (in_array($i, [], true)) {

		}
	}

	/**
	 * @param array<int, string> $a
	 */
	public function doBaz(array $a, int $i, string $s)
	{
		if (in_array($s, $a, true)) {

		}

		if (in_array($i, $a, true)) {

		}
	}

	/**
	 * @param non-empty-array<int, 'a'|'b'> $a
	 */
	public function doLorem(array $a, int $i)
	{
		if (in_array('a', $a, true)) {

		}

		if (in_array('b', $a, true)) {

		}
	}

}
