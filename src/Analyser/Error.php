<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use Exception;
use JsonSerializable;
use Nette\Utils\Strings;
use Override;
use PhpParser\Node;
use PHPStan\ShouldNotHappenException;
use ReturnTypeWillChange;
use Throwable;
use function array_key_exists;
use function array_map;
use function count;
use function is_bool;
use function sprintf;

/**
 * @api
 */
final class Error implements JsonSerializable
{

	public const PATTERN_IDENTIFIER = '[a-zA-Z0-9](?:[a-zA-Z0-9\\.]*[a-zA-Z0-9])?';

	/**
	 * Error constructor.
	 *
	 * @param class-string<Node>|null $nodeType
	 * @param mixed[] $metadata
	 * @param list<FixedErrorDiff> $fixedErrorDiffs one diff per fixed file
	 * @param array<string, string> $traitContexts
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
		private array $fixedErrorDiffs = [],
		private array $traitContexts = [],
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
			$this->renameFixedFile($this->getFilePath(), $newFilePath),
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
			$this->traitFilePath === null ? $this->fixedErrorDiffs : $this->renameFixedFile($this->traitFilePath, $newFilePath),
			$this->traitContexts,
		);
	}

	public function removeTraitContext(): self
	{
		if ($this->traitFilePath === null) {
			throw new ShouldNotHappenException();
		}

		return new self(
			$this->message,
			$this->traitFilePath,
			$this->line,
			$this->canBeIgnored,
			$this->traitFilePath,
			$this->traitFilePath,
			$this->tip,
			$this->nodeLine,
			$this->nodeType,
			$this->identifier,
			$this->metadata,
			$this->fixedErrorDiffs,
			$this->traitContexts,
		);
	}

	public function getTraitFilePath(): ?string
	{
		return $this->traitFilePath;
	}

	/**
	 * Using-class contexts of an error deduplicated directly into the trait
	 * (see ConstantConditionInTraitRule): the file path of each class in whose
	 * context the error was reported => the "trait.php (in context of class X)"
	 * file string it was reported with there. An ignoreErrors path pointing at
	 * one of these files accounts for that class's context only.
	 *
	 * @return array<string, string>
	 */
	public function getTraitContexts(): array
	{
		return $this->traitContexts;
	}

	/**
	 * @param array<string, string> $traitContexts
	 */
	public function withTraitContexts(array $traitContexts): self
	{
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
			$this->identifier,
			$this->metadata,
			$this->fixedErrorDiffs,
			$traitContexts,
		);
	}

	/**
	 * Rebuilds the per-context error this deduplicated trait error was merged
	 * from, for the given using-class file path.
	 */
	public function asReportedInTraitContext(string $contextFilePath): self
	{
		if (!array_key_exists($contextFilePath, $this->traitContexts)) {
			throw new ShouldNotHappenException(sprintf('Unknown trait context %s', $contextFilePath));
		}

		return new self(
			$this->message,
			$this->traitContexts[$contextFilePath],
			$this->line,
			$this->canBeIgnored,
			$contextFilePath,
			$this->traitFilePath,
			$this->tip,
			$this->nodeLine,
			$this->nodeType,
			$this->identifier,
			$this->metadata,
			$this->fixedErrorDiffs,
		);
	}

	/**
	 * Rewrites every path this error carries, for portable storage in the result cache. The caller
	 * owns the transformation - see ResultCachePathTransformer, which passes relativizePath() when
	 * storing and absolutizePath() when loading - so both directions apply exactly the same rules
	 * to the error's paths as to the cache's file-path keys.
	 *
	 * @param callable(string): string $transformPath
	 */
	public function transformPaths(callable $transformPath): self
	{
		return new self(
			$this->message,
			$transformPath($this->file),
			$this->line,
			$this->canBeIgnored,
			$this->filePath === null ? null : $transformPath($this->filePath),
			$this->traitFilePath === null ? null : $transformPath($this->traitFilePath),
			$this->tip,
			$this->nodeLine,
			$this->nodeType,
			$this->identifier,
			$this->metadata,
			array_map(static fn (FixedErrorDiff $diff): FixedErrorDiff => new FixedErrorDiff($transformPath($diff->file), $diff->originalHash, $diff->diff), $this->fixedErrorDiffs),
			$this->transformTraitContexts($transformPath),
		);
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
			$this->identifier,
			$this->metadata,
			$this->fixedErrorDiffs,
			$this->traitContexts,
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
			$this->identifier,
			$this->metadata,
			$this->fixedErrorDiffs,
			$this->traitContexts,
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
			$this->fixedErrorDiffs,
			$this->traitContexts,
		);
	}

	/**
	 * @param mixed[] $metadata
	 */
	public function withMetadata(array $metadata): self
	{
		if ($this->metadata !== []) {
			throw new ShouldNotHappenException('Error already has metadata');
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
			$this->identifier,
			$metadata,
			$this->fixedErrorDiffs,
			$this->traitContexts,
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
	 * Whether the rule attached a fix with RuleErrorBuilder::fixNode() or removeNode(),
	 * to be applied with ErrorFixer or `phpstan analyse --fix`.
	 */
	public function isFixable(): bool
	{
		return $this->fixedErrorDiffs !== [];
	}

	/**
	 * @internal Experimental
	 * @deprecated Use getFixedErrorDiffs(), a fix can change several files.
	 */
	public function getFixedErrorDiff(): ?FixedErrorDiff
	{
		// one diff of a fix of several files would apply only a part of the fix
		return count($this->fixedErrorDiffs) === 1 ? $this->fixedErrorDiffs[0] : null;
	}

	/**
	 * The diffs are applied together or not at all.
	 *
	 * @internal Experimental
	 * @return list<FixedErrorDiff>
	 */
	public function getFixedErrorDiffs(): array
	{
		return $this->fixedErrorDiffs;
	}

	/**
	 * @return list<FixedErrorDiff>
	 */
	private function renameFixedFile(string $oldFile, string $newFile): array
	{
		return array_map(
			static fn (FixedErrorDiff $diff): FixedErrorDiff => $diff->file === $oldFile ? new FixedErrorDiff($newFile, $diff->originalHash, $diff->diff) : $diff,
			$this->fixedErrorDiffs,
		);
	}

	#[ReturnTypeWillChange]
	#[Override]
	public function jsonSerialize(): mixed
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
			'fixedErrorDiffs' => array_map(static fn (FixedErrorDiff $diff): array => [$diff->file, $diff->originalHash, $diff->diff], $this->fixedErrorDiffs),
			'traitContexts' => $this->traitContexts,
		];
	}

	/**
	 * @param mixed[] $json
	 */
	public static function decode(array $json): self
	{
		$fixedErrorDiffs = [];
		foreach ($json['fixedErrorDiffs'] ?? [] as [$file, $hash, $diff]) {
			$fixedErrorDiffs[] = new FixedErrorDiff($file, $hash, $diff);
		}

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
			$fixedErrorDiffs,
			$json['traitContexts'] ?? [],
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
			$properties['fixedErrorDiffs'] ?? [],
			$properties['traitContexts'] ?? [],
		);
	}

	/**
	 * @param callable(string): string $transformPath
	 * @return array<string, string>
	 */
	private function transformTraitContexts(callable $transformPath): array
	{
		$result = [];
		foreach ($this->traitContexts as $contextFilePath => $contextFile) {
			$result[$transformPath($contextFilePath)] = $transformPath($contextFile);
		}

		return $result;
	}

	public static function validateIdentifier(string $identifier): bool
	{
		return Strings::match($identifier, '~^' . self::PATTERN_IDENTIFIER . '$~') !== null;
	}

}
