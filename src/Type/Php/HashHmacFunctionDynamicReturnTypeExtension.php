<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;
use function hash_hmac_algos;
use function in_array;

class HashHmacFunctionDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function __construct(private PhpVersion $phpVersion)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'hash_hmac';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		$args = $functionCall->getArgs();

		if (count($args) < 3) {
			return null;
		}

		$algo = $scope->getType($args[0]->value);

		if (!$algo instanceof ConstantStringType) {
			$types = [new AccessoryNonEmptyStringType()];

			if (!$this->phpVersion->throwsValueErrorForInternalFunctions()) {
				$types[] = new ConstantBooleanType(false);
			}

			return TypeCombinator::union(...$types);
		}

		$knownAlgos = hash_hmac_algos();
		if (in_array($algo->getValue(), $knownAlgos)) {
			return new AccessoryNonEmptyStringType();
		}

		if ($this->phpVersion->throwsValueErrorForInternalFunctions()) {
			return new NeverType();
		}

		return new ConstantBooleanType(false);
	}

}
