<?php

use function PHPStan\Testing\assertType;

assertType('list<string>', password_algos());
