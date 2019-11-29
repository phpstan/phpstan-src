<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use PHPStan\Type\MixedType;
use PHPStan\Type\Type;

class TemplateTypeVariance
{

	private const INVARIANT = 1;
	private const COVARIANT = 2;
	private const CONTRAVARIANT = 3;
	private const CONSTRUCTOR = 4;

	/** @var self[] */
	private static $registry;

	/** @var int */
	private $value;

	private function __construct(int $value)
	{
		$this->value = $value;
	}

	private static function create(int $value): self
	{
		self::$registry[$value] = self::$registry[$value] ?? new self($value);
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

	public static function createConstructor(): self
	{
		return self::create(self::CONSTRUCTOR);
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

	public function constructor(): bool
	{
		return $this->value === self::CONSTRUCTOR;
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
				return self::createCovariant();
			}
			if ($other->covariant()) {
				return self::createCovariant();
			}
			return self::createInvariant();
		}

		return $other;
	}

	public function isValidVariance(Type $a, Type $b): bool
	{
		if ($a instanceof MixedType && !$a instanceof TemplateType) {
			return true;
		}

		if ($b instanceof MixedType && !$b instanceof TemplateType) {
			return true;
		}

		if ($this->invariant()) {
			return $a->equals($b);
		}

		if ($this->covariant()) {
			return $a->isSuperTypeOf($b)->yes();
		}

		if ($this->contravariant()) {
			return $b->isSuperTypeOf($a)->yes();
		}

		throw new \PHPStan\ShouldNotHappenException();
	}

	public function equals(self $other): bool
	{
		return $other->value === $this->value;
	}

	public function validPosition(self $other): bool
	{
		return $other->value === $this->value
			|| $other->invariant()
			|| $this->constructor();
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
			case self::CONSTRUCTOR:
				return 'constructor';
		}

		throw new \PHPStan\ShouldNotHappenException();
	}

	/**
	 * @param array{value: int} $properties
	 * @return self
	 */
	public static function __set_state(array $properties): self
	{
		return new self($properties['value']);
	}

}
