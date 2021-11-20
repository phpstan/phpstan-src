<?php declare(strict_types=1);

namespace Bug6001;

use function PHPStan\Testing\assertType;

assertType('(int|string)', (new \Exception())->getCode());

assertType('(int|string)', (new \RuntimeException())->getCode());

assertType('(int|string)', (new \PDOException())->getCode());
