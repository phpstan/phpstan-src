<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

class Error implements \JsonSerializable
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

	/** @var bool */
	private $warning;

	/** @var int|null */
	private $nodeLine;

	/** @var class-string<\PhpParser\Node>|null */
	private $nodeType;

	/** @var string|null */
	private $identifier;

	/**
	 * Error constructor.
	 *
	 * @param string $message
	 * @param string $file
	 * @param int|null $line
	 * @param bool $canBeIgnored
	 * @param string|null $filePath
	 * @param string|null $traitFilePath
	 * @param string|null $tip
	 * @param bool $warning
	 * @param int|null $nodeLine
	 * @param class-string<\PhpParser\Node>|null $nodeType
	 * @param string|null $identifier
	 */
	public function __construct(
		string $message,
		string $file,
		?int $line = null,
		bool $canBeIgnored = true,
		?string $filePath = null,
		?string $traitFilePath = null,
		?string $tip = null,
		bool $warning = false,
		?int $nodeLine = null,
		?string $nodeType = null,
		?string $identifier = null
	)
	{
		$this->message = $message;
		$this->file = $file;
		$this->line = $line;
		$this->canBeIgnored = $canBeIgnored;
		$this->filePath = $filePath;
		$this->traitFilePath = $traitFilePath;
		$this->tip = $tip;
		$this->warning = $warning;
		$this->nodeLine = $nodeLine;
		$this->nodeType = $nodeType;
		$this->identifier = $identifier;
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

	public function withoutTip(): self
	{
		if ($this->tip === null) {
			return $this;
		}

		return new self(
			$this->message,
			$this->file,
			$this->line,
			$this->canBeIgnored,
			$this->filePath,
			$this->traitFilePath,
			null,
			$this->warning,
			$this->nodeLine,
			$this->nodeType
		);
	}

	public function isWarning(): bool
	{
		return $this->warning;
	}

	public function getNodeLine(): ?int
	{
		return $this->nodeLine;
	}

	/**
	 * @return class-string<\PhpParser\Node>|null
	 */
	public function getNodeType(): ?string
	{
		return $this->nodeType;
	}

	public function getIdentifier(): ?string
	{
		return $this->identifier;
	}

	/**
	 * @return mixed
	 */
	public function jsonSerialize()
	{
		return [
			'message' => $this->message,
			'file' => $this->file,
			'line' => $this->line,
			'canBeIgnored' => $this->canBeIgnored,
			'filePath' => $this->filePath,
			'traitFilePath' => $this->traitFilePath,
			'tip' => $this->tip,
			'warning' => $this->warning,
			'nodeLine' => $this->nodeLine,
			'nodeType' => $this->nodeType,
			'identifier' => $this->identifier,
		];
	}

	/**
	 * @param mixed[] $json
	 * @return self
	 */
	public static function decode(array $json): self
	{
		return new self(
			$json['message'],
			$json['file'],
			$json['line'],
			$json['canBeIgnored'],
			$json['filePath'],
			$json['traitFilePath'],
			$json['tip'],
			$json['warning'],
			$json['nodeLine'] ?? null,
			$json['nodeType'] ?? null,
			$json['identifier'] ?? null
		);
	}

	/**
	 * @param mixed[] $properties
	 * @return self
	 */
	public static function __set_state(array $properties): self
	{
		return new self(
			$properties['message'],
			$properties['file'],
			$properties['line'],
			$properties['canBeIgnored'],
			$properties['filePath'],
			$properties['traitFilePath'],
			$properties['tip'],
			$properties['warning'],
			$properties['nodeLine'] ?? null,
			$properties['nodeType'] ?? null,
			$properties['identifier'] ?? null
		);
	}

}
