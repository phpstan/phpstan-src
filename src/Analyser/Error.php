<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

class Error
{

	/** @var string */
	private $message;

	/** @var string */
	private $file;

	/** @var int|NULL */
	private $line;

	/** @var bool */
	private $canBeIgnored;

	/** @var string|null */
	private $filePath;

	/** @var string|null */
	private $traitFilePath;

	/** @var string|null */
	private $tip;

	public function __construct(
		string $message,
		string $file,
		?int $line = null,
		bool $canBeIgnored = true,
		?string $filePath = null,
		?string $traitFilePath = null,
		?string $tip = null
	)
	{
		$this->message = $message;
		$this->file = $file;
		$this->line = $line;
		$this->canBeIgnored = $canBeIgnored;
		$this->filePath = $filePath;
		$this->traitFilePath = $traitFilePath;
		$this->tip = $tip;
	}

	public function getMessage(): string
	{
		return $this->message;
	}

	public function getFile(): string
	{
		return $this->file;
	}

	public function getFilePath(): string
	{
		if ($this->filePath === null) {
			return $this->file;
		}

		return $this->filePath;
	}

	public function getTraitFilePath(): ?string
	{
		return $this->traitFilePath;
	}

	public function getLine(): ?int
	{
		return $this->line;
	}

	public function canBeIgnored(): bool
	{
		return $this->canBeIgnored;
	}

	public function getTip(): ?string
	{
		return $this->tip;
	}

}
