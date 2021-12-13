<?php declare(strict_types = 1);

namespace PHPStan\Type;

use ArrayAccess;
use Closure;
use Iterator;
use IteratorAggregate;
use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Broker\Broker;
use PHPStan\Broker\ClassNotFoundException;
use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ConstantReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\Php\UniversalObjectCratesClassReflectionExtension;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Reflection\ReflectionProviderStaticAccessor;
use PHPStan\Reflection\TrivialParametersAcceptor;
use PHPStan\Reflection\Type\CalledOnTypeUnresolvedMethodPrototypeReflection;
use PHPStan\Reflection\Type\CalledOnTypeUnresolvedPropertyPrototypeReflection;
use PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection;
use PHPStan\Reflection\Type\UnresolvedPropertyPrototypeReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Traits\NonGenericTypeTrait;
use PHPStan\Type\Traits\UndecidedComparisonTypeTrait;
use Traversable;
use function array_map;
use function array_values;
use function count;
use function in_array;
use function sprintf;
use function strtolower;

/** @api */
class ObjectType implements TypeWithClassName, SubtractableType
{

	use NonGenericTypeTrait;
	use UndecidedComparisonTypeTrait;

	private const EXTRA_OFFSET_CLASSES = ['SimpleXMLElement', 'DOMNodeList', 'Threaded'];

	private string $className;

	private ?Type $subtractedType;

	private ?ClassReflection $classReflection;

	/** @var array<string, array<string, TrinaryLogic>> */
	private static array $superTypes = [];

	private ?self $cachedParent = null;

	/** @var self[]|null */
	private ?array $cachedInterfaces = null;

	/** @var array<string, array<string, array<string, UnresolvedMethodPrototypeReflection>>> */
	private static array $methods = [];

	/** @var array<string, array<string, array<string, UnresolvedPropertyPrototypeReflection>>> */
	private static array $properties = [];

	/** @var array<string, array<string, self>> */
	private static array $ancestors = [];

	/** @var array<string, self> */
	private array $currentAncestors = [];

	/** @api */
	public function __construct(
		string $className,
		?Type $subtractedType = null,
		?ClassReflection $classReflection = null
	)
	{
		if ($subtractedType instanceof NeverType) {
			$subtractedType = null;
		}

		$this->className = $className;
		$this->subtractedType = $subtractedType;
		$this->classReflection = $classReflection;
	}

	public static function resetCaches(): void
	{
		self::$superTypes = [];
		self::$methods = [];
		self::$properties = [];
		self::$ancestors = [];
	}

	private static function createFromReflection(ClassReflection $reflection): self
	{
		if (!$reflection->isGeneric()) {
			return new ObjectType($reflection->getName());
		}

		return new GenericObjectType(
			$reflection->getName(),
			$reflection->typeMapToList($reflection->getActiveTemplateTypeMap())
		);
	}

	public function getClassName(): string
	{
		return $this->className;
	}

	public function hasProperty(string $propertyName): TrinaryLogic
	{
		$classReflection = $this->getClassReflection();
		if ($classReflection === null) {
			return TrinaryLogic::createMaybe();
		}

		if ($classReflection->hasProperty($propertyName)) {
			return TrinaryLogic::createYes();
		}

		if ($classReflection->isFinal()) {
			return TrinaryLogic::createNo();
		}

		return TrinaryLogic::createMaybe();
	}

	public function getProperty(string $propertyName, ClassMemberAccessAnswerer $scope): PropertyReflection
	{
		return $this->getUnresolvedPropertyPrototype($propertyName, $scope)->getTransformedProperty();
	}

	public function getUnresolvedPropertyPrototype(string $propertyName, ClassMemberAccessAnswerer $scope): UnresolvedPropertyPrototypeReflection
	{
		if (!$scope->isInClass()) {
			$canAccessProperty = 'no';
		} else {
			$canAccessProperty = $scope->getClassReflection()->getName();
		}
		$description = $this->describeCache();

		if (isset(self::$properties[$description][$propertyName][$canAccessProperty])) {
			return self::$properties[$description][$propertyName][$canAccessProperty];
		}

		$nakedClassReflection = $this->getNakedClassReflection();
		if ($nakedClassReflection === null) {
			throw new ClassNotFoundException($this->className);
		}

		if (!$nakedClassReflection->hasProperty($propertyName)) {
			$nakedClassReflection = $this->getClassReflection();
		}

		if ($nakedClassReflection === null) {
			throw new ClassNotFoundException($this->className);
		}

		$property = $nakedClassReflection->getProperty($propertyName, $scope);

		$ancestor = $this->getAncestorWithClassName($property->getDeclaringClass()->getName());
		$resolvedClassReflection = null;
		if ($ancestor !== null) {
			$resolvedClassReflection = $ancestor->getClassReflection();
			if ($ancestor !== $this) {
				$property = $ancestor->getUnresolvedPropertyPrototype($propertyName, $scope)->getNakedProperty();
			}
		}
		if ($resolvedClassReflection === null) {
			$resolvedClassReflection = $property->getDeclaringClass();
		}

		return self::$properties[$description][$propertyName][$canAccessProperty] = new CalledOnTypeUnresolvedPropertyPrototypeReflection(
			$property,
			$resolvedClassReflection,
			true,
			$this
		);
	}

	public function getPropertyWithoutTransformingStatic(string $propertyName, ClassMemberAccessAnswerer $scope): PropertyReflection
	{
		$classReflection = $this->getNakedClassReflection();
		if ($classReflection === null) {
			throw new ClassNotFoundException($this->className);
		}

		if (!$classReflection->hasProperty($propertyName)) {
			$classReflection = $this->getClassReflection();
		}

		if ($classReflection === null) {
			throw new ClassNotFoundException($this->className);
		}

		return $classReflection->getProperty($propertyName, $scope);
	}

	/**
	 * @return string[]
	 */
	public function getReferencedClasses(): array
	{
		return [$this->className];
	}

	public function accepts(Type $type, bool $strictTypes): TrinaryLogic
	{
		if ($type instanceof StaticType) {
			return $this->checkSubclassAcceptability($type->getClassName());
		}

		if ($type instanceof CompoundType) {
			return $type->isAcceptedBy($this, $strictTypes);
		}

		if ($type instanceof ClosureType) {
			return $this->isInstanceOf(Closure::class);
		}

		if ($type instanceof ObjectWithoutClassType) {
			return TrinaryLogic::createMaybe();
		}

		if (!$type instanceof TypeWithClassName) {
			return TrinaryLogic::createNo();
		}

		return $this->checkSubclassAcceptability($type->getClassName());
	}

	public function isSuperTypeOf(Type $type): TrinaryLogic
	{
		$thisDescription = $this->describeCache();

		if ($type instanceof self) {
			$description = $type->describeCache();
		} else {
			$description = $type->describe(VerbosityLevel::cache());
		}

		if (isset(self::$superTypes[$thisDescription][$description])) {
			return self::$superTypes[$thisDescription][$description];
		}

		if ($type instanceof CompoundType) {
			return self::$superTypes[$thisDescription][$description] = $type->isSubTypeOf($this);
		}

		if ($type instanceof ObjectWithoutClassType) {
			if ($type->getSubtractedType() !== null) {
				$isSuperType = $type->getSubtractedType()->isSuperTypeOf($this);
				if ($isSuperType->yes()) {
					return self::$superTypes[$thisDescription][$description] = TrinaryLogic::createNo();
				}
			}
			return self::$superTypes[$thisDescription][$description] = TrinaryLogic::createMaybe();
		}

		if (!$type instanceof TypeWithClassName) {
			return self::$superTypes[$thisDescription][$description] = TrinaryLogic::createNo();
		}

		if ($this->subtractedType !== null) {
			$isSuperType = $this->subtractedType->isSuperTypeOf($type);
			if ($isSuperType->yes()) {
				return self::$superTypes[$thisDescription][$description] = TrinaryLogic::createNo();
			}
		}

		if (
			$type instanceof SubtractableType
			&& $type->getSubtractedType() !== null
		) {
			$isSuperType = $type->getSubtractedType()->isSuperTypeOf($this);
			if ($isSuperType->yes()) {
				return self::$superTypes[$thisDescription][$description] = TrinaryLogic::createNo();
			}
		}

		$thisClassName = $this->className;
		$thatClassName = $type->getClassName();

		if ($thatClassName === $thisClassName) {
			return self::$superTypes[$thisDescription][$description] = TrinaryLogic::createYes();
		}

		$reflectionProvider = ReflectionProviderStaticAccessor::getInstance();

		if ($this->getClassReflection() === null || !$reflectionProvider->hasClass($thatClassName)) {
			return self::$superTypes[$thisDescription][$description] = TrinaryLogic::createMaybe();
		}

		$thisClassReflection = $this->getClassReflection();
		$thatClassReflection = $reflectionProvider->getClass($thatClassName);

		if ($thisClassReflection->getName() === $thatClassReflection->getName()) {
			return self::$superTypes[$thisDescription][$description] = TrinaryLogic::createYes();
		}

		if ($thatClassReflection->isSubclassOf($thisClassName)) {
			return self::$superTypes[$thisDescription][$description] = TrinaryLogic::createYes();
		}

		if ($thisClassReflection->isSubclassOf($thatClassName)) {
			return self::$superTypes[$thisDescription][$description] = TrinaryLogic::createMaybe();
		}

		if ($thisClassReflection->isInterface() && !$thatClassReflection->getNativeReflection()->isFinal()) {
			return self::$superTypes[$thisDescription][$description] = TrinaryLogic::createMaybe();
		}

		if ($thatClassReflection->isInterface() && !$thisClassReflection->getNativeReflection()->isFinal()) {
			return self::$superTypes[$thisDescription][$description] = TrinaryLogic::createMaybe();
		}

		return self::$superTypes[$thisDescription][$description] = TrinaryLogic::createNo();
	}

	public function equals(Type $type): bool
	{
		if (!$type instanceof self) {
			return false;
		}

		if ($this->className !== $type->className) {
			return false;
		}

		if ($this->subtractedType === null) {
			if ($type->subtractedType === null) {
				return true;
			}

			return false;
		}

		if ($type->subtractedType === null) {
			return false;
		}

		return $this->subtractedType->equals($type->subtractedType);
	}

	private function checkSubclassAcceptability(string $thatClass): TrinaryLogic
	{
		if ($this->className === $thatClass) {
			return TrinaryLogic::createYes();
		}

		$reflectionProvider = ReflectionProviderStaticAccessor::getInstance();

		if ($this->getClassReflection() === null || !$reflectionProvider->hasClass($thatClass)) {
			return TrinaryLogic::createNo();
		}

		$thisReflection = $this->getClassReflection();
		$thatReflection = $reflectionProvider->getClass($thatClass);

		if ($thisReflection->getName() === $thatReflection->getName()) {
			// class alias
			return TrinaryLogic::createYes();
		}

		if ($thisReflection->isInterface() && $thatReflection->isInterface()) {
			return TrinaryLogic::createFromBoolean(
				$thatReflection->implementsInterface($this->className)
			);
		}

		return TrinaryLogic::createFromBoolean(
			$thatReflection->isSubclassOf($this->className)
		);
	}

	public function describe(VerbosityLevel $level): string
	{
		$preciseNameCallback = function (): string {
			$reflectionProvider = ReflectionProviderStaticAccessor::getInstance();
			if (!$reflectionProvider->hasClass($this->className)) {
				return $this->className;
			}

			return $reflectionProvider->getClassName($this->className);
		};

		$preciseWithSubtracted = function () use ($level): string {
			$description = $this->className;
			if ($this->subtractedType !== null) {
				$description .= sprintf('~%s', $this->subtractedType->describe($level));
			}

			return $description;
		};

		return $level->handle(
			$preciseNameCallback,
			$preciseNameCallback,
			$preciseWithSubtracted,
			function () use ($preciseWithSubtracted): string {
				$reflection = $this->classReflection;
				$line = '';
				if ($reflection !== null) {
					$line .= '-';
					$line .= (string) $reflection->getNativeReflection()->getStartLine();
					$line .= '-';
				}

				return $preciseWithSubtracted() . '-' . static::class . '-' . $line . $this->describeAdditionalCacheKey();
			}
		);
	}

	protected function describeAdditionalCacheKey(): string
	{
		return '';
	}

	private function describeCache(): string
	{
		if (static::class !== self::class) {
			return $this->describe(VerbosityLevel::cache());
		}

		$description = $this->className;
		if ($this->subtractedType !== null) {
			$description .= sprintf('~%s', $this->subtractedType->describe(VerbosityLevel::cache()));
		}

		$reflection = $this->classReflection;
		if ($reflection !== null) {
			$description .= '-';
			$description .= (string) $reflection->getNativeReflection()->getStartLine();
			$description .= '-';
		}

		return $description;
	}

	public function toNumber(): Type
	{
		if ($this->isInstanceOf('SimpleXMLElement')->yes()) {
			return new UnionType([
				new FloatType(),
				new IntegerType(),
			]);
		}

		return new ErrorType();
	}

	public function toInteger(): Type
	{
		if ($this->isInstanceOf('SimpleXMLElement')->yes()) {
			return new IntegerType();
		}

		if (in_array($this->getClassName(), ['CurlHandle', 'CurlMultiHandle'], true)) {
			return new IntegerType();
		}

		return new ErrorType();
	}

	public function toFloat(): Type
	{
		if ($this->isInstanceOf('SimpleXMLElement')->yes()) {
			return new FloatType();
		}
		return new ErrorType();
	}

	public function toString(): Type
	{
		$classReflection = $this->getClassReflection();
		if ($classReflection === null) {
			return new ErrorType();
		}

		if ($classReflection->hasNativeMethod('__toString')) {
			return ParametersAcceptorSelector::selectSingle($this->getMethod('__toString', new OutOfClassScope())->getVariants())->getReturnType();
		}

		return new ErrorType();
	}

	public function toArray(): Type
	{
		$classReflection = $this->getClassReflection();
		if ($classReflection === null) {
			return new ArrayType(new MixedType(), new MixedType());
		}

		$reflectionProvider = ReflectionProviderStaticAccessor::getInstance();

		if (
			!$classReflection->getNativeReflection()->isUserDefined()
			|| UniversalObjectCratesClassReflectionExtension::isUniversalObjectCrate(
				$reflectionProvider,
				Broker::getInstance()->getUniversalObjectCratesClasses(),
				$classReflection
			)
		) {
			return new ArrayType(new MixedType(), new MixedType());
		}
		$arrayKeys = [];
		$arrayValues = [];

		do {
			foreach ($classReflection->getNativeReflection()->getProperties() as $nativeProperty) {
				if ($nativeProperty->isStatic()) {
					continue;
				}

				$declaringClass = $reflectionProvider->getClass($nativeProperty->getDeclaringClass()->getName());
				$property = $declaringClass->getNativeProperty($nativeProperty->getName());

				$keyName = $nativeProperty->getName();
				if ($nativeProperty->isPrivate()) {
					$keyName = sprintf(
						"\0%s\0%s",
						$declaringClass->getName(),
						$keyName
					);
				} elseif ($nativeProperty->isProtected()) {
					$keyName = sprintf(
						"\0*\0%s",
						$keyName
					);
				}

				$arrayKeys[] = new ConstantStringType($keyName);
				$arrayValues[] = $property->getReadableType();
			}

			$classReflection = $classReflection->getParentClass();
		} while ($classReflection !== null);

		return new ConstantArrayType($arrayKeys, $arrayValues);
	}

	public function toBoolean(): BooleanType
	{
		if ($this->isInstanceOf('SimpleXMLElement')->yes()) {
			return new BooleanType();
		}

		return new ConstantBooleanType(true);
	}

	public function canAccessProperties(): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	public function canCallMethods(): TrinaryLogic
	{
		if (strtolower($this->className) === 'stdclass') {
			return TrinaryLogic::createNo();
		}

		return TrinaryLogic::createYes();
	}

	public function hasMethod(string $methodName): TrinaryLogic
	{
		$classReflection = $this->getClassReflection();
		if ($classReflection === null) {
			return TrinaryLogic::createMaybe();
		}

		if ($classReflection->hasMethod($methodName)) {
			return TrinaryLogic::createYes();
		}

		if ($classReflection->isFinal()) {
			return TrinaryLogic::createNo();
		}

		return TrinaryLogic::createMaybe();
	}

	public function getMethod(string $methodName, ClassMemberAccessAnswerer $scope): MethodReflection
	{
		return $this->getUnresolvedMethodPrototype($methodName, $scope)->getTransformedMethod();
	}

	public function getUnresolvedMethodPrototype(string $methodName, ClassMemberAccessAnswerer $scope): UnresolvedMethodPrototypeReflection
	{
		if (!$scope->isInClass()) {
			$canCallMethod = 'no';
		} else {
			$canCallMethod = $scope->getClassReflection()->getName();
		}
		$description = $this->describeCache();
		if (isset(self::$methods[$description][$methodName][$canCallMethod])) {
			return self::$methods[$description][$methodName][$canCallMethod];
		}

		$nakedClassReflection = $this->getNakedClassReflection();
		if ($nakedClassReflection === null) {
			throw new ClassNotFoundException($this->className);
		}

		if (!$nakedClassReflection->hasMethod($methodName)) {
			$nakedClassReflection = $this->getClassReflection();
		}

		if ($nakedClassReflection === null) {
			throw new ClassNotFoundException($this->className);
		}

		$method = $nakedClassReflection->getMethod($methodName, $scope);

		$ancestor = $this->getAncestorWithClassName($method->getDeclaringClass()->getName());
		$resolvedClassReflection = null;
		if ($ancestor !== null) {
			$resolvedClassReflection = $ancestor->getClassReflection();
			if ($ancestor !== $this) {
				$method = $ancestor->getUnresolvedMethodPrototype($methodName, $scope)->getNakedMethod();
			}
		}
		if ($resolvedClassReflection === null) {
			$resolvedClassReflection = $method->getDeclaringClass();
		}

		return self::$methods[$description][$methodName][$canCallMethod] = new CalledOnTypeUnresolvedMethodPrototypeReflection(
			$method,
			$resolvedClassReflection,
			true,
			$this
		);
	}

	public function canAccessConstants(): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	public function hasConstant(string $constantName): TrinaryLogic
	{
		$class = $this->getClassReflection();
		if ($class === null) {
			return TrinaryLogic::createNo();
		}

		return TrinaryLogic::createFromBoolean(
			$class->hasConstant($constantName)
		);
	}

	public function getConstant(string $constantName): ConstantReflection
	{
		$class = $this->getClassReflection();
		if ($class === null) {
			throw new ClassNotFoundException($this->className);
		}

		return $class->getConstant($constantName);
	}

	public function isIterable(): TrinaryLogic
	{
		return $this->isInstanceOf(Traversable::class);
	}

	public function isIterableAtLeastOnce(): TrinaryLogic
	{
		return $this->isInstanceOf(Traversable::class)
			->and(TrinaryLogic::createMaybe());
	}

	public function getIterableKeyType(): Type
	{
		if ($this->isInstanceOf(ArrayAccess::class)->yes()) {
			$tKey = GenericTypeVariableResolver::getType($this, ArrayAccess::class, 'TKey');
			if ($tKey !== null) {
				return $tKey;
			}

			return new MixedType();
		}

		$classReflection = $this->getClassReflection();
		if ($classReflection === null) {
			return new ErrorType();
		}

		if ($this->isInstanceOf(Iterator::class)->yes()) {
			return RecursionGuard::run($this, function (): Type {
				return ParametersAcceptorSelector::selectSingle(
					$this->getMethod('key', new OutOfClassScope())->getVariants()
				)->getReturnType();
			});
		}

		if ($this->isInstanceOf(IteratorAggregate::class)->yes()) {
			$keyType = RecursionGuard::run($this, function (): Type {
				return ParametersAcceptorSelector::selectSingle(
					$this->getMethod('getIterator', new OutOfClassScope())->getVariants()
				)->getReturnType()->getIterableKeyType();
			});
			if (!$keyType instanceof MixedType || $keyType->isExplicitMixed()) {
				return $keyType;
			}
		}

		if ($this->isInstanceOf(Traversable::class)->yes()) {
			$tKey = GenericTypeVariableResolver::getType($this, Traversable::class, 'TKey');
			if ($tKey !== null) {
				return $tKey;
			}

			return new MixedType();
		}

		return new ErrorType();
	}

	public function getIterableValueType(): Type
	{
		if ($this->isInstanceOf(ArrayAccess::class)->yes()) {
			$tValue = GenericTypeVariableResolver::getType($this, ArrayAccess::class, 'TValue');
			if ($tValue !== null) {
				return $tValue;
			}

			return new MixedType();
		}

		if ($this->isInstanceOf(Iterator::class)->yes()) {
			return RecursionGuard::run($this, function (): Type {
				return ParametersAcceptorSelector::selectSingle(
					$this->getMethod('current', new OutOfClassScope())->getVariants()
				)->getReturnType();
			});
		}

		if ($this->isInstanceOf(IteratorAggregate::class)->yes()) {
			$valueType = RecursionGuard::run($this, function (): Type {
				return ParametersAcceptorSelector::selectSingle(
					$this->getMethod('getIterator', new OutOfClassScope())->getVariants()
				)->getReturnType()->getIterableValueType();
			});
			if (!$valueType instanceof MixedType || $valueType->isExplicitMixed()) {
				return $valueType;
			}
		}

		if ($this->isInstanceOf(Traversable::class)->yes()) {
			$tValue = GenericTypeVariableResolver::getType($this, Traversable::class, 'TValue');
			if ($tValue !== null) {
				return $tValue;
			}

			return new MixedType();
		}

		return new ErrorType();
	}

	public function isArray(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isNumericString(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isNonEmptyString(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isLiteralString(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	private function isExtraOffsetAccessibleClass(): TrinaryLogic
	{
		$classReflection = $this->getClassReflection();
		if ($classReflection === null) {
			return TrinaryLogic::createMaybe();
		}

		foreach (self::EXTRA_OFFSET_CLASSES as $extraOffsetClass) {
			if ($classReflection->getName() === $extraOffsetClass) {
				return TrinaryLogic::createYes();
			}
			if ($classReflection->isSubclassOf($extraOffsetClass)) {
				return TrinaryLogic::createYes();
			}
		}

		if ($classReflection->isInterface()) {
			return TrinaryLogic::createMaybe();
		}

		if ($classReflection->isFinal()) {
			return TrinaryLogic::createNo();
		}

		return TrinaryLogic::createMaybe();
	}

	public function isOffsetAccessible(): TrinaryLogic
	{
		return $this->isInstanceOf(ArrayAccess::class)->or(
			$this->isExtraOffsetAccessibleClass()
		);
	}

	public function hasOffsetValueType(Type $offsetType): TrinaryLogic
	{
		if ($this->isInstanceOf(ArrayAccess::class)->yes()) {
			$acceptedOffsetType = RecursionGuard::run($this, function (): Type {
				$parameters = ParametersAcceptorSelector::selectSingle($this->getMethod('offsetSet', new OutOfClassScope())->getVariants())->getParameters();
				if (count($parameters) < 2) {
					throw new ShouldNotHappenException(sprintf(
						'Method %s::%s() has less than 2 parameters.',
						$this->className,
						'offsetSet'
					));
				}

				$offsetParameter = $parameters[0];

				return $offsetParameter->getType();
			});

			if ($acceptedOffsetType->isSuperTypeOf($offsetType)->no()) {
				return TrinaryLogic::createNo();
			}

			return TrinaryLogic::createMaybe();
		}

		return $this->isExtraOffsetAccessibleClass()
			->and(TrinaryLogic::createMaybe());
	}

	public function getOffsetValueType(Type $offsetType): Type
	{
		if (!$this->isExtraOffsetAccessibleClass()->no()) {
			return new MixedType();
		}

		if ($this->isInstanceOf(ArrayAccess::class)->yes()) {
			return RecursionGuard::run($this, function (): Type {
				return ParametersAcceptorSelector::selectSingle($this->getMethod('offsetGet', new OutOfClassScope())->getVariants())->getReturnType();
			});
		}

		return new ErrorType();
	}

	public function setOffsetValueType(?Type $offsetType, Type $valueType, bool $unionValues = true): Type
	{
		if ($this->isOffsetAccessible()->no()) {
			return new ErrorType();
		}

		if ($this->isInstanceOf(ArrayAccess::class)->yes()) {
			$acceptedValueType = new NeverType();
			$acceptedOffsetType = RecursionGuard::run($this, function () use (&$acceptedValueType): Type {
				$parameters = ParametersAcceptorSelector::selectSingle($this->getMethod('offsetSet', new OutOfClassScope())->getVariants())->getParameters();
				if (count($parameters) < 2) {
					throw new ShouldNotHappenException(sprintf(
						'Method %s::%s() has less than 2 parameters.',
						$this->className,
						'offsetSet'
					));
				}

				$offsetParameter = $parameters[0];
				$acceptedValueType = $parameters[1]->getType();

				return $offsetParameter->getType();
			});

			if ($offsetType === null) {
				$offsetType = new NullType();
			}

			if (
				(!$offsetType instanceof MixedType && !$acceptedOffsetType->isSuperTypeOf($offsetType)->yes())
				|| (!$valueType instanceof MixedType && !$acceptedValueType->isSuperTypeOf($valueType)->yes())
			) {
				return new ErrorType();
			}
		}

		// in the future we may return intersection of $this and OffsetAccessibleType()
		return $this;
	}

	public function isCallable(): TrinaryLogic
	{
		$parametersAcceptors = $this->findCallableParametersAcceptors();
		if ($parametersAcceptors === null) {
			return TrinaryLogic::createNo();
		}

		if (
			count($parametersAcceptors) === 1
			&& $parametersAcceptors[0] instanceof TrivialParametersAcceptor
		) {
			return TrinaryLogic::createMaybe();
		}

		return TrinaryLogic::createYes();
	}

	/**
	 * @return ParametersAcceptor[]
	 */
	public function getCallableParametersAcceptors(ClassMemberAccessAnswerer $scope): array
	{
		if ($this->className === Closure::class) {
			return [new TrivialParametersAcceptor()];
		}
		$parametersAcceptors = $this->findCallableParametersAcceptors();
		if ($parametersAcceptors === null) {
			throw new ShouldNotHappenException();
		}

		return $parametersAcceptors;
	}

	/**
	 * @return ParametersAcceptor[]|null
	 */
	private function findCallableParametersAcceptors(): ?array
	{
		$classReflection = $this->getClassReflection();
		if ($classReflection === null) {
			return [new TrivialParametersAcceptor()];
		}

		if ($classReflection->hasNativeMethod('__invoke')) {
			return $this->getMethod('__invoke', new OutOfClassScope())->getVariants();
		}

		if (!$classReflection->getNativeReflection()->isFinal()) {
			return [new TrivialParametersAcceptor()];
		}

		return null;
	}

	public function isCloneable(): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	/**
	 * @param mixed[] $properties
	 */
	public static function __set_state(array $properties): Type
	{
		return new self(
			$properties['className'],
			$properties['subtractedType'] ?? null
		);
	}

	public function isInstanceOf(string $className): TrinaryLogic
	{
		$classReflection = $this->getClassReflection();
		if ($classReflection === null) {
			return TrinaryLogic::createMaybe();
		}

		if ($classReflection->isSubclassOf($className) || $classReflection->getName() === $className) {
			return TrinaryLogic::createYes();
		}

		if ($classReflection->isInterface()) {
			return TrinaryLogic::createMaybe();
		}

		return TrinaryLogic::createNo();
	}

	public function subtract(Type $type): Type
	{
		if ($this->subtractedType !== null) {
			$type = TypeCombinator::union($this->subtractedType, $type);
		}

		return $this->changeSubtractedType($type);
	}

	public function getTypeWithoutSubtractedType(): Type
	{
		return $this->changeSubtractedType(null);
	}

	public function changeSubtractedType(?Type $subtractedType): Type
	{
		return new self($this->className, $subtractedType);
	}

	public function getSubtractedType(): ?Type
	{
		return $this->subtractedType;
	}

	public function traverse(callable $cb): Type
	{
		$subtractedType = $this->subtractedType !== null ? $cb($this->subtractedType) : null;

		if ($subtractedType !== $this->subtractedType) {
			return new self(
				$this->className,
				$subtractedType
			);
		}

		return $this;
	}

	public function getNakedClassReflection(): ?ClassReflection
	{
		if ($this->classReflection !== null) {
			return $this->classReflection;
		}
		$reflectionProvider = ReflectionProviderStaticAccessor::getInstance();
		if (!$reflectionProvider->hasClass($this->className)) {
			return null;
		}

		$this->classReflection = $reflectionProvider->getClass($this->className);

		return $this->classReflection;
	}

	public function getClassReflection(): ?ClassReflection
	{
		if ($this->classReflection !== null) {
			return $this->classReflection;
		}
		$reflectionProvider = ReflectionProviderStaticAccessor::getInstance();
		if (!$reflectionProvider->hasClass($this->className)) {
			return null;
		}

		$classReflection = $reflectionProvider->getClass($this->className);
		if ($classReflection->isGeneric()) {
			return $this->classReflection = $classReflection->withTypes(array_values($classReflection->getTemplateTypeMap()->resolveToBounds()->getTypes()));
		}

		return $this->classReflection = $classReflection;
	}

	/**
	 * @return self|null
	 */
	public function getAncestorWithClassName(string $className): ?TypeWithClassName
	{
		if (isset($this->currentAncestors[$className])) {
			return $this->currentAncestors[$className];
		}

		$thisReflection = $this->getClassReflection();
		if ($thisReflection === null) {
			return null;
		}

		$description = $this->describeCache() . '-' . $thisReflection->getCacheKey();
		if (isset(self::$ancestors[$description][$className])) {
			return self::$ancestors[$description][$className];
		}

		$reflectionProvider = ReflectionProviderStaticAccessor::getInstance();
		if (!$reflectionProvider->hasClass($className)) {
			return null;
		}
		$theirReflection = $reflectionProvider->getClass($className);

		if ($theirReflection->getName() === $thisReflection->getName()) {
			return self::$ancestors[$description][$className] = $this->currentAncestors[$className] = $this;
		}

		foreach ($this->getInterfaces() as $interface) {
			$ancestor = $interface->getAncestorWithClassName($className);
			if ($ancestor !== null) {
				return self::$ancestors[$description][$className] = $this->currentAncestors[$className] = $ancestor;
			}
		}

		$parent = $this->getParent();
		if ($parent !== null) {
			$ancestor = $parent->getAncestorWithClassName($className);
			if ($ancestor !== null) {
				return self::$ancestors[$description][$className] = $this->currentAncestors[$className] = $ancestor;
			}
		}

		return null;
	}

	private function getParent(): ?ObjectType
	{
		if ($this->cachedParent !== null) {
			return $this->cachedParent;
		}
		$thisReflection = $this->getClassReflection();
		if ($thisReflection === null) {
			return null;
		}

		$parentReflection = $thisReflection->getParentClass();
		if ($parentReflection === null) {
			return null;
		}

		return $this->cachedParent = self::createFromReflection($parentReflection);
	}

	/** @return ObjectType[] */
	private function getInterfaces(): array
	{
		if ($this->cachedInterfaces !== null) {
			return $this->cachedInterfaces;
		}
		$thisReflection = $this->getClassReflection();
		if ($thisReflection === null) {
			return $this->cachedInterfaces = [];
		}

		return $this->cachedInterfaces = array_map(static function (ClassReflection $interfaceReflection): self {
			return self::createFromReflection($interfaceReflection);
		}, $thisReflection->getInterfaces());
	}

}
