<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PhpParser\Node\Stmt\TraitUseAdaptation\Precedence;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\InClassNode;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function array_key_exists;
use function array_keys;
use function count;
use function reset;
use function sprintf;
use function strtolower;

/**
 * @implements Rule<InClassNode>
 */
#[RegisteredRule(level: 0)]
final class ConflictingTraitMethodsRule implements Rule
{

	public function __construct(private ReflectionProvider $reflectionProvider)
	{
	}

	public function getNodeType(): string
	{
		return InClassNode::class;
	}

	/**
	 * @return list<IdentifierRuleError>
	 */
	public function processNode(Node $node, Scope $scope): array
	{
		$classReflection = $node->getClassReflection();
		$classLike = $node->getOriginalNode();
		$traitUses = $classLike->getTraitUses();

		if ($traitUses === []) {
			return [];
		}

		// Collect methods defined directly on the class (not from traits)
		$classOwnMethods = [];
		foreach ($classLike->getMethods() as $method) {
			$classOwnMethods[strtolower($method->name->name)] = true;
		}

		// Collect all insteadof adaptations
		// Key: "traitName::methodName" that is being overridden
		$insteadofResolutions = [];
		foreach ($traitUses as $traitUse) {
			foreach ($traitUse->adaptations as $adaptation) {
				if (!$adaptation instanceof Precedence) {
					continue;
				}
				$methodName = strtolower($adaptation->method->name);
				foreach ($adaptation->insteadof as $insteadofTrait) {
					$insteadofResolutions[strtolower((string) $insteadofTrait) . '::' . $methodName] = true;
				}
			}
		}

		// Collect methods from each trait
		// Map: lowercased method name => [traitName => true]
		$methodTraitMap = [];
		foreach ($traitUses as $traitUse) {
			foreach ($traitUse->traits as $traitName) {
				$traitNameStr = (string) $traitName;
				if (!$this->reflectionProvider->hasClass($traitNameStr)) {
					continue;
				}
				$traitReflection = $this->reflectionProvider->getClass($traitNameStr);
				if (!$traitReflection->isTrait()) {
					continue;
				}

				foreach ($traitReflection->getNativeReflection()->getMethods() as $method) {
					$lowerMethodName = strtolower($method->getName());
					$methodTraitMap[$lowerMethodName][$traitReflection->getName()] = [
						'name' => $method->getName(),
						'abstract' => $method->isAbstract(),
					];
				}
			}
		}

		$errors = [];
		foreach ($methodTraitMap as $lowerMethodName => $traits) {
			if (count($traits) <= 1) {
				continue;
			}

			// If the class defines the method itself, no conflict
			if (array_key_exists($lowerMethodName, $classOwnMethods)) {
				continue;
			}

			// Filter out abstract methods - PHP allows abstract + concrete without conflict
			$concreteTraits = [];
			foreach ($traits as $traitName => $methodInfo) {
				if ($methodInfo['abstract']) {
					continue;
				}

				$concreteTraits[$traitName] = $methodInfo;
			}

			if (count($concreteTraits) <= 1) {
				continue;
			}

			// Check which traits still have unresolved conflicts
			$unresolvedTraits = [];
			foreach ($concreteTraits as $traitName => $methodInfo) {
				$key = strtolower($traitName) . '::' . $lowerMethodName;
				if (array_key_exists($key, $insteadofResolutions)) {
					continue;
				}

				$unresolvedTraits[$traitName] = $methodInfo['name'];
			}

			if (count($unresolvedTraits) <= 1) {
				continue;
			}

			$traitNames = array_keys($unresolvedTraits);
			$methodName = reset($unresolvedTraits);

			$errors[] = RuleErrorBuilder::message(sprintf(
				'Trait method %s::%s() has not been applied as %s::%s(), because of collision with %s::%s().',
				$traitNames[1],
				$methodName,
				$classReflection->getDisplayName(),
				$methodName,
				$traitNames[0],
				$methodName,
			))
				->identifier('class.traitMethodCollision')
				->nonIgnorable()
				->build();
		}

		return $errors;
	}

}
