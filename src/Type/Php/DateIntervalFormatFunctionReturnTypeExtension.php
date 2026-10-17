<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\Type;
use function count;

#[AutowiredService]
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../../turbo-ext/src/DateIntervalFormatFunctionReturnTypeExtension.cpp')]
final class DateIntervalFormatFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function __construct(private DateIntervalFormatReturnTypeHelper $helper)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'date_interval_format';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		$args = $functionCall->getArgs();
		if (count($args) < 2) {
			return null;
		}

		return $this->helper->getType(
			$scope->getType($args[1]->value),
			$scope->getType($args[0]->value),
			$scope,
		);
	}

}
