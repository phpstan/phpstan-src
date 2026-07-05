<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\PhpVersion;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\UnaryOperatorTypeSpecifyingExtension;
use function in_array;

#[AutowiredService]
final class BcMathNumberUnaryOperatorTypeSpecifyingExtension implements UnaryOperatorTypeSpecifyingExtension
{

	private ObjectType $bcMathNumberType;

	public function __construct(private PhpVersion $phpVersion)
	{
		$this->bcMathNumberType = new ObjectType('BcMath\Number');
	}

	public function isOperatorSupported(string $operatorSigil, Type $operand): bool
	{
		if (!$this->phpVersion->supportsBcMathNumberOperatorOverloading() || $operand instanceof NeverType) {
			return false;
		}

		if (!in_array($operatorSigil, ['-', '+'], true)) {
			return false;
		}

		return $this->bcMathNumberType->isSuperTypeOf($operand)->yes();
	}

	public function specifyType(string $operatorSigil, Type $operand): Type
	{
		return $this->bcMathNumberType;
	}

}
