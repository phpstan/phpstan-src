<?php declare(strict_types = 1);

namespace PHPStan\Build;

use PhpParser\Node;
use PHPStan\Analyser\ArgumentsNormalizer;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\ExtendedParametersAcceptor;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\VerbosityLevel;
use function count;
use function implode;
use function sprintf;

/**
 * @implements Rule<Node\Expr\CallLike>
 */
final class NamedArgumentsRule implements Rule
{

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private PhpVersion $phpVersion,
	)
	{
	}

	public function getNodeType(): string
	{
		return Node\Expr\CallLike::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$this->phpVersion->supportsNamedArguments()) {
			return [];
		}

		if ($node->isFirstClassCallable()) {
			return [];
		}

		if ($node instanceof Node\Expr\FuncCall && $node->name instanceof Node\Name) {
			if ($this->reflectionProvider->hasFunction($node->name, $scope)) {
				$function = $this->reflectionProvider->getFunction($node->name, $scope);
				$variants = $function->getVariants();
				if (count($variants) !== 1) {
					return [];
				}

				return $this->processArgs($variants[0], $scope, $node);
			}
		}

		if ($node instanceof Node\Expr\New_ && $node->class instanceof Node\Name) {
			if ($this->reflectionProvider->hasClass($node->class->toString())) {
				$class = $this->reflectionProvider->getClass($node->class->toString());
				if ($class->hasConstructor()) {
					$constructor = $class->getConstructor();
					$variants = $constructor->getVariants();
					if (count($variants) !== 1) {
						return [];
					}

					return $this->processArgs($variants[0], $scope, $node);
				}
			}
		}

		if ($node instanceof Node\Expr\StaticCall && $node->class instanceof Node\Name && $node->name instanceof Node\Identifier) {
			$className = $scope->resolveName($node->class);
			if ($this->reflectionProvider->hasClass($className)) {
				$class = $this->reflectionProvider->getClass($className);
				if ($class->hasNativeMethod($node->name->toString())) {
					$method = $class->getNativeMethod($node->name->toString());
					$variants = $method->getVariants();
					if (count($variants) !== 1) {
						return [];
					}

					return $this->processArgs($variants[0], $scope, $node);
				}
			}
		}

		return [];
	}

	/**
	 * @return list<IdentifierRuleError>
	 */
	private function processArgs(ExtendedParametersAcceptor $acceptor, Scope $scope, Node\Expr\FuncCall|Node\Expr\New_|Node\Expr\StaticCall $node): array
	{
		if ($acceptor->isVariadic()) {
			return [];
		}
		$normalizedArgs = ArgumentsNormalizer::reorderArgs($acceptor, $node->getArgs());
		if ($normalizedArgs === null) {
			return [];
		}

		$hasNamedArgument = false;
		foreach ($node->getArgs() as $arg) {
			if ($arg->name === null) {
				continue;
			}

			$hasNamedArgument = true;
			break;
		}

		$errorBuilders = [];
		$parameters = $acceptor->getParameters();
		if (count($parameters) !== count($normalizedArgs)) {
			return [];
		}

		$defaultValueWasPassed = [];
		foreach ($normalizedArgs as $i => $normalizedArg) {
			if ($normalizedArg->unpack) {
				return [];
			}
			$parameter = $parameters[$i];
			if ($parameter->getDefaultValue() === null) {
				continue;
			}
			if (!$parameter->passedByReference()->no()) {
				continue;
			}
			$argValue = $scope->getType($normalizedArg->value);
			if ($normalizedArg->name !== null) {
				continue;
			}

			/** @var Node\Arg|null $originalArg */
			$originalArg = $normalizedArg->getAttribute(ArgumentsNormalizer::ORIGINAL_ARG_ATTRIBUTE);
			if ($originalArg === null) {
				if ($hasNamedArgument) {
					// this is an optional parameter not passed by the user, but filled in by ArgumentsNormalizer
					continue;
				}
			}

			if (count($parameter->getDefaultValue()->getFiniteTypes()) === 0) {
				continue;
			}

			if (!$argValue->equals($parameter->getDefaultValue())) {
				if (count($defaultValueWasPassed) > 0) {
					$errorBuilders[] = RuleErrorBuilder::message(sprintf(
						'You\'re passing a non-default value %s to parameter $%s but previous %s (%s). You can skip %s and use named argument for $%s instead.',
						$argValue->describe(VerbosityLevel::precise()),
						$parameter->getName(),
						count($defaultValueWasPassed) === 1 ? 'argument is passing default value to its parameter' : 'arguments are passing default values to their parameters',
						implode(', ', $defaultValueWasPassed),
						count($defaultValueWasPassed) === 1 ? 'it' : 'them',
						$parameter->getName(),
					))
						->identifier('phpstan.namedArgument')
						->line($normalizedArg->getStartLine())
						->nonIgnorable();
				}
				continue;
			} else {
				if ($originalArg !== null && $originalArg->name !== null) {
					$errorBuilders[] = RuleErrorBuilder::message(sprintf('Named argument $%s can be omitted, type %s is the same as the default value.', $originalArg->name, $argValue->describe(VerbosityLevel::precise())))
						->identifier('phpstan.namedArgumentWithDefaultValue')
						->nonIgnorable();
					continue;
				}
			}

			$defaultValueWasPassed[] = '$' . $parameter->getName();
		}

		if (count($errorBuilders) > 0) {
			$errorBuilders[0]->fixNode($node, static function ($node) use ($acceptor, $hasNamedArgument, $parameters, $scope) {
				$normalizedArgs = ArgumentsNormalizer::reorderArgs($acceptor, $node->getArgs());
				if ($normalizedArgs === null) {
					return $node;
				}

				$newArgs = [];
				$skippedOptional = false;
				foreach ($normalizedArgs as $i => $normalizedArg) {
					/** @var Node\Arg|null $originalArg */
					$originalArg = $normalizedArg->getAttribute(ArgumentsNormalizer::ORIGINAL_ARG_ATTRIBUTE);
					if ($originalArg === null) {
						if ($hasNamedArgument) {
							// this is an optional parameter not passed by the user, but filled in by ArgumentsNormalizer
							continue;
						}

						$originalArg = $normalizedArg;
					}
					$parameter = $parameters[$i];
					if ($parameter->getDefaultValue() === null) {
						$newArgs[] = $originalArg;
						continue;
					}
					if (!$parameter->passedByReference()->no()) {
						$newArgs[] = $originalArg;
						continue;
					}
					if (count($parameter->getDefaultValue()->getFiniteTypes()) === 0) {
						$newArgs[] = $originalArg;
						continue;
					}
					$argValue = $scope->getType($normalizedArg->value);
					if ($argValue->equals($parameter->getDefaultValue())) {
						$skippedOptional = true;
						continue;
					}

					if ($skippedOptional) {
						if ($parameter->getName() === '') {
							throw new ShouldNotHappenException();
						}

						$newArgs[] = new Node\Arg($originalArg->value, $originalArg->byRef, $originalArg->unpack, $originalArg->getAttributes(), new Node\Identifier($parameter->getName()));
						continue;
					}

					$newArgs[] = $originalArg;
				}

				$node->args = $newArgs;

				return $node;
			});
		}

		return array_map(static fn ($builder) => $builder->build(), $errorBuilders);
	}

}
