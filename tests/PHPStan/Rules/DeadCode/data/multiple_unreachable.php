<?php

namespace MultipleUnreachable;

/**
 * @param 'foo' $foo
 */
function foo($foo)
{
    if ($foo === 'foo') {
        return 1;
    }

    echo 'statement 1';
    echo 'statement 2';

    function innerFunction()
    {
        echo 'statement 3';
    }

    echo innerFunction();
}