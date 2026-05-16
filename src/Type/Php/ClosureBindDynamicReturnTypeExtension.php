<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use Closure;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\ClosureType;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;

#[AutowiredService]
final class ClosureBindDynamicReturnTypeExtension implements DynamicStaticMethodReturnTypeExtension
{

	public function getClass(): string
	{
		return Closure::class;
	}

	public function isStaticMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getName() === 'bind';
	}

	public function getTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope): ?Type
	{
		$args = $methodCall->getArgs();
		$closureType = $scope->getType($args[0]->value);
		if (!($closureType instanceof ClosureType)) {
			return null;
		}

		if ($closureType->isStaticClosure()->no()) {
			return $closureType;
		}

		if (isset($args[1]) && $scope->getType($args[1]->value)->isNull()->yes()) {
			return $closureType;
		}

		return new BenevolentUnionType([$closureType, new NullType()]);
	}

}
