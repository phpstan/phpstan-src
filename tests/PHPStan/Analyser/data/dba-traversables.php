<?php

namespace DatabaseAccessTraversables;


use mysqli_result;
use PDOStatement;
use Traversable;
use function PHPStan\Testing\assertType;

class HelloWorld
{
	/**
	 * @param PDOStatement $r
	 * @param Traversable<array{email: string, adaid: int<-32768, 32767>}> $t
	 */
	public function donCollapsePDOStatementAndTraversable($r, $t): void
	{
		$x = $r;
		if (rand(0,1)) {
			$x = $t;
		}
		assertType('PDOStatement|Traversable<mixed, array{email: string, adaid: int<-32768, 32767>}>', $x);
	}

	/**
	 * @param PDOStatement&Traversable<array{email: string, adaid: int<-32768, 32767>}> $t
	 */
	public function allowPDOStatementIntersection($t): void
	{
		assertType('PDOStatement&Traversable<mixed, array{email: string, adaid: int<-32768, 32767>}>', $t);
		foreach($t as $row) {
			assertType('array{email: string, adaid: int<-32768, 32767>}', $row);
		}
		assertType('array|null', $t->fetchAll());
	}

	/**
	 * @param mysqli_result $r
	 * @param Traversable<array{email: string, adaid: int<-32768, 32767>}> $t
	 */
	public function donCollapseMysqliResultAndTraversable($r, $t): void
	{
		$x = $r;
		if (rand(0,1)) {
			$x = $t;
		}
		assertType('mysqli_result|Traversable<mixed, array{email: string, adaid: int<-32768, 32767>}>', $x);
	}

	/**
	 * @param mysqli_result&Traversable<array{email: string, adaid: int<-32768, 32767>}> $t
	 */
	public function allowMysqliResultIntersection($t): void
	{
		assertType('mysqli_result&Traversable<mixed, array{email: string, adaid: int<-32768, 32767>}>', $t);
		foreach($t as $row) {
			assertType('array{email: string, adaid: int<-32768, 32767>}', $row);
		}
		assertType('array|null', $t->fetch_row());
	}
}
