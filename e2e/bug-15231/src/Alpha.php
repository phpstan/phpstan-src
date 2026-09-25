<?php

namespace Demo;

class Alpha
{
    public function label(): string
    {
        return (new Zeta)->name();
    }
}
