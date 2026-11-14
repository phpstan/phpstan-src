<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PHPStan\Analyser\NameScope;
use PHPStan\PhpDoc\TypeNodeResolver;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\Type\Type;
use Tally\Attribute\Before;
use Tally\Attribute\DataProvider;
use Tally\ScratchDirectory;
use function implode;

final class TypeNodeResolverBench
{

	private ?TypeNodeResolver $resolver = null;

	private ?TypeNode $typeNode = null;

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	/**
	 * @return iterable<string, array{int}>
	 */
	public static function items(): iterable
	{
		foreach ([4, 32] as $items) {
			yield 'items-' . $items => [$items];
		}
	}

	#[Before]
	public function boot(int $items): void
	{
		$container = BenchContainer::create($this->scratch);
		$this->resolver = $container->getByType(TypeNodeResolver::class);
		$lexer = $container->getByType(Lexer::class);
		$typeParser = $container->getByType(TypeParser::class);

		$parse = static fn (string $type): TypeNode => $typeParser->parse(new TokenIterator($lexer->tokenize($type)));
		$this->resolver->resolve($parse(self::shape(3, 'float')), new NameScope(null, []));

		$this->typeNode = $parse(self::shape($items, 'int'));
	}

	/**
	 * A non-empty-list shape gets both the list and the non-empty accessory.
	 */
	#[DataProvider(method: 'items')]
	public function benchResolveNonEmptyListShape(int $items): Type
	{
		if ($this->resolver === null || $this->typeNode === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->resolver->resolve($this->typeNode, new NameScope(null, []));
	}

	private static function shape(int $items, string $valueType): string
	{
		$values = [];
		for ($i = 0; $i < $items; $i++) {
			$values[] = $valueType;
		}

		return 'non-empty-list{' . implode(', ', $values) . '}';
	}

}
