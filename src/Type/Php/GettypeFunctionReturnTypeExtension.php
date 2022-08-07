<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\ResourceType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\UnionType;
use function count;

class GettypeFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
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

			$bool = new BooleanType();
			if ($bool->isSuperTypeOf($valueType)->yes()) {
				return new ConstantStringType('boolean');
			}

			$resource = new ResourceType();
			if ($resource->isSuperTypeOf($valueType)->yes()) {
				return new UnionType([
					new ConstantStringType('resource'),
					new ConstantStringType('resource (closed)'),
				]);
			}

			$integer = new IntegerType();
			if ($integer->isSuperTypeOf($valueType)->yes()) {
				return new ConstantStringType('integer');
			}

			$float = new FloatType();
			if ($float->isSuperTypeOf($valueType)->yes()) {
				// for historical reasons "double" is returned in case of a float, and not simply "float"
				return new ConstantStringType('double');
			}

			$null = new NullType();
			if ($null->isSuperTypeOf($valueType)->yes()) {
				return new ConstantStringType('NULL');
			}

			$object = new ObjectWithoutClassType();
			if ($object->isSuperTypeOf($valueType)->yes()) {
				return new ConstantStringType('object');
			}

			return new ConstantStringType('unknown type');
		});
	}

}
