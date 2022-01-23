<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Node\InClassNode;
use PHPStan\Node\InFunctionNode;
use PHPStan\Node\VirtualNode;
use PHPStan\PhpDoc\Tag\VarTag;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\FileTypeMapper;
use function array_keys;
use function array_map;
use function array_merge;
use function count;
use function implode;
use function in_array;
use function is_int;
use function is_string;
use function sprintf;

/**
 * @implements Rule<Node\Stmt>
 */
class WrongVariableNameInVarTagRule implements Rule
{

	public function __construct(
		private FileTypeMapper $fileTypeMapper,
	)
	{
	}

	public function getNodeType(): string
	{
		return Node\Stmt::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (
			$node instanceof Node\Stmt\Property
			|| $node instanceof Node\Stmt\PropertyProperty
			|| $node instanceof Node\Stmt\ClassConst
			|| $node instanceof Node\Stmt\Const_
			|| ($node instanceof VirtualNode && !$node instanceof InFunctionNode && !$node instanceof InClassMethodNode && !$node instanceof InClassNode)
		) {
			return [];
		}

		$varTags = [];
		$function = $scope->getFunction();
		foreach ($node->getComments() as $comment) {
			if (!$comment instanceof Doc) {
				continue;
			}
			$resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
				$scope->getFile(),
				$scope->isInClass() ? $scope->getClassReflection()->getName() : null,
				$scope->isInTrait() ? $scope->getTraitReflection()->getName() : null,
				$function !== null ? $function->getName() : null,
				$comment->getText(),
			);
			foreach ($resolvedPhpDoc->getVarTags() as $key => $varTag) {
				$varTags[$key] = $varTag;
			}
		}

		if (count($varTags) === 0) {
			return [];
		}

		if ($node instanceof Node\Stmt\Foreach_) {
			return $this->processForeach($node->expr, $node->keyVar, $node->valueVar, $varTags);
		}

		if ($node instanceof Node\Stmt\Static_) {
			return $this->processStatic($node->vars, $varTags);
		}

		if ($node instanceof Node\Stmt\Expression) {
			return $this->processExpression($scope, $node->expr, $varTags);
		}

		if ($node instanceof Node\Stmt\Throw_ || $node instanceof Node\Stmt\Return_) {
			return $this->processStmt($scope, $varTags, $node->expr);
		}

		if ($node instanceof Node\Stmt\Global_) {
			return $this->processGlobal($scope, $node, $varTags);
		}

		if ($node instanceof InClassNode || $node instanceof InClassMethodNode || $node instanceof InFunctionNode) {
			$description = 'a function';
			$originalNode = $node->getOriginalNode();
			if ($originalNode instanceof Node\Stmt\Interface_) {
				$description = 'an interface';
			} elseif ($originalNode instanceof Node\Stmt\Class_) {
				$description = 'a class';
			} elseif ($originalNode instanceof Node\Stmt\Enum_) {
				$description = 'an enum';
			} elseif ($originalNode instanceof Node\Stmt\Trait_) {
				throw new ShouldNotHappenException();
			} elseif ($originalNode instanceof Node\Stmt\ClassMethod) {
				$description = 'a method';
			}

			return [
				RuleErrorBuilder::message(sprintf(
					'PHPDoc tag @var above %s has no effect.',
					$description,
				))->build(),
			];
		}

		return $this->processStmt($scope, $varTags, null);
	}

	/**
	 * @param VarTag[] $varTags
	 * @return RuleError[]
	 */
	private function processAssign(Scope $scope, Node\Expr $var, array $varTags): array
	{
		$errors = [];
		$hasMultipleMessage = false;
		$assignedVariables = $this->getAssignedVariables($var);
		foreach (array_keys($varTags) as $key) {
			if (is_int($key)) {
				if (count($varTags) !== 1) {
					if (!$hasMultipleMessage) {
						$errors[] = RuleErrorBuilder::message('Multiple PHPDoc @var tags above single variable assignment are not supported.')->build();
						$hasMultipleMessage = true;
					}
				} elseif (count($assignedVariables) !== 1) {
					$errors[] = RuleErrorBuilder::message(
						'PHPDoc tag @var above assignment does not specify variable name.',
					)->build();
				}
				continue;
			}

			if (!$scope->hasVariableType($key)->no()) {
				continue;
			}

			if (in_array($key, $assignedVariables, true)) {
				continue;
			}

			if (count($assignedVariables) === 1 && count($varTags) === 1) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'Variable $%s in PHPDoc tag @var does not match assigned variable $%s.',
					$key,
					$assignedVariables[0],
				))->build();
			} else {
				$errors[] = RuleErrorBuilder::message(sprintf('Variable $%s in PHPDoc tag @var does not exist.', $key))->build();
			}
		}

		return $errors;
	}

	/**
	 * @return string[]
	 */
	private function getAssignedVariables(Expr $expr): array
	{
		if ($expr instanceof Expr\Variable) {
			if (is_string($expr->name)) {
				return [$expr->name];
			}

			return [];
		}

		if ($expr instanceof Expr\List_ || $expr instanceof Expr\Array_) {
			$names = [];
			foreach ($expr->items as $item) {
				if ($item === null) {
					continue;
				}

				$names = array_merge($names, $this->getAssignedVariables($item->value));
			}

			return $names;
		}

		return [];
	}

	/**
	 * @param VarTag[] $varTags
	 * @return RuleError[]
	 */
	private function processForeach(Node\Expr $iterateeExpr, ?Node\Expr $keyVar, Node\Expr $valueVar, array $varTags): array
	{
		$variableNames = [];
		if ($iterateeExpr instanceof Node\Expr\Variable && is_string($iterateeExpr->name)) {
			$variableNames[] = $iterateeExpr->name;
		}
		if ($keyVar instanceof Node\Expr\Variable && is_string($keyVar->name)) {
			$variableNames[] = $keyVar->name;
		}
		$variableNames = array_merge($variableNames, $this->getAssignedVariables($valueVar));

		$errors = [];
		foreach (array_keys($varTags) as $name) {
			if (is_int($name)) {
				if (count($variableNames) === 1) {
					continue;
				}
				$errors[] = RuleErrorBuilder::message(
					'PHPDoc tag @var above foreach loop does not specify variable name.',
				)->build();
				continue;
			}

			if (in_array($name, $variableNames, true)) {
				continue;
			}

			$errors[] = RuleErrorBuilder::message(sprintf(
				'Variable $%s in PHPDoc tag @var does not match any variable in the foreach loop: %s',
				$name,
				implode(', ', array_map(static fn (string $name): string => sprintf('$%s', $name), $variableNames)),
			))->build();
		}

		return $errors;
	}

	/**
	 * @param Node\Stmt\StaticVar[] $vars
	 * @param VarTag[] $varTags
	 * @return RuleError[]
	 */
	private function processStatic(array $vars, array $varTags): array
	{
		$variableNames = [];
		foreach ($vars as $var) {
			if (!is_string($var->var->name)) {
				continue;
			}

			$variableNames[$var->var->name] = true;
		}

		$errors = [];
		foreach (array_keys($varTags) as $name) {
			if (is_int($name)) {
				if (count($vars) === 1) {
					continue;
				}

				$errors[] = RuleErrorBuilder::message(
					'PHPDoc tag @var above multiple static variables does not specify variable name.',
				)->build();
				continue;
			}

			if (isset($variableNames[$name])) {
				continue;
			}

			$errors[] = RuleErrorBuilder::message(sprintf(
				'Variable $%s in PHPDoc tag @var does not match any static variable: %s',
				$name,
				implode(', ', array_map(static fn (string $name): string => sprintf('$%s', $name), array_keys($variableNames))),
			))->build();
		}

		return $errors;
	}

	/**
	 * @param VarTag[] $varTags
	 * @return RuleError[]
	 */
	private function processExpression(Scope $scope, Expr $expr, array $varTags): array
	{
		if ($expr instanceof Node\Expr\Assign || $expr instanceof Node\Expr\AssignRef) {
			return $this->processAssign($scope, $expr->var, $varTags);
		}

		return $this->processStmt($scope, $varTags, null);
	}

	/**
	 * @param VarTag[] $varTags
	 * @return RuleError[]
	 */
	private function processStmt(Scope $scope, array $varTags, ?Expr $defaultExpr): array
	{
		$errors = [];

		$variableLessVarTags = [];
		foreach ($varTags as $name => $varTag) {
			if (is_int($name)) {
				$variableLessVarTags[] = $varTag;
				continue;
			}

			if (!$scope->hasVariableType($name)->no()) {
				continue;
			}

			$errors[] = RuleErrorBuilder::message(sprintf('Variable $%s in PHPDoc tag @var does not exist.', $name))->build();
		}

		if (count($variableLessVarTags) !== 1 || $defaultExpr === null) {
			if (count($variableLessVarTags) > 0) {
				$errors[] = RuleErrorBuilder::message('PHPDoc tag @var does not specify variable name.')->build();
			}
		}

		return $errors;
	}

	/**
	 * @param VarTag[] $varTags
	 * @return RuleError[]
	 */
	private function processGlobal(Scope $scope, Node\Stmt\Global_ $node, array $varTags): array
	{
		$variableNames = [];
		foreach ($node->vars as $var) {
			if (!$var instanceof Expr\Variable) {
				continue;
			}
			if (!is_string($var->name)) {
				continue;
			}

			$variableNames[$var->name] = true;
		}

		$errors = [];
		foreach (array_keys($varTags) as $name) {
			if (is_int($name)) {
				if (count($variableNames) === 1) {
					continue;
				}

				$errors[] = RuleErrorBuilder::message(
					'PHPDoc tag @var above multiple global variables does not specify variable name.',
				)->build();
				continue;
			}

			if (isset($variableNames[$name])) {
				continue;
			}

			$errors[] = RuleErrorBuilder::message(sprintf(
				'Variable $%s in PHPDoc tag @var does not match any global variable: %s',
				$name,
				implode(', ', array_map(static fn (string $name): string => sprintf('$%s', $name), array_keys($variableNames))),
			))->build();
		}

		return $errors;
	}

}
