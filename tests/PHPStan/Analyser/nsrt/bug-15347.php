<?php declare(strict_types = 1);

namespace Bug15347;

use function PHPStan\Testing\assertType;

/**
 * Computes the 1-based page number containing the first result.
 *
 * @param int<0, max> $firstResult
 * @param int<1, max> $maxResults
 *
 * @return int<1, max>
 */
function computePageNumber(int $firstResult, int $maxResults): int
{
	assertType('int<1, max>', intdiv($firstResult, $maxResults) + 1);

	return intdiv($firstResult, $maxResults) + 1;
}

/**
 * @param int<1, 5> $oneToFive
 * @param int<-7, -1> $minusSevenToMinusOne
 * @param int<-10, 10> $minusTenToTen
 * @param int<-3, 3> $minusThreeToThree
 * @param int<10, 20> $tenToTwenty
 * @param int<0, max> $nonNegative
 * @param int<min, -1> $negative
 * @param positive-int $positive
 * @param 3|int<10, 12> $union
 */
function ranges(
	int $int,
	int $oneToFive,
	int $minusSevenToMinusOne,
	int $minusTenToTen,
	int $minusThreeToThree,
	int $tenToTwenty,
	int $nonNegative,
	int $negative,
	int $positive,
	int $union,
	string $string,
): void
{
	assertType('3', intdiv(7, 2));
	assertType('-3', intdiv(-7, 2));
	assertType('-3', intdiv(7, -2));
	assertType('int<0, 2>', intdiv($oneToFive, 2));
	assertType('int<-3, 0>', intdiv($minusSevenToMinusOne, 2));
	assertType('int<0, 3>', intdiv($minusSevenToMinusOne, -2));
	assertType('int<-10, 10>', intdiv($minusTenToTen, $minusThreeToThree));
	assertType('int<2, 20>', intdiv($tenToTwenty, $oneToFive));
	assertType('int<0, 5>', intdiv(5, $positive));
	assertType('int<min, 0>', intdiv($nonNegative, $negative));
	assertType('int<0, max>', intdiv($negative, $negative));
	assertType('1|int<5, 6>', intdiv($union, 2));
	assertType('int', intdiv($int, $int));
	assertType('int', intdiv($int, 0));
	assertType('int', intdiv($string, 2));
}
