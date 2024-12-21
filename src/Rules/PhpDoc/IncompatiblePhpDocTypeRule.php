<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\Type;
use function is_string;
use function trim;

/**
 * @implements Rule<Node\FunctionLike>
 */
final class IncompatiblePhpDocTypeRule implements Rule
{

	public function __construct(
		private FileTypeMapper $fileTypeMapper,
		private IncompatiblePhpDocTypeCheck $check,
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

		return $this->check->check(
			$scope,
			$node,
			$resolvedPhpDoc,
			$functionName,
			$this->getNativeParameterTypes($node, $scope),
			$this->getByRefParameters($node),
			$this->getNativeReturnType($node, $scope),
		);
	}

	/**
	 * @return array<string, Type>
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
