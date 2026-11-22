<?php declare(strict_types = 1);

namespace PHPStan\IssueBot\Comment;

class BotCommentParserResult
{

	public function __construct(private string $hash, private string $diff, private bool $bleedingEdge)
	{
	}

	public function getHash(): string
	{
		return $this->hash;
	}

	public function getDiff(): string
	{
		return $this->diff;
	}

	public function isBleedingEdge(): bool
	{
		return $this->bleedingEdge;
	}

}
