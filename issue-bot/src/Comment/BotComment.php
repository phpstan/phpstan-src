<?php declare(strict_types = 1);

namespace PHPStan\IssueBot\Comment;

use PHPStan\IssueBot\Playground\PlaygroundExample;

class BotComment extends Comment
{

	private string $resultHash;

	public function __construct(
		string $text,
		PlaygroundExample $playgroundExample,
		private string $diff,
	)
	{
		parent::__construct('phpstan-bot', $text, [$playgroundExample]);
		$this->resultHash = $playgroundExample->getHash();
	}

	public function getResultHash(): string
	{
		return $this->resultHash;
	}

	public function getDiff(): string
	{
		return $this->diff;
	}

}
