<?php declare(strict_types=1);

function getFileThatDoesNotExist(): string
{
    return 'a_file_that_does_not_exist.txt';
}

include __DIR__ . '/' . getFileThatDoesNotExist();
include_once __DIR__ . '/' . getFileThatDoesNotExist();
require __DIR__ . '/' . getFileThatDoesNotExist();
require_once __DIR__ . '/' . getFileThatDoesNotExist();
