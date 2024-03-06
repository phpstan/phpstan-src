<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\PhpDoc\Tag\TemplateTag;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\Generics\TemplateTypeCheck;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\CallableType;
use PHPStan\Type\ClosureType;
use PHPStan\Type\Generic\TemplateTypeScope;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\VerbosityLevel;
use function array_keys;
use function sprintf;

class GenericCallableRuleHelper
{

	public function __construct(
		private TemplateTypeCheck $templateTypeCheck,
	)
	{
	}

	/**
	 * @param array<string, TemplateTag> $functionTemplateTags
	 *
	 * @return list<IdentifierRuleError>
	 */
	public function check(
		Node $node,
		Scope $scope,
		string $location,
		Type $callableType,
		?string $functionName,
		array $functionTemplateTags,
		?ClassReflection $classReflection,
	): array
	{
		$errors = [];

		TypeTraverser::map($callableType, function (Type $type, callable $traverse) use (&$errors, $node, $scope, $location, $functionName, $functionTemplateTags, $classReflection) {
			if (!($type instanceof CallableType || $type instanceof ClosureType)) {
				return $traverse($type);
			}

			$typeDescription = $type->describe(VerbosityLevel::precise());

			$errors = $this->templateTypeCheck->check(
				$scope,
				$node,
				TemplateTypeScope::createWithAnonymousFunction(),
				$type->getTemplateTags(),
				sprintf('PHPDoc tag %s template of %s cannot have existing class %%s as its name.', $location, $typeDescription),
				sprintf('PHPDoc tag %s template of %s cannot have existing type alias %%s as its name.', $location, $typeDescription),
				sprintf('PHPDoc tag %s template %%s of %s has invalid bound type %%s.', $location, $typeDescription),
				sprintf('PHPDoc tag %s template %%s of %s with bound type %%s is not supported.', $location, $typeDescription),
			);

			$templateTags = $type->getTemplateTags();

			$classDescription = null;
			if ($classReflection !== null) {
				$classDescription = $classReflection->getDisplayName();
			}

			if ($functionName !== null) {
				$functionDescription = sprintf('function %s', $functionName);
				if ($classReflection !== null) {
					$functionDescription = sprintf('method %s::%s', $classDescription, $functionName);
				}

				foreach (array_keys($functionTemplateTags) as $name) {
					if (!isset($templateTags[$name])) {
						continue;
					}

					$errors[] = RuleErrorBuilder::message(sprintf(
						'PHPDoc tag %s template %s of %s shadows @template %s for %s.',
						$location,
						$name,
						$typeDescription,
						$name,
						$functionDescription,
					))->identifier('callable.shadowTemplate')->build();
				}
			}

			if ($classReflection !== null) {
				foreach (array_keys($classReflection->getTemplateTags()) as $name) {
					if (!isset($templateTags[$name])) {
						continue;
					}

					$errors[] = RuleErrorBuilder::message(sprintf(
						'PHPDoc tag %s template %s of %s shadows @template %s for class %s.',
						$location,
						$name,
						$typeDescription,
						$name,
						$classDescription,
					))->identifier('callable.shadowTemplate')->build();
				}
			}

			return $traverse($type);
		});

		return $errors;
	}

}
