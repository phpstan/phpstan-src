<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\MethodReturnStatementsNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\FileTypeMapper;
use function sprintf;

/**
 * @implements Rule<MethodReturnStatementsNode>
 */
class TooWideMethodThrowTypeRule implements Rule
{

	public function __construct(private FileTypeMapper $fileTypeMapper, private TooWideThrowTypeCheck $check)
	{
	}

	public function getNodeType(): string
	{
		return MethodReturnStatementsNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$docComment = $node->getDocComment();
		if ($docComment === null) {
			return [];
		}

		$statementResult = $node->getStatementResult();
		$methodReflection = $node->getMethodReflection();
		$classReflection = $node->getClassReflection();
		$resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
			$scope->getFile(),
			$classReflection->getName(),
			$scope->isInTrait() ? $scope->getTraitReflection()->getName() : null,
			$methodReflection->getName(),
			$docComment->getText(),
		);

		if ($resolvedPhpDoc->getThrowsTag() === null) {
			return [];
		}

		$throwType = $resolvedPhpDoc->getThrowsTag()->getType();

		$errors = [];
		foreach ($this->check->check($throwType, $statementResult->getThrowPoints()) as $throwClass) {
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Method %s::%s() has %s in PHPDoc @throws tag but it\'s not thrown.',
				$methodReflection->getDeclaringClass()->getDisplayName(),
				$methodReflection->getName(),
				$throwClass,
			))
				->identifier('exceptions.tooWideThrowType')
				->metadata([
					'exceptionName' => $throwClass,
					'statementDepth' => $node->getAttribute('statementDepth'),
					'statementOrder' => $node->getAttribute('statementOrder'),
				])
				->build();
		}

		return $errors;
	}

}
