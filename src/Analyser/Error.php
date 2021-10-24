<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

/** @api */
class Error implements \JsonSerializable
{

	private string $message;

	private string $file;

	private ?int $line;

	/** @var bool|\Throwable */
	private $canBeIgnored;

	private ?string $filePath;

	private ?string $traitFilePath;

	private ?string $tip;

	private ?int $nodeLine;

	/** @phpstan-var class-string<\PhpParser\Node>|null */
	private ?string $nodeType;

	private ?string $identifier;

	/** @var mixed[] */
	private array $metadata;

	/**
	 * Error constructor.
	 *
	 * @param string $message
	 * @param string $file
	 * @param int|null $line
	 * @param bool|\Throwable $canBeIgnored
	 * @param string|null $filePath
	 * @param string|null $traitFilePath
	 * @param string|null $tip
	 * @param int|null $nodeLine
	 * @param class-string<\PhpParser\Node>|null $nodeType
	 * @param string|null $identifier
	 * @param mixed[] $metadata
	 */
	public function __construct(
		string $message,
		string $file,
		?int $line = null,
		$canBeIgnored = true,
		?string $filePath = null,
		?string $traitFilePath = null,
		?string $tip = null,
		?int $nodeLine = null,
		?string $nodeType = null,
		?string $identifier = null,
		array $metadata = []
	)
	{
		$this->message = $message;
		$this->file = $file;
		$this->line = $line;
		$this->canBeIgnored = $canBeIgnored;
		$this->filePath = $filePath;
		$this->traitFilePath = $traitFilePath;
		$this->tip = $tip;
		$this->nodeLine = $nodeLine;
		$this->nodeType = $nodeType;
		$this->identifier = $identifier;
		$this->metadata = $metadata;
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

	public function changeFilePath(string $newFilePath): self
	{
		if ($this->traitFilePath !== null) {
			throw new \PHPStan\ShouldNotHappenException('Errors in traits not yet supported');
		}

		return new self(
			$this->message,
			$newFilePath,
			$this->line,
			$this->canBeIgnored,
			$newFilePath,
			null,
			$this->tip,
			$this->nodeLine,
			$this->nodeType,
			$this->identifier,
			$this->metadata
		);
	}

	public function changeTraitFilePath(string $newFilePath): self
	{
		return new self(
			$this->message,
			$this->file,
			$this->line,
			$this->canBeIgnored,
			$this->filePath,
			$newFilePath,
			$this->tip,
			$this->nodeLine,
			$this->nodeType,
			$this->identifier,
			$this->metadata
		);
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
		return $this->canBeIgnored === true;
	}

	public function hasNonIgnorableException(): bool
	{
		return $this->canBeIgnored instanceof \Throwable;
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
			$this->nodeLine,
			$this->nodeType
		);
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
	 * @return mixed[]
	 */
	public function getMetadata(): array
	{
		return $this->metadata;
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
			'canBeIgnored' => is_bool($this->canBeIgnored) ? $this->canBeIgnored : 'exception',
			'filePath' => $this->filePath,
			'traitFilePath' => $this->traitFilePath,
			'tip' => $this->tip,
			'nodeLine' => $this->nodeLine,
			'nodeType' => $this->nodeType,
			'identifier' => $this->identifier,
			'metadata' => $this->metadata,
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
			$json['canBeIgnored'] === 'exception' ? new \Exception() : $json['canBeIgnored'],
			$json['filePath'],
			$json['traitFilePath'],
			$json['tip'],
			$json['nodeLine'] ?? null,
			$json['nodeType'] ?? null,
			$json['identifier'] ?? null,
			$json['metadata'] ?? []
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
			$properties['nodeLine'] ?? null,
			$properties['nodeType'] ?? null,
			$properties['identifier'] ?? null,
			$properties['metadata'] ?? []
		);
	}

}
