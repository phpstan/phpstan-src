<?php

declare(strict_types=1);

namespace App;

use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Type\ClosureType;
use PHPStan\Type\DynamicMethodParameterTypeExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PhpParser\Node\Expr\MethodCall;

final class ParameterTypeExtension implements DynamicMethodParameterTypeExtension
{
	public function isMethodSupported(MethodReflection $methodReflection, ParameterReflection $parameter): bool
	{
		if (! $methodReflection->getDeclaringClass()->is(Builder::class)) {
			return false;
		}

		return $methodReflection->getName() === 'with';
	}

	public function getTypeFromMethodCall(
		MethodReflection $methodReflection,
		MethodCall $methodCall,
		ParameterReflection $parameter,
		Scope $scope,
	): Type|null {
		$arg = $methodCall->getArgs()[0] ?? null;
		if (!$arg) {
			return null;
		}

		$type = $scope->getType($arg->value)->getConstantArrays()[0] ?? null;
		if (!$type) {
			return null;
		}

		$model = $scope->getType($methodCall->var)
			->getTemplateType(Builder::class, 'TModel')
			->getObjectClassNames()[0] ?? null;
		if (!$model) {
			return null;
		}

		foreach ($type->getKeyTypes() as $keyType) {
			$relationType = $this->getRelationTypeFromModel($model, (string) $keyType->getValue(), $scope);
			if (!$relationType) {
				continue;
			}

			$newType = new ClosureType([
				new class('test', $relationType) implements ParameterReflection {
					public function __construct(private string $name, private Type $type) {}
					public function getName(): string
					{
						return $this->name;
					}
					public function isOptional(): bool
					{
						return false;
					}
					public function getType(): Type
					{
						return $this->type;
					}
					public function passedByReference(): PassedByReference
					{
						return PassedByReference::createNo();
					}
					public function isVariadic(): bool
					{
						return false;
					}
					public function getDefaultValue(): ?Type
					{
						return null;
					}
				},
			], new MixedType(), false);

			$type = $type->setOffsetValueType($keyType, $newType, false);
		}

		return $type;
	}

	public function getRelationTypeFromModel(string $model, string $relation, Scope $scope): ?Type
	{
		$modelType = new ObjectType($model);

		if (! $modelType->hasMethod($relation)->yes()) {
			return null;
		}

		$relationType = $modelType->getMethod($relation, $scope)->getVariants()[0]->getReturnType();

		if (! (new ObjectType(Relation::class))->isSuperTypeOf($relationType)->yes()) {
			return null;
		}

		return $relationType;
	}
}
