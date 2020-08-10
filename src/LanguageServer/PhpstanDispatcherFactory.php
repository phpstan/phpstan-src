<?php declare(strict_types = 1);

namespace PHPStan\LanguageServer;

use Phpactor\LanguageServer\Adapter\Psr\NullEventDispatcher;
use Phpactor\LanguageServer\Core\Dispatcher\ArgumentResolver\LanguageSeverProtocolParamsResolver;
use Phpactor\LanguageServer\Core\Dispatcher\Dispatcher;
use Phpactor\LanguageServer\Core\Dispatcher\Dispatcher\MiddlewareDispatcher;
use Phpactor\LanguageServer\Core\Dispatcher\DispatcherFactory;
use Phpactor\LanguageServer\Core\Handler\HandlerMethodResolver;
use Phpactor\LanguageServer\Core\Handler\HandlerMethodRunner;
use Phpactor\LanguageServer\Core\Handler\Handlers;
use Phpactor\LanguageServer\Core\Server\ResponseWatcher\DeferredResponseWatcher;
use Phpactor\LanguageServer\Core\Server\RpcClient\JsonRpcClient;
use Phpactor\LanguageServer\Core\Server\Transmitter\MessageTransmitter;
use Phpactor\LanguageServer\Middleware\ErrorHandlingMiddleware;
use Phpactor\LanguageServer\Middleware\HandlerMiddleware;
use Phpactor\LanguageServer\Middleware\InitializeMiddleware;
use Phpactor\LanguageServerProtocol\InitializeParams;
use PHPStan\Command\AnalyseApplication;
use PHPStan\Command\InceptionResult;
use Psr\Log\LoggerInterface;

class PhpstanDispatcherFactory implements DispatcherFactory
{
	private InceptionResult $result;

	private LoggerInterface $logger;

	public function __construct(InceptionResult $result, LoggerInterface $logger)
	{
		$this->result = $result;
		$this->logger = $logger;
	}

	public function create(MessageTransmitter $transmitter, InitializeParams $initializeParams): Dispatcher
	{
		$watcher = new DeferredResponseWatcher();
		$client = new JsonRpcClient($transmitter, $watcher);

		$handlers = new Handlers(
			new DiagnosticsHandler(
				$client,
				$this->result->getContainer()->getByType(AnalyseApplication::class),
				$this->result
			),
		);

		$argumentResolver = new LanguageSeverProtocolParamsResolver();

		return new MiddlewareDispatcher(
			new ErrorHandlingMiddleware($this->logger),
			new InitializeMiddleware($handlers, new NullEventDispatcher()),
			new HandlerMiddleware(
				new HandlerMethodRunner(
					$handlers,
					$argumentResolver,
				)
			)
		);
	}

}
