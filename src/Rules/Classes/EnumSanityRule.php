<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\VerbosityLevel;
use Serializable;
use function array_key_exists;
use function count;
use function implode;
use function sprintf;

/**
 * @implements Rule<Node\Stmt\Enum_>
 */
class EnumSanityRule implements Rule
{

	private const ALLOWED_MAGIC_METHODS = [
		'__call' => true,
		'__callstatic' => true,
		'__invoke' => true,
	];

	public function __construct(
		private ReflectionProvider $reflectionProvider,
	)
	{
	}

	public function getNodeType(): string
	{
		return Node\Stmt\Enum_::class;
	}

	/**
	 * @param Node\Stmt\Enum_ $node
	 */
	public function processNode(Node $node, Scope $scope): array
	{
		$errors = [];

		if ($node->namespacedName === null) {
			throw new ShouldNotHappenException();
		}

		foreach ($node->getMethods() as $methodNode) {
			if ($methodNode->isAbstract()) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'Enum %s contains abstract method %s().',
					$node->namespacedName->toString(),
					$methodNode->name->name,
				))->line($methodNode->getLine())->nonIgnorable()->build();
			}

			$lowercasedMethodName = $methodNode->name->toLowerString();

			if ($methodNode->isMagic()) {
				if ($lowercasedMethodName === '__construct') {
					$errors[] = RuleErrorBuilder::message(sprintf(
						'Enum %s contains constructor.',
						$node->namespacedName->toString(),
					))->line($methodNode->getLine())->nonIgnorable()->build();
				} elseif ($lowercasedMethodName === '__destruct') {
					$errors[] = RuleErrorBuilder::message(sprintf(
						'Enum %s contains destructor.',
						$node->namespacedName->toString(),
					))->line($methodNode->getLine())->nonIgnorable()->build();
				} elseif (!array_key_exists($lowercasedMethodName, self::ALLOWED_MAGIC_METHODS)) {
					$errors[] = RuleErrorBuilder::message(sprintf(
						'Enum %s contains magic method %s().',
						$node->namespacedName->toString(),
						$methodNode->name->name,
					))->line($methodNode->getLine())->nonIgnorable()->build();
				}
			}

			if ($lowercasedMethodName === 'cases') {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'Enum %s cannot redeclare native method %s().',
					$node->namespacedName->toString(),
					$methodNode->name->name,
				))->line($methodNode->getLine())->nonIgnorable()->build();
			}

			if ($node->scalarType === null) {
				continue;
			}

			if ($lowercasedMethodName !== 'from' && $lowercasedMethodName !== 'tryfrom') {
				continue;
			}

			$errors[] = RuleErrorBuilder::message(sprintf(
				'Enum %s cannot redeclare native method %s().',
				$node->namespacedName->toString(),
				$methodNode->name->name,
			))->line($methodNode->getLine())->nonIgnorable()->build();
		}

		if (
			$node->scalarType !== null
			&& $node->scalarType->name !== 'int'
			&& $node->scalarType->name !== 'string'
		) {
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Backed enum %s can have only "int" or "string" type.',
				$node->namespacedName->toString(),
			))->line($node->scalarType->getLine())->nonIgnorable()->build();
		}

		if ($this->reflectionProvider->hasClass($node->namespacedName->toString())) {
			$classReflection = $this->reflectionProvider->getClass($node->namespacedName->toString());

			if ($classReflection->implementsInterface(Serializable::class)) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'Enum %s cannot implement the Serializable interface.',
					$node->namespacedName->toString(),
				))->line($node->getLine())->nonIgnorable()->build();
			}
		}

		$enumCases = [];
		foreach ($node->stmts as $stmt) {
			if (!$stmt instanceof Node\Stmt\EnumCase) {
				continue;
			}
			$caseName = $stmt->name->name;

			if (($stmt->expr instanceof Node\Scalar\LNumber || $stmt->expr instanceof Node\Scalar\String_)) {
				if ($node->scalarType === null) {
					$errors[] = RuleErrorBuilder::message(sprintf(
						'Enum %s is not backed, but case %s has value %s.',
						$node->namespacedName->toString(),
						$caseName,
						$stmt->expr->value,
					))
						->identifier('enum.caseWithValue')
						->line($stmt->getLine())
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

			if ($node->scalarType === null) {
				continue;
			}

			if ($stmt->expr === null) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'Enum case %s::%s does not have a value but the enum is backed with the "%s" type.',
					$node->namespacedName->toString(),
					$caseName,
					$node->scalarType->name,
				))
					->identifier('enum.missingCase')
					->line($stmt->getLine())
					->nonIgnorable()
					->build();
				continue;
			}

			if ($node->scalarType->name === 'int' && !($stmt->expr instanceof Node\Scalar\LNumber)) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'Enum case %s::%s type %s does not match the "int" type.',
					$node->namespacedName->toString(),
					$caseName,
					$scope->getType($stmt->expr)->describe(VerbosityLevel::typeOnly()),
				))
					->identifier('enum.caseType')
					->line($stmt->getLine())
					->nonIgnorable()
					->build();
			}

			$isStringBackedWithoutStringCase = $node->scalarType->name === 'string' && !($stmt->expr instanceof Node\Scalar\String_);
			if (!$isStringBackedWithoutStringCase) {
				continue;
			}
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Enum case %s::%s type %s does not match the "string" type.',
				$node->namespacedName->toString(),
				$caseName,
				$scope->getType($stmt->expr)->describe(VerbosityLevel::typeOnly()),
			))
				->identifier('enum.caseType')
				->line($stmt->getLine())
				->nonIgnorable()
				->build();
		}

		foreach ($enumCases as $caseValue => $caseNames) {
			if (count($caseNames) <= 1) {
				continue;
			}

			$errors[] = RuleErrorBuilder::message(sprintf(
				'Enum %s has duplicate value %s for cases %s.',
				$node->namespacedName->toString(),
				$caseValue,
				implode(', ', $caseNames),
			))
				->identifier('enum.duplicateValue')
				->line($node->getLine())
				->nonIgnorable()
				->build();
		}

		return $errors;
	}

}
