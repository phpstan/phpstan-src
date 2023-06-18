#!/usr/bin/env php
<?php declare(strict_types = 1);

namespace PHPStan\IssueBot;

require_once __DIR__ . '/vendor/autoload.php';

use Github\AuthMethod;
use Github\Client;
use Github\HttpClient\Builder;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Parser\MarkdownParser;
use PHPStan\IssueBot\Comment\BotCommentParser;
use PHPStan\IssueBot\Comment\IssueCommentDownloader;
use PHPStan\IssueBot\Console\DownloadCommand;
use PHPStan\IssueBot\Console\EvaluateCommand;
use PHPStan\IssueBot\Console\RunCommand;
use PHPStan\IssueBot\GitHub\RateLimitPlugin;
use PHPStan\IssueBot\GitHub\RequestCounterPlugin;
use PHPStan\IssueBot\Playground\PlaygroundClient;
use PHPStan\IssueBot\Playground\TabCreator;
use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;
use Symfony\Component\Console\Application;
use function exec;
use function implode;

(static function (): void {
	$token = $_SERVER['GITHUB_PAT'] ?? 'unknown';

	$phpstanSrcCommitBefore = $_SERVER['PHPSTAN_SRC_COMMIT_BEFORE'] ?? 'unknown';
	$phpstanSrcCommitAfter = $_SERVER['PHPSTAN_SRC_COMMIT_AFTER'] ?? 'unknown';

	$rateLimitPlugin = new RateLimitPlugin();
	$requestCounter = new RequestCounterPlugin();

	$httpBuilder = new Builder();
	$httpBuilder->addPlugin($rateLimitPlugin);
	$httpBuilder->addPlugin($requestCounter);

	$client = new Client($httpBuilder);
	$client->authenticate($token, AuthMethod::ACCESS_TOKEN);
	$rateLimitPlugin->setClient($client);

	$markdownEnvironment = new Environment();
	$markdownEnvironment->addExtension(new CommonMarkCoreExtension());
	$markdownEnvironment->addExtension(new GithubFlavoredMarkdownExtension());
	$botCommentParser = new BotCommentParser(new MarkdownParser($markdownEnvironment));
	$issueCommentDownloader = new IssueCommentDownloader($client, $botCommentParser);

	$issueCachePath = __DIR__ . '/tmp/issueCache.tmp';
	$playgroundCachePath = __DIR__ . '/tmp/playgroundCache.tmp';
	$tmpDir = __DIR__ . '/tmp';

	exec('git branch --show-current', $gitBranchLines, $exitCode);
	if ($exitCode === 0) {
		$gitBranch = implode("\n", $gitBranchLines);
	} else {
		$gitBranch = 'dev-master';
	}

	$postGenerator = new PostGenerator(new Differ(new UnifiedDiffOutputBuilder('')));

	$application = new Application();
	$application->add(new DownloadCommand($client, new PlaygroundClient(new \GuzzleHttp\Client()), $issueCommentDownloader, $issueCachePath, $playgroundCachePath));
	$application->add(new RunCommand($playgroundCachePath, $tmpDir));
	$application->add(new EvaluateCommand(new TabCreator(), $postGenerator, $client, $issueCachePath, $playgroundCachePath, $tmpDir, $gitBranch, $phpstanSrcCommitBefore, $phpstanSrcCommitAfter));

	$application->setCatchExceptions(false);
	$application->run();
})();
