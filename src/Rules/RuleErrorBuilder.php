<?php declare(strict_types = 1);

namespace PHPStan\Rules;

class RuleErrorBuilder
{

	private const TYPE_MESSAGE = 1;
	private const TYPE_LINE = 2;
	private const TYPE_FILE = 4;
	private const TYPE_TIP = 8;

	/** @var int */
	private $type;

	/** @var mixed[] */
	private $properties;

	private function __construct(string $message)
	{
		$this->properties['message'] = $message;
		$this->type = self::TYPE_MESSAGE;
	}

	/**
	 * @return array<int, array{string, string}>
	 */
	public static function getRuleErrorTypes(): array
	{
		return [
			self::TYPE_MESSAGE => [
				RuleError::class,
				'message',
				'string',
			],
			self::TYPE_LINE => [
				LineRuleError::class,
				'line',
				'int',
			],
			self::TYPE_FILE => [
				FileRuleError::class,
				'file',
				'string',
			],
			self::TYPE_TIP => [
				TipRuleError::class,
				'tip',
				'string',
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

	public function file(string $file): self
	{
		$this->properties['file'] = $file;
		$this->type |= self::TYPE_FILE;

		return $this;
	}

	public function tip(string $tip): self
	{
		$this->properties['tip'] = $tip;
		$this->type |= self::TYPE_TIP;

		return $this;
	}

	public function build(): RuleError
	{
		/** @var class-string<RuleError> $className */
		$className = sprintf('PHPStan\\Rules\\RuleErrors\\RuleError%d', $this->type);
		if (!class_exists($className)) {
			throw new \PHPStan\ShouldNotHappenException(sprintf('Class %s does not exist.', $className));
		}

		$ruleError = new $className();
		foreach ($this->properties as $propertyName => $value) {
			$ruleError->{$propertyName} = $value;
		}

		return $ruleError;
	}

}
