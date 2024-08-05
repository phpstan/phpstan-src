<?php declare(strict_types=1);

use PHPStan\Rules\Keywords\ClassThatContainsConst;

include ClassThatContainsConst::FILE_DOES_NOT_EXIST;
include_once ClassThatContainsConst::FILE_DOES_NOT_EXIST;
require ClassThatContainsConst::FILE_DOES_NOT_EXIST;
require_once ClassThatContainsConst::FILE_DOES_NOT_EXIST;
