<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use Exception;
use JsonSerializable;
use Nette\Utils\Strings;
use PhpParser\Node;
use PHPStan\ShouldNotHappenException;
use ReturnTypeWillChange;
use Throwable;
use function is_bool;
use function sprintf;

/** @api */
class Error implements JsonSerializable
{

	public const PATTERN_IDENTIFIER = '[a-zA-Z0-9](?:[a-zA-Z0-9\\.]*[a-zA-Z0-9])?';

	/**
	 * Error constructor.
	 *
	 * @param class-string<Node>|null $nodeType
	 * @param mixed[] $metadata
	 */
	public function __construct(
		private string $message,
		private string $file,
		private ?int $line = null,
		private bool|Throwable $canBeIgnored = true,
		private ?string $filePath = null,
		private ?string $traitFilePath = null,
		private ?string $tip = null,
		private ?int $nodeLine = null,
		private ?string $nodeType = null,
		private ?string $identifier = null,
		private array $metadata = [],
	)
	{
		if ($this->identifier !== null && !self::validateIdentifier($this->identifier)) {
			throw new ShouldNotHappenException(sprintf('Invalid identifier: %s', $this->identifier));
		}
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
			throw new ShouldNotHappenException('Errors in traits not yet supported');
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
			$this->metadata,
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
			$this->metadata,
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
		return $this->canBeIgnored instanceof Throwable;
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
			$this->nodeType,
		);
	}

	public function doNotIgnore(): self
	{
		if (!$this->canBeIgnored()) {
			return $this;
		}

		return new self(
			$this->message,
			$this->file,
			$this->line,
			false,
			$this->filePath,
			$this->traitFilePath,
			$this->tip,
			$this->nodeLine,
			$this->nodeType,
		);
	}

	public function withIdentifier(string $identifier): self
	{
		if ($this->identifier !== null) {
			throw new ShouldNotHappenException(sprintf('Error already has an identifier: %s', $this->identifier));
		}

		return new self(
			$this->message,
			$this->file,
			$this->line,
			$this->canBeIgnored,
			$this->filePath,
			$this->traitFilePath,
			$this->tip,
			$this->nodeLine,
			$this->nodeType,
			$identifier,
			$this->metadata,
		);
	}

	public function getNodeLine(): ?int
	{
		return $this->nodeLine;
	}

	/**
	 * @return class-string<Node>|null
	 */
	public function getNodeType(): ?string
	{
		return $this->nodeType;
	}

	/**
	 * Error identifier set via `RuleErrorBuilder::identifier()`.
	 *
	 * List of all current error identifiers in PHPStan: https://phpstan.org/error-identifiers
	 */
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
	#[ReturnTypeWillChange]
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
	 */
	public static function decode(array $json): self
	{
		return new self(
			$json['message'],
			$json['file'],
			$json['line'],
			$json['canBeIgnored'] === 'exception' ? new Exception() : $json['canBeIgnored'],
			$json['filePath'],
			$json['traitFilePath'],
			$json['tip'],
			$json['nodeLine'] ?? null,
			$json['nodeType'] ?? null,
			$json['identifier'] ?? null,
			$json['metadata'] ?? [],
		);
	}

	/**
	 * @param mixed[] $properties
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
			$properties['metadata'] ?? [],
		);
	}

	public static function validateIdentifier(string $identifier): bool
	{
		return Strings::match($identifier, '~^' . self::PATTERN_IDENTIFIER . '$~') !== null;
	}

}
