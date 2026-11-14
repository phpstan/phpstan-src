<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PhpParser\Node;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\ScopeContext;
use PHPStan\Analyser\ScopeFactory;
use PHPStan\DependencyInjection\Container;
use PHPStan\DependencyInjection\ContainerFactory;
use PHPStan\Parser\Parser;
use ReflectionClass;
use ReflectionNamedType;
use Tally\ScratchDirectory;
use function array_key_exists;
use function class_exists;
use function dirname;
use function explode;
use function file_put_contents;
use function interface_exists;
use function is_a;
use function str_starts_with;
use function substr;
use function trim;

/**
 * Code analysed once outside of the counted part, so that a microbenchmark can hand
 * a rule or an extension the nodes and scopes it gets during the analysis.
 */
final class AnalysedCode
{

	/** @var list<array{Node, MutatingScope}> */
	private array $nodes = [];

	private function __construct(private Container $container)
	{
	}

	/**
	 * The classes and functions the code declares are known to reflection, as in an analysed file.
	 */
	public static function analyse(ScratchDirectory $scratch, string $code): self
	{
		$file = $scratch->path() . '/analysed.php';
		file_put_contents($file, $code);

		// the root of the PHPStan that is loaded, so the benchmarks also run against other versions
		$containerFactory = new ContainerFactory(dirname((string) (new ReflectionClass(ContainerFactory::class))->getFileName(), 3));
		$container = $containerFactory->create($scratch->path() . '/container', [$containerFactory->getConfigDirectory() . '/config.level8.neon'], [$file]);

		$analysed = new self($container);

		/** @var Parser $parser */
		$parser = $container->getService('currentPhpVersionRichParser');
		$nodeScopeResolver = $container->getByType(NodeScopeResolver::class);
		$nodeScopeResolver->setAnalysedFiles([$file]);
		$scope = $container->getByType(ScopeFactory::class)->create(ScopeContext::create($file));
		$nodeScopeResolver->processNodes($parser->parseFile($file), $scope, static function (Node $node, Scope $scope) use ($analysed): void {
			// 2.1.34 to 2.2.9 give rules a FiberScope, which asks the fiber of the analysis for its types
			if (is_a($scope, 'PHPStan\Analyser\Fiber\FiberScope')) {
				// @phpstan-ignore class.notFound, method.deprecated (the MutatingScope a FiberScope of 2.1.34 to 2.2.9 resolves its types with)
				$scope = $scope->toMutatingScope();
			}
			if (!$scope instanceof MutatingScope) {
				throw new LogicException('The analysis gave a ' . $scope::class . '.');
			}
			$analysed->nodes[] = [$node, $scope];
		});

		return $analysed;
	}

	public function getContainer(): Container
	{
		return $this->container;
	}

	/**
	 * @template T of Node
	 * @param class-string<T> $nodeClass
	 * @return list<array{T, MutatingScope}> in the order the analysis visited them, with the scopes rules get
	 */
	public function nodes(string $nodeClass): array
	{
		$nodes = [];
		foreach ($this->nodes as [$node, $scope]) {
			if (!$node instanceof $nodeClass) {
				continue;
			}

			$nodes[] = [$node, $scope];
		}

		if ($nodes === []) {
			throw new LogicException('The code has no ' . $nodeClass . '.');
		}

		return $nodes;
	}

	/**
	 * The rule or extension the container registers, or one created the way the container would,
	 * with what each version's constructor asks for.
	 *
	 * @template T of object
	 * @param class-string<T> $class
	 * @param array<string, mixed> $parameters values of constructor parameters, instead of the configured ones
	 * @return T
	 */
	public function get(string $class, array $parameters = []): object
	{
		if ($parameters === []) {
			foreach ($this->container->getServicesByTag('phpstan.rules.rule') as $rule) {
				if ($rule instanceof $class) {
					return $rule;
				}
			}
			if ($this->container->findServiceNamesByType($class) !== []) {
				return $this->container->getByType($class);
			}
		}

		$constructor = (new ReflectionClass($class))->getConstructor();
		$arguments = [];
		foreach ($constructor === null ? [] : $constructor->getParameters() as $parameter) {
			$name = $parameter->getName();
			$type = $parameter->getType();
			$extensions = $parameter->getAttributes('PHPStan\DependencyInjection\AutowiredExtensions');
			$configured = $parameter->getAttributes('PHPStan\DependencyInjection\AutowiredParameter');
			if (array_key_exists($name, $parameters)) {
				$arguments[] = $parameters[$name];
			} elseif ($extensions !== []) {
				$arguments[] = $this->container->getExtensionsCollection($extensions[0]->newInstance()->of);
			} elseif ($configured !== []) {
				$arguments[] = $this->parameter($configured[0]->newInstance()->ref ?? '%' . $name . '%');
			} elseif ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
				$typeName = $type->getName();
				if (is_a($this->container, $typeName)) {
					$arguments[] = $this->container;
				} elseif (class_exists($typeName) || interface_exists($typeName)) {
					$arguments[] = $this->container->getByType($typeName);
				} else {
					throw new LogicException('Unknown type ' . $typeName . ' of the parameter $' . $name . ' of ' . $class . '.');
				}
			} elseif ($parameter->isDefaultValueAvailable()) {
				$arguments[] = $parameter->getDefaultValue();
			} elseif ($this->container->hasParameter($name)) {
				$arguments[] = $this->container->getParameter($name);
			} else {
				throw new LogicException('Pass the parameter $' . $name . ' of ' . $class . '.');
			}
		}

		return new $class(...$arguments);
	}

	private function parameter(string $ref): mixed
	{
		if (str_starts_with($ref, '@')) {
			return $this->container->getService(substr($ref, 1));
		}

		$value = $this->container->getParameters();
		foreach (explode('.', trim($ref, '%')) as $key) {
			$value = $value[$key];
		}

		return $value;
	}

}
