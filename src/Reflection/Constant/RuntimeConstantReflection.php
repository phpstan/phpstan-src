<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Constant;

use PHPStan\BetterReflection\Reflection\Annotation\AnnotationHelper;
use PHPStan\Reflection\GlobalConstantReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;

class RuntimeConstantReflection implements GlobalConstantReflection
{

	/**
	 * @param non-empty-string|null $docComment
	 */
	public function __construct(
		private string $name,
		private Type $valueType,
		private ?string $fileName,
		private ?string $docComment,
	)
	{
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getValueType(): Type
	{
		return $this->valueType;
	}

	public function getFileName(): ?string
	{
		return $this->fileName;
	}

	public function isDeprecated(): TrinaryLogic
	{
		return TrinaryLogic::createFromBoolean(AnnotationHelper::isDeprecated($this->getDocComment()));
	}

	public function getDeprecatedDescription(): ?string
	{
		return null;
	}

	/** @return non-empty-string|null */
	public function getDocComment(): ?string
	{
		return $this->docComment;
	}

	public function isInternal(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

}
