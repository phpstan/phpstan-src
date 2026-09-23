<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug15262;

use function PHPStan\dumpType;
use function PHPStan\Testing\assertType;

class JsonResult {
	/** @param array<mixed> $arr */
	public function __construct(
		protected array $arr
	) {}
}

class CoffeeBreak
{
	public function foo(): JsonResult
    {
        $result = [
            'success' => false,
        ];

        $result['success'] = doBar();
        $result['worldid'] = 1;

        return new JsonResult($result);
    }
}

function doBar():mixed {
	return false;
}
