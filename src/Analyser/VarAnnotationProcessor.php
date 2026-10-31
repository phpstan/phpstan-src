<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Comment\Doc;
use PhpParser\Node;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\TrinaryLogic;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\MixedType;
use function count;

#[AutowiredService]
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../turbo-ext/src/VarAnnotationProcessor.cpp')]
final class VarAnnotationProcessor
{

	public function __construct(
		private FileTypeMapper $fileTypeMapper,
		private RedundantVarTagDetector $redundantVarTagDetector,
		#[AutowiredParameter(ref: '%reportRedundantVarTag%')]
		private bool $reportRedundantVarTag,
	)
	{
	}

	/**
	 * @param array<int, string> $variableNames
	 */
	public function processVarAnnotation(MutatingScope $scope, array $variableNames, Node\Stmt $node, bool &$changed = false): MutatingScope
	{
		return $this->applyVarTags($scope, $variableNames, $node, $changed, false);
	}

	/**
	 * processVarAnnotation() for the variable a foreach iterates, applied again on
	 * entering the loop body, where the loop may have narrowed it to a non-empty array.
	 */
	public function processForeachIterateeVarAnnotation(MutatingScope $scope, string $variableName, Node\Stmt\Foreach_ $node): MutatingScope
	{
		$changed = false;

		return $this->applyVarTags($scope, [$variableName], $node, $changed, true);
	}

	/**
	 * @param array<int, string> $variableNames
	 */
	private function applyVarTags(MutatingScope $scope, array $variableNames, Node\Stmt $node, bool &$changed, bool $narrowedByForeach): MutatingScope
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
			if ($this->reportRedundantVarTag) {
				$this->redundantVarTagDetector->recordVariable($node, $variableName, $varTags[$variableName], $scope, $variableName, true, $narrowedByForeach);
			}
			$scope = $scope->assignVariable($variableName, $variableType, new MixedType(), TrinaryLogic::createYes());
		}

		if (count($variableNames) === 1 && count($varTags) === 1 && isset($varTags[0])) {
			$variableType = $varTags[0]->getType();
			$changed = true;
			if ($this->reportRedundantVarTag) {
				$this->redundantVarTagDetector->recordVariable($node, 0, $varTags[0], $scope, $variableNames[0], true, $narrowedByForeach);
			}
			$scope = $scope->assignVariable($variableNames[0], $variableType, new MixedType(), TrinaryLogic::createYes());
		}

		return $scope;
	}

}
