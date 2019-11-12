<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Broker\Broker;
use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassNameNodePair;
use PHPStan\Rules\Generics\GenericObjectTypeCheck;
use PHPStan\Rules\MissingTypehintCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ErrorType;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\NeverType;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node>
 */
class InvalidPhpDocVarTagTypeRule implements Rule
{

	/** @var FileTypeMapper */
	private $fileTypeMapper;

	/** @var \PHPStan\Broker\Broker */
	private $broker;

	/** @var \PHPStan\Rules\ClassCaseSensitivityCheck */
	private $classCaseSensitivityCheck;

	/** @var \PHPStan\Rules\Generics\GenericObjectTypeCheck */
	private $genericObjectTypeCheck;

	/** @var MissingTypehintCheck */
	private $missingTypehintCheck;

	/** @var bool */
	private $checkClassCaseSensitivity;

	/** @var bool */
	private $checkMissingVarTagTypehint;

	public function __construct(
		FileTypeMapper $fileTypeMapper,
		Broker $broker,
		ClassCaseSensitivityCheck $classCaseSensitivityCheck,
		GenericObjectTypeCheck $genericObjectTypeCheck,
		MissingTypehintCheck $missingTypehintCheck,
		bool $checkClassCaseSensitivity,
		bool $checkMissingVarTagTypehint
	)
	{
		$this->fileTypeMapper = $fileTypeMapper;
		$this->broker = $broker;
		$this->classCaseSensitivityCheck = $classCaseSensitivityCheck;
		$this->genericObjectTypeCheck = $genericObjectTypeCheck;
		$this->missingTypehintCheck = $missingTypehintCheck;
		$this->checkClassCaseSensitivity = $checkClassCaseSensitivity;
		$this->checkMissingVarTagTypehint = $checkMissingVarTagTypehint;
	}

	public function getNodeType(): string
	{
		return \PhpParser\Node::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (
			!$node instanceof Node\Stmt\Foreach_
			&& !$node instanceof Node\Expr\Assign
			&& !$node instanceof Node\Expr\AssignRef
			&& !$node instanceof Node\Stmt\Static_
		) {
			return [];
		}

		$docComment = $node->getDocComment();
		if ($docComment === null) {
			return [];
		}

		$function = $scope->getFunction();
		$resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
			$scope->getFile(),
			$scope->isInClass() ? $scope->getClassReflection()->getName() : null,
			$scope->isInTrait() ? $scope->getTraitReflection()->getName() : null,
			$function !== null ? $function->getName() : null,
			$docComment->getText()
		);

		$errors = [];
		foreach ($resolvedPhpDoc->getVarTags() as $name => $varTag) {
			$varTagType = $varTag->getType();
			$identifier = 'PHPDoc tag @var';
			if (is_string($name)) {
				$identifier .= sprintf(' for variable $%s', $name);
			}
			if (
				$varTagType instanceof ErrorType
				|| ($varTagType instanceof NeverType && !$varTagType->isExplicit())
			) {
				$errors[] = RuleErrorBuilder::message(sprintf('%s contains unresolvable type.', $identifier))->line($docComment->getLine())->build();
				continue;
			}

			if ($this->checkMissingVarTagTypehint) {
				foreach ($this->missingTypehintCheck->getIterableTypesWithMissingValueTypehint($varTagType) as $iterableType) {
					$errors[] = RuleErrorBuilder::message(sprintf(
						'%s has no value type specified in iterable type %s.',
						$identifier,
						$iterableType->describe(VerbosityLevel::typeOnly())
					))->build();
				}
			}

			$errors = array_merge($errors, $this->genericObjectTypeCheck->check(
				$varTagType,
				sprintf('%s contains generic type %%s but class %%s is not generic.', $identifier),
				sprintf('Generic type %%s in %s does not specify all template types of class %%s: %%s', $identifier),
				sprintf('Generic type %%s in %s specifies %%d template types, but class %%s supports only %%d: %%s', $identifier),
				sprintf('Type %%s in generic type %%s in %s is not subtype of template type %%s of class %%s.', $identifier)
			));

			foreach ($this->missingTypehintCheck->getNonGenericObjectTypesWithGenericClass($varTagType) as [$innerName, $genericTypeNames]) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'%s contains generic %s but does not specify its types: %s',
					$identifier,
					$innerName,
					implode(', ', $genericTypeNames)
				))->tip(MissingTypehintCheck::TURN_OFF_NON_GENERIC_CHECK_TIP)->build();
			}

			$referencedClasses = $varTagType->getReferencedClasses();
			foreach ($referencedClasses as $referencedClass) {
				if ($this->broker->hasClass($referencedClass)) {
					if ($this->broker->getClass($referencedClass)->isTrait()) {
						$errors[] = RuleErrorBuilder::message(sprintf(
							sprintf('%s has invalid type %%s.', $identifier),
							$referencedClass
						))->build();
					}
					continue;
				}

				$errors[] = RuleErrorBuilder::message(sprintf(
					sprintf('%s contains unknown class %%s.', $identifier),
					$referencedClass
				))->build();
			}

			if (!$this->checkClassCaseSensitivity) {
				continue;
			}

			$errors = array_merge(
				$errors,
				$this->classCaseSensitivityCheck->checkClassNames(array_map(static function (string $class) use ($node): ClassNameNodePair {
					return new ClassNameNodePair($class, $node);
				}, $referencedClasses))
			);
		}

		return $errors;
	}

}
