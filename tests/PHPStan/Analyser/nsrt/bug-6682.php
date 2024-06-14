<?php

declare(strict_types = 1);

namespace Bug6682;

use function PHPStan\Testing\assertType;

class Example
{
    /**
     * @param array<int, array<string, string|array<string,string>|null>> $data
     */
    public function __construct(array $data)
    {
        $x = array_column($data, null, 'type');
		assertType('array<int|string, array<string, array<string, string>|string|null>>', $x);
    }
}
