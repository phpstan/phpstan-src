<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\ResourceType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\UnionType;
use function count;

final class GettypeFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'gettype';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		if (count($functionCall->getArgs()) < 1) {
			return null;
		}

		$valueType = $scope->getType($functionCall->getArgs()[0]->value);

		return TypeTraverser::map($valueType, static function (Type $valueType, callable $traverse): Type {
			if ($valueType instanceof UnionType || $valueType instanceof IntersectionType) {
				return $traverse($valueType);
			}

			if ($valueType->isString()->yes()) {
				return new ConstantStringType('string');
			}
			if ($valueType->isArray()->yes()) {
				return new ConstantStringType('array');
			}

			if ($valueType->isBoolean()->yes()) {
				return new ConstantStringType('boolean');
			}

			$resource = new ResourceType();
			if ($resource->isSuperTypeOf($valueType)->yes()) {
				return new UnionType([
					new ConstantStringType('resource'),
					new ConstantStringType('resource (closed)'),
				]);
			}

			if ($valueType->isInteger()->yes()) {
				return new ConstantStringType('integer');
			}

			if ($valueType->isFloat()->yes()) {
				// for historical reasons "double" is returned in case of a float, and not simply "float"
				return new ConstantStringType('double');
			}

			if ($valueType->isNull()->yes()) {
				return new ConstantStringType('NULL');
			}

			if ($valueType->isObject()->yes()) {
				return new ConstantStringType('object');
			}

			return TypeCombinator::union(
				new ConstantStringType('string'),
				new ConstantStringType('array'),
				new ConstantStringType('boolean'),
				new ConstantStringType('resource'),
				new ConstantStringType('resource (closed)'),
				new ConstantStringType('integer'),
				new ConstantStringType('double'),
				new ConstantStringType('NULL'),
				new ConstantStringType('object'),
				new ConstantStringType('unknown type'),
			);
		});
	}

}
