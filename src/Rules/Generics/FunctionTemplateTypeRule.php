<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generics;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Internal\SprintfHelper;
use PHPStan\Rules\Rule;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\Generic\TemplateTypeScope;
use function sprintf;

/**
 * @implements Rule<Node\Stmt\Function_>
 */
class FunctionTemplateTypeRule implements Rule
{

	public function __construct(
		private FileTypeMapper $fileTypeMapper,
		private TemplateTypeCheck $templateTypeCheck,
	)
	{
	}

	public function getNodeType(): string
	{
		return Node\Stmt\Function_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$docComment = $node->getDocComment();
		if ($docComment === null) {
			return [];
		}

		if (!isset($node->namespacedName)) {
			throw new ShouldNotHappenException();
		}

		$functionName = (string) $node->namespacedName;
		$resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
			$scope->getFile(),
			null,
			null,
			$functionName,
			$docComment->getText(),
		);

		$escapedFunctionName = SprintfHelper::escapeFormatString($functionName);

		return $this->templateTypeCheck->check(
			$scope,
			$node,
			TemplateTypeScope::createWithFunction($functionName),
			$resolvedPhpDoc->getTemplateTags(),
			sprintf('PHPDoc tag @template for function %s() cannot have existing class %%s as its name.', $escapedFunctionName),
			sprintf('PHPDoc tag @template for function %s() cannot have existing type alias %%s as its name.', $escapedFunctionName),
			sprintf('PHPDoc tag @template %%s for function %s() has invalid bound type %%s.', $escapedFunctionName),
			sprintf('PHPDoc tag @template %%s for function %s() with bound type %%s is not supported.', $escapedFunctionName),
		);
	}

}
