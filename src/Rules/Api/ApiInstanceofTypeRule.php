<?php declare(strict_types = 1);

namespace PHPStan\Rules\Api;

use olvlvl\ComposerAttributeCollector\Attributes;
use PhpParser\Node;
use PhpParser\Node\Expr\Instanceof_;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Parser\TypeTraverserInstanceofVisitor;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Accessory\AccessoryType;
use PHPStan\Type\InstanceofDeprecated;
use PHPStan\Type\TypeTraverserCallable;
use function array_key_exists;
use function sprintf;
use function strtolower;

/**
 * @implements Rule<Instanceof_>
 */
#[RegisteredRule(level: 0)]
final class ApiInstanceofTypeRule implements Rule
{

	/**
	 * Compiled once from the #[InstanceofDeprecated] attributes.
	 *
	 * @var array<lowercase-string, string|null>
	 */
	private readonly array $lowerMap;

	public function __construct(
		private ReflectionProvider $reflectionProvider,
	)
	{
		require_once __DIR__ . '/../../../vendor/attributes.php';

		$lowerMap = [];
		foreach (Attributes::findTargetClasses(InstanceofDeprecated::class) as $class) {
			$lowerMap[strtolower($class->name)] = $class->attribute->insteadUse;
		}
		$this->lowerMap = $lowerMap;
	}

	public function getNodeType(): string
	{
		return Instanceof_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->class instanceof Node\Name) {
			return [];
		}

		if ($node->getAttribute(TypeTraverserInstanceofVisitor::ATTRIBUTE_NAME, false) === true) {
			return [];
		}

		$className = $scope->resolveName($node->class);
		$lowerClassName = strtolower($className);
		if (!array_key_exists($lowerClassName, $this->lowerMap)) {
			return [];
		}

		if ($scope->isInClass()) {
			$classReflection = $scope->getClassReflection();

			if ($classReflection->implementsInterface(TypeTraverserCallable::class)) {
				return [];
			}
		}

		if ($this->reflectionProvider->hasClass($className)) {
			$classReflection = $this->reflectionProvider->getClass($className);
			if ($classReflection->is(AccessoryType::class)) {
				if ($className === $classReflection->getName()) {
					return [];
				}
			}
		}

		$tip = 'Learn more: <fg=cyan>https://phpstan.org/blog/why-is-instanceof-type-wrong-and-getting-deprecated</>';
		if ($this->lowerMap[$lowerClassName] === null) {
			return [
				RuleErrorBuilder::message(sprintf(
					'Doing instanceof %s is error-prone and deprecated.',
					$className,
				))->identifier('phpstanApi.instanceofType')->tip($tip)->build(),
			];
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'Doing instanceof %s is error-prone and deprecated. Use %s instead.',
				$className,
				$this->lowerMap[$lowerClassName],
			))->identifier('phpstanApi.instanceofType')->tip($tip)->build(),
		];
	}

}
