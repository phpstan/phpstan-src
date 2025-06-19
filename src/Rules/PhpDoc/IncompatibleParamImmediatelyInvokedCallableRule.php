<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\FunctionLike;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\VerbosityLevel;
use function is_string;
use function sprintf;
use function trim;

/**
 * @implements Rule<FunctionLike>
 */
#[RegisteredRule(level: 2)]
final class IncompatibleParamImmediatelyInvokedCallableRule implements Rule
{

	public function __construct(
		private FileTypeMapper $fileTypeMapper,
	)
	{
	}

	public function getNodeType(): string
	{
		return FunctionLike::class;
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
		$nativeParameterTypes = [];
		foreach ($node->getParams() as $parameter) {
			if (!$parameter->var instanceof Variable || !is_string($parameter->var->name)) {
				throw new ShouldNotHappenException();
			}
			$nativeParameterTypes[$parameter->var->name] = $scope->getFunctionType(
				$parameter->type,
				$scope->isParameterValueNullable($parameter),
				false,
			);
		}

		$errors = [];
		foreach ($resolvedPhpDoc->getParamsImmediatelyInvokedCallable() as $parameterName => $immediately) {
			$tagName = $immediately ? '@param-immediately-invoked-callable' : '@param-later-invoked-callable';
			if (!isset($nativeParameterTypes[$parameterName])) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'PHPDoc tag %s references unknown parameter: $%s',
					$tagName,
					$parameterName,
				))->identifier('parameter.notFound')->build();
			} elseif ($nativeParameterTypes[$parameterName]->isCallable()->no()) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'PHPDoc tag %s is for parameter $%s with non-callable type %s.',
					$tagName,
					$parameterName,
					$nativeParameterTypes[$parameterName]->describe(VerbosityLevel::typeOnly()),
				))->identifier(sprintf(
					'%s.nonCallable',
					$immediately ? 'paramImmediatelyInvokedCallable' : 'paramLaterInvokedCallable',
				))->build();
			}
		}

		return $errors;
	}

}
