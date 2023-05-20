<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\MethodReturnStatementsNode;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
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
		$statementResult = $node->getStatementResult();
		$methodReflection = $scope->getFunction();
		if (!$methodReflection instanceof MethodReflection) {
			throw new ShouldNotHappenException();
		}
		if (!$scope->isInClass()) {
			throw new ShouldNotHappenException();
		}

		$docComment = $node->getDocComment();
		if ($docComment === null) {
			return [];
		}

		$classReflection = $scope->getClassReflection();
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
				->identifier('throws.unusedType')
				->build();
		}

		return $errors;
	}

}
