<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\Type;
use function count;
use function in_array;

#[AutowiredService]
final class RandomIntFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function __construct(private RandomIntRangeHelper $randomIntRangeHelper)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return in_array($functionReflection->getName(), ['random_int', 'rand', 'mt_rand'], true);
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		$args = $functionCall->getArgs();
		if (in_array($functionReflection->getName(), ['rand', 'mt_rand'], true) && count($args) === 0) {
			return IntegerRangeType::fromInterval(0, null);
		}

		if (count($args) < 2) {
			return null;
		}

		return $this->randomIntRangeHelper->createRange(
			$scope->getType($args[0]->value)->toInteger(),
			$scope->getType($args[1]->value)->toInteger(),
		);
	}

}
