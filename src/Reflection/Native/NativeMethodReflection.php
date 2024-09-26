<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Native;

use PHPStan\Reflection\Assertions;
use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\MethodPrototypeReflection;
use PHPStan\Reflection\ParametersAcceptorWithPhpDocs;
use PHPStan\Reflection\Php\BuiltinMethodReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;
use PHPStan\Type\TypehintHelper;
use ReflectionException;
use function count;
use function strtolower;

final class NativeMethodReflection implements ExtendedMethodReflection
{

	/**
	 * @param ParametersAcceptorWithPhpDocs[] $variants
	 * @param ParametersAcceptorWithPhpDocs[]|null $namedArgumentsVariants
	 */
	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private ClassReflection $declaringClass,
		private BuiltinMethodReflection $reflection,
		private array $variants,
		private ?array $namedArgumentsVariants,
		private TrinaryLogic $hasSideEffects,
		private ?Type $throwType,
		private Assertions $assertions,
		private bool $acceptsNamedArguments,
		private ?Type $selfOutType,
		private ?string $phpDocComment,
	)
	{
	}

	public function getDeclaringClass(): ClassReflection
	{
		return $this->declaringClass;
	}

	public function isStatic(): bool
	{
		return $this->reflection->isStatic();
	}

	public function isPrivate(): bool
	{
		return $this->reflection->isPrivate();
	}

	public function isPublic(): bool
	{
		return $this->reflection->isPublic();
	}

	public function isAbstract(): TrinaryLogic
	{
		return TrinaryLogic::createFromBoolean($this->reflection->isAbstract());
	}

	public function getPrototype(): ClassMemberReflection
	{
		try {
			$prototypeMethod = $this->reflection->getPrototype();
			$prototypeDeclaringClass = $this->declaringClass->getAncestorWithClassName($prototypeMethod->getDeclaringClass()->getName());
			if ($prototypeDeclaringClass === null) {
				$prototypeDeclaringClass = $this->reflectionProvider->getClass($prototypeMethod->getDeclaringClass()->getName());
			}

			if (!$prototypeDeclaringClass->hasNativeMethod($prototypeMethod->getName())) {
				return $this;
			}

			$tentativeReturnType = null;
			if ($prototypeMethod->getTentativeReturnType() !== null) {
				$tentativeReturnType = TypehintHelper::decideTypeFromReflection($prototypeMethod->getTentativeReturnType());
			}

			return new MethodPrototypeReflection(
				$prototypeMethod->getName(),
				$prototypeDeclaringClass,
				$prototypeMethod->isStatic(),
				$prototypeMethod->isPrivate(),
				$prototypeMethod->isPublic(),
				$prototypeMethod->isAbstract(),
				$prototypeMethod->isFinal(),
				$prototypeMethod->isInternal(),
				$prototypeDeclaringClass->getNativeMethod($prototypeMethod->getName())->getVariants(),
				$tentativeReturnType,
			);
		} catch (ReflectionException) {
			return $this;
		}
	}

	public function getName(): string
	{
		return $this->reflection->getName();
	}

	public function getVariants(): array
	{
		return $this->variants;
	}

	public function getOnlyVariant(): ParametersAcceptorWithPhpDocs
	{
		$variants = $this->getVariants();
		if (count($variants) !== 1) {
			throw new ShouldNotHappenException();
		}

		return $variants[0];
	}

	public function getNamedArgumentsVariants(): ?array
	{
		return $this->namedArgumentsVariants;
	}

	public function getDeprecatedDescription(): ?string
	{
		return null;
	}

	public function isDeprecated(): TrinaryLogic
	{
		return $this->reflection->isDeprecated();
	}

	public function isInternal(): TrinaryLogic
	{
		return TrinaryLogic::createFromBoolean($this->reflection->isInternal());
	}

	public function isFinal(): TrinaryLogic
	{
		return TrinaryLogic::createFromBoolean($this->reflection->isFinal());
	}

	public function isFinalByKeyword(): TrinaryLogic
	{
		return $this->isFinal();
	}

	public function getThrowType(): ?Type
	{
		return $this->throwType;
	}

	public function hasSideEffects(): TrinaryLogic
	{
		$name = strtolower($this->getName());
		$isVoid = $this->isVoid();
		if (
			$name !== '__construct'
			&& $isVoid
		) {
			return TrinaryLogic::createYes();
		}

		return $this->hasSideEffects;
	}

	public function isPure(): TrinaryLogic
	{
		if ($this->hasSideEffects()->yes()) {
			return TrinaryLogic::createNo();
		}

		return $this->hasSideEffects->negate();
	}

	private function isVoid(): bool
	{
		foreach ($this->variants as $variant) {
			if (!$variant->getReturnType()->isVoid()->yes()) {
				return false;
			}
		}

		return true;
	}

	public function getDocComment(): ?string
	{
		return $this->phpDocComment;
	}

	public function getAsserts(): Assertions
	{
		return $this->assertions;
	}

	public function acceptsNamedArguments(): bool
	{
		return $this->declaringClass->acceptsNamedArguments() && $this->acceptsNamedArguments;
	}

	public function getSelfOutType(): ?Type
	{
		return $this->selfOutType;
	}

	public function returnsByReference(): TrinaryLogic
	{
		return $this->reflection->returnsByReference();
	}

}
