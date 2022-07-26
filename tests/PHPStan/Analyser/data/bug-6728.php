<?php

namespace Bug6728;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	/** @return array{success: false, errorReason: string}|array{success: true, id: int} */
	public function sayHello(): array
	{
		if (date('d') === '01') {
			return ['success' => false, 'errorReason' => 'Test'];
		}

		return ['success' => true, 'id' => 1];
	}

	public function test(): void
	{
		$retArr = $this->sayHello();
		assertType('array{success: false, errorReason: string}|array{success: true, id: int}', $retArr);
		if ($retArr['success'] === true) {
			assertType('array{success: true, id: int}', $retArr);
			assertType('true', isset($retArr['id']));
			assertType('int', $retArr['id']);
		} else {
			assertType('array{success: false, errorReason: string}', $retArr);
			assertType('string', $retArr['errorReason']);
		}
	}
}
