<?php

namespace NestedTraitUse\Framework;

/** @template TBuilder of Builder */
trait HasBuilder
{
    /** @return TBuilder */
    public function newBuilder(): Builder
    {
        return parent::newBuilder();
    }
}
