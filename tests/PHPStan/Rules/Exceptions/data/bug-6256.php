<?php

namespace Bug6256;

class A
{
    public int $intVal;
}

function doFoo()
{
    $a = new A();
    try {
        $a->intVal = "string";
		} catch (\TypeError $e) {
    }
}
