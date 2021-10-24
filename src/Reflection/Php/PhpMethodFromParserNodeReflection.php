<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Php;

use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\IntegerType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VoidType;

class PhpMethodFromParserNodeReflection extends PhpFunctionFromParserNodeReflection implements MethodReflection
{

	private \PHPStan\Reflection\ClassReflection $declaringClass;

	/**
	 * @param ClassReflection $declaringClass
	 * @param ClassMethod $classMethod
	 * @param TemplateTypeMap $templateTypeMap
	 * @param \PHPStan\Type\Type[] $realParameterTypes
	 * @param \PHPStan\Type\Type[] $phpDocParameterTypes
	 * @param \PHPStan\Type\Type[] $realParameterDefaultValues
	 * @param Type $realReturnType
	 * @param Type|null $phpDocReturnType
	 * @param Type|null $throwType
	 * @param string|null $deprecatedDescription
	 * @param bool $isDeprecated
	 * @param bool $isInternal
	 * @param bool $isFinal
	 * @param bool|null $isPure
	 */
	public function __construct(
		ClassReflection $declaringClass,
		ClassMethod $classMethod,
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
		?bool $isPure
	)
	{
		$name = strtolower($classMethod->name->name);
		if (
			$name === '__construct'
			|| $name === '__destruct'
			|| $name === '__unset'
			|| $name === '__wakeup'
			|| $name === '__clone'
		) {
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

		parent::__construct(
			$classMethod,
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
			$isPure
		);
		$this->declaringClass = $declaringClass;
	}

	public function getDeclaringClass(): ClassReflection
	{
		return $this->declaringClass;
	}

	public function getPrototype(): ClassMemberReflection
	{
		try {
			return $this->declaringClass->getNativeMethod($this->getClassMethod()->name->name)->getPrototype();
		} catch (\PHPStan\Reflection\MissingMethodFromReflectionException $e) {
			return $this;
		}
	}

	private function getClassMethod(): ClassMethod
	{
		/** @var \PhpParser\Node\Stmt\ClassMethod $functionLike */
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

	public function getDocComment(): ?string
	{
		return null;
	}

	public function isBuiltin(): bool
	{
		return false;
	}

}
