<?php declare(strict_types = 1);

namespace PHPStan\Type\Constant;

use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprFloatNode;
use PHPStan\PhpDocParser\Ast\Type\ConstTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\BooleanType;
use PHPStan\Type\CompoundType;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\FloatRangeType;
use PHPStan\Type\FloatType;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\IsSuperTypeOfResult;
use PHPStan\Type\Traits\ConstantNumericComparisonTypeTrait;
use PHPStan\Type\Traits\ConstantScalarTypeTrait;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use function abs;
use function ini_get;
use function ini_set;
use function is_finite;
use function is_nan;
use function str_contains;

/** @api */
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../../turbo-ext/src/ConstantFloatType.cpp')]
class ConstantFloatType extends FloatType implements ConstantScalarType
{

	use ConstantScalarTypeTrait;
	use ConstantNumericComparisonTypeTrait;

	/** @api */
	public function __construct(private float $value)
	{
		parent::__construct();
	}

	public function getValue(): float
	{
		return $this->value;
	}

	public function isSuperTypeOf(Type $type): IsSuperTypeOfResult
	{
		if ($type instanceof self) {
			return IsSuperTypeOfResult::createFromBoolean($this->equals($type));
		}

		if ($type instanceof FloatRangeType) {
			return $type->isSuperTypeOf($this)->no() ? IsSuperTypeOfResult::createNo() : IsSuperTypeOfResult::createMaybe();
		}

		if ($type instanceof parent) {
			return IsSuperTypeOfResult::createMaybe();
		}

		if ($type instanceof CompoundType) {
			return $type->isSubTypeOf($this);
		}

		return IsSuperTypeOfResult::createNo();
	}

	public function equals(Type $type): bool
	{
		return $type instanceof self && ($this->value === $type->value || is_nan($this->value) && is_nan($type->value));
	}

	private function castFloatToString(float $value): string
	{
		$precisionBackup = ini_get('precision');
		ini_set('precision', '-1');
		try {
			if (is_nan($value)) {
				return 'NAN';
			}

			$valueStr = (string) $value;
			if (is_finite($value) && !str_contains($valueStr, '.')) {
				$valueStr .= '.0';
			}

			return $valueStr;
		} finally {
			ini_set('precision', $precisionBackup);
		}
	}

	public function describe(VerbosityLevel $level): string
	{
		return $level->handle(
			static fn (): string => 'float',
			fn (): string => $this->castFloatToString($this->value),
		);
	}

	public function toString(): Type
	{
		if ($this->value === 0.0) {
			return new UnionType([
				new ConstantStringType('0'),
				new ConstantStringType('-0'),
			]);
		}

		return new ConstantStringType((string) $this->value);
	}

	public function toBoolean(): BooleanType
	{
		// (bool) $this->value without the warning PHP 8.5 raises for NAN; -0.0 === 0.0
		return new ConstantBooleanType($this->value !== 0.0);
	}

	/** (int) $this->value without the warning PHP 8.5 raises for NAN and the infinities, which cast to 0. */
	private function toIntegerValue(): int
	{
		return is_finite($this->value) ? (int) $this->value : 0;
	}

	public function toInteger(): Type
	{
		return new ConstantIntegerType($this->toIntegerValue());
	}

	public function toBitwiseNotType(): Type
	{
		return new ConstantIntegerType(~ $this->toIntegerValue());
	}

	public function toAbsoluteNumber(): Type
	{
		return new self(abs($this->value));
	}

	public function toArrayKey(): Type
	{
		return new ConstantIntegerType($this->toIntegerValue());
	}

	public function getFiniteTypes(): array
	{
		if (is_nan($this->value)) {
			return [];
		}

		return [$this];
	}

	public function generalize(GeneralizePrecision $precision): Type
	{
		return new FloatType();
	}

	/**
	 * @return ConstTypeNode
	 */
	public function toPhpDocNode(): TypeNode
	{
		return new ConstTypeNode(new ConstExprFloatNode($this->castFloatToString($this->value)));
	}

}
