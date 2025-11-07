<?php declare(strict_types = 1);

namespace PHPStan\IssueBot\Console;

use Exception;
use Nette\Neon\Neon;
use Nette\Utils\Json;
use PHPStan\IssueBot\Playground\PlaygroundCache;
use PHPStan\IssueBot\Playground\PlaygroundError;
use PHPStan\IssueBot\Playground\PlaygroundResult;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function array_key_exists;
use function exec;
use function explode;
use function file_get_contents;
use function file_put_contents;
use function implode;
use function is_file;
use function microtime;
use function serialize;
use function sha1;
use function sprintf;
use function str_replace;
use function strpos;
use function unserialize;

class RunCommand extends Command
{

	public function __construct(private string $playgroundCachePath, private string $tmpDir)
	{
		parent::__construct();
	}

	protected function configure(): void
	{
		$this->setName('run');
		$this->addArgument('phpVersion', InputArgument::REQUIRED);
		$this->addArgument('playgroundHashes', InputArgument::REQUIRED);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$phpVersion = (int) $input->getArgument('phpVersion');
		$commaSeparatedPlaygroundHashes = $input->getArgument('playgroundHashes');
		$playgroundHashes = explode(',', $commaSeparatedPlaygroundHashes);
		$playgroundCache = $this->loadPlaygroundCache();
		$errors = [];
		foreach ($playgroundHashes as $hash) {
			if (!array_key_exists($hash, $playgroundCache->getResults())) {
				throw new Exception(sprintf('Hash %s must exist', $hash));
			}
			$errors[$hash] = $this->analyseHash($output, $phpVersion, $playgroundCache->getResults()[$hash]);
		}

		$data = ['phpVersion' => $phpVersion, 'errors' => $errors];

		$writeSuccess = file_put_contents(sprintf($this->tmpDir . '/results-%d-%s.tmp', $phpVersion, sha1($commaSeparatedPlaygroundHashes)), serialize($data));
		if ($writeSuccess === false) {
			throw new Exception('Result write unsuccessful');
		}

		return 0;
	}

	/**
	 * @return list<PlaygroundError>
	 */
	private function analyseHash(OutputInterface $output, int $phpVersion, PlaygroundResult $result): array
	{
		$configFiles = [
			__DIR__ . '/../../playground.neon',
			__DIR__ . '/../../vendor/phpstan/phpstan-deprecation-rules/rules.neon',
		];
		if ($result->isBleedingEdge()) {
			$configFiles[] = __DIR__ . '/../../../conf/bleedingEdge.neon';
		}
		if ($result->isStrictRules()) {
			$configFiles[] = __DIR__ . '/../../vendor/phpstan/phpstan-strict-rules/rules.neon';
		}
		$neon = Neon::encode([
			'includes' => $configFiles,
			'parameters' => [
				'level' => $result->getLevel(),
				'inferPrivatePropertyTypeFromConstructor' => true,
				'treatPhpDocTypesAsCertain' => $result->isTreatPhpDocTypesAsCertain(),
				'phpVersion' => $phpVersion,
			],
		]);

		$hash = $result->getHash();
		$neonPath = sprintf($this->tmpDir . '/%s.neon', $hash);
		$codePath = sprintf($this->tmpDir . '/%s.php', $hash);
		file_put_contents($neonPath, $neon);
		file_put_contents($codePath, $result->getCode());

		$commandArray = [
			__DIR__ . '/../../../bin/phpstan',
			'analyse',
			'--error-format',
			'json',
			'--no-progress',
			'-c',
			$neonPath,
			$codePath,
		];

		$output->writeln(sprintf('Starting analysis of %s', $hash));

		$startTime = microtime(true);
		exec(implode(' ', $commandArray), $outputLines, $exitCode);
		$elapsedTime = microtime(true) - $startTime;
		$output->writeln(sprintf('Analysis of %s took %.2f s', $hash, $elapsedTime));

		if ($exitCode !== 0 && $exitCode !== 1) {
			throw new Exception(sprintf('PHPStan exited with code %d during analysis of %s', $exitCode, $hash));
		}

		$json = Json::decode(implode("\n", $outputLines), Json::FORCE_ARRAY);
		$errors = [];
		foreach ($json['files'] as ['messages' => $messages]) {
			foreach ($messages as $message) {
				$messageText = str_replace(sprintf('/%s.php', $hash), '/tmp.php', $message['message']);
				if (strpos($messageText, 'Internal error') !== false) {
					throw new Exception(sprintf('While analysing %s: %s', $hash, $messageText));
				}
				$errors[] = new PlaygroundError($message['line'] ?? -1, $messageText, $message['identifier'] ?? null);
			}
		}

		return $errors;
	}

	private function loadPlaygroundCache(): PlaygroundCache
	{
		if (!is_file($this->playgroundCachePath)) {
			throw new Exception('Playground cache must exist');
		}

		$contents = file_get_contents($this->playgroundCachePath);
		if ($contents === false) {
			throw new Exception('Read unsuccessful');
		}

		return unserialize($contents);
	}

}
