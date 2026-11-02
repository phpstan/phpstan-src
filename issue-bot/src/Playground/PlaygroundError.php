<?php declare(strict_types = 1);

namespace PHPStan\IssueBot\Playground;

class PlaygroundError
{

	public function __construct(private int $line, private string $message, private ?string $identifier, private bool $fixable = false)
	{
	}

	public function getLine(): int
	{
		return $this->line;
	}

	public function getMessage(): string
	{
		// shown like in the table output, so that an error becoming fixable shows up in the diff
		return $this->fixable ? $this->message . ' 🔧' : $this->message;
	}

	public function getIdentifier(): ?string
	{
		return $this->identifier;
	}

}
