<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Type;

/**
 * @api
 */
final class TypeSpecifierContext
{

	public const CONTEXT_TRUE = 0b0001;
	public const CONTEXT_TRUTHY_BUT_NOT_TRUE = 0b0010;
	public const CONTEXT_TRUTHY = self::CONTEXT_TRUE | self::CONTEXT_TRUTHY_BUT_NOT_TRUE;
	public const CONTEXT_FALSE = 0b0100;
	public const CONTEXT_FALSEY_BUT_NOT_FALSE = 0b1000;
	public const CONTEXT_FALSEY = self::CONTEXT_FALSE | self::CONTEXT_FALSEY_BUT_NOT_FALSE;
	public const CONTEXT_BITMASK = 0b1111;

	/** @var self[] */
	private static array $registry;

	private function __construct(
		private ?int $value,
		private ?Type $returnType,
	)
	{
	}

	private static function create(?int $value, ?Type $returnType = null): self
	{
		if ($returnType !== null) {
			// return type bound context is unique for each context and therefore not cacheable
			return new self($value, $returnType);
		}

		self::$registry[$value] ??= new self($value, null);
		return self::$registry[$value];
	}

	public static function createTrue(?Type $returnType = null): self
	{
		return self::create(self::CONTEXT_TRUE, $returnType);
	}

	public static function createTruthy(?Type $returnType = null): self
	{
		return self::create(self::CONTEXT_TRUTHY, $returnType);
	}

	public static function createFalse(?Type $returnType = null): self
	{
		return self::create(self::CONTEXT_FALSE, $returnType);
	}

	public static function createFalsey(?Type $returnType = null): self
	{
		return self::create(self::CONTEXT_FALSEY, $returnType);
	}

	public static function createNull(): self
	{
		return self::create(null);
	}

	public function negate(): self
	{
		if ($this->value === null) {
			throw new ShouldNotHappenException();
		}
		return self::create(~$this->value & self::CONTEXT_BITMASK);
	}

	public function true(): bool
	{
		return $this->value !== null && (bool) ($this->value & self::CONTEXT_TRUE);
	}

	public function truthy(): bool
	{
		return $this->value !== null && (bool) ($this->value & self::CONTEXT_TRUTHY);
	}

	public function false(): bool
	{
		return $this->value !== null && (bool) ($this->value & self::CONTEXT_FALSE);
	}

	public function falsey(): bool
	{
		return $this->value !== null && (bool) ($this->value & self::CONTEXT_FALSEY);
	}

	public function null(): bool
	{
		return $this->value === null;
	}

	public function getReturnType(): ?Type
	{
		return $this->returnType;
	}

}
