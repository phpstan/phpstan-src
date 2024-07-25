<?php declare(strict_types = 1);

namespace PHPStan\Type\Constant;

use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprFloatNode;
use PHPStan\PhpDocParser\Ast\Type\ConstTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
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
use function is_nan;
use function str_contains;

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
		return $type instanceof self && ($this->value === $type->value || is_nan($this->value) && is_nan($type->value));
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

	public function toString(): Type
	{
		return new ConstantStringType((string) $this->value);
	}

	public function toInteger(): Type
	{
		return new ConstantIntegerType((int) $this->value);
	}

	public function toAbsoluteNumber(): Type
	{
		return new self(abs($this->value));
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
