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

	public function __construct(private PhpVersion $phpVersion)
	{
	}

	public function isOperatorSupported(string $operatorSigil, Type $operand): bool
	{
		if (!$this->phpVersion->supportsBcMathNumberOperatorOverloading() || $operand instanceof NeverType) {
			return false;
		}

		if (!in_array($operatorSigil, ['-', '+'], true)) {
			return false;
		}

		$bcMathNumberType = new ObjectType('BcMath\Number');
		return $bcMathNumberType->isSuperTypeOf($operand)->yes();
	}

	public function specifyType(string $operatorSigil, Type $operand): Type
	{
		return new ObjectType('BcMath\Number');
	}

}
