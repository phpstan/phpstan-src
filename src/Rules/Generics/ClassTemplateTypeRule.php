<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generics;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Broker\AnonymousClassNameHelper;
use PHPStan\Rules\Rule;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\Generic\TemplateTypeScope;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Stmt\Class_>
 */
class ClassTemplateTypeRule implements Rule
{

	private \PHPStan\Type\FileTypeMapper $fileTypeMapper;

	private \PHPStan\Rules\Generics\TemplateTypeCheck $templateTypeCheck;

	private \PHPStan\Broker\AnonymousClassNameHelper $anonymousClassNameHelper;

	public function __construct(
		FileTypeMapper $fileTypeMapper,
		TemplateTypeCheck $templateTypeCheck,
		AnonymousClassNameHelper $anonymousClassNameHelper
	)
	{
		$this->fileTypeMapper = $fileTypeMapper;
		$this->templateTypeCheck = $templateTypeCheck;
		$this->anonymousClassNameHelper = $anonymousClassNameHelper;
	}

	public function getNodeType(): string
	{
		return Node\Stmt\Class_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$docComment = $node->getDocComment();
		if ($docComment === null) {
			return [];
		}

		if (isset($node->namespacedName)) {
			$className = (string) $node->namespacedName;
			$errorMessageClass = 'class ' . $className;
		} elseif ((bool) $node->getAttribute('anonymousClass', false)) {
			$className = $this->anonymousClassNameHelper->getAnonymousClassName($node, $scope->getFile());
			$errorMessageClass = 'anonymous class';
		} else {
			throw new \PHPStan\ShouldNotHappenException();
		}

		$resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
			$scope->getFile(),
			$className,
			null,
			null,
			$docComment->getText()
		);

		return $this->templateTypeCheck->check(
			$node,
			TemplateTypeScope::createWithClass($className),
			$resolvedPhpDoc->getTemplateTags(),
			sprintf('PHPDoc tag @template for %s cannot have existing class %%s as its name.', $errorMessageClass),
			sprintf('PHPDoc tag @template for %s cannot have existing type alias %%s as its name.', $errorMessageClass),
			sprintf('PHPDoc tag @template %%s for %s has invalid bound type %%s.', $errorMessageClass),
			sprintf('PHPDoc tag @template %%s for %s with bound type %%s is not supported.', $errorMessageClass)
		);
	}

}
