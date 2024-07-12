<?php // lint >= 8.0

namespace UselessFunctionReturnPhp8;

class FooClass
{
	public function explicitReturnNamed(): void
	{
		error_log("Email-Template couldn't be found by parameters:" . print_r(return: true, value: [
				'template' => 1,
				'spracheid' => 2,
			])
		);
	}

	public function explicitNoReturnNamed(): void
	{
		error_log("Email-Template couldn't be found by parameters:" . print_r(return: false, value: [
				'template' => 1,
				'spracheid' => 2,
			])
		);
	}
}
