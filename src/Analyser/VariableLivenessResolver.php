<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PHPStan\Node\Variable\VariableWrite;
use PHPStan\Node\VariableWritesNode;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use Throwable;
use function array_reverse;
use function array_values;
use function count;
use function in_array;
use function is_string;
use function spl_object_id;

/** Resolve liveness backwards over immutable body fragments. */
final class VariableLivenessResolver
{

	/** @var array<int, VariableWrite> */
	private array $writes = [];

	/** @var array<int, true> */
	private array $readIds = [];

	/** @var array<string, true> */
	private array $readNames = [];

	/** @var array<string, true> */
	private array $mentionedNames = [];

	/** @var array<string, true> */
	private array $escapedNames = [];

	/** @var array<int, Type> */
	private array $redundantTypes = [];

	private bool $opaque = false;

	private bool $allNamesMentioned = false;

	private bool $returnsByReference = false;

	private function __construct()
	{
	}

	public static function resolve(Node\FunctionLike $function, ?VariableFlow $flow): VariableWritesNode
	{
		$self = new self();
		$self->returnsByReference = $function->returnsByRef();
		$imports = [];
		foreach ($function->getParams() as $param) {
			if (!$param->var instanceof Node\Expr\Variable || !is_string($param->var->name)) {
				continue;
			}
			if ($param->byRef || $param->flags !== 0) {
				$self->escapedNames[$param->var->name] = true;
				continue;
			}
			$imports[] = VariableFlow::write(new VariableWrite($param->var->name, $param->var, spl_object_id($param->var), VariableWrite::KIND_PARAMETER));
		}
		if ($function instanceof Node\Expr\Closure) {
			foreach ($function->uses as $use) {
				if (!is_string($use->var->name)) {
					continue;
				}
				if ($use->byRef) {
					$self->escapedNames[$use->var->name] = true;
					continue;
				}
				$imports[] = VariableFlow::write(new VariableWrite($use->var->name, $use->var, spl_object_id($use->var), VariableWrite::KIND_CLOSURE_USE));
			}
		}
		$body = VariableFlow::sequence(...[...$imports, $flow]);
		$self->collect($body);
		if ($self->writes !== [] && !$self->opaque) {
			$self->liveBefore($body, [], new VariableFlowContext([]));
		}

		return new VariableWritesNode($function, array_values($self->writes), $self->readIds, $self->readNames, $self->redundantTypes, $self->mentionedNames, $self->escapedNames, $self->opaque, $self->allNamesMentioned);
	}

	private function collect(?VariableFlow $flow, bool $dead = false): void
	{
		if ($flow === null) {
			return;
		}
		if ($flow instanceof VariableAccessFlow && $flow->name !== 'this' && !in_array($flow->name, Scope::SUPERGLOBAL_VARIABLES, true)) {
			$this->mentionedNames[$flow->name] = true;
			if ($flow->kind === VariableFlow::READ) {
				$this->readNames[$flow->name] = true;
			} elseif ($flow->kind === VariableFlow::ESCAPE) {
				$this->escapedNames[$flow->name] = true;
			}
			if ($flow->write !== null) {
				$id = $flow->write->getId();
				$this->writes[$id] = $flow->write;
				if ($flow->type !== null) {
					$this->redundantTypes[$id] = $flow->type;
				}
				if ($dead) {
					$this->readIds[$id] = true;
				}
			}
		}
		if ($flow->kind === VariableFlow::OPAQUE) {
			$this->opaque = true;
		}
		if (in_array($flow->kind, [VariableFlow::READ_ALL, VariableFlow::MENTION_ALL], true)) {
			$this->allNamesMentioned = true;
		}
		if ($flow instanceof VariableAccessFlow) {
			return;
		}
		if (!$flow instanceof VariableSequenceFlow && !$flow instanceof VariableControlFlow) {
			throw new ShouldNotHappenException();
		}
		foreach ($flow->children as $child) {
			$this->collect($child, $dead || $flow->kind === VariableFlow::DEAD);
		}
		if (!$flow instanceof VariableControlFlow) {
			return;
		}
		if ($flow->kind === VariableFlow::RETURN && $flow->name !== null && $this->returnsByReference) {
			$this->escapedNames[$flow->name] = true;
		}
		foreach ($flow->cases as [$condition, $body]) {
			$this->collect($condition, $dead);
			$this->collect($body, $dead);
		}
		foreach ($flow->catches as [, $catch]) {
			$this->collect($catch, $dead);
		}
	}

	/**
	 * @param array<string, true> $next
	 * @return array<string, true>
	 */
	private function liveBefore(?VariableFlow $flow, array $next, VariableFlowContext $context): array
	{
		if ($flow === null || $flow->kind === VariableFlow::DEAD) {
			return $next;
		}
		if ($flow instanceof VariableAccessFlow) {
			if ($flow->kind === VariableFlow::READ) {
				$next[$flow->name] = true;
			} elseif ($flow->write !== null) {
				if (isset($next[$flow->name])) {
					$this->readIds[$flow->write->getId()] = true;
				}
				unset($next[$flow->name]);
			}
			return $next;
		}
		if ($flow instanceof VariableSequenceFlow) {
			if ($flow->kind === VariableFlow::SEQUENCE) {
				foreach (array_reverse($flow->children) as $child) {
					$next = $this->liveBefore($child, $next, $context);
				}
				return $next;
			}
			$names = [];
			foreach ($flow->children as $child) {
				$names += $this->liveBefore($child, $next, $context);
			}
			return $names;
		}
		if (!$flow instanceof VariableControlFlow) {
			throw new ShouldNotHappenException();
		}
		if ($flow->kind === VariableFlow::ARROW && $flow->arrow !== null) {
			$names = $this->liveBefore($flow->children[0], [], new VariableFlowContext([]));
			foreach ($flow->arrow->params as $param) {
				if (!$param->var instanceof Node\Expr\Variable || !is_string($param->var->name)) {
					continue;
				}
				unset($names[$param->var->name]);
			}
			return $next + $names;
		}
		if ($flow->kind === VariableFlow::LOOP) {
			$head = [];
			do {
				$previousCount = count($head);
				$update = $this->liveBefore($flow->children[2], $head, $context);
				$loopContext = new VariableFlowContext($context->return, [$next, ...$context->breaks], [$update, ...$context->continues], $context->catches, $context->uncaught);
				$body = $this->liveBefore($flow->children[1], $update, $loopContext);
				$afterCondition = $flow->canRepeat ? ($flow->canExit ? $body + $next : $body) : $next;
				$head = $this->liveBefore($flow->children[0], $afterCondition, $context);
			} while (count($head) !== $previousCount);

			return $flow->atLeastOnce ? $this->liveBefore($flow->children[0], $body, $context) : $head;
		}
		if ($flow->kind === VariableFlow::SWITCH) {
			$switchContext = new VariableFlowContext($context->return, [$next, ...$context->breaks], [$next, ...$context->continues], $context->catches, $context->uncaught);
			$entries = [];
			$caseNext = $next;
			$unmatched = $flow->canExit ? $next : [];
			for ($i = count($flow->cases) - 1; $i >= 0; $i--) {
				[, $body, $default] = $flow->cases[$i];
				$caseNext = $this->liveBefore($body, $caseNext, $switchContext);
				$entries[$i] = $caseNext;
				if (!$default) {
					continue;
				}

				$unmatched = $caseNext;
			}
			for ($i = count($flow->cases) - 1; $i >= 0; $i--) {
				[$condition, , $default] = $flow->cases[$i];
				if ($default) {
					continue;
				}
				$unmatched = $this->liveBefore($condition, $entries[$i] + $unmatched, $context);
			}
			return $this->liveBefore($flow->children[0], $unmatched, $context);
		}
		if ($flow->kind === VariableFlow::RETURN) {
			return $context->return;
		}
		if ($flow->kind === VariableFlow::BREAK) {
			return $context->breaks[$flow->level - 1] ?? [];
		}
		if ($flow->kind === VariableFlow::CONTINUE) {
			return $context->continues[$flow->level - 1] ?? [];
		}
		if ($flow->kind === VariableFlow::STOP) {
			return [];
		}
		if ($flow->kind === VariableFlow::THROW) {
			$names = $flow->canExit ? $next : [];
			if ($context->catches === []) {
				return $names + $context->uncaught;
			}
			if ($flow->type === null) {
				throw new ShouldNotHappenException();
			}
			if ($flow->canContainAnyThrowable) {
				foreach ($context->catches as [$catchType, $destination]) {
					if (!$catchType->isSuperTypeOf(new ObjectType(Throwable::class))->yes()) {
						continue;
					}
					$names += $destination;
					break;
				}
			}
			foreach ($context->catches as [$catchType, $destination]) {
				$accepts = $catchType->isSuperTypeOf($flow->type);
				if (!$accepts->no() || !$flow->type->isSuperTypeOf($catchType)->no()) {
					$names += $destination;
				}
				if ($accepts->yes()) {
					return $names;
				}
			}
			return $names + $context->uncaught;
		}
		if ($flow->kind === VariableFlow::TRY_CATCH) {
			$finally = $flow->children[1];
			$normal = $this->liveBefore($finally, $next, $context);
			$breaks = [];
			foreach ($context->breaks as $destination) {
				$breaks[] = $this->liveBefore($finally, $destination, $context);
			}
			$continues = [];
			foreach ($context->continues as $destination) {
				$continues[] = $this->liveBefore($finally, $destination, $context);
			}
			$outerCatches = [];
			foreach ($context->catches as [$type, $destination]) {
				$outerCatches[] = [$type, $this->liveBefore($finally, $destination, $context)];
			}
			$catchContext = new VariableFlowContext($this->liveBefore($finally, $context->return, $context), $breaks, $continues, $outerCatches, $this->liveBefore($finally, $context->uncaught, $context));
			$catches = [];
			foreach ($flow->catches as [$type, $catch]) {
				$catches[] = [$type, $this->liveBefore($catch, $normal, $catchContext)];
			}
			return $this->liveBefore($flow->children[0], $normal, new VariableFlowContext($catchContext->return, $breaks, $continues, [...$catches, ...$outerCatches], $catchContext->uncaught));
		}
		if ($flow->kind === VariableFlow::READ_ALL) {
			$this->readNames += $this->mentionedNames;
			return $next + $this->mentionedNames;
		}
		return $next;
	}

}
