<?php

declare(strict_types=1);

namespace Repro\Site\Domain\Model;

class ContentPage
{
    public const CONTENT_PAGE_TYPE_CONTENT_ELEMENTS = 'content-elements';

    public const CONTENT_PAGE_TYPE_KARAOKE_PLAYER = 'karaoke-player';

    protected ?Issue $parentIssue = null;

    protected ?Lesson $parentLesson = null;

    protected string $title;

    protected string $type;

    protected bool $navigationVisible;

    protected string $navigationColor;

    public function __construct()
    {
    }

    public function getParentIssue(): ?Issue
    {
        return $this->parentIssue;
    }

    public function getParentLesson(): ?Lesson
    {
        return $this->parentLesson;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getNavigationVisible(): bool
    {
        return $this->navigationVisible;
    }

    public function getNavigationColor(): string
    {
        return $this->navigationColor;
    }
}

