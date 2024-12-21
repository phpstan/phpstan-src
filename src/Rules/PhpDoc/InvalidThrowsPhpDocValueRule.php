<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PhpParser\Node;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InPropertyHookNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use Throwable;
use function sprintf;

/**
 * @implements Rule<NodeAbstract>
 */
final class InvalidThrowsPhpDocValueRule implements Rule
{

	public function __construct(private FileTypeMapper $fileTypeMapper)
	{
	}

	public function getNodeType(): string
	{
		return NodeAbstract::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($node instanceof Node\Stmt) {
			if ($node instanceof Node\Stmt\Function_ || $node instanceof Node\Stmt\ClassMethod) {
				return []; // is handled by virtual nodes
			}
		} elseif (!$node instanceof InPropertyHookNode) {
			return [];
		}

		$docComment = $node->getDocComment();
		if ($docComment === null) {
			return [];
		}

		$functionName = null;
		if ($scope->getFunction() !== null) {
			$functionName = $scope->getFunction()->getName();
		}

		$resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
			$scope->getFile(),
			$scope->isInClass() ? $scope->getClassReflection()->getName() : null,
			$scope->isInTrait() ? $scope->getTraitReflection()->getName() : null,
			$functionName,
			$docComment->getText(),
		);

		if ($resolvedPhpDoc->getThrowsTag() === null) {
			return [];
		}

		$phpDocThrowsType = $resolvedPhpDoc->getThrowsTag()->getType();
		if ($phpDocThrowsType->isVoid()->yes()) {
			return [];
		}

		if ($this->isThrowsValid($phpDocThrowsType)) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'PHPDoc tag @throws with type %s is not subtype of Throwable',
				$phpDocThrowsType->describe(VerbosityLevel::typeOnly()),
			))->identifier('throws.notThrowable')->build(),
		];
	}

	private function isThrowsValid(Type $phpDocThrowsType): bool
	{
		$throwType = new ObjectType(Throwable::class);
		if ($phpDocThrowsType instanceof UnionType) {
			foreach ($phpDocThrowsType->getTypes() as $innerType) {
				if (!$this->isThrowsValid($innerType)) {
					return false;
				}
			}

			return true;
		}

		$toIntersectWith = [];
		foreach ($phpDocThrowsType->getObjectClassReflections() as $classReflection) {
			if (!$classReflection->isInterface()) {
				continue;
			}
			foreach ($classReflection->getRequireExtendsTags() as $requireExtendsTag) {
				$toIntersectWith[] = $requireExtendsTag->getType();
			}
		}

		return $throwType->isSuperTypeOf(
			TypeCombinator::intersect($phpDocThrowsType, ...$toIntersectWith),
		)->yes();
	}

}
