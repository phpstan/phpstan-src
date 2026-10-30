<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PhpParser\Comment\Doc;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\RedundantVarTagNode;
use PHPStan\PhpDoc\PhpDocNodeResolver;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/**
 * @implements Rule<RedundantVarTagNode>
 */
#[RegisteredRule(level: 2)]
final class RedundantVarTagRule implements Rule
{

	public function __construct(
		private FileTypeMapper $fileTypeMapper,
	)
	{
	}

	public function getNodeType(): string
	{
		return RedundantVarTagNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		// a trait is analysed once per class using it, and the tag may be
		// redundant in only some of them
		if ($scope->isInTrait()) {
			return [];
		}

		// removing the tag must not let another one take effect: a tag for the
		// same variable it overrides, or named tags a variable-less one hides
		$tagCounts = $this->countVarTags($node->getStmt(), $scope);
		$variableName = $node->getVariableName();
		$type = $node->getVarTag()->getType();
		if ($variableName !== null) {
			if (($tagCounts['$' . $variableName] ?? 0) !== 1 || isset($tagCounts[''])) {
				return [];
			}

			return [
				RuleErrorBuilder::message(sprintf(
					'PHPDoc tag @var for variable $%s with type %s is redundant, the variable already has this type.',
					$variableName,
					$type->describe(VerbosityLevel::getRecommendedLevelByType($type)),
				))->identifier('varTag.redundant')->build(),
			];
		}

		if ($tagCounts !== ['' => 1]) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'PHPDoc tag @var with type %s is redundant, the expression already has this type.',
				$type->describe(VerbosityLevel::getRecommendedLevelByType($type)),
			))->identifier('varTag.redundant')->build(),
		];
	}

	/**
	 * How many @var tags in the PHPDocs above the statement name each variable,
	 * by its name with the $, or '' for the tags without one.
	 *
	 * @return array<string, int>
	 */
	private function countVarTags(Node\Stmt $stmt, Scope $scope): array
	{
		$function = $scope->getFunction();
		$counts = [];
		foreach ($stmt->getComments() as $comment) {
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
			foreach ($resolvedPhpDoc->getPhpDocNodes() as $phpDocNode) {
				foreach (PhpDocNodeResolver::VAR_TAG_NAMES as $tagName) {
					foreach ($phpDocNode->getVarTagValues($tagName) as $tagValue) {
						$counts[$tagValue->variableName] = ($counts[$tagValue->variableName] ?? 0) + 1;
					}
				}
			}
		}

		return $counts;
	}

}
