<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use function count;

final class GetenvFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function __construct(private PhpVersion $phpVersion)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'getenv';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		if (!$this->phpVersion->getenvAcceptsNull()) {
			return null;
		}
		if (count($functionCall->getArgs()) < 1) {
			return null;
		}

		$argType = $scope->getType($functionCall->getArgs()[0]->value);
		if ($argType->isNull()->yes()) {
			return new ArrayType(new StringType(), new StringType());
		}
		if ($argType->isNull()->no()) {
			return new UnionType([new StringType(), new ConstantBooleanType(false)]);
		}

		return null;
	}

}
