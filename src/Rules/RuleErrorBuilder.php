<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PHPStan\ShouldNotHappenException;
use function array_map;
use function class_exists;
use function count;
use function implode;
use function sprintf;

/** @api */
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
	 * @return array<int, array{string, array<array{string|null, string|null, string|null}>}>
	 */
	public static function getRuleErrorTypes(): array
	{
		return [
			self::TYPE_MESSAGE => [
				RuleError::class,
				[
					[
						'message',
						'string',
						'string',
					],
				],
			],
			self::TYPE_LINE => [
				LineRuleError::class,
				[
					[
						'line',
						'int',
						'int',
					],
				],
			],
			self::TYPE_FILE => [
				FileRuleError::class,
				[
					[
						'file',
						'string',
						'string',
					],
					[
						'fileDescription',
						'?string',
						'?string',
					],
				],
			],
			self::TYPE_TIP => [
				TipRuleError::class,
				[
					[
						'tip',
						'string',
						'string',
					],
				],
			],
			self::TYPE_IDENTIFIER => [
				IdentifierRuleError::class,
				[
					[
						'identifier',
						'string',
						'string',
					],
				],
			],
			self::TYPE_METADATA => [
				MetadataRuleError::class,
				[
					[
						'metadata',
						'array',
						'mixed[]',
					],
				],
			],
			self::TYPE_NON_IGNORABLE => [
				NonIgnorableRuleError::class,
				[],
			],
		];
	}

	public static function message(string $message): self
	{
		return new self($message);
	}

	public function line(int $line): self
	{
		$this->properties['line'] = $line;
		$this->type |= self::TYPE_LINE;

		return $this;
	}

	public function file(string $file, ?string $fileDescription = null): self
	{
		$this->properties['file'] = $file;
		$this->properties['fileDescription'] = $fileDescription;
		$this->type |= self::TYPE_FILE;

		return $this;
	}

	public function tip(string $tip): self
	{
		$this->tips = [$tip];
		$this->type |= self::TYPE_TIP;

		return $this;
	}

	public function addTip(string $tip): self
	{
		$this->tips[] = $tip;
		$this->type |= self::TYPE_TIP;

		return $this;
	}

	public function discoveringSymbolsTip(): self
	{
		return $this->tip('Learn more at https://phpstan.org/user-guide/discovering-symbols');
	}

	/**
	 * @param list<string> $reasons
	 */
	public function acceptsReasonsTip(array $reasons): self
	{
		foreach ($reasons as $reason) {
			$this->addTip($reason);
		}

		return $this;
	}

	public function identifier(string $identifier): self
	{
		$this->properties['identifier'] = $identifier;
		$this->type |= self::TYPE_IDENTIFIER;

		return $this;
	}

	/**
	 * @param mixed[] $metadata
	 */
	public function metadata(array $metadata): self
	{
		$this->properties['metadata'] = $metadata;
		$this->type |= self::TYPE_METADATA;

		return $this;
	}

	public function nonIgnorable(): self
	{
		$this->type |= self::TYPE_NON_IGNORABLE;

		return $this;
	}

	public function build(): RuleError
	{
		/** @var class-string<RuleError> $className */
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
