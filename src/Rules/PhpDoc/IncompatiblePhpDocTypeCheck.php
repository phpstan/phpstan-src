<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Internal\SprintfHelper;
use PHPStan\PhpDoc\ResolvedPhpDocBlock;
use PHPStan\PhpDoc\Tag\ParamOutTag;
use PHPStan\PhpDoc\Tag\ParamTag;
use PHPStan\Rules\Generics\GenericObjectTypeCheck;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ClosureType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function array_merge;
use function in_array;
use function sprintf;

final class IncompatiblePhpDocTypeCheck
{

	public function __construct(
		private GenericObjectTypeCheck $genericObjectTypeCheck,
		private UnresolvableTypeHelper $unresolvableTypeHelper,
		private GenericCallableRuleHelper $genericCallableRuleHelper,
	)
	{
	}

	/**
	 * @param array<string, Type> $nativeParameterTypes
	 * @param array<string, bool> $byRefParameters
	 * @return list<IdentifierRuleError>
	 */
	public function check(
		Scope $scope,
		Node $node,
		ResolvedPhpDocBlock $resolvedPhpDoc,
		string $functionName,
		array $nativeParameterTypes,
		array $byRefParameters,
		Type $nativeReturnType,
	): array
	{
		$errors = [];

		foreach (['@param' => $resolvedPhpDoc->getParamTags(), '@param-out' => $resolvedPhpDoc->getParamOutTags(), '@param-closure-this' => $resolvedPhpDoc->getParamClosureThisTags()] as $tagName => $parameters) {
			foreach ($parameters as $parameterName => $phpDocParamTag) {
				$phpDocParamType = $phpDocParamTag->getType();

				if (!isset($nativeParameterTypes[$parameterName])) {
					$errors[] = RuleErrorBuilder::message(sprintf(
						'PHPDoc tag %s references unknown parameter: $%s',
						$tagName,
						$parameterName,
					))->identifier('parameter.notFound')->build();

				} elseif (
					$this->unresolvableTypeHelper->containsUnresolvableType($phpDocParamType)
				) {
					$errors[] = RuleErrorBuilder::message(sprintf(
						'PHPDoc tag %s for parameter $%s contains unresolvable type.',
						$tagName,
						$parameterName,
					))->identifier('parameter.unresolvableType')->build();

				} else {
					$nativeParamType = $nativeParameterTypes[$parameterName];
					if (
						$phpDocParamTag instanceof ParamTag
						&& $phpDocParamTag->isVariadic()
						&& $phpDocParamType->isArray()->yes()
						&& $nativeParamType->isArray()->no()
					) {
						$phpDocParamType = $phpDocParamType->getIterableValueType();
					}

					$escapedParameterName = SprintfHelper::escapeFormatString($parameterName);
					$escapedTagName = SprintfHelper::escapeFormatString($tagName);

					$errors = array_merge($errors, $this->genericObjectTypeCheck->check(
						$phpDocParamType,
						sprintf(
							'PHPDoc tag %s for parameter $%s contains generic type %%s but %%s %%s is not generic.',
							$escapedTagName,
							$escapedParameterName,
						),
						sprintf(
							'Generic type %%s in PHPDoc tag %s for parameter $%s does not specify all template types of %%s %%s: %%s',
							$escapedTagName,
							$escapedParameterName,
						),
						sprintf(
							'Generic type %%s in PHPDoc tag %s for parameter $%s specifies %%d template types, but %%s %%s supports only %%d: %%s',
							$escapedTagName,
							$escapedParameterName,
						),
						sprintf(
							'Type %%s in generic type %%s in PHPDoc tag %s for parameter $%s is not subtype of template type %%s of %%s %%s.',
							$escapedTagName,
							$escapedParameterName,
						),
						sprintf(
							'Call-site variance of %%s in generic type %%s in PHPDoc tag %s for parameter $%s is in conflict with %%s template type %%s of %%s %%s.',
							$escapedTagName,
							$escapedParameterName,
						),
						sprintf(
							'Call-site variance of %%s in generic type %%s in PHPDoc tag %s for parameter $%s is redundant, template type %%s of %%s %%s has the same variance.',
							$escapedTagName,
							$escapedParameterName,
						),
					));

					$errors = array_merge($errors, $this->genericCallableRuleHelper->check(
						$node,
						$scope,
						sprintf('%s for parameter $%s', $escapedTagName, $escapedParameterName),
						$phpDocParamType,
						$functionName,
						$resolvedPhpDoc->getTemplateTags(),
						$scope->isInClass() ? $scope->getClassReflection() : null,
					));

					if ($phpDocParamTag instanceof ParamOutTag) {
						if (!$byRefParameters[$parameterName]) {
							$errors[] = RuleErrorBuilder::message(sprintf(
								'Parameter $%s for PHPDoc tag %s is not passed by reference.',
								$parameterName,
								$tagName,
							))->identifier('parameter.notByRef')->build();

						}
						continue;
					}

					if (in_array($tagName, ['@param', '@param-out'], true)) {
						$isParamSuperType = $nativeParamType->isSuperTypeOf($phpDocParamType);
						if ($isParamSuperType->no()) {
							$errors[] = RuleErrorBuilder::message(sprintf(
								'PHPDoc tag %s for parameter $%s with type %s is incompatible with native type %s.',
								$tagName,
								$parameterName,
								$phpDocParamType->describe(VerbosityLevel::typeOnly()),
								$nativeParamType->describe(VerbosityLevel::typeOnly()),
							))->identifier('parameter.phpDocType')->build();

						} elseif ($isParamSuperType->maybe()) {
							$errorBuilder = RuleErrorBuilder::message(sprintf(
								'PHPDoc tag %s for parameter $%s with type %s is not subtype of native type %s.',
								$tagName,
								$parameterName,
								$phpDocParamType->describe(VerbosityLevel::typeOnly()),
								$nativeParamType->describe(VerbosityLevel::typeOnly()),
							))->identifier('parameter.phpDocType');
							if ($phpDocParamType instanceof TemplateType) {
								$errorBuilder->tip(sprintf('Write @template %s of %s to fix this.', $phpDocParamType->getName(), $nativeParamType->describe(VerbosityLevel::typeOnly())));
							}

							$errors[] = $errorBuilder->build();
						}
					}

					if ($tagName === '@param-closure-this') {
						$isNonClosure = (new ClosureType())->isSuperTypeOf($nativeParamType)->no();
						if ($isNonClosure) {
							$errors[] = RuleErrorBuilder::message(sprintf(
								'PHPDoc tag %s is for parameter $%s with non-Closure type %s.',
								$tagName,
								$parameterName,
								$nativeParamType->describe(VerbosityLevel::typeOnly()),
							))->identifier('paramClosureThis.nonClosure')->build();
						}
					}
				}
			}
		}

		if ($resolvedPhpDoc->getReturnTag() !== null) {
			$phpDocReturnType = $resolvedPhpDoc->getReturnTag()->getType();

			if (
				$this->unresolvableTypeHelper->containsUnresolvableType($phpDocReturnType)
			) {
				$errors[] = RuleErrorBuilder::message('PHPDoc tag @return contains unresolvable type.')->identifier('return.unresolvableType')->build();

			} else {
				$isReturnSuperType = $nativeReturnType->isSuperTypeOf($phpDocReturnType);
				$errors = array_merge($errors, $this->genericObjectTypeCheck->check(
					$phpDocReturnType,
					'PHPDoc tag @return contains generic type %s but %s %s is not generic.',
					'Generic type %s in PHPDoc tag @return does not specify all template types of %s %s: %s',
					'Generic type %s in PHPDoc tag @return specifies %d template types, but %s %s supports only %d: %s',
					'Type %s in generic type %s in PHPDoc tag @return is not subtype of template type %s of %s %s.',
					'Call-site variance of %s in generic type %s in PHPDoc tag @return is in conflict with %s template type %s of %s %s.',
					'Call-site variance of %s in generic type %s in PHPDoc tag @return is redundant, template type %s of %s %s has the same variance.',
				));
				if ($isReturnSuperType->no()) {
					$errors[] = RuleErrorBuilder::message(sprintf(
						'PHPDoc tag @return with type %s is incompatible with native type %s.',
						$phpDocReturnType->describe(VerbosityLevel::typeOnly()),
						$nativeReturnType->describe(VerbosityLevel::typeOnly()),
					))->identifier('return.phpDocType')->build();

				} elseif ($isReturnSuperType->maybe()) {
					$errorBuilder = RuleErrorBuilder::message(sprintf(
						'PHPDoc tag @return with type %s is not subtype of native type %s.',
						$phpDocReturnType->describe(VerbosityLevel::typeOnly()),
						$nativeReturnType->describe(VerbosityLevel::typeOnly()),
					))->identifier('return.phpDocType');
					if ($phpDocReturnType instanceof TemplateType) {
						$errorBuilder->tip(sprintf('Write @template %s of %s to fix this.', $phpDocReturnType->getName(), $nativeReturnType->describe(VerbosityLevel::typeOnly())));
					}

					$errors[] = $errorBuilder->build();
				}

				$errors = array_merge($errors, $this->genericCallableRuleHelper->check(
					$node,
					$scope,
					'@return',
					$phpDocReturnType,
					$functionName,
					$resolvedPhpDoc->getTemplateTags(),
					$scope->isInClass() ? $scope->getClassReflection() : null,
				));
			}
		}

		return $errors;
	}

}
