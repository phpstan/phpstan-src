<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generics;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\Generic\TemplateTypeScope;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Stmt\Interface_>
 */
class InterfaceTemplateTypeRule implements Rule
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
		return Node\Stmt\Interface_::class;
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

		$interfaceName = (string) $node->namespacedName;
		$resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
			$scope->getFile(),
			$interfaceName,
			null,
			null,
			$docComment->getText()
		);

		return $this->templateTypeCheck->check(
			$node,
			TemplateTypeScope::createWithClass($interfaceName),
			$resolvedPhpDoc->getTemplateTags(),
			sprintf('PHPDoc tag @template for interface %s cannot have existing class %%s as its name.', $interfaceName),
			sprintf('PHPDoc tag @template for interface %s cannot have existing type alias %%s as its name.', $interfaceName),
			sprintf('PHPDoc tag @template %%s for interface %s has invalid bound type %%s.', $interfaceName),
			sprintf('PHPDoc tag @template %%s for interface %s with bound type %%s is not supported.', $interfaceName)
		);
	}

}
