<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\FileTypeMapper;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr\MethodCall>
 */
class FriendMethodCallRule implements \PHPStan\Rules\Rule
{

	private FileTypeMapper $fileTypeMapper;

	public function __construct(FileTypeMapper $fileTypeMapper)
	{
		$this->fileTypeMapper = $fileTypeMapper;
	}

	public function getNodeType(): string
	{
		return MethodCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->name instanceof Node\Identifier) {
			return [];
		}
		$methodName = $node->name->name;
		$callee = $scope->getType($node->var);
		if (!$callee->hasMethod($methodName)->yes()) {
			return [];
		}
		$method = $callee->getMethod($methodName, $scope);
		if (!$scope->canCallMethod($method)) {
			return [];
		}
		$docComment = $method->getDocComment();
		if ($docComment === null || $method->getDeclaringClass()->getFileName() === false) {
			return [];
		}
		$friends = $this->fileTypeMapper->getResolvedPhpDoc(
			$method->getDeclaringClass()->getFileName(),
			$method->getDeclaringClass()->getName(),
			null,
			$method->getName(),
			$docComment
		)->getFriends();
		if (\count($friends) === 0) {
			return [];
		}

		if (!$scope->isInClass()) {
			return [
				RuleErrorBuilder::message(sprintf(
					'You may not not call %s::%s() from the global scope as the method lists allowed callers through friends.',
					$method->getDeclaringClass()->getName(),
					$method->getName()
				))->build(),
			];
		}

		$callerClass = $scope->getClassReflection();
		$callerMethod = $scope->getFunctionName();
		foreach ($friends as $friend) {
			$type = $friend->getType();
			if ($callerClass->getName() !== $type->getClassName()) {
				continue;
			}
			if ($friend->getMethod() !== null && $friend->getMethod() !== $callerMethod) {
				continue;
			}
			return []; // caller is whitelisted
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'%s::%s() may not call %s::%s() as it is not listed as a friend.',
				$callerClass->getName(),
				$callerMethod,
				$method->getDeclaringClass()->getName(),
				$method->getName()
			))->build(),
		];
	}

}
