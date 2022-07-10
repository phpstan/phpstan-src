<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\Php\PhpMethodFromParserNodeReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;

/** @implements Rule<InClassMethodNode> */
class FinalPrivateMethodRule implements Rule
{

	public function __construct(
		private PhpVersion $phpVersion,
	)
	{
	}

	public function getNodeType(): string
	{
		return InClassMethodNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$method = $scope->getFunction();
		if (!$method instanceof PhpMethodFromParserNodeReflection) {
			return [];
		}

		if (!$this->phpVersion->producesWarningForFinalPrivateMethods()) {
			return [];
		}

		if ($method->getName() === '__construct') {
			return [];
		}

		if (!$method->isFinal()->yes() || !$method->isPrivate()) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'Private method %s::%s() cannot be final as it is never overridden by other classes.',
				$method->getDeclaringClass()->getDisplayName(),
				$method->getName(),
			))->build(),
		];
	}

}
