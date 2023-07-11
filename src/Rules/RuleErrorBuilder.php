<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PHPStan\Analyser\Error;
use PHPStan\ShouldNotHappenException;
use function array_map;
use function class_exists;
use function count;
use function implode;
use function sprintf;

/**
 * @api
 * @template-covariant T of RuleError
 */
class RuleErrorBuilder
{

	private const TYPE_MESSAGE = 1;
	private const TYPE_LINE = 2;
	private const TYPE_FILE = 4;
	private const TYPE_TIP = 8;
	private const TYPE_IDENTIFIER = 16;
	private const TYPE_METADATA = 32;
	private const TYPE_NON_IGNORABLE = 64;

	private int $type;

	/** @var mixed[] */
	private array $properties;

	/** @var list<string> */
	private array $tips = [];

	private function __construct(string $message)
	{
		$this->properties['message'] = $message;
		$this->type = self::TYPE_MESSAGE;
	}

	/**
	 * @return array<int, array{string, string|null, string|null, string|null}>
	 */
	public static function getRuleErrorTypes(): array
	{
		return [
			self::TYPE_MESSAGE => [
				RuleError::class,
				'message',
				'string',
				'string',
			],
			self::TYPE_LINE => [
				LineRuleError::class,
				'line',
				'int',
				'int',
			],
			self::TYPE_FILE => [
				FileRuleError::class,
				'file',
				'string',
				'string',
			],
			self::TYPE_TIP => [
				TipRuleError::class,
				'tip',
				'string',
				'string',
			],
			self::TYPE_IDENTIFIER => [
				IdentifierRuleError::class,
				'identifier',
				'string',
				'string',
			],
			self::TYPE_METADATA => [
				MetadataRuleError::class,
				'metadata',
				'array',
				'mixed[]',
			],
			self::TYPE_NON_IGNORABLE => [
				NonIgnorableRuleError::class,
				null,
				null,
				null,
			],
		];
	}

	/**
	 * @return self<RuleError>
	 */
	public static function message(string $message): self
	{
		return new self($message);
	}

	/**
	 * @phpstan-this-out self<T&LineRuleError>
	 * @return self<T&LineRuleError>
	 */
	public function line(int $line): self
	{
		$this->properties['line'] = $line;
		$this->type |= self::TYPE_LINE;

		return $this;
	}

	/**
	 * @phpstan-this-out self<T&FileRuleError>
	 * @return self<T&FileRuleError>
	 */
	public function file(string $file): self
	{
		$this->properties['file'] = $file;
		$this->type |= self::TYPE_FILE;

		return $this;
	}

	/**
	 * @phpstan-this-out self<T&TipRuleError>
	 * @return self<T&TipRuleError>
	 */
	public function tip(string $tip): self
	{
		$this->tips = [$tip];
		$this->type |= self::TYPE_TIP;

		return $this;
	}

	/**
	 * @phpstan-this-out self<T&TipRuleError>
	 * @return self<T&TipRuleError>
	 */
	public function addTip(string $tip): self
	{
		$this->tips[] = $tip;
		$this->type |= self::TYPE_TIP;

		return $this;
	}

	/**
	 * @phpstan-this-out self<T&TipRuleError>
	 * @return self<T&TipRuleError>
	 */
	public function discoveringSymbolsTip(): self
	{
		return $this->tip('Learn more at https://phpstan.org/user-guide/discovering-symbols');
	}

	/**
	 * @param list<string> $reasons
	 * @phpstan-this-out self<T&TipRuleError>
	 * @return self<T&TipRuleError>
	 */
	public function acceptsReasonsTip(array $reasons): self
	{
		foreach ($reasons as $reason) {
			$this->addTip($reason);
		}

		return $this;
	}

	/**
	 * Sets an error identifier.
	 *
	 * List of all current error identifiers in PHPStan: https://phpstan.org/error-identifiers
	 *
	 * @phpstan-this-out self<T&IdentifierRuleError>
	 * @return self<T&IdentifierRuleError>
	 */
	public function identifier(string $identifier): self
	{
		if (!Error::validateIdentifier($identifier)) {
			throw new ShouldNotHappenException(sprintf('Invalid identifier: %s', $identifier));
		}

		$this->properties['identifier'] = $identifier;
		$this->type |= self::TYPE_IDENTIFIER;

		return $this;
	}

	/**
	 * @param mixed[] $metadata
	 * @phpstan-this-out self<T&MetadataRuleError>
	 * @return self<T&MetadataRuleError>
	 */
	public function metadata(array $metadata): self
	{
		$this->properties['metadata'] = $metadata;
		$this->type |= self::TYPE_METADATA;

		return $this;
	}

	/**
	 * @phpstan-this-out self<T&NonIgnorableRuleError>
	 * @return self<T&NonIgnorableRuleError>
	 */
	public function nonIgnorable(): self
	{
		$this->type |= self::TYPE_NON_IGNORABLE;

		return $this;
	}

	/**
	 * @return T
	 */
	public function build(): RuleError
	{
		/** @var class-string<T> $className */
		$className = sprintf('PHPStan\\Rules\\RuleErrors\\RuleError%d', $this->type);
		if (!class_exists($className)) {
			throw new ShouldNotHappenException(sprintf('Class %s does not exist.', $className));
		}

		$ruleError = new $className();
		foreach ($this->properties as $propertyName => $value) {
			$ruleError->{$propertyName} = $value;
		}

		if (count($this->tips) > 0) {
			if (count($this->tips) === 1) {
				$ruleError->tip = $this->tips[0];
			} else {
				$ruleError->tip = implode("\n", array_map(static fn (string $tip) => sprintf('• %s', $tip), $this->tips));
			}
		}

		return $ruleError;
	}

}
