<?php declare(strict_types=1);

define('FILE_DOES_NOT_EXIST_INLINE', 'a_file_that_does_not_exist.txt');

include __DIR__ . '/' . FILE_DOES_NOT_EXIST_INLINE;
include_once __DIR__ . '/' . FILE_DOES_NOT_EXIST_INLINE;
require __DIR__ . '/' . FILE_DOES_NOT_EXIST_INLINE;
require_once __DIR__ . '/' . FILE_DOES_NOT_EXIST_INLINE;
