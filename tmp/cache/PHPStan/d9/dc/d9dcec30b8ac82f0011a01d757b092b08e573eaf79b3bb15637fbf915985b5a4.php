<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/vendor/nette/bootstrap/src/Bootstrap/Configurator.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Nette\Bootstrap\Configurator
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-05fbf5496aade80b0a97e14f37b4efcd785416bb2d2ff7442317f95566597cb2',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Nette\\Bootstrap\\Configurator',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/vendor/nette/bootstrap/src/Bootstrap/Configurator.php',
      ),
    ),
    'namespace' => 'Nette\\Bootstrap',
    'name' => 'Nette\\Bootstrap\\Configurator',
    'shortName' => 'Configurator',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Initial system DI container generator.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 22,
    'endLine' => 380,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
      0 => 'Nette\\SmartObject',
    ),
    'immediateConstants' => 
    array (
      'CookieSecret' => 
      array (
        'declaringClassName' => 'Nette\\Bootstrap\\Configurator',
        'implementingClassName' => 'Nette\\Bootstrap\\Configurator',
        'name' => 'CookieSecret',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'nette-debug\'',
          'attributes' => 
          array (
            'startLine' => 26,
            'endLine' => 26,
            'startTokenPos' => 63,
            'startFilePos' => 402,
            'endTokenPos' => 63,
            'endFilePos' => 414,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 26,
        'endLine' => 26,
        'startColumn' => 2,
        'endColumn' => 43,
      ),
      'COOKIE_SECRET' => 
      array (
        'declaringClassName' => 'Nette\\Bootstrap\\Configurator',
        'implementingClassName' => 'Nette\\Bootstrap\\Configurator',
        'name' => 'COOKIE_SECRET',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => 'self::CookieSecret',
          'attributes' => 
          array (
            'startLine' => 29,
            'endLine' => 29,
            'startTokenPos' => 76,
            'startFilePos' => 500,
            'endTokenPos' => 78,
            'endFilePos' => 517,
          ),
        ),
        'docComment' => '/** @deprecated  use Configurator::CookieSecret */',
        'attributes' => 
        array (
        ),
        'startLine' => 29,
        'endLine' => 29,
        'startColumn' => 2,
        'endColumn' => 49,
      ),
    ),
    'immediateProperties' => 
    array (
      'onCompile' => 
      array (
        'declaringClassName' => 'Nette\\Bootstrap\\Configurator',
        'implementingClassName' => 'Nette\\Bootstrap\\Configurator',
        'name' => 'onCompile',
        'modifiers' => 1,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 33,
            'endLine' => 33,
            'startTokenPos' => 89,
            'startFilePos' => 661,
            'endTokenPos' => 90,
            'endFilePos' => 662,
          ),
        ),
        'docComment' => '/** @var callable[]  function (Configurator $sender, DI\\Compiler $compiler); Occurs after the compiler is created */',
        'attributes' => 
        array (
        ),
        'startLine' => 33,
        'endLine' => 33,
        'startColumn' => 2,
        'endColumn' => 24,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'defaultExtensions' => 
      array (
        'declaringClassName' => 'Nette\\Bootstrap\\Configurator',
        'implementingClassName' => 'Nette\\Bootstrap\\Configurator',
        'name' => 'defaultExtensions',
        'modifiers' => 1,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'application\' => [\\Nette\\Bridges\\ApplicationDI\\ApplicationExtension::class, [\'%debugMode%\', [\'%appDir%\'], \'%tempDir%/cache/nette.application\']], \'cache\' => [\\Nette\\Bridges\\CacheDI\\CacheExtension::class, [\'%tempDir%\']], \'constants\' => \\Nette\\Bootstrap\\Extensions\\ConstantsExtension::class, \'database\' => [\\Nette\\Bridges\\DatabaseDI\\DatabaseExtension::class, [\'%debugMode%\']], \'decorator\' => \\Nette\\DI\\Extensions\\DecoratorExtension::class, \'di\' => [\\Nette\\DI\\Extensions\\DIExtension::class, [\'%debugMode%\']], \'extensions\' => \\Nette\\DI\\Extensions\\ExtensionsExtension::class, \'forms\' => \\Nette\\Bridges\\FormsDI\\FormsExtension::class, \'http\' => [\\Nette\\Bridges\\HttpDI\\HttpExtension::class, [\'%consoleMode%\']], \'inject\' => \\Nette\\DI\\Extensions\\InjectExtension::class, \'latte\' => [\\Nette\\Bridges\\ApplicationDI\\LatteExtension::class, [\'%tempDir%/cache/latte\', \'%debugMode%\']], \'mail\' => \\Nette\\Bridges\\MailDI\\MailExtension::class, \'php\' => \\Nette\\Bootstrap\\Extensions\\PhpExtension::class, \'routing\' => [\\Nette\\Bridges\\ApplicationDI\\RoutingExtension::class, [\'%debugMode%\']], \'search\' => [\\Nette\\DI\\Extensions\\SearchExtension::class, [\'%tempDir%/cache/nette.search\']], \'security\' => [\\Nette\\Bridges\\SecurityDI\\SecurityExtension::class, [\'%debugMode%\']], \'session\' => [\\Nette\\Bridges\\HttpDI\\SessionExtension::class, [\'%debugMode%\', \'%consoleMode%\']], \'tracy\' => [\\Tracy\\Bridges\\Nette\\TracyExtension::class, [\'%debugMode%\', \'%consoleMode%\']]]',
          'attributes' => 
          array (
            'startLine' => 36,
            'endLine' => 55,
            'startTokenPos' => 101,
            'startFilePos' => 714,
            'endTokenPos' => 359,
            'endFilePos' => 2132,
          ),
        ),
        'docComment' => '/** @var array */',
        'attributes' => 
        array (
        ),
        'startLine' => 36,
        'endLine' => 55,
        'startColumn' => 2,
        'endColumn' => 3,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'autowireExcludedClasses' => 
      array (
        'declaringClassName' => 'Nette\\Bootstrap\\Configurator',
        'implementingClassName' => 'Nette\\Bootstrap\\Configurator',
        'name' => 'autowireExcludedClasses',
        'modifiers' => 1,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\\ArrayAccess::class, \\Countable::class, \\IteratorAggregate::class, \\stdClass::class, \\Traversable::class]',
          'attributes' => 
          array (
            'startLine' => 58,
            'endLine' => 64,
            'startTokenPos' => 370,
            'startFilePos' => 2233,
            'endTokenPos' => 397,
            'endFilePos' => 2352,
          ),
        ),
        'docComment' => '/** @var string[] of classes which shouldn\'t be autowired */',
        'attributes' => 
        array (
        ),
        'startLine' => 58,
        'endLine' => 64,
        'startColumn' => 2,
        'endColumn' => 3,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'staticParameters' => 
      array (
        'declaringClassName' => 'Nette\\Bootstrap\\Configurator',
        'implementingClassName' => 'Nette\\Bootstrap\\Configurator',
        'name' => 'staticParameters',
        'modifiers' => 2,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/** @var array */',
        'attributes' => 
        array (
        ),
        'startLine' => 67,
        'endLine' => 67,
        'startColumn' => 2,
        'endColumn' => 29,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'dynamicParameters' => 
      array (
        'declaringClassName' => 'Nette\\Bootstrap\\Configurator',
        'implementingClassName' => 'Nette\\Bootstrap\\Configurator',
        'name' => 'dynamicParameters',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 70,
            'endLine' => 70,
            'startTokenPos' => 415,
            'startFilePos' => 2457,
            'endTokenPos' => 416,
            'endFilePos' => 2458,
          ),
        ),
        'docComment' => '/** @var array */',
        'attributes' => 
        array (
        ),
        'startLine' => 70,
        'endLine' => 70,
        'startColumn' => 2,
        'endColumn' => 35,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'services' => 
      array (
        'declaringClassName' => 'Nette\\Bootstrap\\Configurator',
        'implementingClassName' => 'Nette\\Bootstrap\\Configurator',
        'name' => 'services',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 73,
            'endLine' => 73,
            'startTokenPos' => 427,
            'startFilePos' => 2504,
            'endTokenPos' => 428,
            'endFilePos' => 2505,
          ),
        ),
        'docComment' => '/** @var array */',
        'attributes' => 
        array (
        ),
        'startLine' => 73,
        'endLine' => 73,
        'startColumn' => 2,
        'endColumn' => 26,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'configs' => 
      array (
        'declaringClassName' => 'Nette\\Bootstrap\\Configurator',
        'implementingClassName' => 'Nette\\Bootstrap\\Configurator',
        'name' => 'configs',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 76,
            'endLine' => 76,
            'startTokenPos' => 439,
            'startFilePos' => 2566,
            'endTokenPos' => 440,
            'endFilePos' => 2567,
          ),
        ),
        'docComment' => '/** @var array of string|array */',
        'attributes' => 
        array (
        ),
        'startLine' => 76,
        'endLine' => 76,
        'startColumn' => 2,
        'endColumn' => 25,
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
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 79,
        'endLine' => 82,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Nette\\Bootstrap',
        'declaringClassName' => 'Nette\\Bootstrap\\Configurator',
        'implementingClassName' => 'Nette\\Bootstrap\\Configurator',
        'currentClassName' => 'Nette\\Bootstrap\\Configurator',
        'aliasName' => NULL,
      ),
      'setDebugMode' => 
      array (
        'name' => 'setDebugMode',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 90,
            'endLine' => 90,
            'startColumn' => 31,
            'endColumn' => 36,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Set parameter %debugMode%.
 * @param  bool|string|array  $value
 * @return static
 */',
        'startLine' => 90,
        'endLine' => 101,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Nette\\Bootstrap',
        'declaringClassName' => 'Nette\\Bootstrap\\Configurator',
        'implementingClassName' => 'Nette\\Bootstrap\\Configurator',
        'currentClassName' => 'Nette\\Bootstrap\\Configurator',
        'aliasName' => NULL,
      ),
      'isDebugMode' => 
      array (
        'name' => 'isDebugMode',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 104,
        'endLine' => 107,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Nette\\Bootstrap',
        'declaringClassName' => 'Nette\\Bootstrap\\Configurator',
        'implementingClassName' => 'Nette\\Bootstrap\\Configurator',
        'currentClassName' => 'Nette\\Bootstrap\\Configurator',
        'aliasName' => NULL,
      ),
      'setTempDirectory' => 
      array (
        'name' => 'setTempDirectory',
        'parameters' => 
        array (
          'path' => 
          array (
            'name' => 'path',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 114,
            'endLine' => 114,
            'startColumn' => 35,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Sets path to temporary directory.
 * @return static
 */',
        'startLine' => 114,
        'endLine' => 118,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Nette\\Bootstrap',
        'declaringClassName' => 'Nette\\Bootstrap\\Configurator',
        'implementingClassName' => 'Nette\\Bootstrap\\Configurator',
        'currentClassName' => 'Nette\\Bootstrap\\Configurator',
        'aliasName' => NULL,
      ),
      'setTimeZone' => 
      array (
        'name' => 'setTimeZone',
        'parameters' => 
        array (
          'timezone' => 
          array (
            'name' => 'timezone',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 125,
            'endLine' => 125,
            'startColumn' => 30,
            'endColumn' => 45,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Sets the default timezone.
 * @return static
 */',
        'startLine' => 125,
        'endLine' => 130,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Nette\\Bootstrap',
        'declaringClassName' => 'Nette\\Bootstrap\\Configurator',
        'implementingClassName' => 'Nette\\Bootstrap\\Configurator',
        'currentClassName' => 'Nette\\Bootstrap\\Configurator',
        'aliasName' => NULL,
      ),
      'addParameters' => 
      array (
        'name' => 'addParameters',
        'parameters' => 
        array (
          'params' => 
          array (
            'name' => 'params',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 137,
            'endLine' => 137,
            'startColumn' => 32,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Alias for addStaticParameters()
 * @return static
 */',
        'startLine' => 137,
        'endLine' => 140,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Nette\\Bootstrap',
        'declaringClassName' => 'Nette\\Bootstrap\\Configurator',
        'implementingClassName' => 'Nette\\Bootstrap\\Configurator',
        'currentClassName' => 'Nette\\Bootstrap\\Configurator',
        'aliasName' => NULL,
      ),
      'addStaticParameters' => 
      array (
        'name' => 'addStaticParameters',
        'parameters' => 
        array (
          'params' => 
          array (
            'name' => 'params',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 147,
            'endLine' => 147,
            'startColumn' => 38,
            'endColumn' => 50,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Adds new static parameters.
 * @return static
 */',
        'startLine' => 147,
        'endLine' => 151,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Nette\\Bootstrap',
        'declaringClassName' => 'Nette\\Bootstrap\\Configurator',
        'implementingClassName' => 'Nette\\Bootstrap\\Configurator',
        'currentClassName' => 'Nette\\Bootstrap\\Configurator',
        'aliasName' => NULL,
      ),
      'addDynamicParameters' => 
      array (
        'name' => 'addDynamicParameters',
        'parameters' => 
        array (
          'params' => 
          array (
            'name' => 'params',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 158,
            'endLine' => 158,
            'startColumn' => 39,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Adds new dynamic parameters.
 * @return static
 */',
        'startLine' => 158,
        'endLine' => 162,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Nette\\Bootstrap',
        'declaringClassName' => 'Nette\\Bootstrap\\Configurator',
        'implementingClassName' => 'Nette\\Bootstrap\\Configurator',
        'currentClassName' => 'Nette\\Bootstrap\\Configurator',
        'aliasName' => NULL,
      ),
      'addServices' => 
      array (
        'name' => 'addServices',
        'parameters' => 
        array (
          'services' => 
          array (
            'name' => 'services',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 169,
            'endLine' => 169,
            'startColumn' => 30,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Add instances of services.
 * @return static
 */',
        'startLine' => 169,
        'endLine' => 173,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Nette\\Bootstrap',
        'declaringClassName' => 'Nette\\Bootstrap\\Configurator',
        'implementingClassName' => 'Nette\\Bootstrap\\Configurator',
        'currentClassName' => 'Nette\\Bootstrap\\Configurator',
        'aliasName' => NULL,
      ),
      'getDefaultParameters' => 
      array (
        'name' => 'getDefaultParameters',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 176,
        'endLine' => 192,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Nette\\Bootstrap',
        'declaringClassName' => 'Nette\\Bootstrap\\Configurator',
        'implementingClassName' => 'Nette\\Bootstrap\\Configurator',
        'currentClassName' => 'Nette\\Bootstrap\\Configurator',
        'aliasName' => NULL,
      ),
      'enableTracy' => 
      array (
        'name' => 'enableTracy',
        'parameters' => 
        array (
          'logDirectory' => 
          array (
            'name' => 'logDirectory',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 195,
                'endLine' => 195,
                'startTokenPos' => 1030,
                'startFilePos' => 5217,
                'endTokenPos' => 1030,
                'endFilePos' => 5220,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
              'data' => 
              array (
                'types' => 
                array (
                  0 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'string',
                      'isIdentifier' => true,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'null',
                      'isIdentifier' => true,
                    ),
                  ),
                ),
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 195,
            'endLine' => 195,
            'startColumn' => 30,
            'endColumn' => 57,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
          'email' => 
          array (
            'name' => 'email',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 195,
                'endLine' => 195,
                'startTokenPos' => 1040,
                'startFilePos' => 5240,
                'endTokenPos' => 1040,
                'endFilePos' => 5243,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
              'data' => 
              array (
                'types' => 
                array (
                  0 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'string',
                      'isIdentifier' => true,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'null',
                      'isIdentifier' => true,
                    ),
                  ),
                ),
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 195,
            'endLine' => 195,
            'startColumn' => 60,
            'endColumn' => 80,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 195,
        'endLine' => 207,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Nette\\Bootstrap',
        'declaringClassName' => 'Nette\\Bootstrap\\Configurator',
        'implementingClassName' => 'Nette\\Bootstrap\\Configurator',
        'currentClassName' => 'Nette\\Bootstrap\\Configurator',
        'aliasName' => NULL,
      ),
      'enableDebugger' => 
      array (
        'name' => 'enableDebugger',
        'parameters' => 
        array (
          'logDirectory' => 
          array (
            'name' => 'logDirectory',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 213,
                'endLine' => 213,
                'startTokenPos' => 1149,
                'startFilePos' => 5806,
                'endTokenPos' => 1149,
                'endFilePos' => 5809,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
              'data' => 
              array (
                'types' => 
                array (
                  0 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'string',
                      'isIdentifier' => true,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'null',
                      'isIdentifier' => true,
                    ),
                  ),
                ),
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 213,
            'endLine' => 213,
            'startColumn' => 33,
            'endColumn' => 60,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
          'email' => 
          array (
            'name' => 'email',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 213,
                'endLine' => 213,
                'startTokenPos' => 1159,
                'startFilePos' => 5829,
                'endTokenPos' => 1159,
                'endFilePos' => 5832,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
              'data' => 
              array (
                'types' => 
                array (
                  0 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'string',
                      'isIdentifier' => true,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'null',
                      'isIdentifier' => true,
                    ),
                  ),
                ),
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 213,
            'endLine' => 213,
            'startColumn' => 63,
            'endColumn' => 83,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Alias for enableTracy()
 */',
        'startLine' => 213,
        'endLine' => 216,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Nette\\Bootstrap',
        'declaringClassName' => 'Nette\\Bootstrap\\Configurator',
        'implementingClassName' => 'Nette\\Bootstrap\\Configurator',
        'currentClassName' => 'Nette\\Bootstrap\\Configurator',
        'aliasName' => NULL,
      ),
      'createRobotLoader' => 
      array (
        'name' => 'createRobotLoader',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Nette\\Loaders\\RobotLoader',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @throws Nette\\NotSupportedException if RobotLoader is not available
 */',
        'startLine' => 222,
        'endLine' => 238,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Nette\\Bootstrap',
        'declaringClassName' => 'Nette\\Bootstrap\\Configurator',
        'implementingClassName' => 'Nette\\Bootstrap\\Configurator',
        'currentClassName' => 'Nette\\Bootstrap\\Configurator',
        'aliasName' => NULL,
      ),
      'addConfig' => 
      array (
        'name' => 'addConfig',
        'parameters' => 
        array (
          'config' => 
          array (
            'name' => 'config',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 246,
            'endLine' => 246,
            'startColumn' => 28,
            'endColumn' => 34,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Adds configuration file.
 * @param  string|array  $config
 * @return static
 */',
        'startLine' => 246,
        'endLine' => 250,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Nette\\Bootstrap',
        'declaringClassName' => 'Nette\\Bootstrap\\Configurator',
        'implementingClassName' => 'Nette\\Bootstrap\\Configurator',
        'currentClassName' => 'Nette\\Bootstrap\\Configurator',
        'aliasName' => NULL,
      ),
      'createContainer' => 
      array (
        'name' => 'createContainer',
        'parameters' => 
        array (
          'initialize' => 
          array (
            'name' => 'initialize',
            'default' => 
            array (
              'code' => 'true',
              'attributes' => 
              array (
                'startLine' => 256,
                'endLine' => 256,
                'startTokenPos' => 1365,
                'startFilePos' => 6892,
                'endTokenPos' => 1365,
                'endFilePos' => 6895,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'bool',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 256,
            'endLine' => 256,
            'startColumn' => 34,
            'endColumn' => 56,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Nette\\DI\\Container',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Returns system DI container.
 */',
        'startLine' => 256,
        'endLine' => 269,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Nette\\Bootstrap',
        'declaringClassName' => 'Nette\\Bootstrap\\Configurator',
        'implementingClassName' => 'Nette\\Bootstrap\\Configurator',
        'currentClassName' => 'Nette\\Bootstrap\\Configurator',
        'aliasName' => NULL,
      ),
      'loadContainer' => 
      array (
        'name' => 'loadContainer',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Loads system DI container class and returns its name.
 */',
        'startLine' => 275,
        'endLine' => 285,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Nette\\Bootstrap',
        'declaringClassName' => 'Nette\\Bootstrap\\Configurator',
        'implementingClassName' => 'Nette\\Bootstrap\\Configurator',
        'currentClassName' => 'Nette\\Bootstrap\\Configurator',
        'aliasName' => NULL,
      ),
      'generateContainer' => 
      array (
        'name' => 'generateContainer',
        'parameters' => 
        array (
          'compiler' => 
          array (
            'name' => 'compiler',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Nette\\DI\\Compiler',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 291,
            'endLine' => 291,
            'startColumn' => 36,
            'endColumn' => 56,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @internal
 */',
        'startLine' => 291,
        'endLine' => 321,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Nette\\Bootstrap',
        'declaringClassName' => 'Nette\\Bootstrap\\Configurator',
        'implementingClassName' => 'Nette\\Bootstrap\\Configurator',
        'currentClassName' => 'Nette\\Bootstrap\\Configurator',
        'aliasName' => NULL,
      ),
      'createLoader' => 
      array (
        'name' => 'createLoader',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Nette\\DI\\Config\\Loader',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 324,
        'endLine' => 327,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Nette\\Bootstrap',
        'declaringClassName' => 'Nette\\Bootstrap\\Configurator',
        'implementingClassName' => 'Nette\\Bootstrap\\Configurator',
        'currentClassName' => 'Nette\\Bootstrap\\Configurator',
        'aliasName' => NULL,
      ),
      'generateContainerKey' => 
      array (
        'name' => 'generateContainerKey',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 330,
        'endLine' => 341,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Nette\\Bootstrap',
        'declaringClassName' => 'Nette\\Bootstrap\\Configurator',
        'implementingClassName' => 'Nette\\Bootstrap\\Configurator',
        'currentClassName' => 'Nette\\Bootstrap\\Configurator',
        'aliasName' => NULL,
      ),
      'getCacheDirectory' => 
      array (
        'name' => 'getCacheDirectory',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 344,
        'endLine' => 353,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Nette\\Bootstrap',
        'declaringClassName' => 'Nette\\Bootstrap\\Configurator',
        'implementingClassName' => 'Nette\\Bootstrap\\Configurator',
        'currentClassName' => 'Nette\\Bootstrap\\Configurator',
        'aliasName' => NULL,
      ),
      'detectDebugMode' => 
      array (
        'name' => 'detectDebugMode',
        'parameters' => 
        array (
          'list' => 
          array (
            'name' => 'list',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 363,
                'endLine' => 363,
                'startTokenPos' => 1985,
                'startFilePos' => 9582,
                'endTokenPos' => 1985,
                'endFilePos' => 9585,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 363,
            'endLine' => 363,
            'startColumn' => 41,
            'endColumn' => 52,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Detects debug mode by IP addresses or computer names whitelist detection.
 * @param  string|array  $list
 */',
        'startLine' => 363,
        'endLine' => 379,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Nette\\Bootstrap',
        'declaringClassName' => 'Nette\\Bootstrap\\Configurator',
        'implementingClassName' => 'Nette\\Bootstrap\\Configurator',
        'currentClassName' => 'Nette\\Bootstrap\\Configurator',
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