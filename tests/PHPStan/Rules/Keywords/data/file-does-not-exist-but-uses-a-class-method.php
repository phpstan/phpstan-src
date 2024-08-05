<?php declare(strict_types=1);

use PHPStan\Rules\Keywords\ClassThatContainsMethod;

include (new ClassThatContainsMethod())->getFileThatDoesNotExist();
include_once (new ClassThatContainsMethod())->getFileThatDoesNotExist();
require (new ClassThatContainsMethod())->getFileThatDoesNotExist();
require_once (new ClassThatContainsMethod())->getFileThatDoesNotExist();
