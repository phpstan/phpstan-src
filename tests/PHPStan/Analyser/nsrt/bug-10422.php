<?php declare(strict_types = 1);

namespace Bug10422;

use function PHPStan\Testing\assertType;

class TestClass {
	public function test(): void {}
	public function something(): bool { return true; }
}

function (): void {
	/** @var ?TestClass $test */
	$test = null;

	$error = '';

	if (!$test) {
		$error = 'missing test';
	} else if ($test->something()) {
		$error = 'another';
	}
	if ($error) {
		die('Done');
	}
	assertType('Bug10422\TestClass', $test);
	$test->test();
};
