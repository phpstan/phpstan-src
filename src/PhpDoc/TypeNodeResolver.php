<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use Nette\Utils\Strings;
use PHPStan\Analyser\NameScope;
use PHPStan\DependencyInjection\Container;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprArrayNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprFalseNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprFloatNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprIntegerNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprNullNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprStringNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprTrueNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstFetchNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode;
use PHPStan\PhpDocParser\Ast\Type\CallableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\CallableTypeParameterNode;
use PHPStan\PhpDocParser\Ast\Type\ConstTypeNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IntersectionTypeNode;
use PHPStan\PhpDocParser\Ast\Type\NullableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\ThisTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use PHPStan\Reflection\Native\NativeParameterReflection;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\CallableType;
use PHPStan\Type\ClassStringType;
use PHPStan\Type\ClosureType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ConstantTypeHelper;
use PHPStan\Type\ErrorType;
use PHPStan\Type\FloatType;
use PHPStan\Type\Generic\GenericClassStringType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\IterableType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NonexistentParentClassType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\ResourceType;
use PHPStan\Type\StaticType;
use PHPStan\Type\StringType;
use PHPStan\Type\ThisType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeWithClassName;
use PHPStan\Type\VoidType;

class TypeNodeResolver
{

	/** @var TypeNodeResolverExtensionRegistryProvider */
	private $extensionRegistryProvider;

	/** @var Container */
	private $container;

	public function __construct(
		TypeNodeResolverExtensionRegistryProvider $extensionRegistryProvider,
		Container $container
	)
	{
		$this->extensionRegistryProvider = $extensionRegistryProvider;
		$this->container = $container;
	}

	public function resolve(TypeNode $typeNode, NameScope $nameScope): Type
	{
		foreach ($this->extensionRegistryProvider->getRegistry()->getExtensions() as $extension) {
			$type = $extension->resolve($typeNode, $nameScope);
			if ($type !== null) {
				return $type;
			}
		}

		if ($typeNode instanceof IdentifierTypeNode) {
			return $this->resolveIdentifierTypeNode($typeNode, $nameScope);

		} elseif ($typeNode instanceof ThisTypeNode) {
			return $this->resolveThisTypeNode($typeNode, $nameScope);

		} elseif ($typeNode instanceof NullableTypeNode) {
			return $this->resolveNullableTypeNode($typeNode, $nameScope);

		} elseif ($typeNode instanceof UnionTypeNode) {
			return $this->resolveUnionTypeNode($typeNode, $nameScope);

		} elseif ($typeNode instanceof IntersectionTypeNode) {
			return $this->resolveIntersectionTypeNode($typeNode, $nameScope);

		} elseif ($typeNode instanceof ArrayTypeNode) {
			return $this->resolveArrayTypeNode($typeNode, $nameScope);

		} elseif ($typeNode instanceof GenericTypeNode) {
			return $this->resolveGenericTypeNode($typeNode, $nameScope);

		} elseif ($typeNode instanceof CallableTypeNode) {
			return $this->resolveCallableTypeNode($typeNode, $nameScope);

		} elseif ($typeNode instanceof ArrayShapeNode) {
			return $this->resolveArrayShapeNode($typeNode, $nameScope);
		} elseif ($typeNode instanceof ConstTypeNode) {
			return $this->resolveConstTypeNode($typeNode, $nameScope);
		}

		return new ErrorType();
	}

	private function resolveIdentifierTypeNode(IdentifierTypeNode $typeNode, NameScope $nameScope): Type
	{
		switch (strtolower($typeNode->name)) {
			case 'int':
			case 'integer':
				return new IntegerType();

			case 'string':
				return new StringType();

			case 'class-string':
				return new ClassStringType();

			case 'array-key':
				return new BenevolentUnionType([new IntegerType(), new StringType()]);

			case 'bool':
			case 'boolean':
				return new BooleanType();

			case 'true':
				return new ConstantBooleanType(true);

			case 'false':
				return new ConstantBooleanType(false);

			case 'null':
				return new NullType();

			case 'float':
			case 'double':
				return new FloatType();

			case 'array':
				return new ArrayType(new MixedType(), new MixedType());

			case 'iterable':
				return new IterableType(new MixedType(), new MixedType());

			case 'callable':
				return new CallableType();

			case 'resource':
				return new ResourceType();

			case 'mixed':
				return new MixedType(true);

			case 'void':
				return new VoidType();

			case 'object':
				return new ObjectWithoutClassType();

			case 'never':
			case 'never-return':
				return new NeverType(true);

			case 'list':
				return new ArrayType(new IntegerType(), new MixedType());
		}

		if ($nameScope->getClassName() !== null) {
			switch (strtolower($typeNode->name)) {
				case 'self':
					return new ObjectType($nameScope->getClassName());

				case 'static':
					return new StaticType($nameScope->getClassName());

				case 'parent':
					if ($this->getReflectionProvider()->hasClass($nameScope->getClassName())) {
						$classReflection = $this->getReflectionProvider()->getClass($nameScope->getClassName());
						if ($classReflection->getParentClass() !== false) {
							return new ObjectType($classReflection->getParentClass()->getName());
						}
					}

					return new NonexistentParentClassType();
			}
		}

		$templateType = $nameScope->resolveTemplateTypeName($typeNode->name);
		if ($templateType !== null) {
			return $templateType;
		}

		$stringName = $nameScope->resolveStringName($typeNode->name);
		if (strpos($stringName, '-') !== false && strpos($stringName, 'OCI-') !== 0) {
			return new ErrorType();
		}

		return new ObjectType($stringName);
	}

	private function resolveThisTypeNode(ThisTypeNode $typeNode, NameScope $nameScope): Type
	{
		if ($nameScope->getClassName() !== null) {
			return new ThisType($nameScope->getClassName());
		}

		return new ErrorType();
	}

	private function resolveNullableTypeNode(NullableTypeNode $typeNode, NameScope $nameScope): Type
	{
		return TypeCombinator::addNull($this->resolve($typeNode->type, $nameScope));
	}

	private function resolveUnionTypeNode(UnionTypeNode $typeNode, NameScope $nameScope): Type
	{
		$iterableTypeNodes = [];
		$otherTypeNodes = [];

		foreach ($typeNode->types as $innerTypeNode) {
			if ($innerTypeNode instanceof ArrayTypeNode) {
				$iterableTypeNodes[] = $innerTypeNode->type;
			} else {
				$otherTypeNodes[] = $innerTypeNode;
			}
		}

		$otherTypeTypes = $this->resolveMultiple($otherTypeNodes, $nameScope);
		if (count($iterableTypeNodes) > 0) {
			$arrayTypeTypes = $this->resolveMultiple($iterableTypeNodes, $nameScope);
			$arrayTypeType = TypeCombinator::union(...$arrayTypeTypes);
			$addArray = true;

			foreach ($otherTypeTypes as &$type) {
				if (!$type->isIterable()->yes() || !$type->getIterableValueType()->isSuperTypeOf($arrayTypeType)->yes()) {
					continue;
				}

				if ($type instanceof ObjectType) {
					$type = new IntersectionType([$type, new IterableType(new MixedType(), $arrayTypeType)]);
				} elseif ($type instanceof ArrayType) {
					$type = new ArrayType(new MixedType(), $arrayTypeType);
				} elseif ($type instanceof IterableType) {
					$type = new IterableType(new MixedType(), $arrayTypeType);
				} else {
					continue;
				}

				$addArray = false;
			}

			if ($addArray) {
				$otherTypeTypes[] = new ArrayType(new MixedType(), $arrayTypeType);
			}
		}

		return TypeCombinator::union(...$otherTypeTypes);
	}

	private function resolveIntersectionTypeNode(IntersectionTypeNode $typeNode, NameScope $nameScope): Type
	{
		$types = $this->resolveMultiple($typeNode->types, $nameScope);
		return TypeCombinator::intersect(...$types);
	}

	private function resolveArrayTypeNode(ArrayTypeNode $typeNode, NameScope $nameScope): Type
	{
		$itemType = $this->resolve($typeNode->type, $nameScope);
		return new ArrayType(new MixedType(), $itemType);
	}

	private function resolveGenericTypeNode(GenericTypeNode $typeNode, NameScope $nameScope): Type
	{
		$mainTypeName = strtolower($typeNode->type->name);
		$genericTypes = $this->resolveMultiple($typeNode->genericTypes, $nameScope);

		if ($mainTypeName === 'array') {
			if (count($genericTypes) === 1) { // array<ValueType>
				return new ArrayType(new MixedType(true), $genericTypes[0]);

			}

			if (count($genericTypes) === 2) { // array<KeyType, ValueType>
				return new ArrayType($genericTypes[0], $genericTypes[1]);
			}

		} elseif ($mainTypeName === 'list') {
			if (count($genericTypes) === 1) { // list<ValueType>
				return new ArrayType(new IntegerType(), $genericTypes[0]);
			}

			return new ErrorType();
		} elseif ($mainTypeName === 'iterable') {
			if (count($genericTypes) === 1) { // iterable<ValueType>
				return new IterableType(new MixedType(true), $genericTypes[0]);

			}

			if (count($genericTypes) === 2) { // iterable<KeyType, ValueType>
				return new IterableType($genericTypes[0], $genericTypes[1]);
			}
		} elseif ($mainTypeName === 'class-string') {
			if (count($genericTypes) === 1) {
				$genericType = $genericTypes[0];
				if ((new ObjectWithoutClassType())->isSuperTypeOf($genericType)->yes() || $genericType instanceof MixedType) {
					return new GenericClassStringType($genericType);
				}
			}

			return new ErrorType();
		}

		$mainType = $this->resolveIdentifierTypeNode($typeNode->type, $nameScope);

		if ($mainType instanceof TypeWithClassName) {
			if (!$this->getReflectionProvider()->hasClass($mainType->getClassName())) {
				return new GenericObjectType($mainType->getClassName(), $genericTypes);
			}

			$classReflection = $this->getReflectionProvider()->getClass($mainType->getClassName());
			if ($classReflection->isGeneric()) {
				if (in_array($mainType->getClassName(), [
					\Traversable::class,
					\IteratorAggregate::class,
					\Iterator::class,
				], true)) {
					if (count($genericTypes) === 1) {
						return new GenericObjectType($mainType->getClassName(), [
							new MixedType(true),
							$genericTypes[0],
						]);
					}

					if (count($genericTypes) === 2) {
						return new GenericObjectType($mainType->getClassName(), [
							$genericTypes[0],
							$genericTypes[1],
						]);
					}
				}
				if ($mainType->getClassName() === \Generator::class) {
					if (count($genericTypes) === 1) {
						$mixed = new MixedType(true);
						return new GenericObjectType($mainType->getClassName(), [
							$mixed,
							$genericTypes[0],
							$mixed,
							$mixed,
						]);
					}

					if (count($genericTypes) === 2) {
						$mixed = new MixedType(true);
						return new GenericObjectType($mainType->getClassName(), [
							$genericTypes[0],
							$genericTypes[1],
							$mixed,
							$mixed,
						]);
					}
				}

				if (!$mainType->isIterable()->yes()) {
					return new GenericObjectType($mainType->getClassName(), $genericTypes);
				}

				if (
					count($genericTypes) !== 1
					|| $classReflection->getTemplateTypeMap()->count() === 1
				) {
					return new GenericObjectType($mainType->getClassName(), $genericTypes);
				}
			}
		}

		if ($mainType->isIterable()->yes()) {
			if (count($genericTypes) === 1) { // Foo<ValueType>
				return TypeCombinator::intersect(
					$mainType,
					new IterableType(new MixedType(true), $genericTypes[0])
				);
			}

			if (count($genericTypes) === 2) { // Foo<KeyType, ValueType>
				return TypeCombinator::intersect(
					$mainType,
					new IterableType($genericTypes[0], $genericTypes[1])
				);
			}
		}

		if ($mainType instanceof TypeWithClassName) {
			return new GenericObjectType($mainType->getClassName(), $genericTypes);
		}

		return new ErrorType();
	}

	private function resolveCallableTypeNode(CallableTypeNode $typeNode, NameScope $nameScope): Type
	{
		$mainType = $this->resolve($typeNode->identifier, $nameScope);
		$isVariadic = false;
		$parameters = array_map(
			function (CallableTypeParameterNode $parameterNode) use ($nameScope, &$isVariadic): NativeParameterReflection {
				$isVariadic = $isVariadic || $parameterNode->isVariadic;
				return new NativeParameterReflection(
					$parameterNode->parameterName,
					$parameterNode->isOptional,
					$this->resolve($parameterNode->type, $nameScope),
					$parameterNode->isReference ? PassedByReference::createCreatesNewVariable() : PassedByReference::createNo(),
					$parameterNode->isVariadic,
					null
				);
			},
			$typeNode->parameters
		);
		$returnType = $this->resolve($typeNode->returnType, $nameScope);

		if ($mainType instanceof CallableType) {
			return new CallableType($parameters, $returnType, $isVariadic);

		} elseif (
			$mainType instanceof ObjectType
			&& $mainType->getClassName() === \Closure::class
		) {
			return new ClosureType($parameters, $returnType, $isVariadic);
		}

		return new ErrorType();
	}

	private function resolveArrayShapeNode(ArrayShapeNode $typeNode, NameScope $nameScope): Type
	{
		$builder = ConstantArrayTypeBuilder::createEmpty();

		foreach ($typeNode->items as $itemNode) {
			$offsetType = null;
			if ($itemNode->keyName instanceof ConstExprIntegerNode) {
				$offsetType = new ConstantIntegerType((int) $itemNode->keyName->value);
			} elseif ($itemNode->keyName instanceof IdentifierTypeNode) {
				$offsetType = new ConstantStringType($itemNode->keyName->name);
			} elseif ($itemNode->keyName instanceof ConstExprStringNode) {
				$offsetType = new ConstantStringType($itemNode->keyName->value);
			} elseif ($itemNode->keyName !== null) {
				throw new \PHPStan\ShouldNotHappenException('Unsupported key node type: ' . get_class($itemNode->keyName));
			}
			$builder->setOffsetValueType($offsetType, $this->resolve($itemNode->valueType, $nameScope), $itemNode->optional);
		}

		return $builder->getArray();
	}

	private function resolveConstTypeNode(ConstTypeNode $typeNode, NameScope $nameScope): Type
	{
		$constExpr = $typeNode->constExpr;
		if ($constExpr instanceof ConstExprArrayNode) {
			throw new \PHPStan\ShouldNotHappenException(); // we prefer array shapes
		}

		if (
			$constExpr instanceof ConstExprFalseNode
			|| $constExpr instanceof ConstExprTrueNode
			|| $constExpr instanceof ConstExprNullNode
		) {
			throw new \PHPStan\ShouldNotHappenException(); // we prefer IdentifierTypeNode
		}

		if ($constExpr instanceof ConstFetchNode) {
			if ($constExpr->className === '') {
				throw new \PHPStan\ShouldNotHappenException(); // global constant should get parsed as class name in IdentifierTypeNode
			}

			if ($nameScope->getClassName() !== null) {
				switch (strtolower($constExpr->className)) {
					case 'static':
					case 'self':
						$className = $nameScope->getClassName();
						break;

					case 'parent':
						if ($this->getReflectionProvider()->hasClass($nameScope->getClassName())) {
							$classReflection = $this->getReflectionProvider()->getClass($nameScope->getClassName());
							if ($classReflection->getParentClass() === false) {
								return new ErrorType();

							}

							$className = $classReflection->getParentClass()->getName();
						}
				}
			}

			if (!isset($className)) {
				$className = $nameScope->resolveStringName($constExpr->className);
			}

			if (!$this->getReflectionProvider()->hasClass($className)) {
				return new ErrorType();
			}

			$classReflection = $this->getReflectionProvider()->getClass($className);

			$constantName = $constExpr->name;
			if (Strings::endsWith($constantName, '*')) {
				$constantNameStartsWith = Strings::substring($constantName, 0, Strings::length($constantName) - 1);
				$constantTypes = [];
				foreach ($classReflection->getNativeReflection()->getConstants() as $classConstantName => $constantValue) {
					if (!Strings::startsWith($classConstantName, $constantNameStartsWith)) {
						continue;
					}

					$constantTypes[] = ConstantTypeHelper::getTypeFromValue($constantValue);
				}

				if (count($constantTypes) === 0) {
					return new ErrorType();
				}

				return TypeCombinator::union(...$constantTypes);
			}

			if (!$classReflection->hasConstant($constantName)) {
				return new ErrorType();
			}

			return $classReflection->getConstant($constantName)->getValueType();
		}

		if ($constExpr instanceof ConstExprFloatNode) {
			return ConstantTypeHelper::getTypeFromValue((float) $constExpr->value);
		}

		if ($constExpr instanceof ConstExprIntegerNode) {
			return ConstantTypeHelper::getTypeFromValue((int) $constExpr->value);
		}

		if ($constExpr instanceof ConstExprStringNode) {
			return ConstantTypeHelper::getTypeFromValue($constExpr->value);
		}

		return new ErrorType();
	}

	/**
	 * @param TypeNode[] $typeNodes
	 * @param NameScope $nameScope
	 * @return Type[]
	 */
	public function resolveMultiple(array $typeNodes, NameScope $nameScope): array
	{
		$types = [];
		foreach ($typeNodes as $typeNode) {
			$types[] = $this->resolve($typeNode, $nameScope);
		}

		return $types;
	}

	private function getReflectionProvider(): ReflectionProvider
	{
		return $this->container->getByType(ReflectionProvider::class);
	}

}
