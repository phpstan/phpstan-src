<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Constant;

use PHPStan\PhpDoc\ResolvedPhpDocBlock;
use PHPStan\Reflection\GlobalConstantReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\Type;

class RuntimeConstantReflection implements GlobalConstantReflection
{

	private false|ResolvedPhpDocBlock $resolvedPhpDocBlock = false;

	private ?string $deprecatedDescription = null;

	/**
	 * @param non-empty-string|null $docComment
	 */
	public function __construct(
		private string $name,
		private Type $valueType,
		private ?string $fileName,
		private FileTypeMapper $fileTypeMapper,
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
		$resolvedPhpDoc = $this->getResolvedPhpDoc();
		if ($resolvedPhpDoc === null) {
			return TrinaryLogic::createNo();
		}

		return TrinaryLogic::createFromBoolean($resolvedPhpDoc->isDeprecated());
	}

	public function getDeprecatedDescription(): ?string
	{
		if ($this->deprecatedDescription === null && $this->isDeprecated()->yes()) {
			$resolvedPhpDoc = $this->getResolvedPhpDoc();
			if ($resolvedPhpDoc !== null && $resolvedPhpDoc->getDeprecatedTag() !== null) {
				$this->deprecatedDescription = $resolvedPhpDoc->getDeprecatedTag()->getMessage();
			}
		}

		return $this->deprecatedDescription;
	}

	/** @return non-empty-string|null */
	public function getDocComment(): ?string
	{
		return $this->docComment;
	}

	private function getResolvedPhpDoc(): ?ResolvedPhpDocBlock
	{
		if ($this->docComment === null) {
			return null;
		}

		if ($this->resolvedPhpDocBlock !== false) {
			return $this->resolvedPhpDocBlock;
		}

		return $this->resolvedPhpDocBlock = $this->fileTypeMapper->getResolvedPhpDoc($this->getFileName(), null, null, null, $this->docComment);
	}

	public function isInternal(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

}
