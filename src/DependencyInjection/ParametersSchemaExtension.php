<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\Statement;
use Nette\Schema\Context as SchemaContext;
use Nette\Schema\DynamicParameter;
use Nette\Schema\Elements\AnyOf;
use Nette\Schema\Elements\Structure;
use Nette\Schema\Elements\Type;
use Nette\Schema\Expect;
use Nette\Schema\Processor;
use Nette\Schema\Schema;
use PHPStan\ShouldNotHappenException;
use function array_map;
use function count;
use function is_array;
use function substr;

class ParametersSchemaExtension extends CompilerExtension
{

	public function getConfigSchema(): Schema
	{
		return Expect::arrayOf(Expect::type(Statement::class))->min(1);
	}

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		if (!$builder->parameters['__validate']) {
			return;
		}

		/** @var mixed[] $config */
		$config = $this->config;
		$config['analysedPaths'] = new Statement(DynamicParameter::class);
		$config['analysedPathsFromConfig'] = new Statement(DynamicParameter::class);
		$schema = $this->processArgument(
			new Statement('schema', [
				new Statement('structure', [$config]),
			]),
		);
		$processor = new Processor();
		$processor->onNewContext[] = static function (SchemaContext $context): void {
			$context->path = ['parameters'];
		};
		$processor->process($schema, $builder->parameters);
	}

	/**
	 * @param Statement[] $statements
	 */
	private function processSchema(array $statements, bool $required = true): Schema
	{
		if (count($statements) === 0) {
			throw new ShouldNotHappenException();
		}

		$parameterSchema = null;
		foreach ($statements as $statement) {
			$processedArguments = array_map(fn ($argument) => $this->processArgument($argument), $statement->arguments);
			if ($parameterSchema === null) {
				/** @var Type|AnyOf|Structure $parameterSchema */
				$parameterSchema = Expect::{$statement->getEntity()}(...$processedArguments);
			} else {
				$parameterSchema->{$statement->getEntity()}(...$processedArguments);
			}
		}

		if ($required) {
			$parameterSchema->required();
		}

		return $parameterSchema;
	}

	/**
	 * @param mixed $argument
	 * @return mixed
	 */
	private function processArgument($argument, bool $required = true)
	{
		if ($argument instanceof Statement) {
			if ($argument->entity === 'schema') {
				$arguments = [];
				foreach ($argument->arguments as $schemaArgument) {
					if (!$schemaArgument instanceof Statement) {
						throw new ShouldNotHappenException('schema() should contain another statement().');
					}

					$arguments[] = $schemaArgument;
				}

				if (count($arguments) === 0) {
					throw new ShouldNotHappenException('schema() should have at least one argument.');
				}

				return $this->processSchema($arguments, $required);
			}

			return $this->processSchema([$argument], $required);
		} elseif (is_array($argument)) {
			$processedArray = [];
			foreach ($argument as $key => $val) {
				$required = $key[0] !== '?';
				$key = $required ? $key : substr($key, 1);
				$processedArray[$key] = $this->processArgument($val, $required);
			}

			return $processedArray;
		}

		return $argument;
	}

}
