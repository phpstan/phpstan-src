<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;

interface FunctionClosureTypeChangingExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool;

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type;

}
