<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Comment\Doc;
use PhpParser\Node;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\TrinaryLogic;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\MixedType;
use function count;

#[AutowiredService]
final class VarAnnotationProcessor
{

	public function __construct(
		private FileTypeMapper $fileTypeMapper,
	)
	{
	}

	/**
	 * @param array<int, string> $variableNames
	 */
	public function processVarAnnotation(MutatingScope $scope, array $variableNames, Node\Stmt $node, bool &$changed = false): MutatingScope
	{
		$function = $scope->getFunction();
		$varTags = [];
		foreach ($node->getComments() as $comment) {
			if (!$comment instanceof Doc) {
				continue;
			}

			$resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
				$scope->getFile(),
				$scope->isInClass() ? $scope->getClassReflection()->getName() : null,
				$scope->isInTrait() ? $scope->getTraitReflection()->getName() : null,
				$function !== null ? $function->getName() : null,
				$comment->getText(),
			);
			foreach ($resolvedPhpDoc->getVarTags() as $key => $varTag) {
				$varTags[$key] = $varTag;
			}
		}

		if (count($varTags) === 0) {
			return $scope;
		}

		foreach ($variableNames as $variableName) {
			if (!isset($varTags[$variableName])) {
				continue;
			}

			$variableType = $varTags[$variableName]->getType();
			$changed = true;
			$scope = $scope->assignVariable($variableName, $variableType, new MixedType(), TrinaryLogic::createYes());
		}

		if (count($variableNames) === 1 && count($varTags) === 1 && isset($varTags[0])) {
			$variableType = $varTags[0]->getType();
			$changed = true;
			$scope = $scope->assignVariable($variableNames[0], $variableType, new MixedType(), TrinaryLogic::createYes());
		}

		return $scope;
	}

}
