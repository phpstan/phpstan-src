#!/usr/bin/env php
<?php declare(strict_types = 1);

namespace PHPStan\ChangelogGenerator;

require_once __DIR__ . '/vendor/autoload.php';

use Github\Api\Search;
use Github\AuthMethod;
use Github\Client;
use Github\HttpClient\Builder;
use InvalidArgumentException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function array_map;
use function array_merge;
use function count;
use function escapeshellarg;
use function exec;
use function explode;
use function implode;
use function sprintf;

(function (): void {
	require_once __DIR__ . '/../vendor/autoload.php';

	$command = new class() extends Command {

		protected function configure(): void
		{
			$this->setName('run');
			$this->addArgument('fromCommit', InputArgument::REQUIRED);
			$this->addArgument('toCommit', InputArgument::REQUIRED);
			$this->addOption('exclude-branch', null, InputOption::VALUE_REQUIRED);
			$this->addOption('include-headings', null, InputOption::VALUE_NONE);
		}

		protected function execute(InputInterface $input, OutputInterface $output)
		{
			$token = $_SERVER['GITHUB_TOKEN'];

			$rateLimitPlugin = new RateLimitPlugin();
			$httpBuilder = new Builder();
			$httpBuilder->addPlugin($rateLimitPlugin);

			$gitHubClient = new Client($httpBuilder);
			$gitHubClient->authenticate($token, AuthMethod::ACCESS_TOKEN);
			$rateLimitPlugin->setClient($gitHubClient);

			/** @var Search $searchApi */
			$searchApi = $gitHubClient->api('search');

			$command = ['git', 'log', sprintf('%s..%s', $input->getArgument('fromCommit'), $input->getArgument('toCommit'))];
			$excludeBranch = $input->getOption('exclude-branch');
			if ($excludeBranch !== null) {
				$command[] = '--not';
				$command[] = $excludeBranch;
				$command[] = '--no-merges';
			}
			$command[] = '--reverse';
			$command[] = '--pretty=%H %s';

			$commitLines = $this->exec($command);
			$commits = array_map(static function (string $line): array {
				[$hash, $message] = explode(' ', $line, 2);

				return [
					'hash' => $hash,
					'message' => $message,
				];
			}, explode("\n", $commitLines));

			if ($input->getOption('include-headings') === true) {
				$output->writeln(<<<'MARKDOWN'
				Major new features 🚀
				=====================

				Bleeding edge 🔪
				=====================

				*

				*If you want to see the shape of things to come and adopt bleeding edge features early, you can include this config file in your project's `phpstan.neon`:*

				```
				includes:
					- vendor/phpstan/phpstan/conf/bleedingEdge.neon
				```

				*Of course, there are no backwards compatibility guarantees when you include this file. The behaviour and reported errors can change in minor versions with this file included. [Learn more](https://phpstan.org/blog/what-is-bleeding-edge)*

				Improvements 🔧
				=====================

				Bugfixes 🐛
				=====================

				Function signature fixes 🤖
				=======================

				Internals 🔍
				=====================


				MARKDOWN);
			}

			foreach ($commits as $commit) {
				$pullRequests = $searchApi->issues(sprintf('repo:phpstan/phpstan-src %s', $commit['hash']));
				$issues = $searchApi->issues(sprintf('repo:phpstan/phpstan %s', $commit['hash']), 'created');
				$items = array_merge($pullRequests['items'], $issues['items']);
				$parenthesis = 'https://github.com/phpstan/phpstan-src/commit/' . $commit['hash'];
				$thanks = null;
				$issuesToReference = [];
				foreach ($items as $responseItem) {
					if (isset($responseItem['pull_request'])) {
						$parenthesis = sprintf('[#%d](%s)', $responseItem['number'], 'https://github.com/phpstan/phpstan-src/pull/' . $responseItem['number']);
						$thanks = $responseItem['user']['login'];
					} else {
						$issuesToReference[] = sprintf('#%d', $responseItem['number']);
					}
				}

				$output->writeln(sprintf('* %s (%s)%s%s', $commit['message'], $parenthesis, count($issuesToReference) > 0 ? ', ' . implode(', ', $issuesToReference) : '', $thanks !== null ? sprintf(', thanks @%s!', $thanks) : ''));
			}

			return 0;
		}

		/**
		 * @param string[] $commandParts
		 */
		private function exec(array $commandParts): string
		{
			$command = implode(' ', array_map(static fn (string $part): string => escapeshellarg($part), $commandParts));

			exec($command, $outputLines, $statusCode);
			$output = implode("\n", $outputLines);
			if ($statusCode !== 0) {
				throw new InvalidArgumentException(sprintf('Command %s failed: %s', $command, $output));
			}

			return $output;
		}

	};

	$application = new Application();
	$application->add($command);
	$application->setDefaultCommand('run', true);
	$application->setCatchExceptions(false);
	$application->run();
})();
