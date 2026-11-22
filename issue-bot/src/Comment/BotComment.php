<?php declare(strict_types = 1);

namespace PHPStan\IssueBot\Comment;

use PHPStan\IssueBot\Playground\PlaygroundExample;

class BotComment extends Comment
{

	private string $resultHash;

	// not promoted so that comments unserialized from an older issue cache get the default
	private bool $bleedingEdge = false; // phpcs:ignore SlevomatCodingStandard.Classes.RequireConstructorPropertyPromotion.RequiredConstructorPropertyPromotion

	public function __construct(
		string $text,
		PlaygroundExample $playgroundExample,
		private string $diff,
		bool $bleedingEdge,
	)
	{
		parent::__construct('phpstan-bot', $text, [$playgroundExample]);
		$this->resultHash = $playgroundExample->getHash();
		$this->bleedingEdge = $bleedingEdge;
	}

	public function getResultHash(): string
	{
		return $this->resultHash;
	}

	public function getDiff(): string
	{
		return $this->diff;
	}

	/** Whether the comment reports the result with bleeding edge enabled for a snippet that does not enable it. */
	public function isBleedingEdge(): bool
	{
		return $this->bleedingEdge;
	}

}
