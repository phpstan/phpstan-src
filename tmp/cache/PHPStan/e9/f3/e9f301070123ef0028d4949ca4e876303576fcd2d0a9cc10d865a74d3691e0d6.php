<?php declare(strict_types = 1);

// osfsl-/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../react/child-process/src/Process.php-PHPStan\BetterReflection\Reflection\ReflectionClass-React\ChildProcess\Process
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-5d29584ad82378fa87ef53d53d86159fd6a18f575477212effa26eaf4a94231e-8.4.21-6.70.0.1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'React\\ChildProcess\\Process',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../react/child-process/src/Process.php',
      ),
    ),
    'namespace' => 'React\\ChildProcess',
    'name' => 'React\\ChildProcess\\Process',
    'shortName' => 'Process',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Process component.
 *
 * This class also implements the `EventEmitterInterface`
 * which allows you to react to certain events:
 *
 * exit event:
 *     The `exit` event will be emitted whenever the process is no longer running.
 *     Event listeners will receive the exit code and termination signal as two
 *     arguments:
 *
 *     ```php
 *     $process = new Process(\'sleep 10\');
 *     $process->start();
 *
 *     $process->on(\'exit\', function ($code, $term) {
 *         if ($term === null) {
 *             echo \'exit with code \' . $code . PHP_EOL;
 *         } else {
 *             echo \'terminated with signal \' . $term . PHP_EOL;
 *         }
 *     });
 *     ```
 *
 *     Note that `$code` is `null` if the process has terminated, but the exit
 *     code could not be determined.
 *     Similarly, `$term` is `null` unless the process has terminated in response to
 *     an uncaught signal sent to it.
 *     This is not a limitation of this project, but actual how exit codes and signals
 *     are exposed on POSIX systems, for more details see also
 *     [here](https://unix.stackexchange.com/questions/99112/default-exit-code-when-process-is-terminated).
 *
 *     It\'s also worth noting that process termination depends on all file descriptors
 *     being closed beforehand.
 *     This means that all [process pipes](#stream-properties) will emit a `close`
 *     event before the `exit` event and that no more `data` events will arrive after
 *     the `exit` event.
 *     Accordingly, if either of these pipes is in a paused state (`pause()` method
 *     or internally due to a `pipe()` call), this detection may not trigger.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 55,
    'endLine' => 469,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Evenement\\EventEmitter',
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'stdin' => 
      array (
        'declaringClassName' => 'React\\ChildProcess\\Process',
        'implementingClassName' => 'React\\ChildProcess\\Process',
        'name' => 'stdin',
        'modifiers' => 1,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * @var WritableStreamInterface|null|DuplexStreamInterface|ReadableStreamInterface
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 60,
        'endLine' => 60,
        'startColumn' => 5,
        'endColumn' => 18,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'stdout' => 
      array (
        'declaringClassName' => 'React\\ChildProcess\\Process',
        'implementingClassName' => 'React\\ChildProcess\\Process',
        'name' => 'stdout',
        'modifiers' => 1,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * @var ReadableStreamInterface|null|DuplexStreamInterface|WritableStreamInterface
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 65,
        'endLine' => 65,
        'startColumn' => 5,
        'endColumn' => 19,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'stderr' => 
      array (
        'declaringClassName' => 'React\\ChildProcess\\Process',
        'implementingClassName' => 'React\\ChildProcess\\Process',
        'name' => 'stderr',
        'modifiers' => 1,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * @var ReadableStreamInterface|null|DuplexStreamInterface|WritableStreamInterface
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 70,
        'endLine' => 70,
        'startColumn' => 5,
        'endColumn' => 19,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'pipes' => 
      array (
        'declaringClassName' => 'React\\ChildProcess\\Process',
        'implementingClassName' => 'React\\ChildProcess\\Process',
        'name' => 'pipes',
        'modifiers' => 1,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'array()',
          'attributes' => 
          array (
            'startLine' => 83,
            'endLine' => 83,
            'startTokenPos' => 93,
            'startFilePos' => 2916,
            'endTokenPos' => 95,
            'endFilePos' => 2922,
          ),
        ),
        'docComment' => '/**
 * Array with all process pipes (once started)
 *
 * Unless explicitly configured otherwise during construction, the following
 * standard I/O pipes will be assigned by default:
 * - 0: STDIN (`WritableStreamInterface`)
 * - 1: STDOUT (`ReadableStreamInterface`)
 * - 2: STDERR (`ReadableStreamInterface`)
 *
 * @var array<ReadableStreamInterface|WritableStreamInterface|DuplexStreamInterface>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 83,
        'endLine' => 83,
        'startColumn' => 5,
        'endColumn' => 28,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'cmd' => 
      array (
        'declaringClassName' => 'React\\ChildProcess\\Process',
        'implementingClassName' => 'React\\ChildProcess\\Process',
        'name' => 'cmd',
        'modifiers' => 4,
        'type' => NULL,
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 85,
        'endLine' => 85,
        'startColumn' => 5,
        'endColumn' => 17,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'cwd' => 
      array (
        'declaringClassName' => 'React\\ChildProcess\\Process',
        'implementingClassName' => 'React\\ChildProcess\\Process',
        'name' => 'cwd',
        'modifiers' => 4,
        'type' => NULL,
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 86,
        'endLine' => 86,
        'startColumn' => 5,
        'endColumn' => 17,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'env' => 
      array (
        'declaringClassName' => 'React\\ChildProcess\\Process',
        'implementingClassName' => 'React\\ChildProcess\\Process',
        'name' => 'env',
        'modifiers' => 4,
        'type' => NULL,
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 87,
        'endLine' => 87,
        'startColumn' => 5,
        'endColumn' => 17,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'fds' => 
      array (
        'declaringClassName' => 'React\\ChildProcess\\Process',
        'implementingClassName' => 'React\\ChildProcess\\Process',
        'name' => 'fds',
        'modifiers' => 4,
        'type' => NULL,
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 88,
        'endLine' => 88,
        'startColumn' => 5,
        'endColumn' => 17,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'process' => 
      array (
        'declaringClassName' => 'React\\ChildProcess\\Process',
        'implementingClassName' => 'React\\ChildProcess\\Process',
        'name' => 'process',
        'modifiers' => 4,
        'type' => NULL,
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 90,
        'endLine' => 90,
        'startColumn' => 5,
        'endColumn' => 21,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'status' => 
      array (
        'declaringClassName' => 'React\\ChildProcess\\Process',
        'implementingClassName' => 'React\\ChildProcess\\Process',
        'name' => 'status',
        'modifiers' => 4,
        'type' => NULL,
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 91,
        'endLine' => 91,
        'startColumn' => 5,
        'endColumn' => 20,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'exitCode' => 
      array (
        'declaringClassName' => 'React\\ChildProcess\\Process',
        'implementingClassName' => 'React\\ChildProcess\\Process',
        'name' => 'exitCode',
        'modifiers' => 4,
        'type' => NULL,
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 92,
        'endLine' => 92,
        'startColumn' => 5,
        'endColumn' => 22,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'fallbackExitCode' => 
      array (
        'declaringClassName' => 'React\\ChildProcess\\Process',
        'implementingClassName' => 'React\\ChildProcess\\Process',
        'name' => 'fallbackExitCode',
        'modifiers' => 4,
        'type' => NULL,
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 93,
        'endLine' => 93,
        'startColumn' => 5,
        'endColumn' => 30,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'stopSignal' => 
      array (
        'declaringClassName' => 'React\\ChildProcess\\Process',
        'implementingClassName' => 'React\\ChildProcess\\Process',
        'name' => 'stopSignal',
        'modifiers' => 4,
        'type' => NULL,
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 94,
        'endLine' => 94,
        'startColumn' => 5,
        'endColumn' => 24,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'termSignal' => 
      array (
        'declaringClassName' => 'React\\ChildProcess\\Process',
        'implementingClassName' => 'React\\ChildProcess\\Process',
        'name' => 'termSignal',
        'modifiers' => 4,
        'type' => NULL,
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 95,
        'endLine' => 95,
        'startColumn' => 5,
        'endColumn' => 24,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
    ),
    'immediateMethods' => 
    array (
      '__construct' => 
      array (
        'name' => '__construct',
        'parameters' => 
        array (
          'cmd' => 
          array (
            'name' => 'cmd',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 106,
            'endLine' => 106,
            'startColumn' => 33,
            'endColumn' => 36,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'cwd' => 
          array (
            'name' => 'cwd',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 106,
                'endLine' => 106,
                'startTokenPos' => 163,
                'startFilePos' => 3616,
                'endTokenPos' => 163,
                'endFilePos' => 3619,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 106,
            'endLine' => 106,
            'startColumn' => 39,
            'endColumn' => 49,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'env' => 
          array (
            'name' => 'env',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 106,
                'endLine' => 106,
                'startTokenPos' => 170,
                'startFilePos' => 3629,
                'endTokenPos' => 170,
                'endFilePos' => 3632,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 106,
            'endLine' => 106,
            'startColumn' => 52,
            'endColumn' => 62,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
          'fds' => 
          array (
            'name' => 'fds',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 106,
                'endLine' => 106,
                'startTokenPos' => 177,
                'startFilePos' => 3642,
                'endTokenPos' => 177,
                'endFilePos' => 3645,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 106,
            'endLine' => 106,
            'startColumn' => 65,
            'endColumn' => 75,
            'parameterIndex' => 3,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Constructor.
 *
 * @param string $cmd      Command line to run
 * @param null|string $cwd Current working directory or null to inherit
 * @param null|array  $env Environment variables or null to inherit
 * @param null|array  $fds File descriptors to allocate for this process (or null = default STDIO streams)
 * @throws \\LogicException On windows or when proc_open() is not installed
 */',
        'startLine' => 106,
        'endLine' => 145,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'React\\ChildProcess',
        'declaringClassName' => 'React\\ChildProcess\\Process',
        'implementingClassName' => 'React\\ChildProcess\\Process',
        'currentClassName' => 'React\\ChildProcess\\Process',
        'aliasName' => NULL,
      ),
      'start' => 
      array (
        'name' => 'start',
        'parameters' => 
        array (
          'loop' => 
          array (
            'name' => 'loop',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 163,
                'endLine' => 163,
                'startTokenPos' => 496,
                'startFilePos' => 5942,
                'endTokenPos' => 496,
                'endFilePos' => 5945,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 163,
            'endLine' => 163,
            'startColumn' => 27,
            'endColumn' => 38,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
          'interval' => 
          array (
            'name' => 'interval',
            'default' => 
            array (
              'code' => '0.1',
              'attributes' => 
              array (
                'startLine' => 163,
                'endLine' => 163,
                'startTokenPos' => 503,
                'startFilePos' => 5960,
                'endTokenPos' => 503,
                'endFilePos' => 5962,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 163,
            'endLine' => 163,
            'startColumn' => 41,
            'endColumn' => 55,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Start the process.
 *
 * After the process is started, the standard I/O streams will be constructed
 * and available via public properties.
 *
 * This method takes an optional `LoopInterface|null $loop` parameter that can be used to
 * pass the event loop instance to use for this process. You can use a `null` value
 * here in order to use the [default loop](https://github.com/reactphp/event-loop#loop).
 * This value SHOULD NOT be given unless you\'re sure you want to explicitly use a
 * given event loop instance.
 *
 * @param ?LoopInterface $loop        Loop interface for stream construction
 * @param float          $interval    Interval to periodically monitor process state (seconds)
 * @throws \\RuntimeException If the process is already running or fails to start
 */',
        'startLine' => 163,
        'endLine' => 254,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'React\\ChildProcess',
        'declaringClassName' => 'React\\ChildProcess\\Process',
        'implementingClassName' => 'React\\ChildProcess\\Process',
        'currentClassName' => 'React\\ChildProcess\\Process',
        'aliasName' => NULL,
      ),
      'close' => 
      array (
        'name' => 'close',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Close the process.
 *
 * This method should only be invoked via the periodic timer that monitors
 * the process state.
 */',
        'startLine' => 262,
        'endLine' => 287,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'React\\ChildProcess',
        'declaringClassName' => 'React\\ChildProcess\\Process',
        'implementingClassName' => 'React\\ChildProcess\\Process',
        'currentClassName' => 'React\\ChildProcess\\Process',
        'aliasName' => NULL,
      ),
      'terminate' => 
      array (
        'name' => 'terminate',
        'parameters' => 
        array (
          'signal' => 
          array (
            'name' => 'signal',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 295,
                'endLine' => 295,
                'startTokenPos' => 1491,
                'startFilePos' => 10766,
                'endTokenPos' => 1491,
                'endFilePos' => 10769,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 295,
            'endLine' => 295,
            'startColumn' => 31,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Terminate the process with an optional signal.
 *
 * @param int $signal Optional signal (default: SIGTERM)
 * @return bool Whether the signal was sent successfully
 */',
        'startLine' => 295,
        'endLine' => 306,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'React\\ChildProcess',
        'declaringClassName' => 'React\\ChildProcess\\Process',
        'implementingClassName' => 'React\\ChildProcess\\Process',
        'currentClassName' => 'React\\ChildProcess\\Process',
        'aliasName' => NULL,
      ),
      'getCommand' => 
      array (
        'name' => 'getCommand',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the command string used to launch the process.
 *
 * @return string
 */',
        'startLine' => 313,
        'endLine' => 316,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'React\\ChildProcess',
        'declaringClassName' => 'React\\ChildProcess\\Process',
        'implementingClassName' => 'React\\ChildProcess\\Process',
        'currentClassName' => 'React\\ChildProcess\\Process',
        'aliasName' => NULL,
      ),
      'getExitCode' => 
      array (
        'name' => 'getExitCode',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the exit code returned by the process.
 *
 * This value is only meaningful if isRunning() has returned false. Null
 * will be returned if the process is still running.
 *
 * Null may also be returned if the process has terminated, but the exit
 * code could not be determined.
 *
 * @return int|null
 */',
        'startLine' => 329,
        'endLine' => 332,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'React\\ChildProcess',
        'declaringClassName' => 'React\\ChildProcess\\Process',
        'implementingClassName' => 'React\\ChildProcess\\Process',
        'currentClassName' => 'React\\ChildProcess\\Process',
        'aliasName' => NULL,
      ),
      'getPid' => 
      array (
        'name' => 'getPid',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the process ID.
 *
 * @return int|null
 */',
        'startLine' => 339,
        'endLine' => 344,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'React\\ChildProcess',
        'declaringClassName' => 'React\\ChildProcess\\Process',
        'implementingClassName' => 'React\\ChildProcess\\Process',
        'currentClassName' => 'React\\ChildProcess\\Process',
        'aliasName' => NULL,
      ),
      'getStopSignal' => 
      array (
        'name' => 'getStopSignal',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the signal that caused the process to stop its execution.
 *
 * This value is only meaningful if isStopped() has returned true. Null will
 * be returned if the process was never stopped.
 *
 * @return int|null
 */',
        'startLine' => 354,
        'endLine' => 357,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'React\\ChildProcess',
        'declaringClassName' => 'React\\ChildProcess\\Process',
        'implementingClassName' => 'React\\ChildProcess\\Process',
        'currentClassName' => 'React\\ChildProcess\\Process',
        'aliasName' => NULL,
      ),
      'getTermSignal' => 
      array (
        'name' => 'getTermSignal',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the signal that caused the process to terminate its execution.
 *
 * This value is only meaningful if isTerminated() has returned true. Null
 * will be returned if the process was never terminated.
 *
 * @return int|null
 */',
        'startLine' => 367,
        'endLine' => 370,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'React\\ChildProcess',
        'declaringClassName' => 'React\\ChildProcess\\Process',
        'implementingClassName' => 'React\\ChildProcess\\Process',
        'currentClassName' => 'React\\ChildProcess\\Process',
        'aliasName' => NULL,
      ),
      'isRunning' => 
      array (
        'name' => 'isRunning',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Return whether the process is still running.
 *
 * @return bool
 */',
        'startLine' => 377,
        'endLine' => 386,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'React\\ChildProcess',
        'declaringClassName' => 'React\\ChildProcess\\Process',
        'implementingClassName' => 'React\\ChildProcess\\Process',
        'currentClassName' => 'React\\ChildProcess\\Process',
        'aliasName' => NULL,
      ),
      'isStopped' => 
      array (
        'name' => 'isStopped',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Return whether the process has been stopped by a signal.
 *
 * @return bool
 */',
        'startLine' => 393,
        'endLine' => 398,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'React\\ChildProcess',
        'declaringClassName' => 'React\\ChildProcess\\Process',
        'implementingClassName' => 'React\\ChildProcess\\Process',
        'currentClassName' => 'React\\ChildProcess\\Process',
        'aliasName' => NULL,
      ),
      'isTerminated' => 
      array (
        'name' => 'isTerminated',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Return whether the process has been terminated by an uncaught signal.
 *
 * @return bool
 */',
        'startLine' => 405,
        'endLine' => 410,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'React\\ChildProcess',
        'declaringClassName' => 'React\\ChildProcess\\Process',
        'implementingClassName' => 'React\\ChildProcess\\Process',
        'currentClassName' => 'React\\ChildProcess\\Process',
        'aliasName' => NULL,
      ),
      'getCachedStatus' => 
      array (
        'name' => 'getCachedStatus',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Return the cached process status.
 *
 * @return array
 */',
        'startLine' => 417,
        'endLine' => 424,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'React\\ChildProcess',
        'declaringClassName' => 'React\\ChildProcess\\Process',
        'implementingClassName' => 'React\\ChildProcess\\Process',
        'currentClassName' => 'React\\ChildProcess\\Process',
        'aliasName' => NULL,
      ),
      'getFreshStatus' => 
      array (
        'name' => 'getFreshStatus',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Return the updated process status.
 *
 * @return array
 */',
        'startLine' => 431,
        'endLine' => 436,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'React\\ChildProcess',
        'declaringClassName' => 'React\\ChildProcess\\Process',
        'implementingClassName' => 'React\\ChildProcess\\Process',
        'currentClassName' => 'React\\ChildProcess\\Process',
        'aliasName' => NULL,
      ),
      'updateStatus' => 
      array (
        'name' => 'updateStatus',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Update the process status, stop/term signals, and exit code.
 *
 * Stop/term signals are only updated if the process is currently stopped or
 * signaled, respectively. Otherwise, signal values will remain as-is so the
 * corresponding getter methods may be used at a later point in time.
 */',
        'startLine' => 445,
        'endLine' => 468,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'React\\ChildProcess',
        'declaringClassName' => 'React\\ChildProcess\\Process',
        'implementingClassName' => 'React\\ChildProcess\\Process',
        'currentClassName' => 'React\\ChildProcess\\Process',
        'aliasName' => NULL,
      ),
    ),
    'traitsData' => 
    array (
      'aliases' => 
      array (
      ),
      'modifiers' => 
      array (
      ),
      'precedences' => 
      array (
      ),
      'hashes' => 
      array (
      ),
    ),
  ),
));