<?php declare(strict_types = 1);

namespace PHPStan\IssueBot\Comment;

class BotCommentParserResult
{

	public function __construct(private string $hash, private string $diff)
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

}
