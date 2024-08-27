<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClassPropertyNode;
use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<ClassPropertyNode>
 */
final class ReadOnlyPropertyRule implements Rule
{

	public function __construct(private PhpVersion $phpVersion)
	{
	}

	public function getNodeType(): string
	{
		return ClassPropertyNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->isReadOnly()) {
			return [];
		}

		$errors = [];
		if (!$this->phpVersion->supportsReadOnlyProperties()) {
			 $errors[] = RuleErrorBuilder::message('Readonly properties are supported only on PHP 8.1 and later.')->nonIgnorable()
				 ->identifier('property.readOnlyNotSupported')
				 ->build();
		}

		if ($node->getNativeType() === null) {
			 $errors[] = RuleErrorBuilder::message('Readonly property must have a native type.')
				 ->identifier('property.readOnlyNoNativeType')
				 ->nonIgnorable()
				 ->build();
		}

		if ($node->getDefault() !== null) {
			 $errors[] = RuleErrorBuilder::message('Readonly property cannot have a default value.')->nonIgnorable()
				 ->identifier('property.readOnlyDefaultValue')
				 ->build();
		}

		if ($node->isStatic()) {
			$errors[] = RuleErrorBuilder::message('Readonly property cannot be static.')->nonIgnorable()
				->identifier('property.readOnlyStatic')
				->build();
		}

		return $errors;
	}

}
