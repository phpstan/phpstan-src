<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\FileTypeMapper;

class PhpDocInheritanceResolver
{

	private \PHPStan\Type\FileTypeMapper $fileTypeMapper;

	public function __construct(
		FileTypeMapper $fileTypeMapper
	)
	{
		$this->fileTypeMapper = $fileTypeMapper;
	}

	public function resolvePhpDocForProperty(
		?string $docComment,
		ClassReflection $classReflection,
		string $classReflectionFileName,
		?string $declaringTraitName,
		string $propertyName
	): ResolvedPhpDocBlock
	{
		$phpDocBlock = PhpDocBlock::resolvePhpDocBlockForProperty(
			$docComment,
			$classReflection,
			null,
			$propertyName,
			$classReflectionFileName,
			null,
			[],
			[]
		);

		return $this->docBlockTreeToResolvedDocBlock($phpDocBlock, $declaringTraitName, null);
	}

	public function resolvePhpDocForConstant(
		?string $docComment,
		ClassReflection $classReflection,
		string $classReflectionFileName,
		string $constantName
	): ResolvedPhpDocBlock
	{
		$phpDocBlock = PhpDocBlock::resolvePhpDocBlockForConstant(
			$docComment,
			$classReflection,
			null,
			$constantName,
			$classReflectionFileName,
			null,
			[],
			[]
		);

		return $this->docBlockTreeToResolvedDocBlock($phpDocBlock, null, null);
	}

	/**
	 * @param array<int, string> $positionalParameterNames
	 */
	public function resolvePhpDocForMethod(
		?string $docComment,
		string $fileName,
		ClassReflection $classReflection,
		?string $declaringTraitName,
		string $methodName,
		array $positionalParameterNames
	): ResolvedPhpDocBlock
	{
		$phpDocBlock = PhpDocBlock::resolvePhpDocBlockForMethod(
			$docComment,
			$classReflection,
			$declaringTraitName,
			$methodName,
			$fileName,
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
			if (
				$parentPhpDocBlock->getClassReflection()->isBuiltin()
				&& $functionName !== null
				&& strtolower($functionName) === '__construct'
			) {
				continue;
			}
			$parents[] = $this->docBlockTreeToResolvedDocBlock(
				$parentPhpDocBlock,
				$parentPhpDocBlock->getTrait(),
				$functionName
			);
			$parentPhpDocBlocks[] = $parentPhpDocBlock;
		}

		$oneResolvedDockBlock = $this->docBlockToResolvedDocBlock($phpDocBlock, $traitName, $functionName);
		return $oneResolvedDockBlock->merge($parents, $parentPhpDocBlocks);
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
