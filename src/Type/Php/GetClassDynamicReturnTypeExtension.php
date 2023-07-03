<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\ClassStringType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\Enum\EnumCaseObjectType;
use PHPStan\Type\Generic\GenericClassStringType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\StaticType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\UnionType;
use function count;

class GetClassDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'get_class';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		$args = $functionCall->getArgs();

		if (count($args) === 0) {
			if ($scope->isInTrait()) {
				return new ClassStringType();
			}

			if ($scope->isInClass()) {
				return new ConstantStringType($scope->getClassReflection()->getName(), true);
			}

			return new ConstantBooleanType(false);
		}

		$argType = $scope->getType($args[0]->value);

		if ($scope->isInTrait() && TypeUtils::findThisType($argType) !== null) {
			return new ClassStringType();
		}

		return TypeTraverser::map(
			$argType,
			static function (Type $type, callable $traverse): Type {
				if ($type instanceof UnionType || $type instanceof IntersectionType) {
					return $traverse($type);
				}

				if ($type instanceof EnumCaseObjectType) {
					return new GenericClassStringType(new ObjectType($type->getClassName()));
				}

				$objectClassNames = $type->getObjectClassNames();
				if ($type instanceof TemplateType && $objectClassNames === []) {
					if ($type instanceof ObjectWithoutClassType) {
						return new GenericClassStringType($type);
					}

					return new UnionType([
						new GenericClassStringType($type),
						new ConstantBooleanType(false),
					]);
				} elseif ($type instanceof MixedType) {
					return new UnionType([
						new ClassStringType(),
						new ConstantBooleanType(false),
					]);
				} elseif ($type instanceof StaticType) {
					return new GenericClassStringType($type->getStaticObjectType());
				} elseif ($objectClassNames !== []) {
					return new GenericClassStringType($type);
				} elseif ($type instanceof ObjectWithoutClassType) {
					return new ClassStringType();
				}

				return new ConstantBooleanType(false);
			},
		);
	}

}
