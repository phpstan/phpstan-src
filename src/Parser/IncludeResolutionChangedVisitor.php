<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use Override;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PHPStan\DependencyInjection\AutowiredService;
use function in_array;
use function strtolower;

/**
 * Where an `include`/`require` path ends up depends on the working directory, on the `include_path`
 * setting and on the registered stream wrappers, all of which the analysed file can change at
 * runtime. This marks every `Include_` node that such a call appears before in the same file, so
 * that RequireFileExistsRule can treat its path as one that does not resolve to a known place
 * instead of assuming the state of PHPStan's own process.
 */
#[AutowiredService]
final class IncludeResolutionChangedVisitor extends NodeVisitorAbstract
{

	/** Holds `true` when a call earlier in the file changed where a path resolves. */
	public const ATTRIBUTE_NAME = 'includeResolutionChanged';

	private const FUNCTION_NAMES = [
		'chdir',
		'set_include_path',
		'stream_wrapper_register',
		'ini_set',
		'ini_alter',
	];

	private bool $changed = false;

	#[Override]
	public function beforeTraverse(array $nodes): ?array
	{
		$this->changed = false;

		return null;
	}

	#[Override]
	public function enterNode(Node $node): ?Node
	{
		if ($node instanceof Node\Expr\Include_) {
			if ($this->changed) {
				$node->setAttribute(self::ATTRIBUTE_NAME, true);
			}

			return null;
		}

		if (
			!$node instanceof Node\Expr\FuncCall
			|| !$node->name instanceof Node\Name
			|| $node->isFirstClassCallable()
		) {
			return null;
		}

		$functionName = $node->name->toLowerString();
		if (!in_array($functionName, self::FUNCTION_NAMES, true)) {
			return null;
		}

		if (
			($functionName === 'ini_set' || $functionName === 'ini_alter')
			&& !$this->couldSetIncludePath($node)
		) {
			return null;
		}

		$this->changed = true;

		return null;
	}

	/**
	 * An `ini_set()` of an unrelated option such as `memory_limit` leaves include resolution alone.
	 * An option name that is not a literal string could be `include_path` just as well as anything
	 * else.
	 */
	private function couldSetIncludePath(Node\Expr\FuncCall $node): bool
	{
		$args = $node->getArgs();
		if ($args === []) {
			return false;
		}

		$optionName = $args[0]->value;
		if (!$optionName instanceof Node\Scalar\String_) {
			return true;
		}

		return strtolower($optionName->value) === 'include_path';
	}

}
