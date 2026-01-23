<?php

namespace NestedTraitUse\Src;

use NestedTraitUse\Framework\Builder;
use NestedTraitUse\Framework\HasBuilder as BaseHasBuilder;

/** @template TBuilder of Builder */
trait HasBuilder
{
    /** @use BaseHasBuilder<TBuilder> */
    use BaseHasBuilder;
}
