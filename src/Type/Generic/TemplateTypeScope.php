<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use function sprintf;
use function str_starts_with;
use function strlen;
use function substr;

final class TemplateTypeScope
{

	public static function createWithAnonymousFunction(): self
	{
		return new self(null, null);
	}

	public static function createWithTypeAlias(string $className, string $aliasName): self
	{
		return new self($className, '__typeAlias_' . $aliasName);
	}

	public static function createWithFunction(string $functionName): self
	{
		return new self(null, $functionName);
	}

	public static function createWithMethod(string $className, string $functionName): self
	{
		return new self($className, $functionName);
	}

	public static function createWithClass(string $className): self
	{
		return new self($className, null);
	}

	private function __construct(private ?string $className, private ?string $functionName)
	{
	}

	/** @api */
	public function getClassName(): ?string
	{
		return $this->className;
	}

	/** @api */
	public function getFunctionName(): ?string
	{
		return $this->functionName;
	}

	/** @api */
	public function isTypeAlias(): bool
	{
		return $this->functionName !== null && str_starts_with($this->functionName, '__typeAlias_');
	}

	/** @api */
	public function getTypeAliasName(): ?string
	{
		if (!$this->isTypeAlias()) {
			return null;
		}

		return substr($this->functionName, strlen('__typeAlias_'));
	}

	/** @api */
	public function equals(self $other): bool
	{
		return $this->className === $other->className
			&& $this->functionName === $other->functionName;
	}

	/** @api */
	public function describe(): string
	{
		if ($this->className === null && $this->functionName === null) {
			return 'anonymous function';
		}

		if ($this->className === null) {
			return sprintf('function %s()', $this->functionName);
		}

		if ($this->functionName === null) {
			return sprintf('class %s', $this->className);
		}

		return sprintf('method %s::%s()', $this->className, $this->functionName);
	}

}
