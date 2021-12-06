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
 * @implements Rule<Node\Stmt\Interface_>
 */
class InterfaceTemplateTypeRule implements Rule
{

	private FileTypeMapper $fileTypeMapper;

	private TemplateTypeCheck $templateTypeCheck;

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
			throw new ShouldNotHappenException();
		}

		$interfaceName = (string) $node->namespacedName;
		$resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
			$scope->getFile(),
			$interfaceName,
			null,
			null,
			$docComment->getText()
		);

		$escapadInterfaceName = SprintfHelper::escapeFormatString($interfaceName);

		return $this->templateTypeCheck->check(
			$node,
			TemplateTypeScope::createWithClass($interfaceName),
			$resolvedPhpDoc->getTemplateTags(),
			sprintf('PHPDoc tag @template for interface %s cannot have existing class %%s as its name.', $escapadInterfaceName),
			sprintf('PHPDoc tag @template for interface %s cannot have existing type alias %%s as its name.', $escapadInterfaceName),
			sprintf('PHPDoc tag @template %%s for interface %s has invalid bound type %%s.', $escapadInterfaceName),
			sprintf('PHPDoc tag @template %%s for interface %s with bound type %%s is not supported.', $escapadInterfaceName)
		);
	}

}
