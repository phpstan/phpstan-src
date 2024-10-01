<?php declare(strict_types = 1);

namespace PHPStan\Build;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\File\FileHelper;
use PHPStan\Node\InClassNode;
use PHPStan\Reflection\FunctionVariant;
use PHPStan\Reflection\ExtendedFunctionVariant;
use PHPStan\Reflection\Php\DummyParameter;
use PHPStan\Reflection\Php\PhpFunctionFromParserNodeReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Type;
use function in_array;
use function sprintf;
use function str_starts_with;

/**
 * @implements Rule<InClassNode>
 */
final class FinalClassRule implements Rule
{

	public function __construct(private FileHelper $fileHelper)
	{
	}

	public function getNodeType(): string
	{
		return InClassNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$classReflection = $node->getClassReflection();
		if (!$classReflection->isClass()) {
			return [];
		}
		if ($classReflection->isAbstract()) {
			return [];
		}
		if ($classReflection->isFinal()) {
			return [];
		}
		if ($classReflection->isSubclassOf(Type::class)) {
			return [];
		}

		// exceptions
		if (in_array($classReflection->getName(), [
			FunctionVariant::class,
			ExtendedFunctionVariant::class,
			DummyParameter::class,
			PhpFunctionFromParserNodeReflection::class,
		], true)) {
			return [];
		}

		if (str_starts_with($this->fileHelper->normalizePath($scope->getFile()), $this->fileHelper->normalizePath(dirname(__DIR__, 3) . '/tests'))) {
			return [];
		}

		return [
			RuleErrorBuilder::message(
				sprintf('Class %s must be abstract or final.', $classReflection->getDisplayName()),
			)
				->identifier('phpstan.finalClass')
				->build(),
		];
	}

}
