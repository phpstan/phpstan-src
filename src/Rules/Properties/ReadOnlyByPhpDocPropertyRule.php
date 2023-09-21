<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClassPropertyNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<ClassPropertyNode>
 */
class ReadOnlyByPhpDocPropertyRule implements Rule
{

	public function getNodeType(): string
	{
		return ClassPropertyNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!($node->isReadOnlyByPhpDoc() && !$node->isAllowedPrivateMutation()) || $node->isReadOnly()) {
			return [];
		}

		$errors = [];
		if ($node->getDefault() !== null) {
			 $errors[] = RuleErrorBuilder::message('@readonly property cannot have a default value.')
				 ->identifier('property.readOnlyByPhpDocDefaultValue')
				 ->build();
		}

		return $errors;
	}

}
