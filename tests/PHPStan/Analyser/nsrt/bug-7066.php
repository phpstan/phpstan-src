<?php declare(strict_types = 1);

namespace Bug7066;

use function PHPStan\Testing\assertType;

final class SkipStaticVar
{
    public function run()
    {
        static $static = null;

        if (!$static) {
            $static = new SkipStaticVar();
        }

        assertType('Bug7066\SkipStaticVar', $static);
    }
}
