<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generics;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\DependencyInjection\ValidatesStubFiles;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
use PHPStan\Turbo\ShadowedByTurboExtension;

/**
 * @implements Rule<InClassNode>
 */
#[RegisteredRule(level: 2)]
#[ValidatesStubFiles]
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../../turbo-ext/src/MethodTagTemplateTypeRule.cpp')]
final class MethodTagTemplateTypeRule implements Rule
{

	public function __construct(
		private MethodTagTemplateTypeCheck $check,
	)
	{
	}

	public function getNodeType(): string
	{
		return InClassNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$docComment = $node->getDocComment();
		if ($docComment === null) {
			return [];
		}

		return $this->check->check(
			$node->getClassReflection(),
			$scope,
			$node->getOriginalNode(),
			$docComment->getText(),
		);
	}

}
