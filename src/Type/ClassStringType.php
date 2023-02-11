<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Php\PhpVersion;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantStringType;

/** @api */
class ClassStringType extends StringType
{

	/** @api */
	public function __construct()
	{
		parent::__construct();
	}

	public function describe(VerbosityLevel $level): string
	{
		return 'class-string';
	}

	public function accepts(Type $type, bool $strictTypes): TrinaryLogic
	{
		return $this->acceptsWithReason($type, $strictTypes)->result;
	}

	public function acceptsWithReason(Type $type, bool $strictTypes): AcceptsResult
	{
		if ($type instanceof CompoundType) {
			return $type->isAcceptedWithReasonBy($this, $strictTypes);
		}

		return new AcceptsResult($type->isClassStringType(), []);
	}

	public function isSuperTypeOf(Type $type): TrinaryLogic
	{
		if ($type instanceof CompoundType) {
			return $type->isSubTypeOf($this);
		}

		return $type->isClassStringType();
	}

	public function isString(): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	public function looseCompare(Type $type, PhpVersion $phpVersion): BooleanType
	{
		$looseFalse = new UnionType([
			new NullType(),
			new ConstantStringType(''),
			new ConstantStringType('0'),
			new ConstantBooleanType(false),
		]);

		if ($looseFalse->isSuperTypeOf($type)->yes()) {
			return new ConstantBooleanType(false);
		}

		return parent::looseCompare($type, $phpVersion);
	}

	public function isNumericString(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	public function isNonEmptyString(): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	public function isNonFalsyString(): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	public function isLiteralString(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	public function isClassStringType(): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	public function getClassStringObjectType(): Type
	{
		return new ObjectWithoutClassType();
	}

	public function getObjectTypeOrClassStringObjectType(): Type
	{
		return new ObjectWithoutClassType();
	}

	/**
	 * @param mixed[] $properties
	 */
	public static function __set_state(array $properties): Type
	{
		return new self();
	}

}
