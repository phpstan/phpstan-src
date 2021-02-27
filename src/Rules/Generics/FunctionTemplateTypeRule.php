<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generics;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\Generic\TemplateTypeScope;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Stmt\Function_>
 */
class FunctionTemplateTypeRule implements Rule
{

	private \PHPStan\Type\FileTypeMapper $fileTypeMapper;

	private \PHPStan\Rules\Generics\TemplateTypeCheck $templateTypeCheck;

	public function __construct(
		FileTypeMapper $fileTypeMapper,
		TemplateTypeCheck $templateTypeCheck
	)
	{
		$this->fileTypeMapper = $fileTypeMapper;
		$this->templateTypeCheck = $templateTypeCheck;
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
			throw new \PHPStan\ShouldNotHappenException();
		}

		$functionName = (string) $node->namespacedName;
		$resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
			$scope->getFile(),
			null,
			null,
			$functionName,
			$docComment->getText()
		);

		return $this->templateTypeCheck->check(
			$node,
			TemplateTypeScope::createWithFunction($functionName),
			$resolvedPhpDoc->getTemplateTags(),
			sprintf('PHPDoc tag @template for function %s() cannot have existing class %%s as its name.', $functionName),
			sprintf('PHPDoc tag @template for function %s() cannot have existing type alias %%s as its name.', $functionName),
			sprintf('PHPDoc tag @template %%s for function %s() has invalid bound type %%s.', $functionName),
			sprintf('PHPDoc tag @template %%s for function %s() with bound type %%s is not supported.', $functionName)
		);
	}

}
