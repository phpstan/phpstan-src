<?php declare(strict_types = 1);

namespace PHPStan\IssueBot\Issue;

use PHPStan\IssueBot\Comment\Comment;

class Issue
{

	/**
	 * @param Comment[] $comments
	 */
	public function __construct(private int $number, private array $comments)
	{
	}

	public function getNumber(): int
	{
		return $this->number;
	}

	/**
	 * @return Comment[]
	 */
	public function getComments(): array
	{
		return $this->comments;
	}

}
