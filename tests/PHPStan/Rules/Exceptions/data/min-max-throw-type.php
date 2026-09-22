<?php

namespace MinMaxThrowType;

class Foo
{

	/**
	 * @param list<int> $ints
	 * @param non-empty-list<int> $nonEmptyInts
	 * @param list<list<int>> $listOfLists
	 * @param mixed $mixed
	 */
	public function doFoo(int $i, float $f, array $ints, array $nonEmptyInts, array $listOfLists, $mixed): void
	{
		try {
			$a = min(max(1, $f), 5);
		} catch (\ValueError $e) {

		}
		try {
			$a = max($i, $f, 3);
		} catch (\ValueError $e) {

		}
		try {
			$a = max($ints, $nonEmptyInts);
		} catch (\ValueError $e) {

		}
		try {
			$a = min([1, 2]);
		} catch (\ValueError $e) {

		}
		try {
			$a = max(...[1, 2]);
		} catch (\ValueError $e) {

		}
		try {
			$a = max($nonEmptyInts);
		} catch (\ValueError $e) {

		}
		try {
			$a = max(...$ints);
		} catch (\ValueError $e) {

		}
		try {
			$a = max($ints);
		} catch (\ValueError $e) {

		}
		try {
			$a = min([]);
		} catch (\ValueError $e) {

		}
		try {
			$a = max($mixed);
		} catch (\ValueError $e) {

		}
		try {
			$a = max(...$listOfLists);
		} catch (\ValueError $e) {

		}
	}

	public function doBar(array $ints): void
	{
		if ($ints === []) {
			return;
		}

		try {
			$a = max($ints);
		} catch (\ValueError $e) {

		}
	}

}
