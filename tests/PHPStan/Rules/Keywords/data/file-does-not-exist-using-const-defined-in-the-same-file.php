<?php declare(strict_types=1);

define('FILE_DOES_NOT_EXIST_INLINE', 'a-file-that-does-not-exist.php');

include FILE_DOES_NOT_EXIST_INLINE;
include_once FILE_DOES_NOT_EXIST_INLINE;
require FILE_DOES_NOT_EXIST_INLINE;
require_once FILE_DOES_NOT_EXIST_INLINE;
