<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Error;
use Exception;
use Iterator;
use IteratorAggregate;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Throwable;
use Traversable;
use function sprintf;
use function strtolower;

/**
 * @implements Rule<InClassNode>
 */
class ForbiddenInterfacesInClassImplementsRule implements Rule
{

	public function __construct(private PhpVersion $phpVersion)
	{
	}

	public function getNodeType(): string
	{
		return InClassNode::class;
	}

	/**
	 * @param InClassNode $node
	 */
	public function processNode(Node $node, Scope $scope): array
	{
		$classLikeNode = $node->getOriginalNode();
		$classReflection = $node->getClassReflection();

		if ($classReflection->isInterface()) {
			return [];
		}

		if (!$classLikeNode instanceof Node\Stmt\Class_) {
			return [];
		}

		if ($this->implementsInterface($classLikeNode, Traversable::class)) {
			if ($classReflection->isAbstract() && $this->phpVersion->allowsAbstractClassesExtendingTraversable()) {
				return [];
			}

			if ($classReflection->implementsInterface(Iterator::class) || $classReflection->implementsInterface(IteratorAggregate::class)) {
				return [
					RuleErrorBuilder::message(sprintf(
						'Redundant `implements Traversable`. %s already implements %s which extends Traversable.',
						$this->getDisplayableName($classReflection),
						$classReflection->implementsInterface(Iterator::class) ? 'Iterator' : 'IteratorAggregate',
					))
					->build(),
				];
			}

			return [
				RuleErrorBuilder::message(sprintf(
					'%s cannot implement Traversable.',
					$this->getDisplayableName($classReflection),
				))
				->nonIgnorable()
				->tip('Implement either one of IteratorAggregate or Iterator instead.')
				->build(),
			];
		}

		if ($this->implementsInterface($classLikeNode, DateTimeInterface::class)) {
			if ($classReflection->isSubclassOf(DateTime::class) || $classReflection->isSubclassOf(DateTimeImmutable::class)) {
				return [
					RuleErrorBuilder::message(sprintf(
						'Redundant `implements DateTimeInterface`. %s already extends %s which implements DateTimeInterface.',
						$this->getDisplayableName($classReflection),
						$classReflection->isSubclassOf(DateTime::class) ? 'DateTime' : 'DateTimeImmutable',
					))
					->build(),
				];
			}

			return [
				RuleErrorBuilder::message(sprintf(
					'%s cannot implement DateTimeInterface.',
					$this->getDisplayableName($classReflection),
				))->nonIgnorable()->tip('Extend either one of DateTime or DateTimeImmutable instead.')->build(),
			];
		}

		if ($this->implementsInterface($classLikeNode, Throwable::class)) {
			if ($classReflection->isSubclassOf(Exception::class) || $classReflection->isSubclassOf(Error::class)) { // @phpcs:ignore
				return [
					RuleErrorBuilder::message(sprintf(
						'Redundant `implements Throwable`. %s already extends %s which implements Throwable.',
						$this->getDisplayableName($classReflection),
						$classReflection->isSubclassOf(Error::class) ? 'Error' : 'Exception',
					))
					->build(),
				];
			}

			return [
				RuleErrorBuilder::message(sprintf(
					'%s cannot implement Throwable.',
					$this->getDisplayableName($classReflection),
				))->nonIgnorable()->tip('Extend either one of Exception or Error instead.')->build(),
			];
		}

		return [];
	}

	/**
	 * @param class-string $interfaceName
	 */
	private function implementsInterface(Node\Stmt\Class_ $class, string $interfaceName): bool
	{
		foreach ($class->implements as $interface) {
			if ($interface->toLowerString() === strtolower($interfaceName)) {
				return true;
			}
		}

		return false;
	}

	private function getDisplayableName(ClassReflection $classReflection): string
	{
		return $classReflection->isAnonymous() ? 'Anonymous class' : sprintf('Class %s', $classReflection->getDisplayName());
	}

}
