<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use Closure;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\PhpDoc\Tag\TemplateTag;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorWithPhpDocs;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ClosureType;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Generic\TemplateTypeVarianceMap;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

class ClosureFromCallableDynamicReturnTypeExtension implements DynamicStaticMethodReturnTypeExtension
{

	public function getClass(): string
	{
		return Closure::class;
	}

	public function isStaticMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getName() === 'fromCallable';
	}

	public function getTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope): ?Type
	{
		if (!isset($methodCall->getArgs()[0])) {
			return null;
		}

		$callableType = $scope->getType($methodCall->getArgs()[0]->value);
		if ($callableType->isCallable()->no()) {
			return new ErrorType();
		}

		$closureTypes = [];
		foreach ($callableType->getCallableParametersAcceptors($scope) as $variant) {
			$parameters = $variant->getParameters();
			$templateTags = [];

			foreach ($variant->getTemplateTypeMap()->getTypes() as $name => $templateType) {
				if (!$templateType instanceof TemplateType) {
					throw new ShouldNotHappenException();
				}

				$templateTags[$name] = new TemplateTag(
					$name,
					$templateType->getBound(),
					TemplateTypeVariance::createInvariant(),
				);
			}

			$closureTypes[] = new ClosureType(
				$parameters,
				$variant->getReturnType(),
				$variant->isVariadic(),
				$variant->getTemplateTypeMap(),
				$variant->getResolvedTemplateTypeMap(),
				$variant instanceof ParametersAcceptorWithPhpDocs ? $variant->getCallSiteVarianceMap() : TemplateTypeVarianceMap::createEmpty(),
				$templateTags,
			);
		}

		return TypeCombinator::union(...$closureTypes);
	}

}
