<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PHPStan\Analyser\CollectedDataEmitter;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function array_values;
use function sprintf;

/**
 * @implements Rule<CollectedDataNode>
 */
#[RegisteredRule(level: 0)]
final class MethodCallWithPossiblyRenamedNamedArgumentRule implements Rule
{

	public function __construct(private ReflectionProvider $reflectionProvider)
	{
	}

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
				foreach ($calls as $calledClassName => $calledClassCalls) {
					if (!isset($calledClassCalls[$methodName][$prototypeParameterName])) {
						continue;
					}

					// the called method can be declared in an ancestor of the prototype, with the same parameter name
					if (
						$calledClassName !== $prototypeDeclaringClassName
						&& (
							!$this->reflectionProvider->hasClass($prototypeDeclaringClassName)
							|| !$this->reflectionProvider->hasClass($calledClassName)
							|| !$this->reflectionProvider->getClass($prototypeDeclaringClassName)->isSubclassOfClass($this->reflectionProvider->getClass($calledClassName))
						)
					) {
						continue;
					}

					foreach ($calledClassCalls[$methodName][$prototypeParameterName] as [$file, $line]) {
						$message = sprintf(
							'Call to %s::%s() uses named argument for parameter $%s, but %s renames it to $%s.',
							$calledClassName,
							$methodName,
							$prototypeParameterName,
							$methodDeclaringClassName,
							$methodParameterName,
						);

						// one overriding method can rename the parameter of several prototypes matching the same call
						$errors[sprintf('%s:%d:%s', $file, $line, $message)] = RuleErrorBuilder::message($message)
							->identifier('argument.parameterRenamedInSubtype')
							->file($file)
							->line($line)
							->build();
					}
				}
			}
		}

		return array_values($errors);
	}

}
