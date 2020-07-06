<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\FileTypeMapper;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Stmt\ClassMethod>
 */
class InvalidFriendTagTargetRule implements \PHPStan\Rules\Rule
{

	private FileTypeMapper $fileTypeMapper;

	private ReflectionProvider $reflectionProvider;

	public function __construct(FileTypeMapper $fileTypeMapper, ReflectionProvider $reflectionProvider)
	{
		$this->fileTypeMapper = $fileTypeMapper;
		$this->reflectionProvider = $reflectionProvider;
	}

	public function getNodeType(): string
	{
		return \PhpParser\Node\Stmt\ClassMethod::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$docComment = $node->getDocComment();
		if ($docComment === null) {
			return [];
		}
		$resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
			$scope->getFile(),
			$scope->isInClass() ? $scope->getClassReflection()->getName() : null,
			$scope->isInTrait() ? $scope->getTraitReflection()->getName() : null,
			$node->name->name,
			$docComment->getText()
		);
		$errors = [];
		foreach ($resolvedPhpDoc->getFriends() as $friendTag) {
			$className = $friendTag->getType()->getClassName();
			if (!$this->reflectionProvider->hasClass($className)) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'Class %s specified by a @friend tag does not exist.',
					$className
				))->build();
				continue;
			}
			$reflection = $this->reflectionProvider->getClass($className);
			$methodName = $friendTag->getMethod();
			if ($methodName === null || $reflection->hasMethod($methodName)) {
				continue;
			}
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Method %s::%s() specified by a @friend tag does not exist.',
				$className,
				$methodName
			))->build();
		}
		return $errors;
	}

}
