<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;

/** @api */
class TemplateTypeVariance
{

	private const INVARIANT = 1;
	private const COVARIANT = 2;
	private const CONTRAVARIANT = 3;
	private const STATIC = 4;

	/** @var self[] */
	private static array $registry;

	private function __construct(private int $value)
	{
	}

	private static function create(int $value): self
	{
		self::$registry[$value] ??= new self($value);
		return self::$registry[$value];
	}

	public static function createInvariant(): self
	{
		return self::create(self::INVARIANT);
	}

	public static function createCovariant(): self
	{
		return self::create(self::COVARIANT);
	}

	public static function createContravariant(): self
	{
		return self::create(self::CONTRAVARIANT);
	}

	public static function createStatic(): self
	{
		return self::create(self::STATIC);
	}

	public function invariant(): bool
	{
		return $this->value === self::INVARIANT;
	}

	public function covariant(): bool
	{
		return $this->value === self::COVARIANT;
	}

	public function contravariant(): bool
	{
		return $this->value === self::CONTRAVARIANT;
	}

	public function static(): bool
	{
		return $this->value === self::STATIC;
	}

	public function compose(self $other): self
	{
		if ($this->contravariant()) {
			if ($other->contravariant()) {
				return self::createCovariant();
			}
			if ($other->covariant()) {
				return self::createContravariant();
			}
			return self::createInvariant();
		}

		if ($this->covariant()) {
			if ($other->contravariant()) {
				return self::createContravariant();
			}
			if ($other->covariant()) {
				return self::createCovariant();
			}
			return self::createInvariant();
		}

		if ($this->invariant()) {
			return self::createInvariant();
		}

		return $other;
	}

	public function isValidVariance(Type $a, Type $b): TrinaryLogic
	{
		if ($b instanceof NeverType) {
			return TrinaryLogic::createYes();
		}

		if ($a instanceof MixedType && !$a instanceof TemplateType) {
			return TrinaryLogic::createYes();
		}

		if ($a instanceof BenevolentUnionType) {
			if (!$a->isSuperTypeOf($b)->no()) {
				return TrinaryLogic::createYes();
			}
		}

		if ($b instanceof BenevolentUnionType) {
			if (!$b->isSuperTypeOf($a)->no()) {
				return TrinaryLogic::createYes();
			}
		}

		if ($b instanceof MixedType && !$b instanceof TemplateType) {
			return TrinaryLogic::createYes();
		}

		if ($this->invariant()) {
			return TrinaryLogic::createFromBoolean($a->equals($b));
		}

		if ($this->covariant()) {
			return $a->isSuperTypeOf($b);
		}

		if ($this->contravariant()) {
			return $b->isSuperTypeOf($a);
		}

		throw new ShouldNotHappenException();
	}

	public function equals(self $other): bool
	{
		return $other->value === $this->value;
	}

	public function validPosition(self $other): bool
	{
		return $other->value === $this->value
			|| $other->invariant()
			|| $this->static();
	}

	public function describe(): string
	{
		switch ($this->value) {
			case self::INVARIANT:
				return 'invariant';
			case self::COVARIANT:
				return 'covariant';
			case self::CONTRAVARIANT:
				return 'contravariant';
			case self::STATIC:
				return 'static';
		}

		throw new ShouldNotHappenException();
	}

	/**
	 * @param array{value: int} $properties
	 */
	public static function __set_state(array $properties): self
	{
		return new self($properties['value']);
	}

}
