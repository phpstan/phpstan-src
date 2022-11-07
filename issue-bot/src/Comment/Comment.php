<?php declare(strict_types = 1);

namespace PHPStan\IssueBot\Comment;

use PHPStan\IssueBot\Playground\PlaygroundExample;

class Comment
{

	/**
	 * @param non-empty-list<PlaygroundExample> $playgroundExamples
	 */
	public function __construct(
		private string $author,
		private string $text,
		private array $playgroundExamples,
	)
	{
	}

	public function getAuthor(): string
	{
		return $this->author;
	}

	public function getText(): string
	{
		return $this->text;
	}

	/**
	 * @return non-empty-list<PlaygroundExample>
	 */
	public function getPlaygroundExamples(): array
	{
		return $this->playgroundExamples;
	}

}
