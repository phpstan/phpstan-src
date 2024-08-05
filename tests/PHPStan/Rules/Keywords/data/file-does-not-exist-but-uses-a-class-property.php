<?php declare(strict_types=1);

use PHPStan\Rules\Keywords\ClassThatContainsProperties;

$class = new ClassThatContainsProperties();

include $class->fileDoesNotExist;
include_once $class->fileDoesNotExist;
require $class->fileDoesNotExist;
require_once $class->fileDoesNotExist;
