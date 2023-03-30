<?php declare(strict_types = 1);

namespace PHPStan\IssueBot\Playground;

class PlaygroundError
{

	public function __construct(private int $line, private string $message, private ?string $identifier)
	{
	}

	public function getLine(): int
	{
		return $this->line;
	}

	public function getMessage(): string
	{
		return $this->message;
	}

	public function getIdentifier(): ?string
	{
		return $this->identifier;
	}

}
