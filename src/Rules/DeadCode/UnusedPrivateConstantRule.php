<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClassConstantsNode;
use PHPStan\Rules\Constants\AlwaysUsedClassConstantsExtensionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use function sprintf;

/**
 * @implements Rule<ClassConstantsNode>
 */
class UnusedPrivateConstantRule implements Rule
{

	public function __construct(private AlwaysUsedClassConstantsExtensionProvider $extensionProvider)
	{
	}

	public function getNodeType(): string
	{
		return ClassConstantsNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->getClass() instanceof Node\Stmt\Class_ && !$node->getClass() instanceof Node\Stmt\Enum_) {
			return [];
		}
		if (!$scope->isInClass()) {
			throw new ShouldNotHappenException();
		}

		$classReflection = $scope->getClassReflection();

		$constants = [];
		foreach ($node->getConstants() as $constant) {
			if (!$constant->isPrivate()) {
				continue;
			}

			foreach ($constant->consts as $const) {
				$constantName = $const->name->toString();

				$constantReflection = $classReflection->getConstant($constantName);
				foreach ($this->extensionProvider->getExtensions() as $extension) {
					if ($extension->isAlwaysUsed($constantReflection)) {
						continue 2;
					}
				}

				$constants[$constantName] = $const;
			}
		}

		foreach ($node->getFetches() as $fetch) {
			$fetchNode = $fetch->getNode();
			if (!$fetchNode->name instanceof Node\Identifier) {
				continue;
			}

			if ($fetchNode->class instanceof Node\Name) {
				$fetchScope = $fetch->getScope();
				$fetchedOnClass = $fetchScope->resolveName($fetchNode->class);
				if ($fetchedOnClass !== $classReflection->getName()) {
					continue;
				}
			} else {
				$classExprType = $fetch->getScope()->getType($fetchNode->class);
				$isDifferentClass = TrinaryLogic::createNo()->lazyOr(
					$classExprType->getObjectClassNames(),
					static fn (string $objectClassName) => TrinaryLogic::createFromBoolean($objectClassName !== $classReflection->getName()),
				);
				if ($isDifferentClass->yes()) {
					continue;
				}
			}

			unset($constants[$fetchNode->name->toString()]);
		}

		$errors = [];
		foreach ($constants as $constantName => $constantNode) {
			$errors[] = RuleErrorBuilder::message(sprintf('Constant %s::%s is unused.', $classReflection->getDisplayName(), $constantName))
				->line($constantNode->getLine())
				->tip(sprintf('See: %s', 'https://phpstan.org/developing-extensions/always-used-class-constants'))
				->build();
		}

		return $errors;
	}

}
