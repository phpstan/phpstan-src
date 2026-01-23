<?php

namespace NestedTraitUse\Src;

use NestedTraitUse\Framework\Model;

class ChildModel extends Model
{
    /** @use HasBuilder<CustomBuilder> */
    use HasBuilder;
	protected static string $builder = CustomBuilder::class;
}
