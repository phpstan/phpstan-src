<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\Internal\SprintfHelper;
use PHPStan\Rules\Generics\GenericObjectTypeCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ArrayType;
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
		$docComment = $node->getDocComment();
		if ($docComment === null) {
			return [];
		}

		if ($node instanceof Node\Stmt\ClassMethod) {
			$functionName = $node->name->name;
		} elseif ($node instanceof Node\Stmt\Function_) {
			$functionName = trim($scope->getNamespace() . '\\' . $node->name->name, '\\');
		} else {
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

		$errors = [];

		foreach ($resolvedPhpDoc->getParamTags() as $parameterName => $phpDocParamTag) {
			$phpDocParamType = $phpDocParamTag->getType();
			if (!isset($nativeParameterTypes[$parameterName])) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'PHPDoc tag @param references unknown parameter: $%s',
					$parameterName,
				))->identifier('phpDoc.unknownParameter')->metadata(['parameterName' => $parameterName])->build();

			} elseif (
				$this->unresolvableTypeHelper->containsUnresolvableType($phpDocParamType)
			) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'PHPDoc tag @param for parameter $%s contains unresolvable type.',
					$parameterName,
				))->build();

			} else {
				$nativeParamType = $nativeParameterTypes[$parameterName];
				if (
					$phpDocParamTag->isVariadic()
					&& $phpDocParamType instanceof ArrayType
					&& !$nativeParamType instanceof ArrayType
				) {
					$phpDocParamType = $phpDocParamType->getItemType();
				}
				$isParamSuperType = $nativeParamType->isSuperTypeOf($phpDocParamType);

				$escapedParameterName = SprintfHelper::escapeFormatString($parameterName);

				$errors = array_merge($errors, $this->genericObjectTypeCheck->check(
					$phpDocParamType,
					sprintf(
						'PHPDoc tag @param for parameter $%s contains generic type %%s but %%s %%s is not generic.',
						$escapedParameterName,
					),
					sprintf(
						'Generic type %%s in PHPDoc tag @param for parameter $%s does not specify all template types of %%s %%s: %%s',
						$escapedParameterName,
					),
					sprintf(
						'Generic type %%s in PHPDoc tag @param for parameter $%s specifies %%d template types, but %%s %%s supports only %%d: %%s',
						$escapedParameterName,
					),
					sprintf(
						'Type %%s in generic type %%s in PHPDoc tag @param for parameter $%s is not subtype of template type %%s of %%s %%s.',
						$escapedParameterName,
					),
				));

				if ($isParamSuperType->no()) {
					$errors[] = RuleErrorBuilder::message(sprintf(
						'PHPDoc tag @param for parameter $%s with type %s is incompatible with native type %s.',
						$parameterName,
						$phpDocParamType->describe(VerbosityLevel::typeOnly()),
						$nativeParamType->describe(VerbosityLevel::typeOnly()),
					))->build();

				} elseif ($isParamSuperType->maybe()) {
					$errorBuilder = RuleErrorBuilder::message(sprintf(
						'PHPDoc tag @param for parameter $%s with type %s is not subtype of native type %s.',
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

	private function getNativeReturnType(Node\FunctionLike $node, Scope $scope): Type
	{
		return $scope->getFunctionType($node->getReturnType(), false, false);
	}

}
