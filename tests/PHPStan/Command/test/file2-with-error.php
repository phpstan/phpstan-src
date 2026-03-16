<?php declare(strict_types = 1);

// This file also contains intentional errors for testing stop-on-failure
class TestClass
{
    public function methodWithError(): int
    {
        return "not an integer"; // Type error: returning string instead of int
    }
}

$obj = new TestClass();
$result = $obj->nonExistentMethod(); // Method does not exist error
