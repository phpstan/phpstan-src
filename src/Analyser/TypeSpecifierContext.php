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

	private function __construct(private ?int $value, private ?Type $narrowedReturnType = null)
	{
	}

	private static function create(?int $value): self
	{
		$key = $value ?? '';
		self::$registry[$key] ??= new self($value);
		return self::$registry[$key];
	}

	public static function createTrue(): self
	{
		return self::create(self::CONTEXT_TRUE);
	}

	public static function createTruthy(): self
	{
		return self::create(self::CONTEXT_TRUTHY);
	}

	public static function createFalse(): self
	{
		return self::create(self::CONTEXT_FALSE);
	}

	public static function createFalsey(): self
	{
		return self::create(self::CONTEXT_FALSEY);
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

	/**
	 * If non-null, a constraint on the analyzed function/method's return value for the branch this
	 * context represents (the truthy()=true branch). Type-specifying extensions may use it to narrow
	 * arguments more precisely than the truthy/falsey/true/false buckets allow - e.g. for
	 * `count($x) >= 2` the count() extension receives IntegerRangeType(2, max) here.
	 *
	 * It is null in every standard context; only comparison-rewriting code attaches one via
	 * withNarrowedReturnType(), and negate() drops it again.
	 *
	 * @api
	 */
	public function getNarrowedReturnType(): ?Type
	{
		return $this->narrowedReturnType;
	}

	/**
	 * Returns a context carrying the given narrowed return type. Intentionally bypasses the flyweight
	 * registry. The narrowed return type is a one-directional hint valid only for this exact (bitmask, type)
	 * pair - negate() discards it and falls back to bitmask-only behavior.
	 *
	 * @api
	 */
	public function withNarrowedReturnType(Type $narrowedReturnType): self
	{
		return new self($this->value, $narrowedReturnType);
	}

}
