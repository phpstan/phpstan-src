<?php declare(strict_types = 1);

namespace PHPStan\IssueBot\Playground;

class PlaygroundResultTab
{

	/**
	 * @param list<PlaygroundError> $errors
	 */
	public function __construct(private string $title, private array $errors)
	{
	}

	public function getTitle(): string
	{
		return $this->title;
	}

	/**
	 * @return list<PlaygroundError>
	 */
	public function getErrors(): array
	{
		return $this->errors;
	}

}
