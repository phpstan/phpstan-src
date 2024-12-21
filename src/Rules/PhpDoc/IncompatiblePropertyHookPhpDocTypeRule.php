<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InPropertyHookNode;
use PHPStan\Reflection\Php\PhpMethodFromParserNodeReflection;
use PHPStan\Rules\Rule;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\Type;

/**
 * @implements Rule<InPropertyHookNode>
 */
final class IncompatiblePropertyHookPhpDocTypeRule implements Rule
{

	public function __construct(
		private FileTypeMapper $fileTypeMapper,
		private IncompatiblePhpDocTypeCheck $check,
	)
	{
	}

	public function getNodeType(): string
	{
		return InPropertyHookNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$docComment = $node->getDocComment();
		if ($docComment === null) {
			return [];
		}

		$hookReflection = $node->getHookReflection();

		$resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
			$scope->getFile(),
			$node->getClassReflection()->getName(),
			$scope->isInTrait() ? $scope->getTraitReflection()->getName() : null,
			$hookReflection->getName(),
			$docComment->getText(),
		);

		return $this->check->check(
			$scope,
			$node,
			$resolvedPhpDoc,
			$hookReflection->getName(),
			$this->getNativeParameterTypes($hookReflection),
			$this->getByRefParameters($hookReflection),
			$hookReflection->getNativeReturnType(),
		);
	}

	/**
	 * @return array<string, Type>
	 */
	private function getNativeParameterTypes(PhpMethodFromParserNodeReflection $node): array
	{
		$parameters = [];
		foreach ($node->getParameters() as $parameter) {
			$parameters[$parameter->getName()] = $parameter->getNativeType();
		}

		return $parameters;
	}

	/**
	 * @return array<string, false>
	 */
	private function getByRefParameters(PhpMethodFromParserNodeReflection $node): array
	{
		$parameters = [];
		foreach ($node->getParameters() as $parameter) {
			$parameters[$parameter->getName()] = false;
		}

		return $parameters;
	}

}
