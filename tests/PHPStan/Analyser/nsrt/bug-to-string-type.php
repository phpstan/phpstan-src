<?php

namespace BugToStringType;

class ParentClassWithToStringMixedReturn
{
    public function __toString()
    {
        return 'a';
    }
}

class WithParentMixedReturn extends ParentClassWithToStringMixedReturn
{
    public function __toString()
    {
        return 'value';
    }
}

class Consumer extends WithParentMixedReturn
{
    public function __toString()
    {
        return 'value';
    }
}

function test(Consumer $test): void
{
	assertType('mixed', $test->__toString());
}
