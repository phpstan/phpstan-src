<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Php;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\PhpDoc\ResolvedPhpDocBlock;
use PHPStan\Reflection\Assertions;
use PHPStan\Reflection\AttributeReflection;
use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\MissingMethodFromReflectionException;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Turbo\ReferencedByTurboExtension;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use PHPStan\Type\VoidType;
use function array_key_exists;
use function in_array;
use function is_string;
use function sprintf;
use function strtolower;

/**
 * @api
 */
#[ReferencedByTurboExtension(key: 'phpMethodFromParserNodeReflection')]
final class PhpMethodFromParserNodeReflection extends PhpFunctionFromParserNodeReflection implements ExtendedMethodReflection
{

	private const MAGIC_METHOD_PARAMETER_TYPES = [
		'__destruct' => [],
		'__clone' => [],
		'__tostring' => [],
		'__debuginfo' => [],
		'__serialize' => [],
		'__sleep' => [],
		'__wakeup' => [],
		'__get' => ['string'],
		'__set' => ['string', 'mixed'],
		'__isset' => ['string'],
		'__unset' => ['string'],
		'__call' => ['string', 'array'],
		'__callstatic' => ['string', 'array'],
		'__unserialize' => ['array'],
		'__set_state' => ['array'],
	];

	/**
	 * @param Type[] $realParameterTypes
	 * @param Type[] $phpDocParameterTypes
	 * @param Type[] $realParameterDefaultValues
	 * @param array<string, list<AttributeReflection>> $parameterAttributes
	 * @param array<string, bool> $immediatelyInvokedCallableParameters
	 * @param array<string, Type> $phpDocClosureThisTypeParameters
	 * @param list<AttributeReflection> $attributes
	 */
	public function __construct(
		private ClassReflection $declaringClass,
		private ClassMethod|Node\PropertyHook $classMethod,
		private ?string $hookForProperty,
		string $fileName,
		TemplateTypeMap $templateTypeMap,
		array $realParameterTypes,
		array $phpDocParameterTypes,
		array $realParameterDefaultValues,
		array $parameterAttributes,
		Type $realReturnType,
		?Type $phpDocReturnType,
		?Type $throwType,
		?string $deprecatedDescription,
		bool $isDeprecated,
		bool $isInternal,
		private bool $isFinal,
		?bool $isPure,
		bool $acceptsNamedArguments,
		Assertions $assertions,
		private ?Type $selfOutType,
		?string $phpDocComment,
		private ?ResolvedPhpDocBlock $resolvedPhpDoc,
		array $parameterOutTypes,
		array $immediatelyInvokedCallableParameters,
		array $phpDocClosureThisTypeParameters,
		private bool $isConstructor,
		array $attributes,
		array $pureUnlessCallableIsImpureParameters,
		array $pureUnlessParameterPassedParameters,
	)
	{
		if ($this->classMethod instanceof Node\PropertyHook) {
			if ($this->hookForProperty === null) {
				throw new ShouldNotHappenException('Hook was provided but property was not');
			}
		} elseif ($this->hookForProperty !== null) {
			throw new ShouldNotHappenException('Hooked property was provided but hook was not');
		}

		$name = strtolower($classMethod->name->name);
		if ($this->isConstructor) {
			$realReturnType = new VoidType();
		}
		if (in_array($name, ['__destruct', '__unset', '__wakeup', '__clone'], true)) {
			$realReturnType = new VoidType();
		}
		if ($name === '__tostring') {
			$realReturnType = new StringType();
		}
		if ($name === '__isset') {
			$realReturnType = new BooleanType();
		}
		if ($name === '__sleep') {
			$realReturnType = new ArrayType(new IntegerType(), new StringType());
		}
		if ($name === '__set_state') {
			$realReturnType = TypeCombinator::intersect(new ObjectWithoutClassType(), $realReturnType);
		}
		if ($name === '__set') {
			$realReturnType = new VoidType();
		}

		if ($name === '__debuginfo') {
			$realReturnType = TypeCombinator::intersect(new UnionType([new ArrayType(new MixedType(true), new MixedType(true)), new NullType()]), $realReturnType);
		}

		if ($name === '__unserialize') {
			$realReturnType = new VoidType();
		}
		if ($name === '__serialize') {
			$realReturnType = new ArrayType(new MixedType(true), new MixedType(true));
		}

		if (array_key_exists($name, self::MAGIC_METHOD_PARAMETER_TYPES)) {
			$realParameterTypes = $this->resolveMagicMethodParameterTypes($classMethod, self::MAGIC_METHOD_PARAMETER_TYPES[$name], $realParameterTypes);
		}

		parent::__construct(
			$classMethod,
			$fileName,
			$templateTypeMap,
			$realParameterTypes,
			$phpDocParameterTypes,
			$realParameterDefaultValues,
			$parameterAttributes,
			$realReturnType,
			$phpDocReturnType,
			$throwType,
			$deprecatedDescription,
			$isDeprecated,
			$isInternal,
			$isPure,
			$acceptsNamedArguments,
			$assertions,
			$phpDocComment,
			$parameterOutTypes,
			$immediatelyInvokedCallableParameters,
			$phpDocClosureThisTypeParameters,
			$attributes,
			$pureUnlessCallableIsImpureParameters,
			$pureUnlessParameterPassedParameters,
		);
	}

	/**
	 * Untyped parameters of magic methods get the types PHP passes to them.
	 * Parameters the magic method cannot take are reported by MagicMethodParametersRule,
	 * so they are not reported as missing a type.
	 *
	 * @param list<'string'|'mixed'|'array'> $magicParameterTypes
	 * @param Type[] $realParameterTypes
	 * @return Type[]
	 */
	private function resolveMagicMethodParameterTypes(ClassMethod|Node\PropertyHook $classMethod, array $magicParameterTypes, array $realParameterTypes): array
	{
		$i = 0;
		foreach ($classMethod->getParams() as $param) {
			if ($param->variadic) {
				break;
			}

			$magicParameterType = $magicParameterTypes[$i] ?? 'mixed';
			$i++;
			if ($param->type !== null) {
				continue;
			}
			if (!$param->var instanceof Node\Expr\Variable || !is_string($param->var->name)) {
				throw new ShouldNotHappenException();
			}

			if ($magicParameterType === 'string') {
				$type = new StringType();
			} elseif ($magicParameterType === 'mixed') {
				$type = new MixedType(true);
			} else {
				$type = new ArrayType(new MixedType(true), new MixedType(true));
			}

			$realParameterTypes[$param->var->name] = $type;
		}

		return $realParameterTypes;
	}

	public function getDeclaringClass(): ClassReflection
	{
		return $this->declaringClass;
	}

	public function getPrototype(): ClassMemberReflection
	{
		try {
			return $this->declaringClass->getNativeMethod($this->getClassMethod()->name->name)->getPrototype();
		} catch (MissingMethodFromReflectionException) {
			return $this;
		}
	}

	private function getClassMethod(): ClassMethod|Node\PropertyHook
	{
		/** @var Node\Stmt\ClassMethod|Node\PropertyHook $functionLike */
		$functionLike = $this->getFunctionLike();
		return $functionLike;
	}

	public function getName(): string
	{
		$function = $this->getFunctionLike();
		if (!$function instanceof Node\PropertyHook) {
			return parent::getName();
		}

		if ($this->hookForProperty === null) {
			throw new ShouldNotHappenException('Hook was provided but property was not');
		}

		return sprintf('$%s::%s', $this->hookForProperty, $function->name->toString());
	}

	/**
	 * @phpstan-assert-if-true !null $this->getHookedPropertyName()
	 * @phpstan-assert-if-true !null $this->getPropertyHookName()
	 */
	public function isPropertyHook(): bool
	{
		return $this->hookForProperty !== null;
	}

	public function getHookedPropertyName(): ?string
	{
		return $this->hookForProperty;
	}

	/**
	 * @return 'get'|'set'|null
	 */
	public function getPropertyHookName(): ?string
	{
		$function = $this->getFunctionLike();
		if (!$function instanceof Node\PropertyHook) {
			return null;
		}

		$name = $function->name->toLowerString();
		if (!in_array($name, ['get', 'set'], true)) {
			throw new ShouldNotHappenException(sprintf('Unknown property hook: %s', $name));
		}

		return $name;
	}

	public function isStatic(): bool
	{
		$method = $this->getClassMethod();
		if ($method instanceof Node\PropertyHook) {
			return false;
		}

		return $method->isStatic();
	}

	public function isPrivate(): bool
	{
		$method = $this->getClassMethod();
		if ($method instanceof Node\PropertyHook) {
			return false;
		}

		return $method->isPrivate();
	}

	public function isPublic(): bool
	{
		$method = $this->getClassMethod();
		if ($method instanceof Node\PropertyHook) {
			return true;
		}

		return $method->isPublic();
	}

	public function isFinal(): TrinaryLogic
	{
		$method = $this->getClassMethod();
		if ($method instanceof Node\PropertyHook) {
			return TrinaryLogic::createFromBoolean($method->isFinal());
		}

		return TrinaryLogic::createFromBoolean($method->isFinal() || $this->isFinal);
	}

	public function isFinalByKeyword(): TrinaryLogic
	{
		return TrinaryLogic::createFromBoolean($this->getClassMethod()->isFinal());
	}

	public function isBuiltin(): bool
	{
		return false;
	}

	public function getSelfOutType(): ?Type
	{
		return $this->selfOutType;
	}

	public function returnsByReference(): TrinaryLogic
	{
		return TrinaryLogic::createFromBoolean($this->getClassMethod()->returnsByRef());
	}

	public function isAbstract(): TrinaryLogic
	{
		$method = $this->getClassMethod();
		if ($method instanceof Node\PropertyHook) {
			return TrinaryLogic::createFromBoolean($method->body === null);
		}

		return TrinaryLogic::createFromBoolean($method->isAbstract());
	}

	public function isConstructor(): bool
	{
		return $this->isConstructor;
	}

	public function hasSideEffects(): TrinaryLogic
	{
		if (
			strtolower($this->getName()) !== '__construct'
			&& $this->getReturnType()->isVoid()->yes()
		) {
			return TrinaryLogic::createYes();
		}
		if ($this->isPure !== null) {
			return TrinaryLogic::createFromBoolean(!$this->isPure);
		}

		return TrinaryLogic::createMaybe();
	}

	public function getResolvedPhpDoc(): ?ResolvedPhpDocBlock
	{
		return $this->resolvedPhpDoc;
	}

}
