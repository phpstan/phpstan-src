<?php declare(strict_types = 1);

namespace Bug14258;

use function PHPStan\dumpType;
use function PHPStan\Testing\assertType;

class HelloWorld
{
	public function getCustomerId(bool $b): string
	{
		$customerId = 'z';
		if ($b) {
			// typo: fails to override the ID
			$cutsomerId = 'x';
		}
		return $customerId;
	}
}
