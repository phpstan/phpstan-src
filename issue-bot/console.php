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

(static function (): void {
	$token = $_SERVER['GITHUB_PAT'] ?? 'unknown';

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

	$issueCachePath = __DIR__ . '/tmp/issueCache.tmp';
	$playgroundCachePath = __DIR__ . '/tmp/playgroundCache.tmp';
	$tmpDir = __DIR__ . '/tmp';

	$postGenerator = new PostGenerator(new Differ(new UnifiedDiffOutputBuilder('')));

	$application = new Application();
	$application->add(new DownloadCommand($client, $botCommentParser, new PlaygroundClient(new \GuzzleHttp\Client()), $issueCachePath, $playgroundCachePath));
	$application->add(new RunCommand($playgroundCachePath, $tmpDir));
	$application->add(new EvaluateCommand(new TabCreator(), $postGenerator, $issueCachePath, $playgroundCachePath, $tmpDir));

	$application->setCatchExceptions(false);
	$application->run();
})();
