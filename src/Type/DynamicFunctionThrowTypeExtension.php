<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;

interface DynamicFunctionThrowTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool;

	public function getThrowTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $funcCall, Scope $scope): ?Type;

}
