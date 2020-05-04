<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PhpParser\Node;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use PHPStan\Type\VoidType;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\FunctionLike>
 */
class InvalidThrowsPhpDocValueRule implements \PHPStan\Rules\Rule
{

	/** @var FileTypeMapper */
	private $fileTypeMapper;

	/** @var NodeScopeResolver */
	private $nodeScopeResolver;

	public function __construct(
		FileTypeMapper $fileTypeMapper,
		NodeScopeResolver $nodeScopeResolver
	)
	{
		$this->fileTypeMapper = $fileTypeMapper;
		$this->nodeScopeResolver = $nodeScopeResolver;
	}

	public function getNodeType(): string
	{
		return \PhpParser\Node\FunctionLike::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$throwsType = $this->getThrowsType($node, $scope);
		return $this->check($throwsType);
	}

	private function getThrowsType(Node $node, Scope $scope): ?Type
	{
		return $scope->isInClass() === true
			? $this->getMethodThrowsType($node, $scope)
			: $this->getFunctionThrowsType($node, $scope);
	}

	private function getFunctionThrowsType(Node $node, Scope $scope): ?Type
	{
		$docComment = $node->getDocComment();
		if ($docComment === null) {
			return null;
		}

		$functionName = null;
		if ($node instanceof Node\Stmt\Function_) {
			$functionName = trim($scope->getNamespace() . '\\' . $node->name->name, '\\');
		}

		$resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
			$scope->getFile(),
			$scope->isInClass() ? $scope->getClassReflection()->getName() : null,
			$scope->isInTrait() ? $scope->getTraitReflection()->getName() : null,
			$functionName,
			$docComment->getText()
		);

		$throwsTag = $resolvedPhpDoc->getThrowsTag();
		return $throwsTag ? $throwsTag->getType() : null;
	}

	private function getMethodThrowsType(Node $node, Scope $scope): ?Type
	{
		if (!($node instanceof Node\Stmt\ClassMethod)) {
			throw new ShouldNotHappenException();
		}

		[$templateTypeMap, $phpDocParameterTypes, $phpDocReturnType, $phpDocThrowType] = $this->nodeScopeResolver->getPhpDocs($scope, $node);
		return $phpDocThrowType;
	}

	/**
	 * @param Type|null $phpDocThrowsType
	 * @return array<int, RuleErrorBuilder> errors
	 */
	private function check(?Type $phpDocThrowsType): array
	{
		if ($phpDocThrowsType === null) {
			return [];
		}

		if ((new VoidType())->isSuperTypeOf($phpDocThrowsType)->yes()) {
			return [];
		}

		$isThrowsSuperType = (new ObjectType(\Throwable::class))->isSuperTypeOf($phpDocThrowsType);
		if ($isThrowsSuperType->yes()) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'PHPDoc tag @throws with type %s is not subtype of Throwable',
				$phpDocThrowsType->describe(VerbosityLevel::typeOnly())
			))->build(),
		];
	}

}
