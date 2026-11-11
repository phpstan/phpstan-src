<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use Closure;
use PhpParser\Node\Arg;
use PhpParser\Node\ArgPlaceholder;
use PhpParser\Node\ComplexType;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Expr\Cast\Array_;
use PhpParser\Node\Expr\Cast\Bool_;
use PhpParser\Node\Expr\Cast\Double;
use PhpParser\Node\Expr\Cast\Object_;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\Scalar\Float_;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Scalar\MagicConst;
use PhpParser\Node\Scalar\MagicConst\Dir;
use PhpParser\Node\Scalar\MagicConst\File;
use PhpParser\Node\Scalar\MagicConst\Line;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\VariadicPlaceholder;
use PHPStan\Analyser\ConstantResolver;
use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Analyser\ValueDependencyCollector;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Expr\TypeExpr;
use PHPStan\Php\PhpVersion;
use PHPStan\Php\PhpVersions;
use PHPStan\PhpDoc\Tag\TemplateTag;
use PHPStan\Reflection\Callables\CallableParametersAcceptor;
use PHPStan\Reflection\Callables\SimpleImpurePoint;
use PHPStan\Reflection\Callables\SimpleThrowPoint;
use PHPStan\Reflection\Native\NativeParameterReflection;
use PHPStan\Reflection\Php\ExtendedDummyParameter;
use PHPStan\Reflection\ReflectionProvider\ReflectionProviderProvider;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\AccessoryLiteralStringType;
use PHPStan\Type\Accessory\HasOffsetValueType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\CallableAssertionsHelper;
use PHPStan\Type\ClassStringType;
use PHPStan\Type\ClosureOrigin;
use PHPStan\Type\ClosureType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Constant\OversizedArrayBuilder;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\Enum\EnumCaseObjectType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\FloatType;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\Generic\GenericClassStringType;
use PHPStan\Type\Generic\GenericStaticType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVarianceMap;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NonexistentParentClassType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectShapeType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\OperatorTypeSpecifyingExtensionRegistry;
use PHPStan\Type\ParserNodeTypeToPHPStanType;
use PHPStan\Type\StaticType;
use PHPStan\Type\StringSet;
use PHPStan\Type\StringType;
use PHPStan\Type\ThisType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypehintHelper;
use PHPStan\Type\TypeResult;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\TypeWithClassName;
use PHPStan\Type\UnaryOperatorTypeSpecifyingExtensionRegistry;
use PHPStan\Type\UnionType;
use PHPStan\Type\ValueSet;
use stdClass;
use Throwable;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_merge;
use function array_slice;
use function array_values;
use function count;
use function dirname;
use function in_array;
use function is_string;
use function sprintf;
use function strtolower;

#[AutowiredService]
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../turbo-ext/src/InitializerExprTypeResolver.cpp')]
final class InitializerExprTypeResolver
{

	public const CALCULATE_SCALARS_LIMIT = 128;

	/** @var array<string, true> */
	private array $currentlyResolvingClassConstant = [];

	/** @var array<string, Type> */
	private array $classConstantValueTypeCache = [];

	public function __construct(
		private ConstantResolver $constantResolver,
		private ReflectionProviderProvider $reflectionProviderProvider,
		private PhpVersion $phpVersion,
		private OperatorTypeSpecifyingExtensionRegistry $operatorTypeSpecifyingExtensionRegistry,
		private UnaryOperatorTypeSpecifyingExtensionRegistry $unaryOperatorTypeSpecifyingExtensionRegistry,
		private OversizedArrayBuilder $oversizedArrayBuilder,
		#[AutowiredParameter]
		private bool $usePathConstantsAsConstantString,
		private ValueDependencyCollector $valueDependencyCollector,
	)
	{
	}

	/** @api */
	public function getType(Expr $expr, InitializerExprContext $context): Type
	{
		if ($expr instanceof TypeExpr) {
			return $expr->getExprType();
		}
		if ($expr instanceof Int_) {
			return new ConstantIntegerType($expr->value);
		}
		if ($expr instanceof Float_) {
			return new ConstantFloatType($expr->value);
		}
		if ($expr instanceof String_) {
			return new ConstantStringType($expr->value);
		}
		if ($expr instanceof ConstFetch) {
			$constName = (string) $expr->name;
			$loweredConstName = strtolower($constName);
			if ($loweredConstName === 'true') {
				return new ConstantBooleanType(true);
			} elseif ($loweredConstName === 'false') {
				return new ConstantBooleanType(false);
			} elseif ($loweredConstName === 'null') {
				return new NullType();
			}

			$constant = $this->constantResolver->resolveConstant($expr->name, $context);
			if ($constant !== null) {
				return $constant;
			}

			return new ErrorType();
		}
		if ($expr instanceof File) {
			$file = $context->getFile();
			if ($file === null) {
				return new StringType();
			}
			$stringType = new ConstantStringType($file);
			return $this->usePathConstantsAsConstantString ? $stringType : $stringType->generalize(GeneralizePrecision::moreSpecific());
		}
		if ($expr instanceof Dir) {
			$file = $context->getFile();
			if ($file === null) {
				return new StringType();
			}
			$stringType = new ConstantStringType(dirname($file));
			return $this->usePathConstantsAsConstantString ? $stringType : $stringType->generalize(GeneralizePrecision::moreSpecific());
		}
		if ($expr instanceof Line) {
			return new ConstantIntegerType($expr->getStartLine());
		}
		if ($expr instanceof Expr\New_) {
			if ($expr->class instanceof Name) {
				return new ObjectType((string) $expr->class);
			}

			return new ObjectWithoutClassType();
		}
		if ($expr instanceof Expr\Array_) {
			return $this->getArrayType($expr, fn (Expr $expr): Type => $this->getType($expr, $context));
		}
		if ($expr instanceof Expr\Cast) {
			return $this->getCastType($expr, fn (Expr $expr): Type => $this->getType($expr, $context));
		}
		if ($expr instanceof Expr\CallLike && $expr->isPartialFunctionApplication()) {
			return $this->getFirstClassCallableType($expr, $context, false);
		}
		if ($expr instanceof Expr\Closure && $expr->static) {
			$parameters = [];
			$isVariadic = false;
			$firstOptionalParameterIndex = null;
			foreach ($expr->params as $i => $param) {
				$isOptionalCandidate = $param->default !== null || $param->variadic;

				if ($isOptionalCandidate) {
					if ($firstOptionalParameterIndex === null) {
						$firstOptionalParameterIndex = $i;
					}
				} else {
					$firstOptionalParameterIndex = null;
				}
			}

			foreach ($expr->params as $i => $param) {
				if ($param->variadic) {
					$isVariadic = true;
				}
				if (!$param->var instanceof Variable || !is_string($param->var->name)) {
					throw new ShouldNotHappenException();
				}
				$parameters[] = new NativeParameterReflection(
					$param->var->name,
					$firstOptionalParameterIndex !== null && $i >= $firstOptionalParameterIndex,
					$this->getFunctionType($param->type, $this->isParameterValueNullable($param), false, $context),
					$param->byRef
						? PassedByReference::createCreatesNewVariable()
						: PassedByReference::createNo(),
					$param->variadic,
					$param->default !== null ? $this->getType($param->default, $context) : null,
				);
			}

			$returnType = new MixedType(false);
			if ($expr->returnType !== null) {
				$returnType = $this->getFunctionType($expr->returnType, false, false, $context);
			}

			return new ClosureType(
				$parameters,
				$returnType,
				$isVariadic,
				TemplateTypeMap::createEmpty(),
				TemplateTypeMap::createEmpty(),
				TemplateTypeVarianceMap::createEmpty(),
				acceptsNamedArguments: TrinaryLogic::createYes(),
				isStatic: TrinaryLogic::createYes(),
			);
		}
		if ($expr instanceof Expr\ArrayDimFetch && $expr->dim !== null) {
			$var = $this->getType($expr->var, $context);
			$dim = $this->getType($expr->dim, $context);
			return $var->getOffsetValueType($dim);
		}
		if ($expr instanceof ClassConstFetch && $expr->name instanceof Identifier) {
			return $this->getClassConstFetchType($expr->class, $expr->name->toString(), $context->getClassName(), fn (Expr $expr): Type => $this->getType($expr, $context));
		}
		if ($expr instanceof Expr\UnaryPlus) {
			return $this->getUnaryPlusType($expr->expr, fn (Expr $expr): Type => $this->getType($expr, $context));
		}
		if ($expr instanceof Expr\UnaryMinus) {
			return $this->getUnaryMinusType($expr->expr, fn (Expr $expr): Type => $this->getType($expr, $context));
		}
		if ($expr instanceof Expr\BinaryOp\Coalesce) {
			$leftType = $this->getType($expr->left, $context);
			$rightType = $this->getType($expr->right, $context);

			return TypeCombinator::union(TypeCombinator::removeNull($leftType), $rightType);
		}

		if ($expr instanceof Expr\Ternary) {
			$elseType = $this->getType($expr->else, $context);
			if ($expr->if === null) {
				$condType = $this->getType($expr->cond, $context);

				return TypeCombinator::union(
					TypeCombinator::removeFalsey($condType),
					$elseType,
				);
			}

			$ifType = $this->getType($expr->if, $context);

			return TypeCombinator::union(
				TypeCombinator::removeFalsey($ifType),
				$elseType,
			);
		}

		if ($expr instanceof Expr\FuncCall && $expr->name instanceof Name && $expr->name->toLowerString() === 'constant') {
			$firstArg = $expr->args[0] ?? null;
			if ($firstArg instanceof Arg && $firstArg->value instanceof String_) {
				$constant = $this->constantResolver->resolvePredefinedConstant($firstArg->value->value);
				if ($constant !== null) {
					return $constant;
				}
			}
		}

		if ($expr instanceof Expr\BooleanNot) {
			$exprBooleanType = $this->getType($expr->expr, $context)->toBoolean();

			if ($exprBooleanType instanceof ConstantBooleanType) {
				return new ConstantBooleanType(!$exprBooleanType->getValue());
			}

			return new BooleanType();
		}

		if ($expr instanceof Expr\BitwiseNot) {
			return $this->getBitwiseNotType($expr->expr, fn (Expr $expr): Type => $this->getType($expr, $context));
		}

		if ($expr instanceof Expr\BinaryOp\Concat) {
			return $this->getConcatType($expr->left, $expr->right, fn (Expr $expr): Type => $this->getType($expr, $context));
		}

		if ($expr instanceof Expr\BinaryOp\BitwiseAnd) {
			return $this->getBitwiseAndType($expr->left, $expr->right, fn (Expr $expr): Type => $this->getType($expr, $context));
		}

		if ($expr instanceof Expr\BinaryOp\BitwiseOr) {
			return $this->getBitwiseOrType($expr->left, $expr->right, fn (Expr $expr): Type => $this->getType($expr, $context));
		}

		if ($expr instanceof Expr\BinaryOp\BitwiseXor) {
			return $this->getBitwiseXorType($expr->left, $expr->right, fn (Expr $expr): Type => $this->getType($expr, $context));
		}

		if ($expr instanceof Expr\BinaryOp\Spaceship) {
			return $this->getSpaceshipType($expr->left, $expr->right, fn (Expr $expr): Type => $this->getType($expr, $context));
		}

		if (
			$expr instanceof Expr\BinaryOp\BooleanAnd
			|| $expr instanceof Expr\BinaryOp\LogicalAnd
			|| $expr instanceof Expr\BinaryOp\BooleanOr
			|| $expr instanceof Expr\BinaryOp\LogicalOr
		) {
			return new BooleanType();
		}

		if ($expr instanceof Expr\BinaryOp\Div) {
			return $this->getDivType($expr->left, $expr->right, fn (Expr $expr): Type => $this->getType($expr, $context));
		}

		if ($expr instanceof Expr\BinaryOp\Mod) {
			return $this->getModType($expr->left, $expr->right, fn (Expr $expr): Type => $this->getType($expr, $context));
		}

		if ($expr instanceof Expr\BinaryOp\Plus) {
			return $this->getPlusType($expr->left, $expr->right, fn (Expr $expr): Type => $this->getType($expr, $context));
		}

		if ($expr instanceof Expr\BinaryOp\Minus) {
			return $this->getMinusType($expr->left, $expr->right, fn (Expr $expr): Type => $this->getType($expr, $context));
		}

		if ($expr instanceof Expr\BinaryOp\Mul) {
			return $this->getMulType($expr->left, $expr->right, fn (Expr $expr): Type => $this->getType($expr, $context));
		}

		if ($expr instanceof Expr\BinaryOp\Pow) {
			return $this->getPowType($expr->left, $expr->right, fn (Expr $expr): Type => $this->getType($expr, $context));
		}

		if ($expr instanceof Expr\BinaryOp\ShiftLeft) {
			return $this->getShiftLeftType($expr->left, $expr->right, fn (Expr $expr): Type => $this->getType($expr, $context));
		}

		if ($expr instanceof Expr\BinaryOp\ShiftRight) {
			return $this->getShiftRightType($expr->left, $expr->right, fn (Expr $expr): Type => $this->getType($expr, $context));
		}

		if ($expr instanceof BinaryOp\Identical) {
			return $this->resolveIdenticalType(
				$this->getType($expr->left, $context),
				$this->getType($expr->right, $context),
			)->type;
		}

		if ($expr instanceof BinaryOp\NotIdentical) {
			return $this->getType(new Expr\BooleanNot(new BinaryOp\Identical($expr->left, $expr->right)), $context);
		}

		if ($expr instanceof BinaryOp\Equal) {
			return $this->resolveEqualType(
				$this->getType($expr->left, $context),
				$this->getType($expr->right, $context),
			)->type;
		}

		if ($expr instanceof BinaryOp\NotEqual) {
			return $this->getType(new Expr\BooleanNot(new BinaryOp\Equal($expr->left, $expr->right)), $context);
		}

		if ($expr instanceof Expr\BinaryOp\Smaller) {
			return $this->getType($expr->left, $context)->isSmallerThan($this->getType($expr->right, $context), $this->phpVersion)->toBooleanType();
		}

		if ($expr instanceof Expr\BinaryOp\SmallerOrEqual) {
			return $this->getType($expr->left, $context)->isSmallerThanOrEqual($this->getType($expr->right, $context), $this->phpVersion)->toBooleanType();
		}

		if ($expr instanceof Expr\BinaryOp\Greater) {
			return $this->getType($expr->right, $context)->isSmallerThan($this->getType($expr->left, $context), $this->phpVersion)->toBooleanType();
		}

		if ($expr instanceof Expr\BinaryOp\GreaterOrEqual) {
			return $this->getType($expr->right, $context)->isSmallerThanOrEqual($this->getType($expr->left, $context), $this->phpVersion)->toBooleanType();
		}

		if ($expr instanceof Expr\BinaryOp\LogicalXor) {
			$leftBooleanType = $this->getType($expr->left, $context)->toBoolean();
			$rightBooleanType = $this->getType($expr->right, $context)->toBoolean();

			if (
				$leftBooleanType instanceof ConstantBooleanType
				&& $rightBooleanType instanceof ConstantBooleanType
			) {
				return new ConstantBooleanType(
					$leftBooleanType->getValue() xor $rightBooleanType->getValue(),
				);
			}

			return new BooleanType();
		}

		if ($expr instanceof MagicConst\Class_) {
			if ($context->getTraitName() !== null) {
				return new IntersectionType([
					new ClassStringType(),
					new AccessoryLiteralStringType(),
				]);
			}

			if ($context->getClassName() === null) {
				return new ConstantStringType('');
			}

			return new ConstantStringType($context->getClassName(), true);
		}

		if ($expr instanceof MagicConst\Namespace_) {
			if ($context->getTraitName() !== null) {
				return new IntersectionType([
					new StringType(),
					new AccessoryLiteralStringType(),
				]);
			}

			return new ConstantStringType($context->getNamespace() ?? '');
		}

		if ($expr instanceof MagicConst\Method) {
			return new ConstantStringType($context->getMethod() ?? '');
		}

		if ($expr instanceof MagicConst\Function_) {
			return new ConstantStringType($context->getFunction() ?? '');
		}

		if ($expr instanceof MagicConst\Trait_) {
			if ($context->getTraitName() === null) {
				return new ConstantStringType('');
			}

			return new ConstantStringType($context->getTraitName(), true);
		}

		if ($expr instanceof MagicConst\Property) {
			$contextProperty = $context->getProperty();
			if ($contextProperty === null) {
				return new ConstantStringType('');
			}

			return new ConstantStringType($contextProperty);
		}

		if ($expr instanceof PropertyFetch && $expr->name instanceof Identifier) {
			$fetchedOnType = $this->getType($expr->var, $context);
			if (!$fetchedOnType->hasInstanceProperty($expr->name->name)->yes()) {
				return new ErrorType();
			}

			return $fetchedOnType->getInstanceProperty($expr->name->name, new OutOfClassScope())->getReadableType();
		}

		return new MixedType();
	}

	/**
	 * Resolves the backing value of an enum case. A read of the case while
	 * its value is being resolved gives mixed - like a class constant does - to prevent endless recursions.
	 */
	public function getEnumCaseValueType(string $className, string $caseName, Expr $valueExpr, InitializerExprContext $context): Type
	{
		$resolvingName = sprintf('%s::%s', $className, $caseName);
		if (array_key_exists($resolvingName, $this->currentlyResolvingClassConstant)) {
			return new MixedType();
		}

		$this->currentlyResolvingClassConstant[$resolvingName] = true;
		try {
			return $this->getType($valueExpr, $context);
		} finally {
			unset($this->currentlyResolvingClassConstant[$resolvingName]);
		}
	}

	/**
	 * @param callable(Expr): Type $getTypeCallback
	 */
	public function getConcatType(Expr $left, Expr $right, callable $getTypeCallback): Type
	{
		$leftType = $getTypeCallback($left);
		$rightType = $getTypeCallback($right);

		return $this->resolveConcatType($leftType, $rightType);
	}

	public function resolveConcatType(Type $left, Type $right): Type
	{
		return $left->stringConcatenate($right, $this->phpVersion);
	}

	/**
	 * @param callable(Expr): Type $getTypeCallback
	 * @param PhpVersions|null $phpVersions the analysed scope's versions, the configured version when null
	 */
	public function getArrayType(Expr\Array_ $expr, callable $getTypeCallback, ?PhpVersions $phpVersions = null): Type
	{
		// unpacking an array with string keys is an Error below PHP 8.1, so the
		// string keys are kept whenever any analysed version supports it
		$keepStringKeys = $phpVersions !== null
			? !$phpVersions->supportsArrayUnpackingWithStringKeys()->no()
			: $this->phpVersion->supportsArrayUnpackingWithStringKeys();

		if (count($expr->items) > ConstantArrayTypeBuilder::ARRAY_COUNT_LIMIT) {
			return $this->oversizedArrayBuilder->build($expr, $getTypeCallback);
		}

		$arrayBuilder = ConstantArrayTypeBuilder::createEmpty();
		$isList = null;
		$hasOffsetValueTypes = [];
		foreach ($expr->items as $arrayItem) {
			$valueType = $getTypeCallback($arrayItem->value);
			if ($arrayItem->unpack) {
				$constantArrays = $valueType->getConstantArrays();
				if (count($constantArrays) > 0) {
					$totalArrays = count($constantArrays);

					// Unpacking merges string keys by name, while integer keys are always
					// renumbered, so they're merged by their position among integer keys.
					// A slot missing from some of the unpacked arrays becomes optional.
					/** @var array<string, array{keyType: ConstantStringType|null, valueTypes: list<Type>, presentCount: int, anyOptional: bool}> $slots */
					$slots = [];
					/** @var list<string> $slotOrder */
					$slotOrder = [];

					foreach ($constantArrays as $constantArrayType) {
						$nextIntegerSlot = 0;
						foreach ($constantArrayType->getKeyTypes() as $i => $keyType) {
							if ($keepStringKeys && $keyType->isString()->yes()) {
								$slotKey = 's' . $keyType->getValue();
								$slotKeyType = $keyType;
							} else {
								$slotKey = 'i' . $nextIntegerSlot;
								$slotKeyType = null;
								$nextIntegerSlot++;
							}

							if (!isset($slots[$slotKey])) {
								$slots[$slotKey] = [
									'keyType' => $slotKeyType,
									'valueTypes' => [],
									'presentCount' => 0,
									'anyOptional' => false,
								];
								$slotOrder[] = $slotKey;
							}

							$slots[$slotKey]['valueTypes'][] = $constantArrayType->getValueTypes()[$i];
							$slots[$slotKey]['presentCount']++;
							if (!$constantArrayType->isOptionalKey($i)) {
								continue;
							}

							$slots[$slotKey]['anyOptional'] = true;
						}
					}

					foreach ($slotOrder as $slotKey) {
						$slot = $slots[$slotKey];
						$mergedValueType = TypeCombinator::union(...$slot['valueTypes']);
						$isOptional = $slot['anyOptional'] || $slot['presentCount'] < $totalArrays;
						$slotKeyType = $slot['keyType'];
						$arrayBuilder->setOffsetValueType($slotKeyType, $mergedValueType, $isOptional);

						if ($slotKeyType === null) {
							continue;
						}

						$keyValue = $slotKeyType->getValue();
						if (isset($hasOffsetValueTypes[$keyValue])) {
							$hasOffsetValueTypes[$keyValue] = new HasOffsetValueType(
								$slotKeyType,
								$isOptional
									? TypeCombinator::union($hasOffsetValueTypes[$keyValue]->getValueType(), $mergedValueType)
									: $mergedValueType,
							);
						} elseif (!$isOptional) {
							$hasOffsetValueTypes[$keyValue] = new HasOffsetValueType($slotKeyType, $mergedValueType);
						}
					}
				} else {
					$arrayBuilder->degradeToGeneralArray();

					if ($keepStringKeys && !$valueType->getIterableKeyType()->isString()->no()) {
						$isList = false;
						$offsetType = $valueType->getIterableKeyType();

						foreach ($hasOffsetValueTypes as $key => $hasOffsetValueType) {
							if (!$offsetType->isSuperTypeOf($hasOffsetValueType->getOffsetType())->yes()) {
								continue;
							}

							unset($hasOffsetValueTypes[$key]);
						}
					} else {
						$isList ??= $arrayBuilder->isList();
						$offsetType = new IntegerType();
					}

					$arrayBuilder->setOffsetValueType($offsetType, $valueType->getIterableValueType(), !$valueType->isIterableAtLeastOnce()->yes());
				}
			} else {
				$arrayBuilder->setOffsetValueType(
					$arrayItem->key !== null ? $getTypeCallback($arrayItem->key) : null,
					$valueType,
				);
			}
		}

		$arrayType = $arrayBuilder->getArray();
		if ($isList === true) {
			$arrayType = TypeCombinator::intersect($arrayType, new AccessoryArrayListType());
		}

		if (count($hasOffsetValueTypes) > 0 && !$arrayType->isConstantArray()->yes()) {
			$arrayType = TypeCombinator::intersect($arrayType, ...array_values($hasOffsetValueTypes));
		}

		return $arrayType;
	}

	/**
	 * @param callable(Expr): Type $getTypeCallback
	 */
	public function getCastType(Expr\Cast $expr, callable $getTypeCallback): Type
	{
		if ($expr instanceof \PhpParser\Node\Expr\Cast\Int_) {
			return $getTypeCallback($expr->expr)->toInteger();
		}
		if ($expr instanceof Bool_) {
			return $getTypeCallback($expr->expr)->toBoolean();
		}
		if ($expr instanceof Double) {
			return $getTypeCallback($expr->expr)->toFloat();
		}
		if ($expr instanceof \PhpParser\Node\Expr\Cast\String_) {
			return $getTypeCallback($expr->expr)->toString();
		}
		if ($expr instanceof Array_) {
			return $getTypeCallback($expr->expr)->toArray();
		}
		if ($expr instanceof Object_) {
			return $this->getCastObjectType($getTypeCallback($expr->expr));
		}

		return new MixedType();
	}

	public function getCastObjectType(Type $exprType): Type
	{
		$castToObject = static function (Type $type): Type {
			$constantArrays = $type->getConstantArrays();
			if (count($constantArrays) > 0) {
				$objects = [];
				foreach ($constantArrays as $constantArray) {
					$properties = [];
					$optionalProperties = [];
					foreach ($constantArray->getKeyTypes() as $i => $keyType) {
						$valueType = $constantArray->getValueTypes()[$i];
						$optional = $constantArray->isOptionalKey($i);
						if ($optional) {
							$optionalProperties[] = $keyType->getValue();
						}
						$properties[$keyType->getValue()] = $valueType;
					}

					$objects[] = new IntersectionType([new ObjectShapeType($properties, $optionalProperties), new ObjectType(stdClass::class)]);
				}

				return TypeCombinator::union(...$objects);
			}
			if ($type->isObject()->yes()) {
				return $type;
			}

			return new ObjectType('stdClass');
		};

		if ($exprType instanceof UnionType) {
			return TypeCombinator::union(...array_map($castToObject, $exprType->getTypes()));
		}

		return $castToObject($exprType);
	}

	/**
	 * @param Name|Identifier|ComplexType|null $type
	 */
	public function getFunctionType($type, bool $isNullable, bool $isVariadic, InitializerExprContext $context): Type
	{
		if ($isNullable) {
			return TypeCombinator::addNull(
				$this->getFunctionType($type, false, $isVariadic, $context),
			);
		}
		if ($isVariadic) {
			if ($this->phpVersion->supportsNamedArguments()) {
				return new ArrayType(new UnionType([IntegerRangeType::createAllGreaterThanOrEqualTo(0), new StringType()]), $this->getFunctionType(
					$type,
					false,
					false,
					$context,
				));
			}

			return new IntersectionType([new ArrayType(IntegerRangeType::createAllGreaterThanOrEqualTo(0), $this->getFunctionType(
				$type,
				false,
				false,
				$context,
			)), new AccessoryArrayListType()]);
		}

		if ($type instanceof Name) {
			$className = (string) $type;
			$lowercasedClassName = strtolower($className);
			if ($lowercasedClassName === 'parent') {
				$classReflection = null;
				if ($context->getClassName() !== null && $this->getReflectionProvider()->hasClass($context->getClassName())) {
					$classReflection = $this->getReflectionProvider()->getClass($context->getClassName());
				}
				if ($classReflection !== null && $classReflection->getParentClass() !== null) {
					return new ObjectType($classReflection->getParentClass()->getName());
				}

				return new NonexistentParentClassType();
			}
		}

		$classReflection = null;
		if ($context->getClassName() !== null && $this->getReflectionProvider()->hasClass($context->getClassName())) {
			$classReflection = $this->getReflectionProvider()->getClass($context->getClassName());
		}

		return ParserNodeTypeToPHPStanType::resolve($type, $classReflection);
	}

	private function isParameterValueNullable(Param $parameter): bool
	{
		if ($parameter->default instanceof ConstFetch) {
			return strtolower((string) $parameter->default->name) === 'null';
		}

		return false;
	}

	/**
	 * @param array<int, Type>|null $boundArgTypes the types of the bound arguments of a partial function application,
	 *                                            keyed like its raw arguments; null to resolve them as constant expressions
	 */
	public function getFirstClassCallableType(Expr\CallLike $expr, InitializerExprContext $context, bool $nativeTypesPromoted, ?array $boundArgTypes = null): Type
	{
		$partialApplicationArgs = null;
		if (!$expr->isFirstClassCallable()) {
			$partialApplicationArgs = $expr->getRawArgs();
			if ($boundArgTypes === null) {
				$boundArgTypes = [];
				foreach ($partialApplicationArgs as $i => $arg) {
					if (!$arg instanceof Arg) {
						continue;
					}
					$boundArgTypes[$i] = $this->getType($arg->value, $context);
				}
			}
		}

		if ($expr instanceof FuncCall) {
			if ($expr->name instanceof Name) {
				if ($this->getReflectionProvider()->hasFunction($expr->name, $context)) {
					$function = $this->getReflectionProvider()->getFunction($expr->name, $context);
					return $this->createFirstClassCallable(
						$function,
						$function->getVariants(),
						$nativeTypesPromoted,
						$partialApplicationArgs,
						$boundArgTypes ?? [],
						ClosureOrigin::forFunction($function->getName()),
					);
				}

				return new ObjectType(Closure::class);
			}
		}

		if ($expr instanceof Expr\StaticCall) {
			if (!$expr->class instanceof Name) {
				return new ObjectType(Closure::class);
			}

			if (!$expr->name instanceof Identifier) {
				return new ObjectType(Closure::class);
			}

			$classReflection = null;
			if ($context->getClassName() !== null && $this->getReflectionProvider()->hasClass($context->getClassName())) {
				$classReflection = $this->getReflectionProvider()->getClass($context->getClassName());
			}

			$classType = $this->resolveTypeByName($expr->class, $classReflection);
			$methodName = $expr->name->toString();
			if (!$classType->hasMethod($methodName)->yes()) {
				return new ObjectType(Closure::class);
			}

			$method = $classType->getMethod($methodName, new OutOfClassScope());
			$classType = $this->resolveTypeByNameWithLateStaticBinding($expr->class, $classType, $method);
			if (!$classType->hasMethod($methodName)->yes()) {
				return new ObjectType(Closure::class);
			}
			$method = $classType->getMethod($methodName, new OutOfClassScope());

			return $this->createFirstClassCallable(
				$method,
				$method->getVariants(),
				$nativeTypesPromoted,
				$partialApplicationArgs,
				$boundArgTypes ?? [],
				ClosureOrigin::forMethod($classType, $methodName, true),
			);
		}

		if ($expr instanceof New_) {
			return new ErrorType();
		}

		throw new ShouldNotHappenException();
	}

	/**
	 * @param ParametersAcceptor[] $variants
	 * @param array<Arg|ArgPlaceholder|VariadicPlaceholder>|null $partialApplicationArgs the arguments of a partial function application, null for a first-class callable
	 * @param array<int, Type> $boundArgTypes the types of the bound arguments of a partial function application, keyed like $partialApplicationArgs
	 * @param ClosureOrigin|null $origin the callee, whose call a call of the closure replays
	 */
	public function createFirstClassCallable(
		FunctionReflection|ExtendedMethodReflection|null $function,
		array $variants,
		bool $nativeTypesPromoted,
		?array $partialApplicationArgs = null,
		array $boundArgTypes = [],
		?ClosureOrigin $origin = null,
	): Type
	{
		if ($origin !== null && $partialApplicationArgs !== null) {
			$origin = $origin->withPartialApplication($partialApplicationArgs, $boundArgTypes);
		}

		// variants of multi-variant built-ins carry functionMap parameter names,
		// but the closure accepts the native names as named arguments
		$namedArgumentNames = [];
		if ($function !== null) {
			foreach ($function->getNamedArgumentsVariants() ?? [] as $namedArgumentsVariant) {
				foreach ($namedArgumentsVariant->getParameters() as $i => $parameter) {
					$namedArgumentNames[$i] = $parameter->getName();
				}
			}
		}

		$closureTypes = [];

		foreach ($variants as $variant) {
			$returnType = $variant->getReturnType();
			if ($variant instanceof ExtendedParametersAcceptor) {
				$returnType = $nativeTypesPromoted ? $variant->getNativeReturnType() : $returnType;
			}

			$throwPoints = [];
			$impurePoints = [];
			$acceptsNamedArguments = TrinaryLogic::createYes();
			$mustUseReturnValue = TrinaryLogic::createMaybe();
			$isStaticClosure = TrinaryLogic::createMaybe();
			if ($variant instanceof CallableParametersAcceptor) {
				$throwPoints = $variant->getThrowPoints();
				$impurePoints = $variant->getImpurePoints();
				$acceptsNamedArguments = $variant->acceptsNamedArguments();
				$mustUseReturnValue = $variant->mustUseReturnValue();
				$isStaticClosure = $variant->isStaticClosure();
			} elseif ($function !== null) {
				$returnTypeForThrow = $variant->getReturnType();
				$throwType = $function->getThrowType();
				if ($throwType === null) {
					if ($returnTypeForThrow instanceof NeverType && $returnTypeForThrow->isExplicit()) {
						$throwType = new ObjectType(Throwable::class);
					}
				}

				if ($throwType !== null) {
					if (!$throwType->isVoid()->yes()) {
						$throwPoints[] = SimpleThrowPoint::createExplicit($throwType, true);
					}
				} else {
					if (!(new ObjectType(Throwable::class))->isSuperTypeOf($returnTypeForThrow)->yes()) {
						$throwPoints[] = SimpleThrowPoint::createImplicit();
					}
				}

				$impurePoint = SimpleImpurePoint::createFromVariant($function, $variant);
				if ($impurePoint !== null) {
					$impurePoints[] = $impurePoint;
				}

				$acceptsNamedArguments = $function->acceptsNamedArguments();
				$mustUseReturnValue = $function->mustUseReturnValue();
				// binding an object to the closure of a static method, or to a partial
				// application of a function, fails; a function's first-class callable can be bound
				if ($function instanceof ExtendedMethodReflection ? $function->isStatic() : $partialApplicationArgs !== null) {
					$isStaticClosure = TrinaryLogic::createYes();
				}
			}

			$parameters = $variant->getParameters();
			if (count($namedArgumentNames) > 0) {
				$parameters = self::renameParameters($parameters, $namedArgumentNames);
			}
			$isVariadic = $variant->isVariadic();
			$templateTypeMap = $variant->getTemplateTypeMap();
			$variantOrigin = $origin;
			if ($partialApplicationArgs !== null) {
				$partialApplication = PartialFunctionApplicationParameters::apply($parameters, $partialApplicationArgs, $boundArgTypes, $returnType, $templateTypeMap);
				if ($partialApplication === null) {
					// creating the closure from this variant throws
					continue;
				}
				[$parameters, $isVariadic, $returnType, $templateTypeMap, $boundParameterNames] = $partialApplication;
				if ($variantOrigin !== null) {
					$variantOrigin = $variantOrigin->withBoundParameterNames($boundParameterNames);
				}
			}

			$templateTags = [];
			foreach ($templateTypeMap->getTypes() as $templateType) {
				if (!$templateType instanceof TemplateType) {
					continue;
				}
				$templateTags[$templateType->getName()] = new TemplateTag(
					$templateType->getName(),
					$templateType->getBound(),
					$templateType->getDefault(),
					$templateType->getVariance(),
				);
			}
			if ($function !== null) {
				$assertions = $function->getAsserts();
			} elseif ($variant instanceof CallableParametersAcceptor) {
				$assertions = $variant->getAsserts();
			} else {
				$assertions = Assertions::createEmpty();
			}

			// a conditional return type referencing the function's own parameter,
			// like @return ($value is int ? true : false) on is_int(),
			// also becomes a type predicate of the resulting Closure
			$assertions = CallableAssertionsHelper::withConditionalReturnPredicate($assertions, $variant);
			$closureTypes[] = new ClosureType(
				$parameters,
				$returnType,
				$isVariadic,
				$templateTypeMap,
				$variant->getResolvedTemplateTypeMap(),
				$variant instanceof ExtendedParametersAcceptor ? $variant->getCallSiteVarianceMap() : TemplateTypeVarianceMap::createEmpty(),
				$templateTags,
				$throwPoints,
				$impurePoints,
				acceptsNamedArguments: $acceptsNamedArguments,
				mustUseReturnValue: $mustUseReturnValue,
				assertions: $assertions,
				isStatic: $isStaticClosure,
				origin: $variantOrigin,
			);
		}

		if ($partialApplicationArgs !== null && count($closureTypes) === 0) {
			return new ObjectType(Closure::class);
		}

		return TypeCombinator::union(...$closureTypes);
	}

	/**
	 * @param list<ParameterReflection> $parameters
	 * @param array<int, string> $names
	 * @return list<ParameterReflection>
	 */
	private static function renameParameters(array $parameters, array $names): array
	{
		$renamed = [];
		foreach ($parameters as $i => $parameter) {
			if (
				!isset($names[$i])
				|| $names[$i] === $parameter->getName()
				|| !$parameter instanceof ExtendedParameterReflection
			) {
				$renamed[] = $parameter;
				continue;
			}

			$renamed[] = new ExtendedDummyParameter(
				$names[$i],
				$parameter->getType(),
				$parameter->isOptional(),
				$parameter->passedByReference(),
				$parameter->isVariadic(),
				$parameter->getDefaultValue(),
				$parameter->getNativeType(),
				$parameter->getPhpDocType(),
				$parameter->getOutType(),
				$parameter->isImmediatelyInvokedCallable(),
				$parameter->getClosureThisType(),
				$parameter->getAttributes(),
				$parameter->getAllowedConstants(),
				$parameter->isPureUnlessCallableIsImpureParameter(),
				$parameter->isPureUnlessParameterPassedParameter(),
			);
		}

		return $renamed;
	}

	/**
	 * @param callable(Expr): Type $getTypeCallback
	 */
	public function getBitwiseAndType(Expr $left, Expr $right, callable $getTypeCallback): Type
	{
		$leftType = $getTypeCallback($left);
		$rightType = $getTypeCallback($right);

		return $this->operatorTypeSpecifyingExtensionRegistry
			->callOperatorTypeSpecifyingExtensions(new BinaryOp\BitwiseAnd($left, $right), $leftType, $rightType)
			?? $leftType->bitwiseAnd($rightType, $this->phpVersion);
	}

	/**
	 * @param callable(Expr): Type $getTypeCallback
	 */
	public function getBitwiseOrType(Expr $left, Expr $right, callable $getTypeCallback): Type
	{
		$leftType = $getTypeCallback($left);
		$rightType = $getTypeCallback($right);

		return $this->operatorTypeSpecifyingExtensionRegistry
			->callOperatorTypeSpecifyingExtensions(new BinaryOp\BitwiseOr($left, $right), $leftType, $rightType)
			?? $leftType->bitwiseOr($rightType, $this->phpVersion);
	}

	/**
	 * @param callable(Expr): Type $getTypeCallback
	 */
	public function getBitwiseXorType(Expr $left, Expr $right, callable $getTypeCallback): Type
	{
		$leftType = $getTypeCallback($left);
		$rightType = $getTypeCallback($right);

		return $this->operatorTypeSpecifyingExtensionRegistry
			->callOperatorTypeSpecifyingExtensions(new BinaryOp\BitwiseXor($left, $right), $leftType, $rightType)
			?? $leftType->bitwiseXor($rightType, $this->phpVersion);
	}

	/**
	 * @param callable(Expr): Type $getTypeCallback
	 */
	public function getSpaceshipType(Expr $left, Expr $right, callable $getTypeCallback): Type
	{
		$leftTypes = $getTypeCallback($left);
		$rightTypes = $getTypeCallback($right);

		if ($leftTypes instanceof NeverType || $rightTypes instanceof NeverType) {
			return $this->getNeverType($leftTypes, $rightTypes);
		}

		$leftValues = $leftTypes->getConstantScalarValues();
		$rightValues = $rightTypes->getConstantScalarValues();

		$leftValuesCount = count($leftValues);
		$rightValuesCount = count($rightValues);
		if ($leftValuesCount > 0 && $rightValuesCount > 0 && $leftValuesCount * $rightValuesCount <= self::CALCULATE_SCALARS_LIMIT) {
			$resultTypes = [];
			foreach ($leftValues as $leftValue) {
				foreach ($rightValues as $rightValue) {
					$resultType = new ConstantIntegerType(ValueSet::compareScalarValues($leftValue, $rightValue, $this->phpVersion) ?? 1);
					$resultTypes[] = $resultType;
				}
			}
			return TypeCombinator::union(...$resultTypes);
		}

		return IntegerRangeType::fromInterval(-1, 1);
	}

	/**
	 * @param callable(Expr): Type $getTypeCallback
	 */
	public function getDivType(Expr $left, Expr $right, callable $getTypeCallback): Type
	{
		$leftType = $getTypeCallback($left);
		$rightType = $getTypeCallback($right);

		$specifiedTypes = $this->operatorTypeSpecifyingExtensionRegistry
			->callOperatorTypeSpecifyingExtensions(new BinaryOp\Div($left, $right), $leftType, $rightType);
		if ($specifiedTypes !== null) {
			return $specifiedTypes;
		}

		$result = $leftType->arithmeticDivide($rightType, $this->phpVersion);

		// the remainder can be narrowed in the scope, e.g. by `$a % $b === 0`
		if ($leftType->isInteger()->yes() && $rightType->isInteger()->yes()) {
			$modType = $getTypeCallback(new BinaryOp\Mod($left, $right));
			if ($modType->isInteger()->yes() && (new ConstantIntegerType(0))->isSuperTypeOf($modType)->yes()) {
				$withoutFloat = TypeCombinator::remove($result, new FloatType());

				// PHP_INT_MIN / -1 divides without a remainder but still overflows to a float
				if (!$withoutFloat instanceof NeverType) {
					return $withoutFloat;
				}
			}
		}

		return $result;
	}

	/**
	 * @param callable(Expr): Type $getTypeCallback
	 */
	public function getModType(Expr $left, Expr $right, callable $getTypeCallback): Type
	{
		$leftType = $getTypeCallback($left);
		$rightType = $getTypeCallback($right);

		return $this->operatorTypeSpecifyingExtensionRegistry
			->callOperatorTypeSpecifyingExtensions(new BinaryOp\Mod($left, $right), $leftType, $rightType)
			?? $leftType->arithmeticModulo($rightType, $this->phpVersion);
	}

	/**
	 * @param callable(Expr): Type $getTypeCallback
	 */
	public function getPlusType(Expr $left, Expr $right, callable $getTypeCallback): Type
	{
		$leftType = $getTypeCallback($left);
		$rightType = $getTypeCallback($right);

		return $this->operatorTypeSpecifyingExtensionRegistry
			->callOperatorTypeSpecifyingExtensions(new BinaryOp\Plus($left, $right), $leftType, $rightType)
			?? $leftType->arithmeticAdd($rightType, $this->phpVersion);
	}

	/**
	 * @param callable(Expr): Type $getTypeCallback
	 */
	public function getMinusType(Expr $left, Expr $right, callable $getTypeCallback): Type
	{
		$leftType = $getTypeCallback($left);
		$rightType = $getTypeCallback($right);

		return $this->operatorTypeSpecifyingExtensionRegistry
			->callOperatorTypeSpecifyingExtensions(new BinaryOp\Minus($left, $right), $leftType, $rightType)
			?? $leftType->arithmeticSubtract($rightType, $this->phpVersion);
	}

	/**
	 * @param callable(Expr): Type $getTypeCallback
	 */
	public function getMulType(Expr $left, Expr $right, callable $getTypeCallback): Type
	{
		$leftType = $getTypeCallback($left);
		$rightType = $getTypeCallback($right);

		return $this->operatorTypeSpecifyingExtensionRegistry
			->callOperatorTypeSpecifyingExtensions(new BinaryOp\Mul($left, $right), $leftType, $rightType)
			?? $leftType->arithmeticMultiply($rightType, $this->phpVersion);
	}

	/**
	 * @param callable(Expr): Type $getTypeCallback
	 */
	public function getPowType(Expr $left, Expr $right, callable $getTypeCallback): Type
	{
		$leftType = $getTypeCallback($left);
		$rightType = $getTypeCallback($right);

		return $this->operatorTypeSpecifyingExtensionRegistry
			->callOperatorTypeSpecifyingExtensions(new BinaryOp\Pow($left, $right), $leftType, $rightType)
			?? $leftType->arithmeticPower($rightType, $this->phpVersion);
	}

	/**
	 * @param callable(Expr): Type $getTypeCallback
	 */
	public function getShiftLeftType(Expr $left, Expr $right, callable $getTypeCallback): Type
	{
		$leftType = $getTypeCallback($left);
		$rightType = $getTypeCallback($right);

		return $this->operatorTypeSpecifyingExtensionRegistry
			->callOperatorTypeSpecifyingExtensions(new BinaryOp\ShiftLeft($left, $right), $leftType, $rightType)
			?? $leftType->bitwiseShiftLeft($rightType, $this->phpVersion);
	}

	/**
	 * @param callable(Expr): Type $getTypeCallback
	 */
	public function getShiftRightType(Expr $left, Expr $right, callable $getTypeCallback): Type
	{
		$leftType = $getTypeCallback($left);
		$rightType = $getTypeCallback($right);

		return $this->operatorTypeSpecifyingExtensionRegistry
			->callOperatorTypeSpecifyingExtensions(new BinaryOp\ShiftRight($left, $right), $leftType, $rightType)
			?? $leftType->bitwiseShiftRight($rightType, $this->phpVersion);
	}

	/**
	 * @return TypeResult<BooleanType>
	 */
	public function resolveIdenticalType(Type $leftType, Type $rightType): TypeResult
	{
		if ($leftType instanceof NeverType || $rightType instanceof NeverType) {
			return new TypeResult(new ConstantBooleanType(false), []);
		}

		if ($leftType instanceof ConstantScalarType && $rightType instanceof ConstantScalarType) {
			return new TypeResult(new ConstantBooleanType($leftType->getValue() === $rightType->getValue()), []);
		}

		$leftTypeFiniteTypes = $leftType->getFiniteTypes();
		$rightTypeFiniteType = $rightType->getFiniteTypes();
		if (count($leftTypeFiniteTypes) === 1 && count($rightTypeFiniteType) === 1) {
			return new TypeResult(new ConstantBooleanType($leftTypeFiniteTypes[0]->equals($rightTypeFiniteType[0])), []);
		}

		$leftIsSuperTypeOfRight = $leftType->isSuperTypeOf($rightType);
		$rightIsSuperTypeOfLeft = $rightType->isSuperTypeOf($leftType);
		if ($leftIsSuperTypeOfRight->no() && $rightIsSuperTypeOfLeft->no()) {
			return new TypeResult(new ConstantBooleanType(false), array_merge($leftIsSuperTypeOfRight->getReasons(), $rightIsSuperTypeOfLeft->getReasons()));
		}

		if ($leftType instanceof ConstantArrayType && $rightType instanceof ConstantArrayType) {
			return $this->resolveConstantArrayTypeComparison($leftType, $rightType, fn ($leftValueType, $rightValueType): TypeResult => $this->resolveIdenticalType($leftValueType, $rightValueType));
		}

		return new TypeResult(new BooleanType(), []);
	}

	/**
	 * @return TypeResult<BooleanType>
	 */
	public function resolveEqualType(Type $leftType, Type $rightType, ?PhpVersions $phpVersions = null): TypeResult
	{
		if ($leftType instanceof NeverType || $rightType instanceof NeverType) {
			return new TypeResult(new ConstantBooleanType(false), []);
		}

		if (
			($leftType->isEnum()->yes() && $rightType->isTrue()->no())
			|| ($rightType->isEnum()->yes() && $leftType->isTrue()->no())
		) {
			return $this->resolveIdenticalType($leftType, $rightType);
		}

		$versions = $phpVersions !== null ? $phpVersions->getLooseComparisonVersions() : [$this->phpVersion];

		// same-type sides cannot coerce - loose equals strict, except for
		// two numeric strings, which compare as numbers
		if (
			($leftType->isString()->yes() && $rightType->isString()->yes() && (self::isNonNumericString($leftType, $versions) || self::isNonNumericString($rightType, $versions)))
			|| ($leftType->isInteger()->yes() && $rightType->isInteger()->yes())
		) {
			return $this->resolveIdenticalType($leftType, $rightType);
		}

		if ($leftType instanceof ConstantArrayType && $rightType instanceof ConstantArrayType) {
			return $this->resolveConstantArrayTypeComparison($leftType, $rightType, fn ($leftValueType, $rightValueType): TypeResult => $this->resolveEqualType($leftValueType, $rightValueType, $phpVersions));
		}

		// constant only when every PHP version agrees
		$result = $leftType->looseCompare($rightType, $versions[0]);
		foreach (array_slice($versions, 1) as $version) {
			if (!$result->equals($leftType->looseCompare($rightType, $version))) {
				$result = new BooleanType();
				break;
			}
		}
		if ($result->isTrue()->yes() || $result->isFalse()->yes()) {
			return new TypeResult($result, []);
		}

		// either side's possibly loosely equal type in any version rules the other side out
		foreach ([[$leftType, $rightType], [$rightType, $leftType]] as [$type, $otherType]) {
			$possiblyEqualTypes = [];
			foreach ($versions as $version) {
				$possiblyEqualTypes[] = $type->getPossiblyLooselyEqualType($version);
			}
			if (TypeCombinator::union(...$possiblyEqualTypes)->isSuperTypeOf($otherType)->no()) {
				return new TypeResult(new ConstantBooleanType(false), []);
			}
		}

		// either side's always loosely equal type in every version holds the other side
		foreach ([[$leftType, $rightType], [$rightType, $leftType]] as [$type, $otherType]) {
			$alwaysEqualTypes = [];
			foreach ($versions as $version) {
				$alwaysEqualTypes[] = $type->getAlwaysLooselyEqualType($version);
			}
			if (TypeCombinator::intersect(...$alwaysEqualTypes)->isSuperTypeOf($otherType)->yes()) {
				return new TypeResult(new ConstantBooleanType(true), []);
			}
		}

		return new TypeResult($result, []);
	}

	/**
	 * Whether the strings are numeric in none of the PHP versions. A constant
	 * string's isNumericString() follows the PHP version running PHPStan,
	 * while '1 ' is numeric since PHP 8.0 only.
	 *
	 * @param non-empty-list<PhpVersion> $phpVersions
	 */
	private static function isNonNumericString(Type $type, array $phpVersions): bool
	{
		if (!$type->isNumericString()->no()) {
			return false;
		}

		foreach ($type->getConstantStrings() as $constantString) {
			foreach ($phpVersions as $phpVersion) {
				if (StringSet::parseNumericString($constantString->getValue(), $phpVersion) !== null) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * @param callable(Type, Type): TypeResult<BooleanType> $valueComparisonCallback
	 * @return TypeResult<BooleanType>
	 */
	private function resolveConstantArrayTypeComparison(ConstantArrayType $leftType, ConstantArrayType $rightType, callable $valueComparisonCallback): TypeResult
	{
		$leftKeyTypes = $leftType->getKeyTypes();
		$rightKeyTypes = $rightType->getKeyTypes();
		$leftValueTypes = $leftType->getValueTypes();
		$rightValueTypes = $rightType->getValueTypes();

		$resultType = new ConstantBooleanType(true);

		foreach ($leftKeyTypes as $i => $leftKeyType) {
			$leftOptional = $leftType->isOptionalKey($i);
			if ($leftOptional) {
				$resultType = new BooleanType();
			}

			if (count($rightKeyTypes) === 0) {
				if (!$leftOptional) {
					return new TypeResult(new ConstantBooleanType(false), []);
				}
				continue;
			}

			$found = false;
			foreach ($rightKeyTypes as $j => $rightKeyType) {
				unset($rightKeyTypes[$j]);

				if ($leftKeyType->equals($rightKeyType)) {
					$found = true;
					break;
				} elseif (!$rightType->isOptionalKey($j)) {
					return new TypeResult(new ConstantBooleanType(false), []);
				}
			}

			if (!$found) {
				if (!$leftOptional) {
					return new TypeResult(new ConstantBooleanType(false), []);
				}
				continue;
			}

			if (!isset($j)) {
				throw new ShouldNotHappenException();
			}

			$rightOptional = $rightType->isOptionalKey($j);
			if ($rightOptional) {
				$resultType = new BooleanType();
				if ($leftOptional) {
					continue;
				}
			}

			$leftIdenticalToRightResult = $valueComparisonCallback($leftValueTypes[$i], $rightValueTypes[$j]);
			$leftIdenticalToRight = $leftIdenticalToRightResult->type;
			if ($leftIdenticalToRight->isFalse()->yes()) {
				return $leftIdenticalToRightResult;
			}
			$resultType = TypeCombinator::union($resultType, $leftIdenticalToRight);
		}

		foreach (array_keys($rightKeyTypes) as $j) {
			if (!$rightType->isOptionalKey($j)) {
				return new TypeResult(new ConstantBooleanType(false), []);
			}
			$resultType = new BooleanType();
		}

		return new TypeResult($resultType->toBoolean(), []);
	}

	/**
	 * With $nativeTypesPromoted, PHPDoc types of the constants are ignored:
	 * a constant that can be overridden in a child class resolves only to its native type.
	 *
	 * @param callable(Expr): Type $getTypeCallback
	 */
	public function getClassConstFetchTypeByReflection(Name|Expr $class, string $constantName, ?ClassReflection $classReflection, callable $getTypeCallback, bool $nativeTypesPromoted = false): Type
	{
		$isObject = false;
		if ($class instanceof Name) {
			$constantClass = (string) $class;
			$constantClassType = new ObjectType($constantClass);
			$namesToResolve = [
				'self',
				'parent',
			];
			if ($classReflection !== null) {
				if ($classReflection->isFinal()) {
					$namesToResolve[] = 'static';
				} elseif (strtolower($constantClass) === 'static') {
					if (strtolower($constantName) === 'class') {
						return new GenericClassStringType(new StaticType($classReflection));
					}

					$namesToResolve[] = 'static';
					$isObject = true;
				}
			}
			if (in_array(strtolower($constantClass), $namesToResolve, true)) {
				$resolvedName = $this->resolveName($class, $classReflection);
				if (strtolower($resolvedName) === 'parent' && strtolower($constantName) === 'class') {
					return new ClassStringType();
				}
				$constantClassType = $this->resolveTypeByName($class, $classReflection);
			}

			if (strtolower($constantName) === 'class') {
				return new ConstantStringType($constantClassType->getClassName(), true);
			}
		} elseif ($class instanceof String_ && strtolower($constantName) === 'class') {
			return new ConstantStringType($class->value, true);
		} else {
			$constantClassType = $getTypeCallback($class);
			$isObject = true;
		}

		if (strtolower($constantName) === 'class') {
			return $constantClassType->toClassConstantType($this->getReflectionProvider());
		}

		if ($constantClassType->isClassString()->yes()) {
			if ($constantClassType->isConstantScalarValue()->yes()) {
				$isObject = false;
			}
			$constantClassType = $constantClassType->getClassStringObjectType();
		}

		$types = [];
		$referencedClasses = $constantClassType->getObjectClassNames();
		foreach ($referencedClasses as $referencedClass) {
			if (!$this->getReflectionProvider()->hasClass($referencedClass)) {
				continue;
			}
			$fetchedOnType = count($referencedClasses) === 1 ? $constantClassType : new ObjectType($referencedClass);

			$constantClassReflection = $this->getReflectionProvider()->getClass($referencedClass);
			if (!$constantClassReflection->hasConstant($constantName)) {
				if ($constantClassReflection->getName() === 'Attribute' && $constantName === 'TARGET_CONSTANT') {
					return new ConstantIntegerType(1 << 16);
				}
				continue;
			}

			if ($constantClassReflection->isEnum() && $constantClassReflection->hasEnumCase($constantName)) {
				$types[] = new EnumCaseObjectType($constantClassReflection->getName(), $constantName);
				continue;
			}

			$resolvingName = sprintf('%s::%s', $constantClassReflection->getName(), $constantName);
			if (array_key_exists($resolvingName, $this->currentlyResolvingClassConstant)) {
				$types[] = new MixedType();
				continue;
			}

			$cacheKey = $nativeTypesPromoted ? $resolvingName . '#native' : $resolvingName;
			if (!$isObject && array_key_exists($cacheKey, $this->classConstantValueTypeCache)) {
				$types[] = $this->classConstantValueTypeCache[$cacheKey];
				continue;
			}

			$this->currentlyResolvingClassConstant[$resolvingName] = true;

			if (!$isObject) {
				$reflectionConstant = $constantClassReflection->getNativeReflection()->getReflectionConstant($constantName);
				if ($reflectionConstant === false) {
					unset($this->currentlyResolvingClassConstant[$resolvingName]);
					continue;
				}
				$reflectionConstantDeclaringClass = $reflectionConstant->getDeclaringClass();
				// the resolved type is cached: a class it names that does not exist is what the
				// declaring class declares depends on
				$this->valueDependencyCollector->enterDeclaration($reflectionConstantDeclaringClass->getFileName() ?: null, $reflectionConstantDeclaringClass->getName());
				try {
					$constantType = $this->getType($reflectionConstant->getValueExpression(), InitializerExprContext::fromClass($reflectionConstantDeclaringClass->getName(), $reflectionConstantDeclaringClass->getFileName() ?: null));
				} finally {
					$this->valueDependencyCollector->leaveDeclaration();
				}
				$nativeType = null;
				if ($reflectionConstant->getType() !== null) {
					$nativeType = TypehintHelper::decideTypeFromReflection($reflectionConstant->getType(), selfClass: $constantClassReflection);
				}
				$phpDocType = $constantClassReflection->getConstantPhpDocType($constantName);
				$resolvedType = $this->transformStaticType($this->constantResolver->resolveClassConstantType(
					$constantClassReflection->getName(),
					$constantName,
					$constantType,
					$nativeType,
					$nativeTypesPromoted ? null : $phpDocType,
				), $fetchedOnType);
				if ($phpDocType === null) {
					// the PHPDoc type is the only input that differs, so both variants share the resolved type
					$this->classConstantValueTypeCache[$resolvingName] = $resolvedType;
					$this->classConstantValueTypeCache[$resolvingName . '#native'] = $resolvedType;
				} else {
					$this->classConstantValueTypeCache[$cacheKey] = $resolvedType;
				}
				$types[] = $resolvedType;
				unset($this->currentlyResolvingClassConstant[$resolvingName]);
				continue;
			}

			$constantReflection = $constantClassReflection->getConstant($constantName);
			if (
				!$constantClassReflection->isFinal()
				&& !$constantReflection->isFinal()
				&& ($nativeTypesPromoted || !$constantReflection->hasPhpDocType())
				&& !$constantReflection->hasNativeType()
			) {
				unset($this->currentlyResolvingClassConstant[$resolvingName]);
				return new MixedType();
			}

			$nativeType = $constantReflection->getNativeType();
			if ($constantClassReflection->isFinal() || ($nativeTypesPromoted && $constantReflection->isFinal())) {
				$constantType = $this->getType($constantReflection->getValueExpr(), InitializerExprContext::fromClassReflection($constantReflection->getDeclaringClass()));
			} elseif ($nativeTypesPromoted && $nativeType !== null) {
				$constantType = $nativeType;
			} else {
				$constantType = $constantReflection->getValueType();
			}

			$constantType = $this->transformStaticType($this->constantResolver->resolveClassConstantType(
				$constantClassReflection->getName(),
				$constantName,
				$constantType,
				$nativeType,
				$nativeTypesPromoted ? null : $constantClassReflection->getConstantPhpDocType($constantName),
			), $fetchedOnType);
			unset($this->currentlyResolvingClassConstant[$resolvingName]);
			$types[] = $constantType;
		}

		if (count($types) > 0) {
			return TypeCombinator::union(...$types);
		}

		if (!$constantClassType->hasConstant($constantName)->yes()) {
			return new ErrorType();
		}

		return $this->transformStaticType($constantClassType->getConstant($constantName)->getValueType(), $constantClassType);
	}

	/**
	 * Resolves `static` in the type of a class constant against the class
	 * the constant is fetched on.
	 */
	private function transformStaticType(Type $type, Type $fetchedOnType): Type
	{
		return TypeTraverser::map($type, static function (Type $type, callable $traverse) use ($fetchedOnType): Type {
			if (!$type instanceof StaticType) {
				return $traverse($type);
			}
			if ($fetchedOnType instanceof StaticType) {
				return $traverse($type->changeBaseClass($fetchedOnType->getClassReflection()));
			}
			if ($type instanceof GenericStaticType) {
				$fetchedOnTypeReflections = $fetchedOnType->getObjectClassReflections();
				if (count($fetchedOnTypeReflections) === 1) {
					return $traverse($type->changeBaseClass($fetchedOnTypeReflections[0])->getStaticObjectType());
				}
			}

			return $fetchedOnType;
		});
	}

	/**
	 * @param callable(Expr): Type $getTypeCallback
	 */
	public function getClassConstFetchType(Name|Expr $class, string $constantName, ?string $className, callable $getTypeCallback): Type
	{
		$classReflection = null;
		if ($className !== null && $this->getReflectionProvider()->hasClass($className)) {
			$classReflection = $this->getReflectionProvider()->getClass($className);
		}

		return $this->getClassConstFetchTypeByReflection($class, $constantName, $classReflection, $getTypeCallback);
	}

	/**
	 * @param callable(Expr): Type $getTypeCallback
	 */
	public function getUnaryPlusType(Expr $expr, callable $getTypeCallback): Type
	{
		$type = $getTypeCallback($expr);

		$specifiedTypes = $this->unaryOperatorTypeSpecifyingExtensionRegistry
			->callUnaryOperatorTypeSpecifyingExtensions('+', $type);
		if ($specifiedTypes !== null) {
			return $specifiedTypes;
		}

		return $type->toNumber();
	}

	/**
	 * @param callable(Expr): Type $getTypeCallback
	 */
	public function getUnaryMinusType(Expr $expr, callable $getTypeCallback): Type
	{
		$type = $getTypeCallback($expr);

		return $this->unaryOperatorTypeSpecifyingExtensionRegistry
			->callUnaryOperatorTypeSpecifyingExtensions('-', $type)
			?? $type->arithmeticNegate($this->phpVersion);
	}

	/**
	 * @param callable(Expr): Type $getTypeCallback
	 */
	public function getBitwiseNotType(Expr $expr, callable $getTypeCallback): Type
	{
		$exprType = $getTypeCallback($expr);

		$specifiedTypes = $this->unaryOperatorTypeSpecifyingExtensionRegistry
			->callUnaryOperatorTypeSpecifyingExtensions('~', $exprType);
		if ($specifiedTypes !== null) {
			return $specifiedTypes;
		}

		return $this->getBitwiseNotTypeFromType($exprType);
	}

	public function getBitwiseNotTypeFromType(Type $exprType): Type
	{
		return $exprType->toBitwiseNotType();
	}

	private function resolveName(Name $name, ?ClassReflection $classReflection): string
	{
		$originalClass = (string) $name;
		if ($classReflection !== null) {
			$lowerClass = strtolower($originalClass);

			if (in_array($lowerClass, [
				'self',
				'static',
			], true)) {
				return $classReflection->getName();
			} elseif ($lowerClass === 'parent') {
				if ($classReflection->getParentClass() !== null) {
					return $classReflection->getParentClass()->getName();
				}
			}
		}

		return $originalClass;
	}

	private function resolveTypeByName(Name $name, ?ClassReflection $classReflection): TypeWithClassName
	{
		if ($name->toLowerString() === 'static' && $classReflection !== null) {
			return new StaticType($classReflection);
		}

		$originalClass = $this->resolveName($name, $classReflection);
		if ($classReflection !== null) {
			$thisType = new ThisType($classReflection);
			$ancestor = $thisType->getAncestorWithClassName($originalClass);
			if ($ancestor !== null) {
				return $ancestor;
			}
		}

		return new ObjectType($originalClass);
	}

	private function resolveTypeByNameWithLateStaticBinding(Name $class, Type $classType, MethodReflection $methodReflectionCandidate): Type
	{
		if (
			$classType instanceof StaticType
			&& !in_array($class->toLowerString(), ['self', 'static', 'parent'], true)
		) {
			if ($methodReflectionCandidate->isStatic()) {
				$classType = $classType->getStaticObjectType();
			}
		}

		return $classType;
	}

	private function getReflectionProvider(): ReflectionProvider
	{
		return $this->reflectionProviderProvider->getReflectionProvider();
	}

	private function getNeverType(Type $leftType, Type $rightType): Type
	{
		// make sure we don't lose the explicit flag in the process
		if ($leftType instanceof NeverType && $leftType->isExplicit()) {
			return $leftType;
		}
		if ($rightType instanceof NeverType && $rightType->isExplicit()) {
			return $rightType;
		}
		return new NeverType();
	}

}
