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
	}
}

