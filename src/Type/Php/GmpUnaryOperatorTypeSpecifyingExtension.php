<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\UnaryOperatorTypeSpecifyingExtension;
use function in_array;

#[AutowiredService]
final class GmpUnaryOperatorTypeSpecifyingExtension implements UnaryOperatorTypeSpecifyingExtension
{

	private ObjectType $gmpType;

	public function __construct()
	{
		$this->gmpType = new ObjectType('GMP');
	}

	public function isOperatorSupported(string $operatorSigil, Type $operand): bool
	{
		if ($operand instanceof NeverType) {
			return false;
		}

		if (!in_array($operatorSigil, ['-', '+', '~'], true)) {
			return false;
		}

		return $this->gmpType->isSuperTypeOf($operand)->yes();
	}

	public function specifyType(string $operatorSigil, Type $operand): Type
	{
		return $this->gmpType;
	}

}
