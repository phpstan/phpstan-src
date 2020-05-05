<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\FileTypeMapper;

class PhpDocInheritanceResolver
{

	/** @var \PHPStan\Type\FileTypeMapper */
	private $fileTypeMapper;

	public function __construct(
		FileTypeMapper $fileTypeMapper
	)
	{
		$this->fileTypeMapper = $fileTypeMapper;
	}

	public function resolvePhpDocForProperty(
		?string $docComment,
		ClassReflection $classReflection,
		?string $declaringTraitName,
		string $propertyName
	): ?ResolvedPhpDocBlock
	{
		$phpDocBlock = PhpDocBlock::resolvePhpDocBlockForProperty(
			$docComment,
			$classReflection,
			null,
			$propertyName,
			$classReflection->requireFileName(),
			null,
			[],
			[]
		);

		return $this->docBlockTreeToResolvedDocBlock($phpDocBlock, $declaringTraitName, null);
	}

	/**
	 * @param string|null $docComment
	 * @param ClassReflection $classReflection
	 * @param string|null $declaringTraitName
	 * @param string $methodName
	 * @param array<int, string> $positionalParameterNames
	 * @return ResolvedPhpDocBlock
	 */
	public function resolvePhpDocForMethod(
		?string $docComment,
		ClassReflection $classReflection,
		?string $declaringTraitName,
		string $methodName,
		array $positionalParameterNames
	)
	{
		$phpDocBlock = PhpDocBlock::resolvePhpDocBlockForMethod(
			$docComment,
			$classReflection,
			$declaringTraitName,
			$methodName,
			$classReflection->requireFileName(),
			null,
			$positionalParameterNames,
			$positionalParameterNames
		);

		return $this->docBlockTreeToResolvedDocBlock($phpDocBlock, $phpDocBlock->getTrait(), $methodName);
	}

	private function docBlockTreeToResolvedDocBlock(PhpDocBlock $phpDocBlock, ?string $traitName, ?string $functionName): ResolvedPhpDocBlock
	{
		$parents = [];
		$parentPhpDocBlocks = [];

		foreach ($phpDocBlock->getParents() as $parentPhpDocBlock) {
			$parents[] = $this->docBlockTreeToResolvedDocBlock(
				$parentPhpDocBlock,
				$parentPhpDocBlock->getTrait(),
				$functionName
			);
			$parentPhpDocBlocks[] = $parentPhpDocBlock;
		}

		$oneResolvedDockBlock = $this->docBlockToResolvedDocBlock($phpDocBlock, $traitName, $functionName);
		return $oneResolvedDockBlock->cloneAndMerge($parents, $parentPhpDocBlocks);
	}

	private function docBlockToResolvedDocBlock(PhpDocBlock $phpDocBlock, ?string $traitName, ?string $functionName): ResolvedPhpDocBlock
	{
		$classReflection = $phpDocBlock->getClassReflection();

		return $this->fileTypeMapper->getResolvedPhpDoc(
			$phpDocBlock->getFile(),
			$classReflection->getName(),
			$traitName,
			$functionName,
			$phpDocBlock->getDocComment()
		);
	}
}
