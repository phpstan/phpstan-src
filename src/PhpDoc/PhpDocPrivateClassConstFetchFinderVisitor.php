<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use Nette\Utils\Strings;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\Analyser\Scope;
use PHPStan\Node\Constant\ClassConstantFetch;
use PHPStan\PhpDocParser\Ast\AbstractNodeVisitor;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstFetchNode;
use PHPStan\Reflection\ClassReflection;
use function preg_quote;
use function str_replace;

class PhpDocPrivateClassConstFetchFinderVisitor extends AbstractNodeVisitor
{

	/** @var list<ClassConstantFetch> */
	public array $classConstantFetches = [];

	public function __construct(private ClassReflection $classReflection, private Scope $scope)
	{
	}

	public function enterNode(\PHPStan\PhpDocParser\Ast\Node $node)
	{
		if (!$node instanceof ConstFetchNode) {
			return null;
		}

		if ($node->className !== 'self') {
			return null;
		}

		$constantName = $node->name;
		if (Strings::contains($constantName, '*')) {
			// convert * into .*? and escape everything else so the constants can be matched against the pattern
			$pattern = '{^' . str_replace('\\*', '.*?', preg_quote($constantName)) . '$}D';
			foreach ($this->classReflection->getNativeReflection()->getReflectionConstants() as $reflectionConstant) {
				$classConstantName = $reflectionConstant->getName();
				if (Strings::match($classConstantName, $pattern) === null) {
					continue;
				}

				$this->addClassConstantFetch($classConstantName);
			}
		} else {
			$this->addClassConstantFetch($constantName);
		}

		return null;
	}

	private function addClassConstantFetch(string $constantName): void
	{
		$this->classConstantFetches[] = new ClassConstantFetch(
			new Expr\ClassConstFetch(
				new Node\Name\FullyQualified($this->classReflection->getName()),
				$constantName,
			),
			$this->scope,
		);
	}

}
