<?php

namespace Bug12767;

use function PHPStan\Testing\assertType;

class SkipDynamicVariable
{
	public function bar(): void
	{
		$employee = (object) ['data' => ['dd1' => 1, 'dd2' => 2]];

		for ($i=1; $i <= 2; $i++) {
			${'field'.$i} = $employee->data['dd'.$i];

			assertType('int', ${'field'.$i});
		}
	}
}
