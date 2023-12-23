<?php declare(strict_types = 1);

namespace PHPStan\Type\Constant;

use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprFloatNode;
use PHPStan\PhpDocParser\Ast\Type\ConstTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\TrinaryLogic;
use PHPStan\Type\CompoundType;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\FloatType;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\Traits\ConstantNumericComparisonTypeTrait;
use PHPStan\Type\Traits\ConstantScalarTypeTrait;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function abs;
use function ini_get;
use function ini_set;
use function is_finite;
use function str_contains;
use const PHP_FLOAT_EPSILON;

/** @api */
class ConstantFloatType extends FloatType implements ConstantScalarType
{

	use ConstantScalarTypeTrait;
	use ConstantScalarToBooleanTrait;
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

	public function equals(Type $type): bool
	{
		return $type instanceof self && abs($this->value - $type->value) < PHP_FLOAT_EPSILON;
	}

	private function castFloatToString(float $value): string
	{
		$precisionBackup = ini_get('precision');
		ini_set('precision', '-1');
		try {
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

	public function isSuperTypeOf(Type $type): TrinaryLogic
	{
		if ($type instanceof self) {
			if (!$this->equals($type)) {
				if (abs($this->value - $type->value) < PHP_FLOAT_EPSILON) {
					return TrinaryLogic::createMaybe();
				}

				return TrinaryLogic::createNo();
			}

			return TrinaryLogic::createYes();
		}

		if ($type instanceof parent) {
			return TrinaryLogic::createMaybe();
		}

		if ($type instanceof CompoundType) {
			return $type->isSubTypeOf($this);
		}

		return TrinaryLogic::createNo();
	}

	public function toString(): Type
	{
		return new ConstantStringType((string) $this->value);
	}

	public function toInteger(): Type
	{
		return new ConstantIntegerType((int) $this->value);
	}

	public function toArrayKey(): Type
	{
		return new ConstantIntegerType((int) $this->value);
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

	/**
	 * @param mixed[] $properties
	 */
	public static function __set_state(array $properties): Type
	{
		return new self($properties['value']);
	}

}
