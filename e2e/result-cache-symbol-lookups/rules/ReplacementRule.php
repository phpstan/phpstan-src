<?php declare(strict_types = 1);

namespace ResultCacheE2ESymbolLookupsRules;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Reports legacyCall() only while a class exists - it asks the reflection provider and nothing else.
 *
 * @implements Rule<FuncCall>
 */
final class ReplacementRule implements Rule
{

	public function __construct(private ReflectionProvider $reflectionProvider)
	{
	}

	public function getNodeType(): string
	{
		return FuncCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->name instanceof Name || $node->name->toLowerString() !== 'legacycall') {
			return [];
		}

		if (!$this->reflectionProvider->hasClass('ResultCacheE2ESymbolLookups\Replacement')) {
			return [];
		}

		return [RuleErrorBuilder::message('Use Replacement instead of legacyCall().')->identifier('e2e.replacement')->build()];
	}

}
