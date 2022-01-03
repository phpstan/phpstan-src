<?php

namespace MysqliResultGeneric;

use mysqli;
use mysqli_result;
use function PHPStan\Testing\assertType;

class Foo {
	public function bar(mysqli $mysqli) {
		$query = 'select question_id, title from question_view';

		/** @var mysqli_result<array{question_id: int, title: string}> */
		$questions = $mysqli->query($query);

		foreach($questions as $question) {
			assertType('int', $question['question_id']);
			assertType('string', $question['title']);
		}

		assertType('MysqliResultGeneric\Foo|false|null', $questions->fetch_object(Foo::class));
		assertType('MysqliResultGeneric\Foo|false|null', $questions->fetch_object(__CLASS__));
	}

	/**
	 * @param mysqli_result<array{question_id: int, title: string}> $result
	 * @return void
	 */
	public function fn(mysqli_result $result) {
		assertType('MysqliResultGeneric\Foo|false|null', mysqli_fetch_object($result, Foo::class));
		assertType('MysqliResultGeneric\Foo|false|null', mysqli_fetch_object($result, __CLASS__));
	}
}

