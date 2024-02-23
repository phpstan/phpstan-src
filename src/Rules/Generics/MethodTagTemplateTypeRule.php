<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generics;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Internal\SprintfHelper;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\Generic\TemplateTypeScope;
use PHPStan\Type\VerbosityLevel;
use function array_keys;
use function array_merge;
use function sprintf;

/**
 * @implements Rule<InClassNode>
 */
class MethodTagTemplateTypeRule implements Rule
{

	public function __construct(
		private FileTypeMapper $fileTypeMapper,
		private TemplateTypeCheck $templateTypeCheck,
	)
	{
	}

	public function getNodeType(): string
	{
		return InClassNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$docComment = $node->getDocComment();
		if ($docComment === null) {
			return [];
		}

		$classReflection = $node->getClassReflection();
		$className = $classReflection->getDisplayName();
		$resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
			$scope->getFile(),
			$classReflection->getName(),
			$scope->isInTrait() ? $scope->getTraitReflection()->getName() : null,
			null,
			$docComment->getText(),
		);

		$messages = [];
		$escapedClassName = SprintfHelper::escapeFormatString($className);
		$classTemplateTypes = $classReflection->getTemplateTypeMap()->getTypes();

		foreach ($resolvedPhpDoc->getMethodTags() as $methodName => $methodTag) {
			$methodTemplateTags = $methodTag->getTemplateTags();
			$escapedMethodName = SprintfHelper::escapeFormatString($methodName);

			$messages = array_merge($messages, $this->templateTypeCheck->check(
				$scope,
				$node,
				TemplateTypeScope::createWithMethod($className, $methodName),
				$methodTemplateTags,
				sprintf('PHPDoc tag @method template for method %s::%s() cannot have existing class %%s as its name.', $escapedClassName, $escapedMethodName),
				sprintf('PHPDoc tag @method template for method %s::%s() cannot have existing type alias %%s as its name.', $escapedClassName, $escapedMethodName),
				sprintf('PHPDoc tag @method template %%s for method %s::%s() has invalid bound type %%s.', $escapedClassName, $escapedMethodName),
				sprintf('PHPDoc tag @method template %%s for method %s::%s() with bound type %%s is not supported.', $escapedClassName, $escapedMethodName),
			));

			foreach (array_keys($methodTemplateTags) as $name) {
				if (!isset($classTemplateTypes[$name])) {
					continue;
				}

				$messages[] = RuleErrorBuilder::message(sprintf('PHPDoc tag @method template %s for method %s::%s() shadows @template %s for class %s.', $name, $className, $methodName, $classTemplateTypes[$name]->describe(VerbosityLevel::typeOnly()), $classReflection->getDisplayName(false)))
					->identifier('methodTag.shadowTemplate')
					->build();
			}
		}

		return $messages;
	}

}
