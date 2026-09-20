<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PhpParser\Node;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\DependencyInjection\ValidatesStubFiles;
use PHPStan\Node\InPropertyHookNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ConditionalType;
use PHPStan\Type\ConditionalTypeForParameter;
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
#[RegisteredRule(level: 2)]
#[ValidatesStubFiles]
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
			if ($node instanceof Node\Stmt\ClassLike || $node instanceof Node\Stmt\Function_ || $node instanceof Node\Stmt\ClassMethod) {
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
		// `void` standalone means "does not throw" and is a valid @throws type (it is
		// likewise allowed as a branch of a conditional throws type). As a union member
		// such as Throwable|void it is rejected in the UnionType handling below.
		if ($phpDocThrowsType->isVoid()->yes()) {
			return true;
		}

		// Conditional @throws types like ($x is 0 ? Exception : void) are valid as long
		// as both branches are valid throws types (a Throwable subtype or void).
		if ($phpDocThrowsType instanceof ConditionalType) {
			return $this->isThrowsValid($phpDocThrowsType->getIf())
				&& $this->isThrowsValid($phpDocThrowsType->getElse());
		}

		if ($phpDocThrowsType instanceof ConditionalTypeForParameter) {
			return $this->isThrowsValid($phpDocThrowsType->getIf())
				&& $this->isThrowsValid($phpDocThrowsType->getElse());
		}

		$throwType = new ObjectType(Throwable::class);
		if ($phpDocThrowsType instanceof UnionType) {
			foreach ($phpDocThrowsType->getTypes() as $innerType) {
				if ($innerType->isVoid()->yes() || !$this->isThrowsValid($innerType)) {
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
