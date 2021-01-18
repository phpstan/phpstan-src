<?php

namespace Getopt;

use function getopt;
use function PHPStan\Testing\assertType;

$opts = getopt("ab:c::", ["longopt1", "longopt2:", "longopt3::"]);
assertType('(array<string, array<int, mixed>|string|false>|false)', $opts);
