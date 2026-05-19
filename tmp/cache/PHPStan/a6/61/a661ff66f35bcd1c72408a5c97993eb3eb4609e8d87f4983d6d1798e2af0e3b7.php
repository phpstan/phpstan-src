<?php declare(strict_types = 1);

// ftm-/home/runner/work/phpstan-src/phpstan-src/src/Command/CommandHelper.php
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v5-2.3.2',
   'data' => 
  array (
    0 => 
    array (
      'fec90f63ad1e485c09968a5cda044b4d' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'PHPStan\\Command',
         'uses' => 
        array (
          'semver' => 'Composer\\Semver\\Semver',
          'xdebughandler' => 'Composer\\XdebugHandler\\XdebugHandler',
          'helpers' => 'Nette\\DI\\Helpers',
          'invalidconfigurationexception' => 'Nette\\DI\\InvalidConfigurationException',
          'servicecreationexception' => 'Nette\\DI\\ServiceCreationException',
          'filenotfoundexception' => 'Nette\\FileNotFoundException',
          'invalidstateexception' => 'Nette\\InvalidStateException',
          'validationexception' => 'Nette\\Schema\\ValidationException',
          'assertionexception' => 'Nette\\Utils\\AssertionException',
          'strings' => 'Nette\\Utils\\Strings',
          'filecachestorage' => 'PHPStan\\Cache\\FileCacheStorage',
          'symfonyoutput' => 'PHPStan\\Command\\Symfony\\SymfonyOutput',
          'symfonystyle' => 'PHPStan\\Command\\Symfony\\SymfonyStyle',
          'container' => 'PHPStan\\DependencyInjection\\Container',
          'containerfactory' => 'PHPStan\\DependencyInjection\\ContainerFactory',
          'duplicateincludedfilesexception' => 'PHPStan\\DependencyInjection\\DuplicateIncludedFilesException',
          'invalidexcludepathsexception' => 'PHPStan\\DependencyInjection\\InvalidExcludePathsException',
          'invalidignorederrorpatternsexception' => 'PHPStan\\DependencyInjection\\InvalidIgnoredErrorPatternsException',
          'loaderfactory' => 'PHPStan\\DependencyInjection\\LoaderFactory',
          'missingimplementedinterfaceinservicewithtagexception' => 'PHPStan\\DependencyInjection\\MissingImplementedInterfaceInServiceWithTagException',
          'generatedconfig' => 'PHPStan\\ExtensionInstaller\\GeneratedConfig',
          'fileexcluder' => 'PHPStan\\File\\FileExcluder',
          'filefinder' => 'PHPStan\\File\\FileFinder',
          'filehelper' => 'PHPStan\\File\\FileHelper',
          'parentdirectoryrelativepathhelper' => 'PHPStan\\File\\ParentDirectoryRelativePathHelper',
          'simplerelativepathhelper' => 'PHPStan\\File\\SimpleRelativePathHelper',
          'composerhelper' => 'PHPStan\\Internal\\ComposerHelper',
          'directorycreator' => 'PHPStan\\Internal\\DirectoryCreator',
          'directorycreatorexception' => 'PHPStan\\Internal\\DirectoryCreatorException',
          'stubfilesprovider' => 'PHPStan\\PhpDoc\\StubFilesProvider',
          'shouldnothappenexception' => 'PHPStan\\ShouldNotHappenException',
          'reflectionclass' => 'ReflectionClass',
          'inputinterface' => 'Symfony\\Component\\Console\\Input\\InputInterface',
          'consoleoutputinterface' => 'Symfony\\Component\\Console\\Output\\ConsoleOutputInterface',
          'outputinterface' => 'Symfony\\Component\\Console\\Output\\OutputInterface',
          'throwable' => 'Throwable',
        ),
         'className' => 'PHPStan\\Command\\CommandHelper',
         'functionName' => NULL,
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => NULL,
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
          'directory_separator' => 'DIRECTORY_SEPARATOR',
          'e_error' => 'E_ERROR',
          'php_version_id' => 'PHP_VERSION_ID',
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '4486f0c51c4a037867842156ea7ad089' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'PHPStan\\Command',
         'uses' => 
        array (
          'semver' => 'Composer\\Semver\\Semver',
          'xdebughandler' => 'Composer\\XdebugHandler\\XdebugHandler',
          'helpers' => 'Nette\\DI\\Helpers',
          'invalidconfigurationexception' => 'Nette\\DI\\InvalidConfigurationException',
          'servicecreationexception' => 'Nette\\DI\\ServiceCreationException',
          'filenotfoundexception' => 'Nette\\FileNotFoundException',
          'invalidstateexception' => 'Nette\\InvalidStateException',
          'validationexception' => 'Nette\\Schema\\ValidationException',
          'assertionexception' => 'Nette\\Utils\\AssertionException',
          'strings' => 'Nette\\Utils\\Strings',
          'filecachestorage' => 'PHPStan\\Cache\\FileCacheStorage',
          'symfonyoutput' => 'PHPStan\\Command\\Symfony\\SymfonyOutput',
          'symfonystyle' => 'PHPStan\\Command\\Symfony\\SymfonyStyle',
          'container' => 'PHPStan\\DependencyInjection\\Container',
          'containerfactory' => 'PHPStan\\DependencyInjection\\ContainerFactory',
          'duplicateincludedfilesexception' => 'PHPStan\\DependencyInjection\\DuplicateIncludedFilesException',
          'invalidexcludepathsexception' => 'PHPStan\\DependencyInjection\\InvalidExcludePathsException',
          'invalidignorederrorpatternsexception' => 'PHPStan\\DependencyInjection\\InvalidIgnoredErrorPatternsException',
          'loaderfactory' => 'PHPStan\\DependencyInjection\\LoaderFactory',
          'missingimplementedinterfaceinservicewithtagexception' => 'PHPStan\\DependencyInjection\\MissingImplementedInterfaceInServiceWithTagException',
          'generatedconfig' => 'PHPStan\\ExtensionInstaller\\GeneratedConfig',
          'fileexcluder' => 'PHPStan\\File\\FileExcluder',
          'filefinder' => 'PHPStan\\File\\FileFinder',
          'filehelper' => 'PHPStan\\File\\FileHelper',
          'parentdirectoryrelativepathhelper' => 'PHPStan\\File\\ParentDirectoryRelativePathHelper',
          'simplerelativepathhelper' => 'PHPStan\\File\\SimpleRelativePathHelper',
          'composerhelper' => 'PHPStan\\Internal\\ComposerHelper',
          'directorycreator' => 'PHPStan\\Internal\\DirectoryCreator',
          'directorycreatorexception' => 'PHPStan\\Internal\\DirectoryCreatorException',
          'stubfilesprovider' => 'PHPStan\\PhpDoc\\StubFilesProvider',
          'shouldnothappenexception' => 'PHPStan\\ShouldNotHappenException',
          'reflectionclass' => 'ReflectionClass',
          'inputinterface' => 'Symfony\\Component\\Console\\Input\\InputInterface',
          'consoleoutputinterface' => 'Symfony\\Component\\Console\\Output\\ConsoleOutputInterface',
          'outputinterface' => 'Symfony\\Component\\Console\\Output\\OutputInterface',
          'throwable' => 'Throwable',
        ),
         'className' => 'PHPStan\\Command\\CommandHelper',
         'functionName' => 'begin',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => NULL,
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
          'directory_separator' => 'DIRECTORY_SEPARATOR',
          'e_error' => 'E_ERROR',
          'php_version_id' => 'PHP_VERSION_ID',
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '93187cbc146be047f3aad0b76ba09f32' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'PHPStan\\Command',
         'uses' => 
        array (
          'semver' => 'Composer\\Semver\\Semver',
          'xdebughandler' => 'Composer\\XdebugHandler\\XdebugHandler',
          'helpers' => 'Nette\\DI\\Helpers',
          'invalidconfigurationexception' => 'Nette\\DI\\InvalidConfigurationException',
          'servicecreationexception' => 'Nette\\DI\\ServiceCreationException',
          'filenotfoundexception' => 'Nette\\FileNotFoundException',
          'invalidstateexception' => 'Nette\\InvalidStateException',
          'validationexception' => 'Nette\\Schema\\ValidationException',
          'assertionexception' => 'Nette\\Utils\\AssertionException',
          'strings' => 'Nette\\Utils\\Strings',
          'filecachestorage' => 'PHPStan\\Cache\\FileCacheStorage',
          'symfonyoutput' => 'PHPStan\\Command\\Symfony\\SymfonyOutput',
          'symfonystyle' => 'PHPStan\\Command\\Symfony\\SymfonyStyle',
          'container' => 'PHPStan\\DependencyInjection\\Container',
          'containerfactory' => 'PHPStan\\DependencyInjection\\ContainerFactory',
          'duplicateincludedfilesexception' => 'PHPStan\\DependencyInjection\\DuplicateIncludedFilesException',
          'invalidexcludepathsexception' => 'PHPStan\\DependencyInjection\\InvalidExcludePathsException',
          'invalidignorederrorpatternsexception' => 'PHPStan\\DependencyInjection\\InvalidIgnoredErrorPatternsException',
          'loaderfactory' => 'PHPStan\\DependencyInjection\\LoaderFactory',
          'missingimplementedinterfaceinservicewithtagexception' => 'PHPStan\\DependencyInjection\\MissingImplementedInterfaceInServiceWithTagException',
          'generatedconfig' => 'PHPStan\\ExtensionInstaller\\GeneratedConfig',
          'fileexcluder' => 'PHPStan\\File\\FileExcluder',
          'filefinder' => 'PHPStan\\File\\FileFinder',
          'filehelper' => 'PHPStan\\File\\FileHelper',
          'parentdirectoryrelativepathhelper' => 'PHPStan\\File\\ParentDirectoryRelativePathHelper',
          'simplerelativepathhelper' => 'PHPStan\\File\\SimpleRelativePathHelper',
          'composerhelper' => 'PHPStan\\Internal\\ComposerHelper',
          'directorycreator' => 'PHPStan\\Internal\\DirectoryCreator',
          'directorycreatorexception' => 'PHPStan\\Internal\\DirectoryCreatorException',
          'stubfilesprovider' => 'PHPStan\\PhpDoc\\StubFilesProvider',
          'shouldnothappenexception' => 'PHPStan\\ShouldNotHappenException',
          'reflectionclass' => 'ReflectionClass',
          'inputinterface' => 'Symfony\\Component\\Console\\Input\\InputInterface',
          'consoleoutputinterface' => 'Symfony\\Component\\Console\\Output\\ConsoleOutputInterface',
          'outputinterface' => 'Symfony\\Component\\Console\\Output\\OutputInterface',
          'throwable' => 'Throwable',
        ),
         'className' => 'PHPStan\\Command\\CommandHelper',
         'functionName' => 'executeBootstrapFile',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => NULL,
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
          'directory_separator' => 'DIRECTORY_SEPARATOR',
          'e_error' => 'E_ERROR',
          'php_version_id' => 'PHP_VERSION_ID',
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
    ),
    1 => 
    array (
      '/home/runner/work/phpstan-src/phpstan-src/src/Command/CommandHelper.php' => '7274fd569241ab1af8e1c21e2267ea6b0082ca0bcea6ce90788608924421f96b',
    ),
  ),
));