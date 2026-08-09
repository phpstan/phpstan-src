<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\BetterReflection\Reflector\Exception\IdentifierNotFound;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\InClassNode;
use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;

/**
 * @implements Rule<InClassNode>
 */
#[RegisteredRule(level: 0)]
final class MissingPropertyHookImplementationRule implements Rule
{

	public function __construct(private PhpVersion $phpVersion)
	{
	}

	public function getNodeType(): string
	{
		return InClassNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$this->phpVersion->supportsPropertyHooks()) {
			return [];
		}

		$classReflection = $node->getClassReflection();
		if ($classReflection->isInterface()) {
			return [];
		}
		if ($classReflection->isAbstract()) {
			return [];
		}

		try {
			$nativeProperties = $classReflection->getNativeReflection()->getProperties();
		} catch (IdentifierNotFound) {
			return [];
		}

		$messages = [];
		foreach ($nativeProperties as $property) {
			if (!$property->isAbstract()) {
				continue;
			}

			$declaringClass = $property->getBetterReflection()->getDeclaringClass();
			$declaringClassType = 'class';
			if ($declaringClass->isInterface()) {
				$declaringClassType = 'interface';
			} elseif ($declaringClass->isTrait()) {
				$declaringClassType = 'trait';
			}

			$messages[] = RuleErrorBuilder::message(sprintf(
				'Non-abstract class %s contains abstract property $%s from %s %s.',
				$classReflection->getDisplayName(),
				$property->getName(),
				$declaringClassType,
				$declaringClass->getName(),
			))
				->nonIgnorable()
				->identifier('property.abstract')
				->build();
		}

		return $messages;
	}

}
