<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Php;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Reflection\Assertions;
use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\MissingMethodFromReflectionException;
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
use function strtolower;

/**
 * @api
 */
class PhpMethodFromParserNodeReflection extends PhpFunctionFromParserNodeReflection implements ExtendedMethodReflection
{

	/**
	 * @param Type[] $realParameterTypes
	 * @param Type[] $phpDocParameterTypes
	 * @param Type[] $realParameterDefaultValues
	 */
	public function __construct(
		private ClassReflection $declaringClass,
		ClassMethod $classMethod,
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
		bool $isFinal,
		?bool $isPure,
		bool $acceptsNamedArguments,
		Assertions $assertions,
		private ?Type $selfOutType,
		?string $phpDocComment,
		array $parameterOutTypes,
	)
	{
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
			$isFinal || $classMethod->isFinal(),
			$isPure,
			$acceptsNamedArguments,
			$assertions,
			$phpDocComment,
			$parameterOutTypes,
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

	private function getClassMethod(): ClassMethod
	{
		/** @var Node\Stmt\ClassMethod $functionLike */
		$functionLike = $this->getFunctionLike();
		return $functionLike;
	}

	public function isStatic(): bool
	{
		return $this->getClassMethod()->isStatic();
	}

	public function isPrivate(): bool
	{
		return $this->getClassMethod()->isPrivate();
	}

	public function isPublic(): bool
	{
		return $this->getClassMethod()->isPublic();
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

}
