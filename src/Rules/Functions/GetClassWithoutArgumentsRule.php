<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\Conditional;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function count;
use function in_array;
use function sprintf;

/**
 * @implements Rule<FuncCall>
 */
#[Conditional(
	attribute: new RegisteredRule(level: 0),
	if: '%featureToggles.phpLanguageDeprecations%',
)]
final class GetClassWithoutArgumentsRule implements Rule
{

	public function __construct(
		private ReflectionProvider $reflectionProvider,
	)
	{
	}

	public function getNodeType(): string
	{
		return FuncCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->name instanceof Node\Name || $node->isFirstClassCallable() || count($node->getArgs()) > 0) {
			return [];
		}

		if (!$this->reflectionProvider->hasFunction($node->name, $scope)) {
			return [];
		}

		$functionName = $this->reflectionProvider->getFunction($node->name, $scope)->getName();
		if (!in_array($functionName, ['get_class', 'get_parent_class'], true)) {
			return [];
		}

		if ($functionName === 'get_class' && !$scope->isInClass()) {
			return [
				RuleErrorBuilder::message('Function get_class() cannot be called without arguments outside of a class.')
					->identifier('outOfClass.getClass')
					->build(),
			];
		}

		if ($scope->getPhpVersion()->deprecatesGetClassWithoutArguments()->no()) {
			return [];
		}

		$keyword = $functionName === 'get_class' ? 'self' : 'parent';
		$errorBuilder = RuleErrorBuilder::message(sprintf(
			'Calling %s() without arguments is deprecated in PHP 8.3.',
			$functionName,
		))->identifier('function.deprecatedWithoutArguments');

		if ($keyword === 'parent') {
			if (!$scope->isInClass() || $scope->isInTrait()) {
				return [$errorBuilder->tip('Pass the object or class name to get_parent_class().')->build()];
			}

			if ($scope->getClassReflection()->getParentClass() === null) {
				return [
					$errorBuilder
						->tip(sprintf('Class %s does not extend any class, so this call always returns false.', $scope->getClassReflection()->getDisplayName()))
						->build(),
				];
			}
		}

		return [
			$errorBuilder
				->tip(sprintf('Use %s::class instead.', $keyword))
				->fixNode($node, static fn () => new ClassConstFetch(new Node\Name($keyword), 'class'))
				->build(),
		];
	}

}
