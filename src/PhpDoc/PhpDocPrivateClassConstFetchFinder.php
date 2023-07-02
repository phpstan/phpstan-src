<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PHPStan\Analyser\Scope;
use PHPStan\Node\Constant\ClassConstantFetch;
use PHPStan\PhpDocParser\Ast\NodeTraverser;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\FileTypeMapper;

class PhpDocPrivateClassConstFetchFinder
{

	public function __construct(
		private FileTypeMapper $fileTypeMapper,
	)
	{
	}

	/**
	 * @return list<ClassConstantFetch>
	 */
	public function findClassConstFetches(string $phpDoc, Scope $scope): array
	{
		if (!$scope->isInClass()) {
			throw new ShouldNotHappenException();
		}

		$resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
			$scope->getFile(),
			$scope->isInClass() ? $scope->getClassReflection()->getName() : null,
			$scope->isInTrait() ? $scope->getTraitReflection()->getName() : null,
			null,
			$phpDoc,
		);

		$visitor = new PhpDocPrivateClassConstFetchFinderVisitor($scope->getClassReflection(), $scope);

		(new NodeTraverser([$visitor]))->traverse($resolvedPhpDoc->getPhpDocNodes());

		return $visitor->classConstantFetches;
	}

}
