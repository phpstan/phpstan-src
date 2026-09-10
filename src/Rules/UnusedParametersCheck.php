<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Param;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\VariableWritesNode;
use PHPStan\Reflection\Php\PhpFunctionFromParserNodeReflection;
use PHPStan\Type\ConditionalTypeForParameter;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use function is_string;
use function ltrim;
use function sprintf;

/**
 * The per-parameter half of the unused-parameter rules, on top of the
 * variable-writes engine: a parameter binds a value, so it is unused unless
 * that value is read on some path (overwriting it first is not a use);
 * func_get_args() observes every parameter's original value; a by-ref
 * parameter gives the caller the variable, so any mention counts.
 */
#[AutowiredService]
final class UnusedParametersCheck
{

	/**
	 * @param Param[] $parameters
	 * @param 'constructor.unusedParameter'|'function.unusedParameter'|'method.unusedParameter' $identifier
	 * @return list<IdentifierRuleError>
	 */
	public function getUnusedParameterErrors(
		VariableWritesNode $node,
		PhpFunctionFromParserNodeReflection $function,
		array $parameters,
		string $unusedParameterMessage,
		string $identifier,
		bool $reportExactLine,
	): array
	{
		if ($node->isOpaque()) {
			return [];
		}

		$contractParameterNames = $this->getContractReferencedParameterNames($function);
		$errors = [];
		foreach ($parameters as $parameter) {
			// a promoted parameter is a property - its value is always used
			if ($parameter->flags !== 0) {
				continue;
			}
			if (!$parameter->var instanceof Variable || !is_string($parameter->var->name)) {
				continue;
			}
			if (isset($contractParameterNames[$parameter->var->name])) {
				continue;
			}
			$write = $node->getWriteForNode($parameter->var);
			if ($write !== null) {
				if ($node->isRead($write) || $node->areAllVariableNamesReferenced()) {
					continue;
				}
			} elseif ($node->isVariableReferenced($parameter->var->name)) {
				continue;
			}

			$errorBuilder = RuleErrorBuilder::message(sprintf($unusedParameterMessage, $parameter->var->name))
				->identifier($identifier);
			if ($reportExactLine) {
				$errorBuilder->line($parameter->var->getStartLine());
			}
			$errors[] = $errorBuilder->build();
		}

		return $errors;
	}

	/**
	 * Parameters the signature's PhpDoc contract refers to - the subject of a
	 * phpstan-assert tag, or a parameter a conditional type in the signature
	 * switches on. They serve the call site even when the body ignores them.
	 *
	 * @return array<string, true>
	 */
	private function getContractReferencedParameterNames(PhpFunctionFromParserNodeReflection $function): array
	{
		$names = [];
		foreach ($function->getAsserts()->getAll() as $assert) {
			$names[ltrim($assert->getParameter()->getParameterName(), '$')] = true;
		}
		$variant = $function->getOnlyVariant();
		$typesToScan = [$variant->getReturnType()];
		foreach ($variant->getParameters() as $parameterReflection) {
			$typesToScan[] = $parameterReflection->getType();
		}
		foreach ($typesToScan as $typeToScan) {
			TypeTraverser::map($typeToScan, static function (Type $type, callable $traverse) use (&$names): Type {
				if ($type instanceof ConditionalTypeForParameter) {
					$names[ltrim($type->getParameterName(), '$')] = true;
				}

				return $traverse($type);
			});
		}

		return $names;
	}

}
