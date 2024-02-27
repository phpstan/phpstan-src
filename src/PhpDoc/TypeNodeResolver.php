<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use Closure;
use Generator;
use Iterator;
use IteratorAggregate;
use Nette\Utils\Strings;
use PhpParser\Node\Name;
use PHPStan\Analyser\ConstantResolver;
use PHPStan\Analyser\NameScope;
use PHPStan\PhpDoc\Tag\TemplateTag;
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
use PHPStan\PhpDocParser\Ast\Type\ConditionalTypeForParameterNode;
use PHPStan\PhpDocParser\Ast\Type\ConditionalTypeNode;
use PHPStan\PhpDocParser\Ast\Type\ConstTypeNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IntersectionTypeNode;
use PHPStan\PhpDocParser\Ast\Type\InvalidTypeNode;
use PHPStan\PhpDocParser\Ast\Type\NullableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\ObjectShapeNode;
use PHPStan\PhpDocParser\Ast\Type\OffsetAccessTypeNode;
use PHPStan\PhpDocParser\Ast\Type\ThisTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use PHPStan\Reflection\InitializerExprContext;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\Native\NativeParameterReflection;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\AccessoryLiteralStringType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\CallableType;
use PHPStan\Type\ClassStringType;
use PHPStan\Type\ClosureType;
use PHPStan\Type\ConditionalType;
use PHPStan\Type\ConditionalTypeForParameter;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Enum\EnumCaseObjectType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\FloatType;
use PHPStan\Type\Generic\GenericClassStringType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeFactory;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeScope;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Helper\GetTemplateTypeType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\IterableType;
use PHPStan\Type\KeyOfType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NonAcceptingNeverType;
use PHPStan\Type\NonexistentParentClassType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectShapeType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\OffsetAccessType;
use PHPStan\Type\ResourceType;
use PHPStan\Type\StaticType;
use PHPStan\Type\StaticTypeFactory;
use PHPStan\Type\StringAlwaysAcceptingObjectWithToStringType;
use PHPStan\Type\StringType;
use PHPStan\Type\ThisType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeAliasResolver;
use PHPStan\Type\TypeAliasResolverProvider;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\UnionType;
use PHPStan\Type\ValueOfType;
use PHPStan\Type\VoidType;
use Traversable;
use function array_key_exists;
use function array_map;
use function count;
use function explode;
use function get_class;
use function in_array;
use function max;
use function min;
use function preg_match;
use function preg_quote;
use function str_contains;
use function str_replace;
use function str_starts_with;
use function strtolower;
use function substr;

class TypeNodeResolver
{

	/** @var array<string, true> */
	private array $genericTypeResolvingStack = [];

	public function __construct(
		private TypeNodeResolverExtensionRegistryProvider $extensionRegistryProvider,
		private ReflectionProvider\ReflectionProviderProvider $reflectionProviderProvider,
		private TypeAliasResolverProvider $typeAliasResolverProvider,
		private ConstantResolver $constantResolver,
		private InitializerExprTypeResolver $initializerExprTypeResolver,
	)
	{
	}

	/** @api */
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

		} elseif ($typeNode instanceof ConditionalTypeNode) {
			return $this->resolveConditionalTypeNode($typeNode, $nameScope);

		} elseif ($typeNode instanceof ConditionalTypeForParameterNode) {
			return $this->resolveConditionalTypeForParameterNode($typeNode, $nameScope);

		} elseif ($typeNode instanceof ArrayTypeNode) {
			return $this->resolveArrayTypeNode($typeNode, $nameScope);

		} elseif ($typeNode instanceof GenericTypeNode) {
			return $this->resolveGenericTypeNode($typeNode, $nameScope);

		} elseif ($typeNode instanceof CallableTypeNode) {
			return $this->resolveCallableTypeNode($typeNode, $nameScope);

		} elseif ($typeNode instanceof ArrayShapeNode) {
			return $this->resolveArrayShapeNode($typeNode, $nameScope);
		} elseif ($typeNode instanceof ObjectShapeNode) {
			return $this->resolveObjectShapeNode($typeNode, $nameScope);
		} elseif ($typeNode instanceof ConstTypeNode) {
			return $this->resolveConstTypeNode($typeNode, $nameScope);
		} elseif ($typeNode instanceof OffsetAccessTypeNode) {
			return $this->resolveOffsetAccessNode($typeNode, $nameScope);
		} elseif ($typeNode instanceof InvalidTypeNode) {
			return new MixedType(true);
		}

		return new ErrorType();
	}

	private function resolveIdentifierTypeNode(IdentifierTypeNode $typeNode, NameScope $nameScope): Type
	{
		switch (strtolower($typeNode->name)) {
			case 'int':
			case 'integer':
				return new IntegerType();

			case 'positive-int':
				return IntegerRangeType::fromInterval(1, null);

			case 'negative-int':
				return IntegerRangeType::fromInterval(null, -1);

			case 'non-positive-int':
				return IntegerRangeType::fromInterval(null, 0);

			case 'non-negative-int':
				return IntegerRangeType::fromInterval(0, null);

			case 'non-zero-int':
				return new UnionType([
					IntegerRangeType::fromInterval(null, -1),
					IntegerRangeType::fromInterval(1, null),
				]);

			case 'string':
			case 'lowercase-string':
				return new StringType();

			case 'literal-string':
				return new IntersectionType([new StringType(), new AccessoryLiteralStringType()]);

			case 'class-string':
			case 'interface-string':
			case 'trait-string':
			case 'enum-string':
				return new ClassStringType();

			case 'callable-string':
				return new IntersectionType([new StringType(), new CallableType()]);

			case 'array-key':
				return new BenevolentUnionType([new IntegerType(), new StringType()]);

			case 'scalar':
				$type = $this->tryResolvePseudoTypeClassType($typeNode, $nameScope);

				if ($type !== null) {
					return $type;
				}

				return new UnionType([new IntegerType(), new FloatType(), new StringType(), new BooleanType()]);

			case 'empty-scalar':
				return TypeCombinator::intersect(
					new UnionType([new IntegerType(), new FloatType(), new StringType(), new BooleanType()]),
					StaticTypeFactory::falsey(),
				);

			case 'non-empty-scalar':
				return TypeCombinator::remove(
					new UnionType([new IntegerType(), new FloatType(), new StringType(), new BooleanType()]),
					StaticTypeFactory::falsey(),
				);

			case 'number':
				$type = $this->tryResolvePseudoTypeClassType($typeNode, $nameScope);

				if ($type !== null) {
					return $type;
				}

				return new UnionType([new IntegerType(), new FloatType()]);

			case 'numeric':
				$type = $this->tryResolvePseudoTypeClassType($typeNode, $nameScope);

				if ($type !== null) {
					return $type;
				}

				return new UnionType([
					new IntegerType(),
					new FloatType(),
					new IntersectionType([
						new StringType(),
						new AccessoryNumericStringType(),
					]),
				]);

			case 'numeric-string':
				return new IntersectionType([
					new StringType(),
					new AccessoryNumericStringType(),
				]);

			case 'non-empty-string':
			case 'non-empty-lowercase-string':
				return new IntersectionType([
					new StringType(),
					new AccessoryNonEmptyStringType(),
				]);

			case 'truthy-string':
			case 'non-falsy-string':
				return new IntersectionType([
					new StringType(),
					new AccessoryNonFalsyStringType(),
				]);

			case 'non-empty-literal-string':
				return new IntersectionType([
					new StringType(),
					new AccessoryNonEmptyStringType(),
					new AccessoryLiteralStringType(),
				]);

			case 'bool':
				return new BooleanType();

			case 'boolean':
				$type = $this->tryResolvePseudoTypeClassType($typeNode, $nameScope);

				if ($type !== null) {
					return $type;
				}

				return new BooleanType();

			case 'true':
				return new ConstantBooleanType(true);

			case 'false':
				return new ConstantBooleanType(false);

			case 'null':
				return new NullType();

			case 'float':
				return new FloatType();

			case 'double':
				$type = $this->tryResolvePseudoTypeClassType($typeNode, $nameScope);

				if ($type !== null) {
					return $type;
				}

				return new FloatType();

			case 'array':
			case 'associative-array':
				return new ArrayType(new MixedType(), new MixedType());

			case 'non-empty-array':
				return TypeCombinator::intersect(
					new ArrayType(new MixedType(), new MixedType()),
					new NonEmptyArrayType(),
				);

			case 'iterable':
				return new IterableType(new MixedType(), new MixedType());

			case 'callable':
			case 'pure-callable':
				return new CallableType();

			case 'resource':
				$type = $this->tryResolvePseudoTypeClassType($typeNode, $nameScope);

				if ($type !== null) {
					return $type;
				}

				return new ResourceType();

			case 'open-resource':
			case 'closed-resource':
				return new ResourceType();

			case 'mixed':
				return new MixedType(true);

			case 'non-empty-mixed':
				return new MixedType(true, StaticTypeFactory::falsey());

			case 'void':
				return new VoidType();

			case 'object':
				return new ObjectWithoutClassType();

			case 'callable-object':
				return new IntersectionType([new ObjectWithoutClassType(), new CallableType()]);

			case 'callable-array':
				return new IntersectionType([new ArrayType(new MixedType(), new MixedType()), new CallableType()]);

			case 'never':
			case 'noreturn':
				$type = $this->tryResolvePseudoTypeClassType($typeNode, $nameScope);

				if ($type !== null) {
					return $type;
				}

				return new NonAcceptingNeverType();

			case 'never-return':
			case 'never-returns':
			case 'no-return':
				return new NonAcceptingNeverType();

			case 'list':
				return AccessoryArrayListType::intersectWith(new ArrayType(new IntegerType(), new MixedType()));
			case 'non-empty-list':
				return AccessoryArrayListType::intersectWith(TypeCombinator::intersect(
					new ArrayType(new IntegerType(), new MixedType()),
					new NonEmptyArrayType(),
				));
			case '__always-list':
				return TypeCombinator::intersect(
					new ArrayType(new IntegerType(), new MixedType()),
					new AccessoryArrayListType(),
				);

			case 'empty':
				$type = $this->tryResolvePseudoTypeClassType($typeNode, $nameScope);
				if ($type !== null) {
					return $type;
				}

				return StaticTypeFactory::falsey();
			case '__stringandstringable':
				return new StringAlwaysAcceptingObjectWithToStringType();
		}

		if ($nameScope->getClassName() !== null) {
			switch (strtolower($typeNode->name)) {
				case 'self':
					return new ObjectType($nameScope->getClassName());

				case 'static':
					if ($this->getReflectionProvider()->hasClass($nameScope->getClassName())) {
						$classReflection = $this->getReflectionProvider()->getClass($nameScope->getClassName());

						return new StaticType($classReflection);
					}

					return new ErrorType();
				case 'parent':
					if ($this->getReflectionProvider()->hasClass($nameScope->getClassName())) {
						$classReflection = $this->getReflectionProvider()->getClass($nameScope->getClassName());
						if ($classReflection->getParentClass() !== null) {
							return new ObjectType($classReflection->getParentClass()->getName());
						}
					}

					return new NonexistentParentClassType();
			}
		}

		if (!$nameScope->shouldBypassTypeAliases()) {
			$typeAlias = $this->getTypeAliasResolver()->resolveTypeAlias($typeNode->name, $nameScope);
			if ($typeAlias !== null) {
				return $typeAlias;
			}
		}

		$templateType = $nameScope->resolveTemplateTypeName($typeNode->name);
		if ($templateType !== null) {
			return $templateType;
		}

		$stringName = $nameScope->resolveStringName($typeNode->name);
		if (str_contains($stringName, '-') && !str_starts_with($stringName, 'OCI-')) {
			return new ErrorType();
		}

		if ($this->mightBeConstant($typeNode->name) && !$this->getReflectionProvider()->hasClass($stringName)) {
			$constType = $this->tryResolveConstant($typeNode->name, $nameScope);
			if ($constType !== null) {
				return $constType;
			}
		}

		return new ObjectType($stringName);
	}

	private function mightBeConstant(string $name): bool
	{
		return preg_match('((?:^|\\\\)[A-Z_][A-Z0-9_]*$)', $name) > 0;
	}

	private function tryResolveConstant(string $name, NameScope $nameScope): ?Type
	{
		foreach ($nameScope->resolveConstantNames($name) as $constName) {
			$nameNode = new Name\FullyQualified(explode('\\', $constName));
			$constType = $this->constantResolver->resolveConstant($nameNode, null);
			if ($constType !== null) {
				return $constType;
			}
		}

		return null;
	}

	private function tryResolvePseudoTypeClassType(IdentifierTypeNode $typeNode, NameScope $nameScope): ?Type
	{
		if ($nameScope->hasUseAlias($typeNode->name)) {
			return new ObjectType($nameScope->resolveStringName($typeNode->name));
		}

		if ($nameScope->getNamespace() === null) {
			return null;
		}

		$className = $nameScope->resolveStringName($typeNode->name);

		if ($this->getReflectionProvider()->hasClass($className)) {
			return new ObjectType($className);
		}

		return null;
	}

	private function resolveThisTypeNode(ThisTypeNode $typeNode, NameScope $nameScope): Type
	{
		$className = $nameScope->getClassName();
		if ($className !== null) {
			if ($this->getReflectionProvider()->hasClass($className)) {
				return new ThisType($this->getReflectionProvider()->getClass($className));
			}
		}

		return new ErrorType();
	}

	private function resolveNullableTypeNode(NullableTypeNode $typeNode, NameScope $nameScope): Type
	{
		return TypeCombinator::union($this->resolve($typeNode->type, $nameScope), new NullType());
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

	private function resolveConditionalTypeNode(ConditionalTypeNode $typeNode, NameScope $nameScope): Type
	{
		return new ConditionalType(
			$this->resolve($typeNode->subjectType, $nameScope),
			$this->resolve($typeNode->targetType, $nameScope),
			$this->resolve($typeNode->if, $nameScope),
			$this->resolve($typeNode->else, $nameScope),
			$typeNode->negated,
		);
	}

	private function resolveConditionalTypeForParameterNode(ConditionalTypeForParameterNode $typeNode, NameScope $nameScope): Type
	{
		return new ConditionalTypeForParameter(
			$typeNode->parameterName,
			$this->resolve($typeNode->targetType, $nameScope),
			$this->resolve($typeNode->if, $nameScope),
			$this->resolve($typeNode->else, $nameScope),
			$typeNode->negated,
		);
	}

	private function resolveArrayTypeNode(ArrayTypeNode $typeNode, NameScope $nameScope): Type
	{
		$itemType = $this->resolve($typeNode->type, $nameScope);
		return new ArrayType(new BenevolentUnionType([new IntegerType(), new StringType()]), $itemType);
	}

	private function resolveGenericTypeNode(GenericTypeNode $typeNode, NameScope $nameScope): Type
	{
		$mainTypeName = strtolower($typeNode->type->name);
		$genericTypes = $this->resolveMultiple($typeNode->genericTypes, $nameScope);
		$variances = array_map(
			static function (string $variance): TemplateTypeVariance {
				switch ($variance) {
					case GenericTypeNode::VARIANCE_INVARIANT:
						return TemplateTypeVariance::createInvariant();
					case GenericTypeNode::VARIANCE_COVARIANT:
						return TemplateTypeVariance::createCovariant();
					case GenericTypeNode::VARIANCE_CONTRAVARIANT:
						return TemplateTypeVariance::createContravariant();
					case GenericTypeNode::VARIANCE_BIVARIANT:
						return TemplateTypeVariance::createBivariant();
				}
			},
			$typeNode->variances,
		);

		if (in_array($mainTypeName, ['array', 'non-empty-array'], true)) {
			if (count($genericTypes) === 1) { // array<ValueType>
				$arrayType = new ArrayType(new BenevolentUnionType([new IntegerType(), new StringType()]), $genericTypes[0]);
			} elseif (count($genericTypes) === 2) { // array<KeyType, ValueType>
				$keyType = TypeCombinator::intersect($genericTypes[0], new UnionType([
					new IntegerType(),
					new StringType(),
				]));
				$arrayType = new ArrayType($keyType->toArrayKey(), $genericTypes[1]);
			} else {
				return new ErrorType();
			}

			if ($mainTypeName === 'non-empty-array') {
				return TypeCombinator::intersect($arrayType, new NonEmptyArrayType());
			}

			return $arrayType;
		} elseif (in_array($mainTypeName, ['list', 'non-empty-list'], true)) {
			if (count($genericTypes) === 1) { // list<ValueType>
				$listType = AccessoryArrayListType::intersectWith(new ArrayType(new IntegerType(), $genericTypes[0]));
				if ($mainTypeName === 'non-empty-list') {
					return TypeCombinator::intersect($listType, new NonEmptyArrayType());
				}

				return $listType;
			}

			return new ErrorType();
		} elseif ($mainTypeName === 'iterable') {
			if (count($genericTypes) === 1) { // iterable<ValueType>
				return new IterableType(new MixedType(true), $genericTypes[0]);

			}

			if (count($genericTypes) === 2) { // iterable<KeyType, ValueType>
				return new IterableType($genericTypes[0], $genericTypes[1]);
			}
		} elseif (in_array($mainTypeName, ['class-string', 'interface-string'], true)) {
			if (count($genericTypes) === 1) {
				$genericType = $genericTypes[0];
				if ($genericType->isObject()->yes() || $genericType instanceof MixedType) {
					return new GenericClassStringType($genericType);
				}
			}

			return new ErrorType();
		} elseif ($mainTypeName === 'int') {
			if (count($genericTypes) === 2) { // int<min, max>, int<1, 3>

				if ($genericTypes[0] instanceof ConstantIntegerType) {
					$min = $genericTypes[0]->getValue();
				} elseif ($typeNode->genericTypes[0] instanceof IdentifierTypeNode && $typeNode->genericTypes[0]->name === 'min') {
					$min = null;
				} else {
					return new ErrorType();
				}

				if ($genericTypes[1] instanceof ConstantIntegerType) {
					$max = $genericTypes[1]->getValue();
				} elseif ($typeNode->genericTypes[1] instanceof IdentifierTypeNode && $typeNode->genericTypes[1]->name === 'max') {
					$max = null;
				} else {
					return new ErrorType();
				}

				return IntegerRangeType::fromInterval($min, $max);
			}
		} elseif ($mainTypeName === 'key-of') {
			if (count($genericTypes) === 1) { // key-of<ValueType>
				$type = new KeyOfType($genericTypes[0]);
				return $type->isResolvable() ? $type->resolve() : $type;
			}

			return new ErrorType();
		} elseif ($mainTypeName === 'value-of') {
			if (count($genericTypes) === 1) { // value-of<ValueType>
				$type = new ValueOfType($genericTypes[0]);

				return $type->isResolvable() ? $type->resolve() : $type;
			}

			return new ErrorType();
		} elseif ($mainTypeName === 'int-mask-of') {
			if (count($genericTypes) === 1) { // int-mask-of<Class::CONST*>
				$maskType = $this->expandIntMaskToType($genericTypes[0]);
				if ($maskType !== null) {
					return $maskType;
				}
			}

			return new ErrorType();
		} elseif ($mainTypeName === 'int-mask') {
			if (count($genericTypes) > 0) { // int-mask<1, 2, 4>
				$maskType = $this->expandIntMaskToType(TypeCombinator::union(...$genericTypes));
				if ($maskType !== null) {
					return $maskType;
				}
			}

			return new ErrorType();
		} elseif ($mainTypeName === '__benevolent') {
			if (count($genericTypes) === 1) {
				return TypeUtils::toBenevolentUnion($genericTypes[0]);
			}
			return new ErrorType();
		} elseif ($mainTypeName === 'template-type') {
			if (count($genericTypes) === 3) {
				$result = [];
				/** @var class-string $ancestorClassName */
				foreach ($genericTypes[1]->getObjectClassNames() as $ancestorClassName) {
					foreach ($genericTypes[2]->getConstantStrings() as $templateTypeName) {
						$result[] = new GetTemplateTypeType($genericTypes[0], $ancestorClassName, $templateTypeName->getValue());
					}
				}

				return TypeCombinator::union(...$result);
			}

			return new ErrorType();
		}

		$mainType = $this->resolveIdentifierTypeNode($typeNode->type, $nameScope);
		$mainTypeObjectClassNames = $mainType->getObjectClassNames();
		if (count($mainTypeObjectClassNames) > 1) {
			if ($mainType instanceof TemplateType) {
				return new ErrorType();
			}
			throw new ShouldNotHappenException();
		}
		$mainTypeClassName = $mainTypeObjectClassNames[0] ?? null;

		if ($mainTypeClassName !== null) {
			if (!$this->getReflectionProvider()->hasClass($mainTypeClassName)) {
				return new GenericObjectType($mainTypeClassName, $genericTypes, null, null, $variances);
			}

			$classReflection = $this->getReflectionProvider()->getClass($mainTypeClassName);
			if ($classReflection->isGeneric()) {
				if (in_array($mainTypeClassName, [
					Traversable::class,
					IteratorAggregate::class,
					Iterator::class,
				], true)) {
					if (count($genericTypes) === 1) {
						return new GenericObjectType($mainTypeClassName, [
							new MixedType(true),
							$genericTypes[0],
						], null, null, [
							TemplateTypeVariance::createInvariant(),
							$variances[0],
						]);
					}

					if (count($genericTypes) === 2) {
						return new GenericObjectType($mainTypeClassName, [
							$genericTypes[0],
							$genericTypes[1],
						], null, null, [
							$variances[0],
							$variances[1],
						]);
					}
				}
				if ($mainTypeClassName === Generator::class) {
					if (count($genericTypes) === 1) {
						$mixed = new MixedType(true);
						return new GenericObjectType($mainTypeClassName, [
							$mixed,
							$genericTypes[0],
							$mixed,
							$mixed,
						], null, null, [
							TemplateTypeVariance::createInvariant(),
							$variances[0],
							TemplateTypeVariance::createInvariant(),
							TemplateTypeVariance::createInvariant(),
						]);
					}

					if (count($genericTypes) === 2) {
						$mixed = new MixedType(true);
						return new GenericObjectType($mainTypeClassName, [
							$genericTypes[0],
							$genericTypes[1],
							$mixed,
							$mixed,
						], null, null, [
							$variances[0],
							$variances[1],
							TemplateTypeVariance::createInvariant(),
							TemplateTypeVariance::createInvariant(),
						]);
					}
				}

				if (!$mainType->isIterable()->yes()) {
					return new GenericObjectType($mainTypeClassName, $genericTypes, null, null, $variances);
				}

				if (
					count($genericTypes) !== 1
					|| $classReflection->getTemplateTypeMap()->count() === 1
				) {
					return new GenericObjectType($mainTypeClassName, $genericTypes, null, null, $variances);
				}
			}
		}

		if ($mainType->isIterable()->yes()) {
			if ($mainTypeClassName !== null) {
				if (isset($this->genericTypeResolvingStack[$mainTypeClassName])) {
					return new ErrorType();
				}

				$this->genericTypeResolvingStack[$mainTypeClassName] = true;
			}

			try {
				if (count($genericTypes) === 1) { // Foo<ValueType>
					return TypeCombinator::intersect(
						$mainType,
						new IterableType(new MixedType(true), $genericTypes[0]),
					);
				}

				if (count($genericTypes) === 2) { // Foo<KeyType, ValueType>
					return TypeCombinator::intersect(
						$mainType,
						new IterableType($genericTypes[0], $genericTypes[1]),
					);
				}
			} finally {
				if ($mainTypeClassName !== null) {
					unset($this->genericTypeResolvingStack[$mainTypeClassName]);
				}
			}
		}

		if ($mainTypeClassName !== null) {
			return new GenericObjectType($mainTypeClassName, $genericTypes, null, null, $variances);
		}

		return new ErrorType();
	}

	private function resolveCallableTypeNode(CallableTypeNode $typeNode, NameScope $nameScope): Type
	{
		$templateTags = [];

		if (count($typeNode->templateTypes ?? []) > 0) {
			foreach ($typeNode->templateTypes as $templateType) {
				$templateTags[$templateType->name] = new TemplateTag(
					$templateType->name,
					$templateType->bound !== null
						? $this->resolve($templateType->bound, $nameScope)
						: new MixedType(),
					TemplateTypeVariance::createInvariant(),
				);
			}
			$templateTypeScope = TemplateTypeScope::createWithAnonymousFunction();

			$templateTypeMap = new TemplateTypeMap(array_map(
				static fn (TemplateTag $tag): Type => TemplateTypeFactory::fromTemplateTag($templateTypeScope, $tag),
				$templateTags,
			));

			$nameScope = $nameScope->withTemplateTypeMap($templateTypeMap);
		} else {
			$templateTypeMap = TemplateTypeMap::createEmpty();
		}

		$mainType = $this->resolve($typeNode->identifier, $nameScope);

		$isVariadic = false;
		$parameters = array_map(
			function (CallableTypeParameterNode $parameterNode) use ($nameScope, &$isVariadic): NativeParameterReflection {
				$isVariadic = $isVariadic || $parameterNode->isVariadic;
				$parameterName = $parameterNode->parameterName;
				if (str_starts_with($parameterName, '$')) {
					$parameterName = substr($parameterName, 1);
				}

				return new NativeParameterReflection(
					$parameterName,
					$parameterNode->isOptional || $parameterNode->isVariadic,
					$this->resolve($parameterNode->type, $nameScope),
					$parameterNode->isReference ? PassedByReference::createCreatesNewVariable() : PassedByReference::createNo(),
					$parameterNode->isVariadic,
					null,
				);
			},
			$typeNode->parameters,
		);

		$returnType = $this->resolve($typeNode->returnType, $nameScope);

		if ($mainType instanceof CallableType) {
			return new CallableType($parameters, $returnType, $isVariadic, $templateTypeMap, null, $templateTags);

		} elseif (
			$mainType instanceof ObjectType
			&& $mainType->getClassName() === Closure::class
		) {
			return new ClosureType($parameters, $returnType, $isVariadic, $templateTypeMap, null, null, $templateTags);
		}

		return new ErrorType();
	}

	private function resolveArrayShapeNode(ArrayShapeNode $typeNode, NameScope $nameScope): Type
	{
		$builder = ConstantArrayTypeBuilder::createEmpty();
		if (count($typeNode->items) > ConstantArrayTypeBuilder::ARRAY_COUNT_LIMIT) {
			$builder->degradeToGeneralArray(true);
		}

		foreach ($typeNode->items as $itemNode) {
			$offsetType = null;
			if ($itemNode->keyName instanceof ConstExprIntegerNode) {
				$offsetType = new ConstantIntegerType((int) $itemNode->keyName->value);
			} elseif ($itemNode->keyName instanceof IdentifierTypeNode) {
				$offsetType = new ConstantStringType($itemNode->keyName->name);
			} elseif ($itemNode->keyName instanceof ConstExprStringNode) {
				$offsetType = new ConstantStringType($itemNode->keyName->value);
			} elseif ($itemNode->keyName !== null) {
				throw new ShouldNotHappenException('Unsupported key node type: ' . get_class($itemNode->keyName));
			}
			$builder->setOffsetValueType($offsetType, $this->resolve($itemNode->valueType, $nameScope), $itemNode->optional);
		}

		$arrayType = $builder->getArray();
		if ($typeNode->kind === ArrayShapeNode::KIND_LIST) {
			$arrayType = AccessoryArrayListType::intersectWith($arrayType);
		}

		return $arrayType;
	}

	private function resolveObjectShapeNode(ObjectShapeNode $typeNode, NameScope $nameScope): Type
	{
		$properties = [];
		$optionalProperties = [];
		foreach ($typeNode->items as $itemNode) {
			if ($itemNode->keyName instanceof IdentifierTypeNode) {
				$propertyName = $itemNode->keyName->name;
			} elseif ($itemNode->keyName instanceof ConstExprStringNode) {
				$propertyName = $itemNode->keyName->value;
			}

			if ($itemNode->optional) {
				$optionalProperties[] = $propertyName;
			}

			$properties[$propertyName] = $this->resolve($itemNode->valueType, $nameScope);
		}

		return new ObjectShapeType($properties, $optionalProperties);
	}

	private function resolveConstTypeNode(ConstTypeNode $typeNode, NameScope $nameScope): Type
	{
		$constExpr = $typeNode->constExpr;
		if ($constExpr instanceof ConstExprArrayNode) {
			throw new ShouldNotHappenException(); // we prefer array shapes
		}

		if (
			$constExpr instanceof ConstExprFalseNode
			|| $constExpr instanceof ConstExprTrueNode
			|| $constExpr instanceof ConstExprNullNode
		) {
			throw new ShouldNotHappenException(); // we prefer IdentifierTypeNode
		}

		if ($constExpr instanceof ConstFetchNode) {
			if ($constExpr->className === '') {
				throw new ShouldNotHappenException(); // global constant should get parsed as class name in IdentifierTypeNode
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
							if ($classReflection->getParentClass() === null) {
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
			if (Strings::contains($constantName, '*')) {
				// convert * into .*? and escape everything else so the constants can be matched against the pattern
				$pattern = '{^' . str_replace('\\*', '.*?', preg_quote($constantName)) . '$}D';
				$constantTypes = [];
				foreach ($classReflection->getNativeReflection()->getReflectionConstants() as $reflectionConstant) {
					$classConstantName = $reflectionConstant->getName();
					if (Strings::match($classConstantName, $pattern) === null) {
						continue;
					}

					if ($classReflection->isEnum() && $classReflection->hasEnumCase($classConstantName)) {
						$constantTypes[] = new EnumCaseObjectType($classReflection->getName(), $classConstantName);
						continue;
					}

					$declaringClassName = $reflectionConstant->getDeclaringClass()->getName();
					if (!$this->getReflectionProvider()->hasClass($declaringClassName)) {
						continue;
					}

					$constantTypes[] = $this->initializerExprTypeResolver->getType(
						$reflectionConstant->getValueExpression(),
						InitializerExprContext::fromClassReflection(
							$this->getReflectionProvider()->getClass($declaringClassName),
						),
					);
				}

				if (count($constantTypes) === 0) {
					return new ErrorType();
				}

				return TypeCombinator::union(...$constantTypes);
			}

			if (!$classReflection->hasConstant($constantName)) {
				return new ErrorType();
			}

			if ($classReflection->isEnum() && $classReflection->hasEnumCase($constantName)) {
				return new EnumCaseObjectType($classReflection->getName(), $constantName);
			}

			$reflectionConstant = $classReflection->getNativeReflection()->getReflectionConstant($constantName);
			if ($reflectionConstant === false) {
				return new ErrorType();
			}
			$declaringClass = $reflectionConstant->getDeclaringClass();

			return $this->initializerExprTypeResolver->getType($reflectionConstant->getValueExpression(), InitializerExprContext::fromClass($declaringClass->getName(), $declaringClass->getFileName() ?: null));
		}

		if ($constExpr instanceof ConstExprFloatNode) {
			return new ConstantFloatType((float) $constExpr->value);
		}

		if ($constExpr instanceof ConstExprIntegerNode) {
			return new ConstantIntegerType((int) $constExpr->value);
		}

		if ($constExpr instanceof ConstExprStringNode) {
			return new ConstantStringType($constExpr->value);
		}

		return new ErrorType();
	}

	private function resolveOffsetAccessNode(OffsetAccessTypeNode $typeNode, NameScope $nameScope): Type
	{
		$type = $this->resolve($typeNode->type, $nameScope);
		$offset = $this->resolve($typeNode->offset, $nameScope);

		if ($type->isOffsetAccessible()->no() || $type->hasOffsetValueType($offset)->no()) {
			return new ErrorType();
		}

		return new OffsetAccessType($type, $offset);
	}

	private function expandIntMaskToType(Type $type): ?Type
	{
		$ints = array_map(static fn (ConstantIntegerType $type) => $type->getValue(), TypeUtils::getConstantIntegers($type));
		if (count($ints) === 0) {
			return null;
		}

		$values = [];

		foreach ($ints as $int) {
			if ($int !== 0 && !array_key_exists($int, $values)) {
				foreach ($values as $value) {
					$computedValue = $value | $int;
					$values[$computedValue] = $computedValue;
				}
			}

			$values[$int] = $int;
		}

		$values[0] = 0;

		$min = min($values);
		$max = max($values);

		if ($max - $min === count($values) - 1) {
			return IntegerRangeType::fromInterval($min, $max);
		}

		return TypeCombinator::union(...array_map(static fn ($value) => new ConstantIntegerType($value), $values));
	}

	/**
	 * @api
	 * @param TypeNode[] $typeNodes
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
		return $this->reflectionProviderProvider->getReflectionProvider();
	}

	private function getTypeAliasResolver(): TypeAliasResolver
	{
		return $this->typeAliasResolverProvider->getTypeAliasResolver();
	}

}
