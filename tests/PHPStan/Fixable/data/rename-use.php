<?php declare(strict_types = 1);

namespace RenameUse;

use RenameUseOther\Foo;
use RenameUseOther\Foo as Lorem;

function doFoo(Foo $foo, Lorem $lorem): void
{
}
