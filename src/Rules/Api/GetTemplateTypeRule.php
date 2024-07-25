<?php declare(strict_types = 1);

namespace PHPStan\Rules\Api;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Type;
use function count;
use function sprintf;

/**
 * @implements Rule<MethodCall>
 */
final class GetTemplateTypeRule implements Rule
{

	public function __construct(private ReflectionProvider $reflectionProvider)
	{
	}

	public function getNodeType(): string
	{
		return MethodCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$args = $node->getArgs();
		if (count($args) < 2) {
			return [];
		}
		if (!$node->name instanceof Node\Identifier) {
			return [];
		}

		if ($node->name->toLowerString() !== 'gettemplatetype') {
			return [];
		}

		$calledOnType = $scope->getType($node->var);
		$methodReflection = $scope->getMethodReflection($calledOnType, $node->name->toString());
		if ($methodReflection === null) {
			return [];
		}

		if (!$methodReflection->getDeclaringClass()->is(Type::class)) {
			return [];
		}

		$classType = $scope->getType($args[0]->value);
		$templateType = $scope->getType($args[1]->value);
		$errors = [];
		foreach ($classType->getConstantStrings() as $classNameType) {
			if (!$this->reflectionProvider->hasClass($classNameType->getValue())) {
				continue;
			}
			$classReflection = $this->reflectionProvider->getClass($classNameType->getValue());
			$templateTypeMap = $classReflection->getTemplateTypeMap();
			foreach ($templateType->getConstantStrings() as $templateTypeName) {
				if ($templateTypeMap->hasType($templateTypeName->getValue())) {
					continue;
				}

				$errors[] = RuleErrorBuilder::message(sprintf(
					'Call to %s::%s() references unknown template type %s on class %s.',
					$methodReflection->getDeclaringClass()->getDisplayName(),
					$methodReflection->getName(),
					$templateTypeName->getValue(),
					$classReflection->getDisplayName(),
				))->identifier('phpstanApi.getTemplateType')->build();
			}
		}

		return $errors;
	}

}
