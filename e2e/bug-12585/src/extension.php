<?php

namespace Bug12585;

use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ClosureType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\MethodParameterClosureTypeExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeWithClassName;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VerbosityLevel;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\VariadicPlaceholder;

use function array_push;
use function array_shift;
use function count;
use function explode;
use function in_array;

final class EloquentBuilderRelationParameterExtension implements MethodParameterClosureTypeExtension
{
    /** @var list<string> */
    private array $methods = ['whereHas', 'withWhereHas'];

    public function __construct(private ReflectionProvider $reflectionProvider)
    {
    }

    public function isMethodSupported(MethodReflection $methodReflection, ParameterReflection $parameter): bool
    {
        if (! $methodReflection->getDeclaringClass()->is(Builder::class)) {
            return false;
        }

        return in_array($methodReflection->getName(), $this->methods, strict: true);
    }

    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, ParameterReflection $parameter, Scope $scope): Type|null
    {
        $method    = $methodReflection->getName();
        $relations = $this->getRelationsFromMethodCall($methodCall, $scope);
        $models    = $this->getModelsFromRelations($relations);

        if (count($models) === 0) {
            return null;
        }

        $type = $this->getBuilderTypeForModels($models);

        if ($method === 'withWhereHas') {
            $type = TypeCombinator::union($type, ...$relations);
        }

        return new ClosureType([new ClosureQueryParameter('query', $type)], new MixedType());
    }

    /**
     * @param array<int, Type> $relations
     * @return array<int, string>
     */
    private function getModelsFromRelations(array $relations): array
    {
        $models = [];

        foreach ($relations as $relation) {
            $classNames = $relation->getTemplateType(Relation::class, 'TRelatedModel')->getObjectClassNames();
            foreach ($classNames as $className) {
                $models[] = $className;
            }
        }

        return $models;
    }

    /** @return array<int, Type> */
    private function getRelationsFromMethodCall(MethodCall $methodCall, Scope $scope): array
    {
        $relationType = null;

        foreach ($methodCall->args as $arg) {
            if ($arg instanceof VariadicPlaceholder) {
                continue;
            }

            if ($arg->name === null || $arg->name->toString() === 'relation') {
                $relationType = $scope->getType($arg->value);
                break;
            }
        }

        if ($relationType === null) {
            return [];
        }

        $calledOnModels = $scope->getType($methodCall->var)
            ->getTemplateType(Builder::class, 'TModel')
            ->getObjectClassNames();

        $values        = array_map(fn ($type) => $type->getValue(), $relationType->getConstantStrings());
        $relationTypes = [$relationType];

        foreach ($values as $relation) {
            $relationTypes = array_merge(
                $relationTypes,
                $this->getRelationTypeFromString($calledOnModels, explode('.', $relation), $scope)
            );
        }

        return array_values(array_filter(
            $relationTypes,
            static fn ($r) => (new ObjectType(Relation::class))->isSuperTypeOf($r)->yes()
        ));
    }

    /**
     * @param list<string> $calledOnModels
     * @param list<string> $relationParts
     * @return list<Type>
     */
    private function getRelationTypeFromString(array $calledOnModels, array $relationParts, Scope $scope): array
    {
        $relations = [];

        while ($relationName = array_shift($relationParts)) {
            $relations     = [];
            $relatedModels = [];

            foreach ($calledOnModels as $model) {
                $modelType = new ObjectType($model);

                if (! $modelType->hasMethod($relationName)->yes()) {
                    continue;
                }

                $relationType = $modelType->getMethod($relationName, $scope)->getVariants()[0]->getReturnType();

                if (! (new ObjectType(Relation::class))->isSuperTypeOf($relationType)->yes()) {
                    continue;
                }

                $relations[] = $relationType;

                array_push($relatedModels, ...$relationType->getTemplateType(Relation::class, 'TRelatedModel')->getObjectClassNames());
            }

            $calledOnModels = $relatedModels;
        }

        return $relations;
    }

    private function determineBuilderName(string $modelClassName): string
    {
        $method = $this->reflectionProvider->getClass($modelClassName)->getNativeMethod('query');

        $returnType = $method->getVariants()[0]->getReturnType();

        if (in_array(Builder::class, $returnType->getReferencedClasses(), true)) {
            return Builder::class;
        }

        $classNames = $returnType->getObjectClassNames();

        if (count($classNames) === 1) {
            return $classNames[0];
        }

        return $returnType->describe(VerbosityLevel::value());
    }

    /**
     * @param array<int, string|TypeWithClassName>|string|TypeWithClassName $models
     * @return ($models is array<int, string|TypeWithClassName> ? Type : ObjectType)
     */
    private function getBuilderTypeForModels(array|string|TypeWithClassName $models): Type
    {
        $models = is_array($models) ? $models : [$models];
        $models = array_unique($models, SORT_REGULAR);

        $mappedModels = [];
        foreach ($models as $model) {
            if (is_string($model)) {
                $mappedModels[$model] = new ObjectType($model);
            } else {
                $mappedModels[$model->getClassName()] = $model;
            }
        }

        $groupedByBuilder = [];
        foreach ($mappedModels as $class => $type) {
            $builderName = $this->determineBuilderName($class);
            $groupedByBuilder[$builderName][] = $type;
        }

        $builderTypes = [];
        foreach ($groupedByBuilder as $builder => $models) {
            $builderReflection = $this->reflectionProvider->getClass($builder);

            $builderTypes[] = $builderReflection->isGeneric()
            ? new GenericObjectType($builder, [TypeCombinator::union(...$models)])
            : new ObjectType($builder);
        }

        return TypeCombinator::union(...$builderTypes);
    }
}

final class ClosureQueryParameter implements ParameterReflection
{
    public function __construct(private string $name, private Type $type)
    {
    }

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

    public function getDefaultValue(): Type|null
    {
        return null;
    }
}
