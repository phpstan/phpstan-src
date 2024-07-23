<?php

declare(strict_types=1);

namespace Repro\Site\Domain\Model;

use DateTime;

class Issue
{
    protected array $settings;

    protected ?SchoolYear $parentSchoolYear = null;

    protected string $title;

    protected int $startDate;

    protected string $holidayTitle;


    public function __construct()
    {
    }

    public function getParentSchoolYear(): SchoolYear
    {
        return $this->parentSchoolYear;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getStartDate(): int
    {
        return $this->startDate;
    }

    public function getHolidayTitle(): string
    {
        return $this->holidayTitle;
    }
}
