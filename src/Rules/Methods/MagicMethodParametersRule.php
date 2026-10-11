<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function array_key_exists;
use function count;
use function sprintf;
use function strtolower;

/** @implements Rule<InClassMethodNode> */
#[RegisteredRule(level: 0)]
final class MagicMethodParametersRule implements Rule
{

	private const PARAMETERS_COUNT = [
		'__destruct' => 0,
		'__clone' => 0,
		'__get' => 1,
		'__set' => 2,
		'__unset' => 1,
		'__isset' => 1,
		'__call' => 2,
		'__callstatic' => 2,
		'__tostring' => 0,
		'__debuginfo' => 0,
		'__serialize' => 0,
		'__unserialize' => 1,
		'__set_state' => 1,
		'__sleep' => 0,
		'__wakeup' => 0,
	];

	public function getNodeType(): string
	{
		return InClassMethodNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$methodName = $node->getOriginalNode()->name->toString();
		$lowercasedMethodName = strtolower($methodName);
		if (!array_key_exists($lowercasedMethodName, self::PARAMETERS_COUNT)) {
			return [];
		}

		$expectedCount = self::PARAMETERS_COUNT[$lowercasedMethodName];
		$params = [];
		foreach ($node->getOriginalNode()->params as $param) {
			if ($param->variadic) {
				continue;
			}

			$params[] = $param;
		}

		$className = $node->getClassReflection()->getDisplayName();
		if (count($params) !== $expectedCount) {
			if ($expectedCount === 0) {
				$message = sprintf('Method %s::%s() cannot take arguments.', $className, $methodName);
			} elseif ($expectedCount === 1) {
				$message = sprintf('Method %s::%s() must take exactly 1 argument.', $className, $methodName);
			} else {
				$message = sprintf('Method %s::%s() must take exactly %d arguments.', $className, $methodName, $expectedCount);
			}

			return [
				RuleErrorBuilder::message($message)
					->identifier('method.magicParameters')
					->nonIgnorable()
					->build(),
			];
		}

		foreach ($params as $param) {
			if (!$param->byRef) {
				continue;
			}

			return [
				RuleErrorBuilder::message(sprintf('Method %s::%s() cannot take arguments by reference.', $className, $methodName))
					->identifier('method.magicParameterByRef')
					->nonIgnorable()
					->build(),
			];
		}

		return [];
	}

}
