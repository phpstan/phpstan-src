<?php declare(strict_types = 1);

namespace PHPStan\Rules\Pure;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\FileTypeMapper;
use function array_keys;
use function sprintf;
use function trim;

/**
 * Reports a docblock that marks a function or method pure or impure and also
 * makes its purity conditional with a @pure-unless-* tag. Only the docblock
 * itself counts, not tags inherited from a parent or set on the class.
 *
 * @implements Rule<Node\FunctionLike>
 */
final class ConflictingPurityTagsRule implements Rule
{

	public function __construct(private FileTypeMapper $fileTypeMapper)
	{
	}

	public function getNodeType(): string
	{
		return Node\FunctionLike::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($node instanceof Node\Stmt\ClassMethod) {
			if (!$scope->isInClass()) {
				return [];
			}
			$functionName = $node->name->name;
			$description = sprintf('Method %s::%s()', $scope->getClassReflection()->getDisplayName(), $functionName);
			$identifier = 'pureMethod.conflictingPurityTags';
		} elseif ($node instanceof Node\Stmt\Function_) {
			$functionName = trim($scope->getNamespace() . '\\' . $node->name->name, '\\');
			$description = sprintf('Function %s()', $functionName);
			$identifier = 'pureFunction.conflictingPurityTags';
		} else {
			return [];
		}

		$docComment = $node->getDocComment();
		if ($docComment === null) {
			return [];
		}

		$resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
			$scope->getFile(),
			$scope->isInClass() ? $scope->getClassReflection()->getName() : null,
			$scope->isInTrait() ? $scope->getTraitReflection()->getName() : null,
			$functionName,
			$docComment->getText(),
		);

		$isPure = $resolvedPhpDoc->isPure();
		if ($isPure === null) {
			return [];
		}

		$errors = [];
		foreach ([
			'@pure-unless-callable-is-impure' => $resolvedPhpDoc->getParamsPureUnlessCallableIsImpure(),
			'@pure-unless-parameter-passed' => $resolvedPhpDoc->getParamsPureUnlessParameterPassed(),
		] as $tagName => $parameters) {
			foreach (array_keys($parameters) as $parameterName) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'%s is marked as %s, which conflicts with %s for parameter $%s.',
					$description,
					$isPure ? 'pure' : 'impure',
					$tagName,
					$parameterName,
				))->identifier($identifier)->build();
			}
		}

		return $errors;
	}

}
