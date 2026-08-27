<?php declare(strict_types = 1);

namespace App;

$hog = [];
for ($i = 0; $i < 200; $i++) {
    $hog[] = str_repeat('x', 10 * 1024 * 1024) . $i;
}

interface EatsMemoryWhenAutoloaded
{

    public const ERRORS = [];

}
