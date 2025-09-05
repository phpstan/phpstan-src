<?php declare(strict_types = 1);

// This file contains an intentional error for testing stop-on-failure
function testFunction(): string
{
    return 123; // Type error: returning int instead of string
}

$undefinedVariable = $nonExistentVar; // Undefined variable error
