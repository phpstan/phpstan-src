<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\Reflection\Php\PhpPropertyReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Type;

class ResolvedPropertyReflection implements WrapperPropertyReflection
{

	private ?Type $readableType = null;

	private ?Type $writableType = null;

	public function __construct(private PropertyReflection $reflection, private TemplateTypeMap $templateTypeMap)
	{
	}

	public function getOriginalReflection(): PropertyReflection
	{
		return $this->reflection;
	}

	public function getDeclaringClass(): ClassReflection
	{
		return $this->reflection->getDeclaringClass();
	}

	public function getDeclaringTrait(): ?ClassReflection
	{
		if ($this->reflection instanceof PhpPropertyReflection) {
			return $this->reflection->getDeclaringTrait();
		}

		return null;
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

	public function getReadableType(): Type
	{
		$type = $this->readableType;
		if ($type !== null) {
			return $type;
		}

		$type = TemplateTypeHelper::resolveTemplateTypes(
			$this->reflection->getReadableType(),
			$this->templateTypeMap,
		);
		$type = TemplateTypeHelper::resolveTemplateTypes(
			$type,
			$this->templateTypeMap,
		);

		$this->readableType = $type;

		return $type;
	}

	public function getWritableType(): Type
	{
		$type = $this->writableType;
		if ($type !== null) {
			return $type;
		}

		$type = TemplateTypeHelper::resolveTemplateTypes(
			$this->reflection->getWritableType(),
			$this->templateTypeMap,
		);
		$type = TemplateTypeHelper::resolveTemplateTypes(
			$type,
			$this->templateTypeMap,
		);

		$this->writableType = $type;

		return $type;
	}

	public function canChangeTypeAfterAssignment(): bool
	{
		return $this->reflection->canChangeTypeAfterAssignment();
	}

	public function isReadable(): bool
	{
		return $this->reflection->isReadable();
	}

	public function isWritable(): bool
	{
		return $this->reflection->isWritable();
	}

	public function getDocComment(): ?string
	{
		return $this->reflection->getDocComment();
	}

	public function isDeprecated(): TrinaryLogic
	{
		return $this->reflection->isDeprecated();
	}

	public function getDeprecatedDescription(): ?string
	{
		return $this->reflection->getDeprecatedDescription();
	}

	public function isInternal(): TrinaryLogic
	{
		return $this->reflection->isInternal();
	}

}
