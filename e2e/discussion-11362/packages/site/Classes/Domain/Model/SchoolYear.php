<?php

declare(strict_types=1);

namespace Repro\Site\Domain\Model;

class SchoolYear
{
    protected int $startDate;

    protected int $endDate;

    protected int $introStartDate;

    protected int $introEndDate;

    public function __construct()
    {
    }

    public function getStartDate(): int
    {
        return $this->startDate;
    }

    public function getEndDate(): int
    {
        return $this->endDate;
    }

    public function getIntroStartDate(): int
    {
        return $this->introStartDate;
    }

    public function getIntroEndDate(): int
    {
        return $this->introEndDate;
    }
}
