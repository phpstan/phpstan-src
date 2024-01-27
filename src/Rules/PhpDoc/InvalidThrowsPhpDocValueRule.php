<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use Throwable;
use function sprintf;

/**
 * @implements Rule<Node\Stmt>
 */
class InvalidThrowsPhpDocValueRule implements Rule
{

	public function __construct(private FileTypeMapper $fileTypeMapper)
	{
	}

	public function getNodeType(): string
	{
		return Node\Stmt::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($node instanceof Node\Stmt\Function_ || $node instanceof Node\Stmt\ClassMethod) {
			return []; // is handled by virtual nodes
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

		if ($this->isThrowValid($phpDocThrowsType)) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'PHPDoc tag @throws with type %s is not subtype of Throwable',
				$phpDocThrowsType->describe(VerbosityLevel::typeOnly()),
			))->build(),
		];
	}

	private function isThrowValid(Type $phpDocThrowsType): bool
	{
		$throwType = new ObjectType(Throwable::class);
		if ($throwType->isSuperTypeOf($phpDocThrowsType)->yes()) {
			return true;
		}

		foreach ($phpDocThrowsType->getObjectClassReflections() as $classReflection) {
			foreach ($classReflection->getRequireExtendsTags() as $requireExtendsTag) {
				if ($throwType->isSuperTypeOf($requireExtendsTag->getType())->yes()) {
					return true;
				}
			}

			foreach ($classReflection->getRequireImplementsTags() as $requireImplementsTag) {
				if ($throwType->isSuperTypeOf($requireImplementsTag->getType())->yes()) {
					return true;
				}
			}
		}

		return false;
	}

}
