<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Php;

use PhpParser\Modifiers;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Reflection\Assertions;
use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\MissingMethodFromReflectionException;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VoidType;
use function in_array;
use function sprintf;
use function strtolower;

/**
 * @api
 */
final class PhpMethodFromParserNodeReflection extends PhpFunctionFromParserNodeReflection implements ExtendedMethodReflection
{

	/**
	 * @param Type[] $realParameterTypes
	 * @param Type[] $phpDocParameterTypes
	 * @param Type[] $realParameterDefaultValues
	 * @param array<string, bool> $immediatelyInvokedCallableParameters
	 * @param array<string, Type> $phpDocClosureThisTypeParameters
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
		array $parameterOutTypes,
		array $immediatelyInvokedCallableParameters,
		array $phpDocClosureThisTypeParameters,
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
		if (in_array($name, ['__construct', '__destruct', '__unset', '__wakeup', '__clone'], true)) {
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
			$realReturnType = TypeCombinator::intersect(TypeCombinator::addNull(
				new ArrayType(new MixedType(true), new MixedType(true)),
			), $realReturnType);
		}

		if ($name === '__unserialize') {
			$realReturnType = new VoidType();
		}
		if ($name === '__serialize') {
			$realReturnType = new ArrayType(new MixedType(true), new MixedType(true));
		}

		parent::__construct(
			$classMethod,
			$fileName,
			$templateTypeMap,
			$realParameterTypes,
			$phpDocParameterTypes,
			$realParameterDefaultValues,
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
		);
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
			return TrinaryLogic::createFromBoolean((bool) ($method->flags & Modifiers::FINAL));
		}

		return TrinaryLogic::createFromBoolean($method->isFinal() || $this->isFinal);
	}

	public function isFinalByKeyword(): TrinaryLogic
	{
		$method = $this->getClassMethod();
		if ($method instanceof Node\PropertyHook) {
			return TrinaryLogic::createFromBoolean((bool) ($method->flags & Modifiers::FINAL));
		}

		return TrinaryLogic::createFromBoolean($method->isFinal());
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

}
