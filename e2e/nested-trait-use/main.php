<?php

use NestedTraitUse\Src\ChildModel;

use function PHPStan\Testing\assertType;

function test(ChildModel $model): void
{
	assertType('NestedTraitUse\Src\CustomBuilder', $model->newBuilder());
}
