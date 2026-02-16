<?php declare(strict_types = 1);

namespace PHPStan\Rules\Operators;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use function get_class;
use function sprintf;

/**
 * @implements Rule<Node\Expr>
 */
#[RegisteredRule(level: 0)]
final class InvalidIncDecOperationRule implements Rule
{

	public function __construct(
		private RuleLevelHelper $ruleLevelHelper,
		private PhpVersion $phpVersion,
	)
	{
	}

	public function getNodeType(): string
	{
		return Node\Expr::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (
			!$node instanceof Node\Expr\PreInc
			&& !$node instanceof Node\Expr\PostInc
			&& !$node instanceof Node\Expr\PreDec
			&& !$node instanceof Node\Expr\PostDec
		) {
			return [];
		}

		switch (get_class($node)) {
			case Node\Expr\PreInc::class:
				$nodeType = 'preInc';
				break;
			case Node\Expr\PostInc::class:
				$nodeType = 'postInc';
				break;
			case Node\Expr\PreDec::class:
				$nodeType = 'preDec';
				break;
			case Node\Expr\PostDec::class:
				$nodeType = 'postDec';
				break;
			default:
				throw new ShouldNotHappenException();
		}

		$operatorString = $node instanceof Node\Expr\PreInc || $node instanceof Node\Expr\PostInc ? '++' : '--';

		if (
			!$node->var instanceof Node\Expr\Variable
			&& !$node->var instanceof Node\Expr\ArrayDimFetch
			&& !$node->var instanceof Node\Expr\PropertyFetch
			&& !$node->var instanceof Node\Expr\StaticPropertyFetch
		) {
			return [
				RuleErrorBuilder::message(sprintf(
					'Cannot use %s on a non-variable.',
					$operatorString,
				))
					->line($node->var->getStartLine())
					->identifier(sprintf('%s.expr', $nodeType))
					->build(),
			];
		}

		$string = new StringType();
		$deprecatedString = false;
		if (
			(
				$this->phpVersion->deprecatesDecOnNonNumericString()
				&& (
					$node instanceof Node\Expr\PreDec
					|| $node instanceof Node\Expr\PostDec
				)
			) || (
				$this->phpVersion->deprecatesIncOnNonNumericString()
				&& (
					$node instanceof Node\Expr\PreInc
					|| $node instanceof Node\Expr\PostInc
				)
			)
		) {
			$string = new IntersectionType([$string, new AccessoryNumericStringType()]);
			$deprecatedString = true;
		}

		$allowedTypes = [new FloatType(), new IntegerType(), $string, new ObjectType('SimpleXMLElement')];
		$deprecatedNull = false;
		if (
			!$this->phpVersion->deprecatesDecOnNonNumericString()
			|| $node instanceof Node\Expr\PreInc
			|| $node instanceof Node\Expr\PostInc
		) {
			$allowedTypes[] = new NullType();
		} else {
			$deprecatedNull = true;
		}

		$deprecatedBool = false;
		if (!$this->phpVersion->deprecatesDecOnNonNumericString()) {
			$allowedTypes[] = new BooleanType();
		} else {
			$deprecatedBool = true;
		}

		if ($this->phpVersion->supportsBcMathNumberOperatorOverloading()) {
			$allowedTypes[] = new ObjectType('BcMath\Number');
		}

		$allowedTypes = new UnionType($allowedTypes);

		$varType = $this->ruleLevelHelper->findTypeToCheck(
			$scope,
			$node->var,
			'',
			static fn (Type $type): bool => $allowedTypes->isSuperTypeOf($type)->yes(),
		)->getType();

		if ($varType instanceof ErrorType || $allowedTypes->isSuperTypeOf($varType)->yes()) {
			return [];
		}

		$errorBuilder = RuleErrorBuilder::message(sprintf(
			'Cannot use %s on %s.',
			$operatorString,
			$varType->describe(VerbosityLevel::value()),
		))
			->line($node->var->getStartLine())
			->identifier(sprintf('%s.type', $nodeType));

		if (!$varType->isString()->no() && $deprecatedString) {
			$errorBuilder->addTip(sprintf(
				'Operator %s is deprecated for non-numeric-strings. Either narrow the type to numeric-string, or use %s().',
				$operatorString,
				$node instanceof Node\Expr\PreDec || $node instanceof Node\Expr\PostDec ? 'str_decrement' : 'str_increment',
			));
		}
		if (!$varType->isNull()->no() && $deprecatedNull) {
			$errorBuilder->addTip(sprintf('Operator %s is deprecated for null.', $operatorString));
		}

		if (!$varType->isBoolean()->no() && $deprecatedBool) {
			$errorBuilder->addTip(sprintf('Operator %s is deprecated for %s.', $operatorString, TypeCombinator::intersect($varType, new BooleanType())->describe(VerbosityLevel::value())));
		}

		return [
			$errorBuilder->build(),
		];
	}

}
