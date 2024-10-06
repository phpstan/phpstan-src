<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClassPropertiesNode;
use PHPStan\Node\Property\PropertyRead;
use PHPStan\Rules\Properties\ReadWritePropertiesExtensionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\SubtractableType;
use function array_key_exists;
use function array_map;
use function count;
use function sprintf;
use function str_contains;

/**
 * @implements Rule<ClassPropertiesNode>
 */
final class UnusedPrivatePropertyRule implements Rule
{

	/**
	 * @param string[] $alwaysWrittenTags
	 * @param string[] $alwaysReadTags
	 */
	public function __construct(
		private ReadWritePropertiesExtensionProvider $extensionProvider,
		private array $alwaysWrittenTags,
		private array $alwaysReadTags,
		private bool $checkUninitializedProperties,
	)
	{
	}

	public function getNodeType(): string
	{
		return ClassPropertiesNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->getClass() instanceof Node\Stmt\Class_) {
			return [];
		}
		$classReflection = $node->getClassReflection();
		$classType = new ObjectType($classReflection->getName(), null, $classReflection);
		$properties = [];
		foreach ($node->getProperties() as $property) {
			if (!$property->isPrivate()) {
				continue;
			}
			if ($property->isDeclaredInTrait()) {
				continue;
			}

			$alwaysRead = false;
			$alwaysWritten = false;
			if ($property->getPhpDoc() !== null) {
				$text = $property->getPhpDoc();
				foreach ($this->alwaysReadTags as $tag) {
					if (!str_contains($text, $tag)) {
						continue;
					}

					$alwaysRead = true;
					break;
				}

				foreach ($this->alwaysWrittenTags as $tag) {
					if (!str_contains($text, $tag)) {
						continue;
					}

					$alwaysWritten = true;
					break;
				}
			}

			$propertyName = $property->getName();
			if (!$alwaysRead || !$alwaysWritten) {
				if (!$classReflection->hasNativeProperty($propertyName)) {
					continue;
				}

				$propertyReflection = $classReflection->getNativeProperty($propertyName);

				foreach ($this->extensionProvider->getExtensions() as $extension) {
					if ($alwaysRead && $alwaysWritten) {
						break;
					}
					if (!$alwaysRead && $extension->isAlwaysRead($propertyReflection, $propertyName)) {
						$alwaysRead = true;
					}
					if ($alwaysWritten || !$extension->isAlwaysWritten($propertyReflection, $propertyName)) {
						continue;
					}

					$alwaysWritten = true;
				}
			}

			$read = $alwaysRead;
			$written = $alwaysWritten || $property->getDefault() !== null;
			$properties[$propertyName] = [
				'read' => $read,
				'written' => $written,
				'node' => $property,
			];
		}

		foreach ($node->getPropertyUsages() as $usage) {
			$fetch = $usage->getFetch();
			if ($fetch->name instanceof Node\Identifier) {
				$propertyNames = [$fetch->name->toString()];
			} else {
				$propertyNameType = $usage->getScope()->getType($fetch->name);
				$strings = $propertyNameType->getConstantStrings();
				if (count($strings) === 0) {
					// handle subtractions of a dynamic property fetch
					foreach($properties as $propertyName => $data) {
						if (!(new ConstantStringType($propertyName))->isSuperTypeOf($propertyNameType)->no()) {
							unset($properties[$propertyName]);
						}
					}

					continue;
				}

				$propertyNames = array_map(static fn (ConstantStringType $type): string => $type->getValue(), $strings);
			}
			if ($fetch instanceof Node\Expr\PropertyFetch) {
				$fetchedOnType = $usage->getScope()->getType($fetch->var);
			} else {
				if ($fetch->class instanceof Node\Name) {
					$fetchedOnType = $usage->getScope()->resolveTypeByName($fetch->class);
				} else {
					$fetchedOnType = $usage->getScope()->getType($fetch->class);
				}
			}

			foreach ($propertyNames as $propertyName) {
				if (!array_key_exists($propertyName, $properties)) {
					continue;
				}
				$propertyReflection = $usage->getScope()->getPropertyReflection($fetchedOnType, $propertyName);
				if ($propertyReflection === null) {
					if (!$classType->isSuperTypeOf($fetchedOnType)->no()) {
						if ($usage instanceof PropertyRead) {
							$properties[$propertyName]['read'] = true;
						} else {
							$properties[$propertyName]['written'] = true;
						}
					}
					continue;
				}
				if ($propertyReflection->getDeclaringClass()->getName() !== $classReflection->getName()) {
					if (!$classType->isSuperTypeOf($fetchedOnType)->no()) {
						if ($usage instanceof PropertyRead) {
							$properties[$propertyName]['read'] = true;
						} else {
							$properties[$propertyName]['written'] = true;
						}
					}
					continue;
				}

				if ($usage instanceof PropertyRead) {
					$properties[$propertyName]['read'] = true;
				} else {
					$properties[$propertyName]['written'] = true;
				}
			}
		}

		[$uninitializedProperties] = $node->getUninitializedProperties($scope, []);

		$errors = [];
		foreach ($properties as $name => $data) {
			$propertyNode = $data['node'];
			if ($propertyNode->isStatic()) {
				$propertyName = sprintf('Static property %s::$%s', $classReflection->getDisplayName(), $name);
			} else {
				$propertyName = sprintf('Property %s::$%s', $classReflection->getDisplayName(), $name);
			}
			$tip = sprintf('See: %s', 'https://phpstan.org/developing-extensions/always-read-written-properties');
			if (!$data['read']) {
				if (!$data['written']) {
					$errors[] = RuleErrorBuilder::message(sprintf('%s is unused.', $propertyName))
						->line($propertyNode->getStartLine())
						->tip($tip)
						->identifier('property.unused')
						->build();
				} else {
					$errors[] = RuleErrorBuilder::message(sprintf('%s is never read, only written.', $propertyName))
						->line($propertyNode->getStartLine())
						->identifier('property.onlyWritten')
						->tip($tip)
						->build();
				}
			} elseif (!$data['written'] && (!array_key_exists($name, $uninitializedProperties) || !$this->checkUninitializedProperties)) {
				$errors[] = RuleErrorBuilder::message(sprintf('%s is never written, only read.', $propertyName))
					->line($propertyNode->getStartLine())
					->identifier('property.onlyRead')
					->tip($tip)
					->build();
			}
		}

		return $errors;
	}

}
