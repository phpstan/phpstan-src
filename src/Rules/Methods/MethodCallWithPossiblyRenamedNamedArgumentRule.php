<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PHPStan\Analyser\CollectedDataEmitter;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function array_key_exists;
use function sprintf;

/**
 * @implements Rule<CollectedDataNode>
 */
#[RegisteredRule(level: 0)]
final class MethodCallWithPossiblyRenamedNamedArgumentRule implements Rule
{

	public function getNodeType(): string
	{
		return CollectedDataNode::class;
	}

	public function processNode(Node $node, NodeCallbackInvoker&Scope&CollectedDataEmitter $scope): array
	{
		$calls = [];
		foreach ($node->get(NamedArgumentParameterMethodCallsCollector::class) as $file => $data) {
			foreach ($data as [$declaringClassName, $methodName, $parameterName, $callLine]) {
				$calls[$declaringClassName][$methodName][$parameterName][] = [$file, $callLine];
			}
		}

		$errors = [];
		foreach ($node->get(OverridingMethodRenamesParameterCollector::class) as $data) {
			foreach ($data as [$prototypeDeclaringClassName, $methodName, $methodDeclaringClassName, $prototypeParameterName, $methodParameterName]) {
				if (!array_key_exists($prototypeDeclaringClassName, $calls)) {
					continue;
				}

				$prototypeClassCalls = $calls[$prototypeDeclaringClassName];
				if (!array_key_exists($methodName, $prototypeClassCalls)) {
					continue;
				}

				$prototypeMethodCalls = $prototypeClassCalls[$methodName];
				if (!array_key_exists($prototypeParameterName, $prototypeMethodCalls)) {
					continue;
				}

				if (!array_key_exists($prototypeParameterName, $prototypeMethodCalls)) {
					continue;
				}

				$callsWithParameter = $prototypeMethodCalls[$prototypeParameterName];
				foreach ($callsWithParameter as [$file, $line]) {
					$errors[] = RuleErrorBuilder::message(sprintf(
						'Call to %s::%s() uses named argument for parameter $%s, but %s renames it to $%s.',
						$prototypeDeclaringClassName,
						$methodName,
						$prototypeParameterName,
						$methodDeclaringClassName,
						$methodParameterName,
					))->identifier('argument.parameterRenamedInSubtype')
						->file($file)
						->line($line)
						->build();
				}
			}
		}

		return $errors;
	}

}
