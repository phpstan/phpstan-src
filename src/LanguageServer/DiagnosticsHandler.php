<?php

namespace PHPStan\LanguageServer;

use Amp\Promise;
use Amp\Success;
use PHPStan\Command\AnalyseApplication;
use PHPStan\Command\AnalysisResult;
use PHPStan\Command\InceptionResult;
use Phpactor\LanguageServerProtocol\Diagnostic;
use Phpactor\LanguageServerProtocol\DiagnosticSeverity;
use Phpactor\LanguageServerProtocol\DidChangeTextDocumentNotification;
use Phpactor\LanguageServerProtocol\DidChangeTextDocumentParams;
use Phpactor\LanguageServerProtocol\DidOpenTextDocumentNotification;
use Phpactor\LanguageServerProtocol\DidOpenTextDocumentParams;
use Phpactor\LanguageServerProtocol\DidSaveTextDocumentNotification;
use Phpactor\LanguageServerProtocol\DidSaveTextDocumentParams;
use Phpactor\LanguageServerProtocol\Position;
use Phpactor\LanguageServerProtocol\Range;
use Phpactor\LanguageServerProtocol\SaveOptions;
use Phpactor\LanguageServerProtocol\ServerCapabilities;
use Phpactor\LanguageServerProtocol\TextDocumentSyncKind;
use Phpactor\LanguageServerProtocol\TextDocumentSyncOptions;
use Phpactor\LanguageServer\Core\Handler\CanRegisterCapabilities;
use Phpactor\LanguageServer\Core\Handler\Handler;
use Phpactor\LanguageServer\Core\Server\RpcClient;
use Symfony\Component\Console\Input\ArrayInput;

class DiagnosticsHandler implements Handler, CanRegisterCapabilities
{
    private RpcClient $client;
    private AnalyseApplication $analyser;
    private InceptionResult $result;

    public function __construct(RpcClient $client, AnalyseApplication $analyser, InceptionResult $result)
    {
        $this->client = $client;
        $this->analyser = $analyser;
        $this->result = $result;
    }

    /**
     * {@inheritDoc}
     */
    public function methods(): array
    {
        return [
            DidSaveTextDocumentNotification::METHOD => 'didSave',
        ];
    }

    /**
     * @return Promise<null>
     */
    public function didSave(DidSaveTextDocumentParams $didSave): Promise
    {
        $result = $this->analyser->analyse(
            [
                str_replace('file://', '', $didSave->textDocument->uri)
            ],
            false,
            $this->result->getStdOutput(),
            $this->result->getErrorOutput(),
            true,
            true,
            null,
            new ArrayInput([])
        );

        $this->client->notification('textDocument/publishDiagnostics', [
            'uri' => $didSave->textDocument->uri,
            'version' => $didSave->textDocument->version,
            'diagnostics' => $this->buildDiagnostics($result)
        ]);

        return new Success(null);
    }

    public function registerCapabiltiies(ServerCapabilities $capabilities): void
    {
        $syncOptions = new TextDocumentSyncOptions();
        $syncOptions->save = new SaveOptions(true);
        $syncOptions->willSave = true;
        $syncOptions->change = TextDocumentSyncKind::FULL;
        $capabilities->textDocumentSync = $syncOptions;
    }

    /**
     * @return array<Diagnostic>
     */
    private function buildDiagnostics(AnalysisResult $result): array
    {
        $diagnostics = [];
        var_dump($result);
        foreach ($result->getFileSpecificErrors() as $error) {

            $diagnostics[] = new Diagnostic(
                new Range(
                    new Position($error->getLine() - 1, 0),
                    new Position($error->getLine() - 1, 100)
                ),
                $error->getMessage(),
                DiagnosticSeverity::ERROR
            );
        }

        return $diagnostics;
    }
}
