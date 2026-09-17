<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\Type;
use function count;

#[AutowiredService]
final class StrShuffleFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function __construct(private StringBytesReturnTypeHelper $stringBytesReturnTypeHelper)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'str_shuffle';
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): ?Type
	{
		$args = $functionCall->getArgs();
		if (count($args) < 1) {
			return null;
		}

		return $this->stringBytesReturnTypeHelper->getReorderedStringType($scope->getType($args[0]->value));
	}

}
