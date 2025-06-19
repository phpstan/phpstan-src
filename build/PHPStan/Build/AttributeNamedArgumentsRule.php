<?php declare(strict_types = 1);

namespace PHPStan\Build;

use PhpParser\Node;
use PhpParser\Node\Attribute;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use function count;
use function sprintf;

/**
 * @implements Rule<Attribute>
 */
final class AttributeNamedArgumentsRule implements Rule
{

	public function __construct(private ReflectionProvider $reflectionProvider)
	{
	}

	public function getNodeType(): string
	{
		return Attribute::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$attributeName = $node->name->toString();
		if (!$this->reflectionProvider->hasClass($attributeName)) {
			return [];
		}

		$attributeReflection = $this->reflectionProvider->getClass($attributeName);
		if (!$attributeReflection->hasConstructor()) {
			return [];
		}
		$constructor = $attributeReflection->getConstructor();
		if (!$constructor->acceptsNamedArguments()->yes()) {
			return [];
		}

		$variants = $constructor->getVariants();
		if (count($variants) !== 1) {
			return [];
		}

		$parameters = $variants[0]->getParameters();

		foreach ($node->args as $arg) {
			if ($arg->name !== null) {
				break;
			}

			return [
				RuleErrorBuilder::message(sprintf('Attribute %s is not using named arguments.', $node->name->toString()))
					->identifier('phpstan.attributeWithoutNamedArguments')
					->nonIgnorable()
					->fixNode($node, static function (Node $node) use ($parameters) {
						$args = $node->args;
						foreach ($args as $i => $arg) {
							if ($arg->name !== null) {
								break;
							}

							$parameterName = $parameters[$i]->getName();
							if ($parameterName === '') {
								throw new ShouldNotHappenException();
							}

							$arg->name = new Node\Identifier($parameterName);
						}

						return $node;
					})
					->build(),
			];
		}

		return [];
	}

}
