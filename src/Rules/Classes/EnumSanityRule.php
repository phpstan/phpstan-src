<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\IntegerType;
use PHPStan\Type\StringType;
use PHPStan\Type\VerbosityLevel;
use Serializable;
use function array_key_exists;
use function count;
use function implode;
use function in_array;
use function sprintf;

/**
 * @implements Rule<InClassNode>
 */
class EnumSanityRule implements Rule
{

	private const ALLOWED_MAGIC_METHODS = [
		'__call' => true,
		'__callstatic' => true,
		'__invoke' => true,
	];

	public function getNodeType(): string
	{
		return InClassNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$classReflection = $node->getClassReflection();
		if (!$classReflection->isEnum()) {
			return [];
		}

		/** @var Node\Stmt\Enum_ $enumNode */
		$enumNode = $node->getOriginalNode();

		$errors = [];

		foreach ($enumNode->getMethods() as $methodNode) {
			if ($methodNode->isAbstract()) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'Enum %s contains abstract method %s().',
					$classReflection->getDisplayName(),
					$methodNode->name->name,
				))
					->identifier('enum.abstractMethod')
					->line($methodNode->getStartLine())
					->nonIgnorable()
					->build();
			}

			$lowercasedMethodName = $methodNode->name->toLowerString();

			if ($methodNode->isMagic()) {
				if ($lowercasedMethodName === '__construct') {
					$errors[] = RuleErrorBuilder::message(sprintf(
						'Enum %s contains constructor.',
						$classReflection->getDisplayName(),
					))
						->identifier('enum.constructor')
						->line($methodNode->getStartLine())
						->nonIgnorable()
						->build();
				} elseif ($lowercasedMethodName === '__destruct') {
					$errors[] = RuleErrorBuilder::message(sprintf(
						'Enum %s contains destructor.',
						$classReflection->getDisplayName(),
					))
						->identifier('enum.destructor')
						->line($methodNode->getStartLine())
						->nonIgnorable()
						->build();
				} elseif (!array_key_exists($lowercasedMethodName, self::ALLOWED_MAGIC_METHODS)) {
					$errors[] = RuleErrorBuilder::message(sprintf(
						'Enum %s contains magic method %s().',
						$classReflection->getDisplayName(),
						$methodNode->name->name,
					))
						->identifier('enum.magicMethod')
						->line($methodNode->getStartLine())
						->nonIgnorable()
						->build();
				}
			}

			if ($lowercasedMethodName === 'cases') {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'Enum %s cannot redeclare native method %s().',
					$classReflection->getDisplayName(),
					$methodNode->name->name,
				))
					->identifier('enum.methodRedeclaration')
					->line($methodNode->getStartLine())
					->nonIgnorable()
					->build();
			}

			if ($enumNode->scalarType === null) {
				continue;
			}

			if (!in_array($lowercasedMethodName, ['from', 'tryfrom'], true)) {
				continue;
			}

			$errors[] = RuleErrorBuilder::message(sprintf(
				'Enum %s cannot redeclare native method %s().',
				$classReflection->getDisplayName(),
				$methodNode->name->name,
			))
				->identifier('enum.methodRedeclaration')
				->line($methodNode->getStartLine())
				->nonIgnorable()
				->build();
		}

		if (
			$enumNode->scalarType !== null
			&& !in_array($enumNode->scalarType->name, ['int', 'string'], true)
		) {
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Backed enum %s can have only "int" or "string" type.',
				$classReflection->getDisplayName(),
			))
				->identifier('enum.backingType')
				->line($enumNode->scalarType->getStartLine())
				->nonIgnorable()
				->build();
		}

		if ($classReflection->implementsInterface(Serializable::class)) {
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Enum %s cannot implement the Serializable interface.',
				$classReflection->getDisplayName(),
			))
				->identifier('enum.serializable')
				->line($enumNode->getStartLine())
				->nonIgnorable()
				->build();
		}

		$enumCases = [];
		foreach ($enumNode->stmts as $stmt) {
			if (!$stmt instanceof Node\Stmt\EnumCase) {
				continue;
			}
			$caseName = $stmt->name->name;

			if ($stmt->expr instanceof Node\Scalar\Int_ || $stmt->expr instanceof Node\Scalar\String_) {
				if ($enumNode->scalarType === null) {
					$errors[] = RuleErrorBuilder::message(sprintf(
						'Enum %s is not backed, but case %s has value %s.',
						$classReflection->getDisplayName(),
						$caseName,
						$stmt->expr->value,
					))
						->identifier('enum.caseWithValue')
						->line($stmt->getStartLine())
						->nonIgnorable()
						->build();
				} else {
					$caseValue = $stmt->expr->value;

					if (!isset($enumCases[$caseValue])) {
						$enumCases[$caseValue] = [];
					}

					$enumCases[$caseValue][] = $caseName;
				}
			}

			if ($enumNode->scalarType === null) {
				continue;
			}

			if ($stmt->expr === null) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'Enum case %s::%s does not have a value but the enum is backed with the "%s" type.',
					$classReflection->getDisplayName(),
					$caseName,
					$enumNode->scalarType->name,
				))
					->identifier('enum.missingCase')
					->line($stmt->getStartLine())
					->nonIgnorable()
					->build();
				continue;
			}

			$exprType = $scope->getType($stmt->expr);
			$scalarType = $enumNode->scalarType->toLowerString() === 'int' ? new IntegerType() : new StringType();
			if ($scalarType->isSuperTypeOf($exprType)->yes()) {
				continue;
			}

			$errors[] = RuleErrorBuilder::message(sprintf(
				'Enum case %s::%s value %s does not match the "%s" type.',
				$classReflection->getDisplayName(),
				$caseName,
				$exprType->describe(VerbosityLevel::value()),
				$scalarType->describe(VerbosityLevel::typeOnly()),
			))
				->identifier('enum.caseType')
				->line($stmt->getStartLine())
				->nonIgnorable()
				->build();
		}

		foreach ($enumCases as $caseValue => $caseNames) {
			if (count($caseNames) <= 1) {
				continue;
			}

			$errors[] = RuleErrorBuilder::message(sprintf(
				'Enum %s has duplicate value %s for cases %s.',
				$classReflection->getDisplayName(),
				$caseValue,
				implode(', ', $caseNames),
			))
				->identifier('enum.duplicateValue')
				->line($enumNode->getStartLine())
				->nonIgnorable()
				->build();
		}

		return $errors;
	}

}
