<?php

declare(strict_types=1);

namespace Repro\Site\Domain\Model;

class Lesson
{
    protected ?SchoolLevel $schoolLevel = null;

    protected ?Issue $parentIssue = null;

    protected int $lessonNumber;

    public function getSchoolLevel(): ?SchoolLevel
    {
        return $this->schoolLevel;
    }

    public function getParentIssue(): ?Issue
    {
        return $this->parentIssue;
    }

    public function getLessonNumber(): int
    {
        return $this->lessonNumber;
    }
}
