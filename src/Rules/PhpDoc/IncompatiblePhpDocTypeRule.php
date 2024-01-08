<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\Internal\SprintfHelper;
use PHPStan\PhpDoc\Tag\ParamOutTag;
use PHPStan\PhpDoc\Tag\ParamTag;
use PHPStan\Rules\Generics\GenericObjectTypeCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function array_merge;
use function is_string;
use function sprintf;
use function trim;

/**
 * @implements Rule<Node\FunctionLike>
 */
class IncompatiblePhpDocTypeRule implements Rule
{

	public function __construct(
		private FileTypeMapper $fileTypeMapper,
		private GenericObjectTypeCheck $genericObjectTypeCheck,
		private UnresolvableTypeHelper $unresolvableTypeHelper,
	)
	{
	}

	public function getNodeType(): string
	{
		return Node\FunctionLike::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($node instanceof Node\Stmt\ClassMethod) {
			$functionName = $node->name->name;
		} elseif ($node instanceof Node\Stmt\Function_) {
			$functionName = trim($scope->getNamespace() . '\\' . $node->name->name, '\\');
		} else {
			return [];
		}

		$docComment = $node->getDocComment();
		if ($docComment === null) {
			return [];
		}

		$resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
			$scope->getFile(),
			$scope->isInClass() ? $scope->getClassReflection()->getName() : null,
			$scope->isInTrait() ? $scope->getTraitReflection()->getName() : null,
			$functionName,
			$docComment->getText(),
		);
		$nativeParameterTypes = $this->getNativeParameterTypes($node, $scope);
		$nativeReturnType = $this->getNativeReturnType($node, $scope);
		$byRefParameters = $this->getByRefParameters($node);

		$errors = [];

		foreach ([$resolvedPhpDoc->getParamTags(), $resolvedPhpDoc->getParamOutTags()] as $parameters) {
			foreach ($parameters as $parameterName => $phpDocParamTag) {
				$phpDocParamType = $phpDocParamTag->getType();
				$tagName = $phpDocParamTag instanceof ParamTag ? '@param' : '@param-out';

				if (!isset($nativeParameterTypes[$parameterName])) {
					$errors[] = RuleErrorBuilder::message(sprintf(
						'PHPDoc tag %s references unknown parameter: $%s',
						$tagName,
						$parameterName,
					))->identifier('phpDoc.unknownParameter')->metadata(['parameterName' => $parameterName])->build();

				} elseif (
					$this->unresolvableTypeHelper->containsUnresolvableType($phpDocParamType)
				) {
					$errors[] = RuleErrorBuilder::message(sprintf(
						'PHPDoc tag %s for parameter $%s contains unresolvable type.',
						$tagName,
						$parameterName,
					))->build();

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
					$isParamSuperType = $nativeParamType->isSuperTypeOf($phpDocParamType);

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

					if ($phpDocParamTag instanceof ParamOutTag) {
						if (!$byRefParameters[$parameterName]) {
							$errors[] = RuleErrorBuilder::message(sprintf(
								'Parameter $%s for PHPDoc tag %s is not passed by reference.',
								$parameterName,
								$tagName,
							))->build();

						}
						continue;
					}

					if ($isParamSuperType->no()) {
						$errors[] = RuleErrorBuilder::message(sprintf(
							'PHPDoc tag %s for parameter $%s with type %s is incompatible with native type %s.',
							$tagName,
							$parameterName,
							$phpDocParamType->describe(VerbosityLevel::typeOnly()),
							$nativeParamType->describe(VerbosityLevel::typeOnly()),
						))->build();

					} elseif ($isParamSuperType->maybe()) {
						$errorBuilder = RuleErrorBuilder::message(sprintf(
							'PHPDoc tag %s for parameter $%s with type %s is not subtype of native type %s.',
							$tagName,
							$parameterName,
							$phpDocParamType->describe(VerbosityLevel::typeOnly()),
							$nativeParamType->describe(VerbosityLevel::typeOnly()),
						));
						if ($phpDocParamType instanceof TemplateType) {
							$errorBuilder->tip(sprintf('Write @template %s of %s to fix this.', $phpDocParamType->getName(), $nativeParamType->describe(VerbosityLevel::typeOnly())));
						}

						$errors[] = $errorBuilder->build();
					}
				}
			}
		}

		if ($resolvedPhpDoc->getReturnTag() !== null) {
			$phpDocReturnType = $resolvedPhpDoc->getReturnTag()->getType();

			if (
				$this->unresolvableTypeHelper->containsUnresolvableType($phpDocReturnType)
			) {
				$errors[] = RuleErrorBuilder::message('PHPDoc tag @return contains unresolvable type.')->build();

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
					))->build();

				} elseif ($isReturnSuperType->maybe()) {
					$errorBuilder = RuleErrorBuilder::message(sprintf(
						'PHPDoc tag @return with type %s is not subtype of native type %s.',
						$phpDocReturnType->describe(VerbosityLevel::typeOnly()),
						$nativeReturnType->describe(VerbosityLevel::typeOnly()),
					));
					if ($phpDocReturnType instanceof TemplateType) {
						$errorBuilder->tip(sprintf('Write @template %s of %s to fix this.', $phpDocReturnType->getName(), $nativeReturnType->describe(VerbosityLevel::typeOnly())));
					}

					$errors[] = $errorBuilder->build();
				}

			}
		}

		return $errors;
	}

	/**
	 * @return Type[]
	 */
	private function getNativeParameterTypes(Node\FunctionLike $node, Scope $scope): array
	{
		$nativeParameterTypes = [];
		foreach ($node->getParams() as $parameter) {
			$isNullable = $scope->isParameterValueNullable($parameter);
			if (!$parameter->var instanceof Variable || !is_string($parameter->var->name)) {
				throw new ShouldNotHappenException();
			}
			$nativeParameterTypes[$parameter->var->name] = $scope->getFunctionType(
				$parameter->type,
				$isNullable,
				false,
			);
		}

		return $nativeParameterTypes;
	}

	/**
	 * @return array<string, bool>
	 */
	private function getByRefParameters(Node\FunctionLike $node): array
	{
		$nativeParameterTypes = [];
		foreach ($node->getParams() as $parameter) {
			if (!$parameter->var instanceof Variable || !is_string($parameter->var->name)) {
				throw new ShouldNotHappenException();
			}
			$nativeParameterTypes[$parameter->var->name] = $parameter->byRef;
		}

		return $nativeParameterTypes;
	}

	private function getNativeReturnType(Node\FunctionLike $node, Scope $scope): Type
	{
		return $scope->getFunctionType($node->getReturnType(), false, false);
	}

}
