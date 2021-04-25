<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;

interface DynamicMethodThrowTypeExtension
{

	public function isMethodSupported(MethodReflection $methodReflection): bool;

	public function getThrowTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): ?Type;

}
