<?php declare(strict_types = 1);

namespace Bug13920;

class TestOutput {
	public function __construct() {}
}

final class MyTest
{
	public function testRun(): void
	{
		$savedArgv = $_SERVER['argv'];

		try {
			$output = new class() extends TestOutput {
				public function __construct()
				{
					parent::__construct();
				}
			};
		} finally {
			$_SERVER['argv'] = $savedArgv;
		}
	}
}
