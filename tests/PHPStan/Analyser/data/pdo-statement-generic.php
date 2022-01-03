<?php

namespace PdoStatementGeneric;

use PDOStatement;
use PDO;

use function PHPStan\Testing\assertType;

class Foo {
    public function bar(PDO $pdo) {
        $query = 'select question_id, title from question_view';

        /** @var PDOStatement<array{question_id: int, title: string}> */
        $questions = $pdo->query($query, PDO::FETCH_ASSOC);

        foreach($questions as $question) {
            assertType('int', $question['question_id']);
            assertType('string', $question['title']);
        }
    }

	public function foobar(PDOStatement $statement) {
		assertType('PdoStatementGeneric\Foo|false', $statement->fetchObject(Foo::class));
	}
}


