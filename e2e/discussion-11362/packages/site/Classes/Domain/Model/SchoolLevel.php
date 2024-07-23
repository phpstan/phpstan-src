<?php

declare(strict_types=1);

namespace Repro\Site\Domain\Model;

class SchoolLevel
{
    protected string $title;

    public function getTitle(): string
    {
        return $this->title;
    }
}
