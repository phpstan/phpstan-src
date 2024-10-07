<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\IsSuperTypeOfResult;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use function sprintf;

/**
 * @api
 */
final class TemplateTypeVariance
{

	private const INVARIANT = 1;
	private const COVARIANT = 2;
	private const CONTRAVARIANT = 3;
	private const STATIC = 4;
	private const BIVARIANT = 5;

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

	public static function createBivariant(): self
	{
		return self::create(self::BIVARIANT);
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

	public function bivariant(): bool
	{
		return $this->value === self::BIVARIANT;
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
			if ($other->bivariant()) {
				return self::createBivariant();
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
			if ($other->bivariant()) {
				return self::createBivariant();
			}
			return self::createInvariant();
		}

		if ($this->invariant()) {
			return self::createInvariant();
		}

		if ($this->bivariant()) {
			return self::createBivariant();
		}

		return $other;
	}

	public function isValidVariance(TemplateType $templateType, Type $a, Type $b): IsSuperTypeOfResult
	{
		if ($b instanceof NeverType) {
			return IsSuperTypeOfResult::createYes();
		}

		if ($a instanceof MixedType && !$a instanceof TemplateType) {
			return IsSuperTypeOfResult::createYes();
		}

		if ($a instanceof BenevolentUnionType) {
			if (!$a->isSuperTypeOf($b)->no()) {
				return IsSuperTypeOfResult::createYes();
			}
		}

		if ($b instanceof BenevolentUnionType) {
			if (!$b->isSuperTypeOf($a)->no()) {
				return IsSuperTypeOfResult::createYes();
			}
		}

		if ($b instanceof MixedType && !$b instanceof TemplateType) {
			return IsSuperTypeOfResult::createYes();
		}

		if ($this->invariant()) {
			$result = $a->equals($b);
			$reasons = [];
			if (!$result) {
				if (
					$templateType->getScope()->getClassName() !== null
					&& $a->isSuperTypeOf($b)->yes()
				) {
					$reasons[] = sprintf(
						'Template type %s on class %s is not covariant. Learn more: <fg=cyan>https://phpstan.org/blog/whats-up-with-template-covariant</>',
						$templateType->getName(),
						$templateType->getScope()->getClassName(),
					);
				}
			}

			return new IsSuperTypeOfResult(TrinaryLogic::createFromBoolean($result), $reasons);
		}

		if ($this->covariant()) {
			return $a->isSuperTypeOf($b);
		}

		if ($this->contravariant()) {
			return $b->isSuperTypeOf($a);
		}

		if ($this->bivariant()) {
			return IsSuperTypeOfResult::createYes();
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
			|| $this->bivariant()
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
			case self::BIVARIANT:
				return 'bivariant';
		}

		throw new ShouldNotHappenException();
	}

	/**
	 * @return GenericTypeNode::VARIANCE_*
	 */
	public function toPhpDocNodeVariance(): string
	{
		switch ($this->value) {
			case self::INVARIANT:
				return GenericTypeNode::VARIANCE_INVARIANT;
			case self::COVARIANT:
				return GenericTypeNode::VARIANCE_COVARIANT;
			case self::CONTRAVARIANT:
				return GenericTypeNode::VARIANCE_CONTRAVARIANT;
			case self::BIVARIANT:
				return GenericTypeNode::VARIANCE_BIVARIANT;
		}

		throw new ShouldNotHappenException();
	}

}
