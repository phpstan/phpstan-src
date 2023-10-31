<?php declare(strict_types = 1);

namespace PHPStan\Type\Constant;

use Nette\Utils\RegexpException;
use Nette\Utils\Strings;
use PhpParser\Node\Name;
use PHPStan\Analyser\OutOfClassScope;
use PHPStan\DependencyInjection\BleedingEdgeToggle;
use PHPStan\PhpDocParser\Ast\ConstExpr\QuoteAwareConstExprStringNode;
use PHPStan\PhpDocParser\Ast\Type\ConstTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\ConstantReflection;
use PHPStan\Reflection\InaccessibleMethod;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\PhpVersionStaticAccessor;
use PHPStan\Reflection\ReflectionProviderStaticAccessor;
use PHPStan\Reflection\TrivialParametersAcceptor;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryLiteralStringType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
use PHPStan\Type\ClassStringType;
use PHPStan\Type\CompoundType;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\Generic\GenericClassStringType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StaticType;
use PHPStan\Type\StringType;
use PHPStan\Type\Traits\ConstantScalarTypeTrait;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VerbosityLevel;
use function addcslashes;
use function in_array;
use function is_float;
use function is_int;
use function is_numeric;
use function key;
use function strlen;
use function substr;
use function substr_count;

/** @api */
class ConstantStringType extends StringType implements ConstantScalarType
{

	private const DESCRIBE_LIMIT = 20;

	use ConstantScalarTypeTrait;
	use ConstantScalarToBooleanTrait;

	private ?ObjectType $objectType = null;

	private ?Type $arrayKeyType = null;

	/** @api */
	public function __construct(private string $value, private bool $isClassString = false)
	{
		parent::__construct();
	}

	public function getValue(): string
	{
		return $this->value;
	}

	public function getConstantStrings(): array
	{
		return [$this];
	}

	public function isClassStringType(): TrinaryLogic
	{
		if ($this->isClassString) {
			return TrinaryLogic::createYes();
		}

		$reflectionProvider = ReflectionProviderStaticAccessor::getInstance();

		return TrinaryLogic::createFromBoolean($reflectionProvider->hasClass($this->value));
	}

	public function getClassStringObjectType(): Type
	{
		if ($this->isClassStringType()->yes()) {
			return new ObjectType($this->value);
		}

		return new ErrorType();
	}

	public function getObjectTypeOrClassStringObjectType(): Type
	{
		return $this->getClassStringObjectType();
	}

	/**
	 * @deprecated use isClassStringType() instead
	 */
	public function isClassString(): bool
	{
		return $this->isClassStringType()->yes();
	}

	public function describe(VerbosityLevel $level): string
	{
		return $level->handle(
			static fn (): string => 'string',
			function (): string {
				$value = $this->value;

				if (!$this->isClassString) {
					try {
						$value = Strings::truncate($value, self::DESCRIBE_LIMIT);
					} catch (RegexpException) {
						$value = substr($value, 0, self::DESCRIBE_LIMIT) . "\u{2026}";
					}
				}

				return self::export($value);
			},
			fn (): string => self::export($this->value),
		);
	}

	private function export(string $value): string
	{
		$escapedValue = addcslashes($value, "\0..\37");
		if ($escapedValue !== $value) {
			return '"' . addcslashes($value, "\0..\37\\\"") . '"';
		}

		return "'" . addcslashes($value, '\\\'') . "'";
	}

	public function isSuperTypeOf(Type $type): TrinaryLogic
	{
		if ($type instanceof GenericClassStringType) {
			if ($this->isClassStringType()->no()) {
				return TrinaryLogic::createNo();
			}

			$genericType = $type->getGenericType();
			if ($genericType instanceof StaticType) {
				$genericType = $genericType->getStaticObjectType();
			}

			// We are transforming constant class-string to ObjectType. But we need to filter out
			// an uncertainty originating in possible ObjectType's class subtypes.
			$objectType = $this->getObjectType();

			// Do not use TemplateType's isSuperTypeOf handling directly because it takes ObjectType
			// uncertainty into account.
			if ($genericType instanceof TemplateType) {
				$isSuperType = $genericType->getBound()->isSuperTypeOf($objectType);
			} else {
				$isSuperType = $genericType->isSuperTypeOf($objectType);
			}

			// Explicitly handle the uncertainty for Yes & Maybe.
			if ($isSuperType->yes()) {
				return TrinaryLogic::createMaybe();
			}
			return TrinaryLogic::createNo();
		}
		if ($type instanceof ClassStringType) {
			return $this->isClassStringType()->yes() ? TrinaryLogic::createMaybe() : TrinaryLogic::createNo();
		}

		if ($type instanceof self) {
			return $this->value === $type->value ? TrinaryLogic::createYes() : TrinaryLogic::createNo();
		}

		if ($type instanceof parent) {
			return TrinaryLogic::createMaybe();
		}

		if ($type instanceof CompoundType) {
			return $type->isSubTypeOf($this);
		}

		return TrinaryLogic::createNo();
	}

	public function isCallable(): TrinaryLogic
	{
		if ($this->value === '') {
			return TrinaryLogic::createNo();
		}

		$reflectionProvider = ReflectionProviderStaticAccessor::getInstance();

		// 'my_function'
		if ($reflectionProvider->hasFunction(new Name($this->value), null)) {
			return TrinaryLogic::createYes();
		}

		// 'MyClass::myStaticFunction'
		$matches = Strings::match($this->value, '#^([a-zA-Z_\\x7f-\\xff\\\\][a-zA-Z0-9_\\x7f-\\xff\\\\]*)::([a-zA-Z_\\x7f-\\xff][a-zA-Z0-9_\\x7f-\\xff]*)\z#');
		if ($matches !== null) {
			if (!$reflectionProvider->hasClass($matches[1])) {
				return TrinaryLogic::createMaybe();
			}

			$phpVersion = PhpVersionStaticAccessor::getInstance();
			$classRef = $reflectionProvider->getClass($matches[1]);
			if ($classRef->hasMethod($matches[2])) {
				$method = $classRef->getMethod($matches[2], new OutOfClassScope());
				if (
					BleedingEdgeToggle::isBleedingEdge()
					&& !$phpVersion->supportsCallableInstanceMethods()
					&& !$method->isStatic()
				) {
					return TrinaryLogic::createNo();
				}

				return TrinaryLogic::createYes();
			}

			if (!$classRef->getNativeReflection()->isFinal()) {
				return TrinaryLogic::createMaybe();
			}

			return TrinaryLogic::createNo();
		}

		return TrinaryLogic::createNo();
	}

	/**
	 * @return ParametersAcceptor[]
	 */
	public function getCallableParametersAcceptors(ClassMemberAccessAnswerer $scope): array
	{
		$reflectionProvider = ReflectionProviderStaticAccessor::getInstance();

		// 'my_function'
		$functionName = new Name($this->value);
		if ($reflectionProvider->hasFunction($functionName, null)) {
			return $reflectionProvider->getFunction($functionName, null)->getVariants();
		}

		// 'MyClass::myStaticFunction'
		$matches = Strings::match($this->value, '#^([a-zA-Z_\\x7f-\\xff\\\\][a-zA-Z0-9_\\x7f-\\xff\\\\]*)::([a-zA-Z_\\x7f-\\xff][a-zA-Z0-9_\\x7f-\\xff]*)\z#');
		if ($matches !== null) {
			if (!$reflectionProvider->hasClass($matches[1])) {
				return [new TrivialParametersAcceptor()];
			}

			$classReflection = $reflectionProvider->getClass($matches[1]);
			if ($classReflection->hasMethod($matches[2])) {
				$method = $classReflection->getMethod($matches[2], $scope);
				if (!$scope->canCallMethod($method)) {
					return [new InaccessibleMethod($method)];
				}

				return $method->getVariants();
			}

			if (!$classReflection->getNativeReflection()->isFinal()) {
				return [new TrivialParametersAcceptor()];
			}
		}

		throw new ShouldNotHappenException();
	}

	public function toNumber(): Type
	{
		if (is_numeric($this->value)) {
			$value = $this->value;
			$value = +$value;
			if (is_float($value)) {
				return new ConstantFloatType($value);
			}

			return new ConstantIntegerType($value);
		}

		return new ErrorType();
	}

	public function toInteger(): Type
	{
		return new ConstantIntegerType((int) $this->value);
	}

	public function toFloat(): Type
	{
		return new ConstantFloatType((float) $this->value);
	}

	public function toArrayKey(): Type
	{
		if ($this->arrayKeyType !== null) {
			return $this->arrayKeyType;
		}

		/** @var int|string $offsetValue */
		$offsetValue = key([$this->value => null]);
		return $this->arrayKeyType = is_int($offsetValue) ? new ConstantIntegerType($offsetValue) : new ConstantStringType($offsetValue);
	}

	public function isString(): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	public function isNumericString(): TrinaryLogic
	{
		return TrinaryLogic::createFromBoolean(is_numeric($this->getValue()));
	}

	public function isNonEmptyString(): TrinaryLogic
	{
		return TrinaryLogic::createFromBoolean($this->getValue() !== '');
	}

	public function isNonFalsyString(): TrinaryLogic
	{
		return TrinaryLogic::createFromBoolean(!in_array($this->getValue(), ['', '0'], true));
	}

	public function isLiteralString(): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	public function hasOffsetValueType(Type $offsetType): TrinaryLogic
	{
		if ($offsetType instanceof ConstantIntegerType) {
			return TrinaryLogic::createFromBoolean(
				$offsetType->getValue() < strlen($this->value),
			);
		}

		return parent::hasOffsetValueType($offsetType);
	}

	public function getOffsetValueType(Type $offsetType): Type
	{
		if ($offsetType instanceof ConstantIntegerType) {
			if ($offsetType->getValue() < strlen($this->value)) {
				return new self($this->value[$offsetType->getValue()]);
			}

			return new ErrorType();
		}

		return parent::getOffsetValueType($offsetType);
	}

	public function setOffsetValueType(?Type $offsetType, Type $valueType, bool $unionValues = true): Type
	{
		$valueStringType = $valueType->toString();
		if ($valueStringType instanceof ErrorType) {
			return new ErrorType();
		}
		if (
			$offsetType instanceof ConstantIntegerType
			&& $valueStringType instanceof ConstantStringType
		) {
			$value = $this->value;
			$offsetValue = $offsetType->getValue();
			if ($offsetValue < 0) {
				return new ErrorType();
			}
			$stringValue = $valueStringType->getValue();
			if (strlen($stringValue) !== 1) {
				return new ErrorType();
			}
			$value[$offsetValue] = $stringValue;

			return new self($value);
		}

		return parent::setOffsetValueType($offsetType, $valueType);
	}

	public function append(self $otherString): self
	{
		return new self($this->getValue() . $otherString->getValue());
	}

	public function generalize(GeneralizePrecision $precision): Type
	{
		if ($this->isClassString) {
			if ($precision->isMoreSpecific()) {
				return new ClassStringType();
			}

			return new StringType();
		}

		if ($this->getValue() !== '' && $precision->isMoreSpecific()) {
			if ($this->getValue() !== '0') {
				return new IntersectionType([
					new StringType(),
					new AccessoryNonFalsyStringType(),
					new AccessoryLiteralStringType(),
				]);
			}

			return new IntersectionType([
				new StringType(),
				new AccessoryNonEmptyStringType(),
				new AccessoryLiteralStringType(),
			]);
		}

		if ($precision->isMoreSpecific()) {
			return new IntersectionType([
				new StringType(),
				new AccessoryLiteralStringType(),
			]);
		}

		return new StringType();
	}

	public function getSmallerType(): Type
	{
		$subtractedTypes = [
			new ConstantBooleanType(true),
			IntegerRangeType::createAllGreaterThanOrEqualTo((float) $this->value),
		];

		if ($this->value === '') {
			$subtractedTypes[] = new NullType();
			$subtractedTypes[] = new StringType();
		}

		if (!(bool) $this->value) {
			$subtractedTypes[] = new ConstantBooleanType(false);
		}

		return TypeCombinator::remove(new MixedType(), TypeCombinator::union(...$subtractedTypes));
	}

	public function getSmallerOrEqualType(): Type
	{
		$subtractedTypes = [
			IntegerRangeType::createAllGreaterThan((float) $this->value),
		];

		if (!(bool) $this->value) {
			$subtractedTypes[] = new ConstantBooleanType(true);
		}

		return TypeCombinator::remove(new MixedType(), TypeCombinator::union(...$subtractedTypes));
	}

	public function getGreaterType(): Type
	{
		$subtractedTypes = [
			new ConstantBooleanType(false),
			IntegerRangeType::createAllSmallerThanOrEqualTo((float) $this->value),
		];

		if ((bool) $this->value) {
			$subtractedTypes[] = new ConstantBooleanType(true);
		}

		return TypeCombinator::remove(new MixedType(), TypeCombinator::union(...$subtractedTypes));
	}

	public function getGreaterOrEqualType(): Type
	{
		$subtractedTypes = [
			IntegerRangeType::createAllSmallerThan((float) $this->value),
		];

		if ((bool) $this->value) {
			$subtractedTypes[] = new ConstantBooleanType(false);
		}

		return TypeCombinator::remove(new MixedType(), TypeCombinator::union(...$subtractedTypes));
	}

	public function canAccessConstants(): TrinaryLogic
	{
		return $this->isClassStringType();
	}

	public function hasConstant(string $constantName): TrinaryLogic
	{
		return $this->getObjectType()->hasConstant($constantName);
	}

	public function getConstant(string $constantName): ConstantReflection
	{
		return $this->getObjectType()->getConstant($constantName);
	}

	private function getObjectType(): ObjectType
	{
		return $this->objectType ??= new ObjectType($this->value);
	}

	public function toPhpDocNode(): TypeNode
	{
		if (substr_count($this->value, "\n") > 0) {
			return $this->generalize(GeneralizePrecision::moreSpecific())->toPhpDocNode();
		}

		return new ConstTypeNode(new QuoteAwareConstExprStringNode($this->value, QuoteAwareConstExprStringNode::SINGLE_QUOTED));
	}

	/**
	 * @param mixed[] $properties
	 */
	public static function __set_state(array $properties): Type
	{
		return new self($properties['value'], $properties['isClassString'] ?? false);
	}

}
