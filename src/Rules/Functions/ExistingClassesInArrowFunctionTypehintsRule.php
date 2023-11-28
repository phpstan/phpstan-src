<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Rules\FunctionDefinitionCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\NonAcceptingNeverType;
use PHPStan\Type\ParserNodeTypeToPHPStanType;
use function array_merge;

/**
 * @implements Rule<Node\Expr\ArrowFunction>
 */
class ExistingClassesInArrowFunctionTypehintsRule implements Rule
{

	public function __construct(private FunctionDefinitionCheck $check, private PhpVersion $phpVersion)
	{
	}

	public function getNodeType(): string
	{
		return Node\Expr\ArrowFunction::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$messages = [];
		if ($node->returnType !== null && !$this->phpVersion->supportsNeverReturnTypeInArrowFunction()) {
			$returnType = ParserNodeTypeToPHPStanType::resolve($node->returnType, $scope->isInClass() ? $scope->getClassReflection() : null);
			if ($returnType instanceof NonAcceptingNeverType) {
				$messages[] = RuleErrorBuilder::message('Never return type in arrow function is supported only on PHP 8.2 and later.')
					->identifier('return.neverTypeNotSupported')
					->nonIgnorable()
					->build();
			}
		}

		return array_merge($messages, $this->check->checkAnonymousFunction(
			$scope,
			$node->getParams(),
			$node->getReturnType(),
			'Parameter $%s of anonymous function has invalid type %s.',
			'Anonymous function has invalid return type %s.',
			'Anonymous function uses native union types but they\'re supported only on PHP 8.0 and later.',
			'Parameter $%s of anonymous function has unresolvable native type.',
			'Anonymous function has unresolvable native return type.',
		));
	}

}
