<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use Closure;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\MethodReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\ClosureType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;

#[AutowiredService]
final class ClosureBindToDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{

	public function getClass(): string
	{
		return Closure::class;
	}

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getName() === 'bindTo';
	}

	public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): ?Type
	{
		$closureType = $scope->getType($methodCall->var);
		if (!($closureType instanceof ClosureType)) {
			return null;
		}

		if ($closureType->isStaticClosure()->yes()) {
			$args = $methodCall->getArgs();
			$newThisIsNull = isset($args[0]) ? $scope->getType($args[0]->value)->isNull() : TrinaryLogic::createYes();
			if ($newThisIsNull->yes()) {
				return $closureType;
			}
			if ($newThisIsNull->no()) {
				return new NullType();
			}

			return new BenevolentUnionType([$closureType, new NullType()]);
		}

		return new BenevolentUnionType([$closureType, new NullType()]);
	}

}
