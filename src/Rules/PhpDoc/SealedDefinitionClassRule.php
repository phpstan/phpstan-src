<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\InClassNode;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\ClassNameNodePair;
use PHPStan\Rules\ClassNameUsageLocation;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\VerbosityLevel;
use function array_merge;
use function count;
use function sprintf;

/**
 * @implements Rule<InClassNode>
 */
#[RegisteredRule(level: 2)]
final class SealedDefinitionClassRule implements Rule
{

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private ClassNameCheck $classCheck,
		#[AutowiredParameter]
		private bool $checkClassCaseSensitivity,
		#[AutowiredParameter(ref: '%tips.discoveringSymbols%')]
		private bool $discoveringSymbolsTip,
	)
	{
	}

	public function getNodeType(): string
	{
		return InClassNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$classReflection = $node->getClassReflection();
		$sealedTags = $classReflection->getSealedTags();

		if (count($sealedTags) === 0) {
			return [];
		}

		if ($classReflection->isEnum()) {
			return [
				RuleErrorBuilder::message('PHPDoc tag @phpstan-sealed is only valid on class or interface.')
					->identifier('sealed.onEnum')
					->build(),
			];
		}

		$errors = [];
		foreach ($sealedTags as $sealedTag) {
			$type = $sealedTag->getType();
			$classNames = $type->getObjectClassNames();
			if (count($classNames) === 0) {
				$errors[] = RuleErrorBuilder::message(sprintf('PHPDoc tag @phpstan-sealed contains non-object type %s.', $type->describe(VerbosityLevel::typeOnly())))
					->identifier('sealed.nonObject')
					->build();
				continue;
			}

			foreach ($classNames as $class) {
				if (!$this->reflectionProvider->hasClass($class)) {
					$errorBuilder = RuleErrorBuilder::message(sprintf('PHPDoc tag @phpstan-sealed contains unknown class %s.', $class))
					->identifier('class.notFound');

					if ($this->discoveringSymbolsTip) {
						$errorBuilder->discoveringSymbolsTip();
					}

					$errors[] = $errorBuilder->build();
					continue;
				}

				$errors = array_merge(
					$errors,
					$this->classCheck->checkClassNames($scope, [
						new ClassNameNodePair($class, $node),
					], ClassNameUsageLocation::from(ClassNameUsageLocation::PHPDOC_TAG_SEALED), $this->checkClassCaseSensitivity),
				);
			}
		}

		return $errors;
	}

}
