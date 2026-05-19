<?php declare(strict_types = 1);

// ftm-/home/runner/work/phpstan-src/phpstan-src/src/Analyser/ResultCache/ResultCacheManager.php
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v5-2.3.2',
   'data' => 
  array (
    0 => 
    array (
      '5bc5fe76b22536f431a0ab35958601ea' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'PHPStan\\Analyser\\ResultCache',
         'uses' => 
        array (
          'neon' => 'Nette\\Neon\\Neon',
          'analyserresult' => 'PHPStan\\Analyser\\AnalyserResult',
          'error' => 'PHPStan\\Analyser\\Error',
          'fileanalyserresult' => 'PHPStan\\Analyser\\FileAnalyserResult',
          'collecteddata' => 'PHPStan\\Collectors\\CollectedData',
          'output' => 'PHPStan\\Command\\Output',
          'exportedtraitnode' => 'PHPStan\\Dependency\\ExportedNode\\ExportedTraitNode',
          'exportednodefetcher' => 'PHPStan\\Dependency\\ExportedNodeFetcher',
          'rootexportednode' => 'PHPStan\\Dependency\\RootExportedNode',
          'autowiredparameter' => 'PHPStan\\DependencyInjection\\AutowiredParameter',
          'container' => 'PHPStan\\DependencyInjection\\Container',
          'generatefactory' => 'PHPStan\\DependencyInjection\\GenerateFactory',
          'projectconfighelper' => 'PHPStan\\DependencyInjection\\ProjectConfigHelper',
          'couldnotreadfileexception' => 'PHPStan\\File\\CouldNotReadFileException',
          'filefinder' => 'PHPStan\\File\\FileFinder',
          'filehelper' => 'PHPStan\\File\\FileHelper',
          'filewriter' => 'PHPStan\\File\\FileWriter',
          'arrayhelper' => 'PHPStan\\Internal\\ArrayHelper',
          'composerhelper' => 'PHPStan\\Internal\\ComposerHelper',
          'stubfilesprovider' => 'PHPStan\\PhpDoc\\StubFilesProvider',
          'shouldnothappenexception' => 'PHPStan\\ShouldNotHappenException',
          'reflectionclass' => 'ReflectionClass',
          'reflectionexception' => 'ReflectionException',
          'throwable' => 'Throwable',
        ),
         'className' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheManager',
         'functionName' => NULL,
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => NULL,
         'typeAliasesMap' => 
        array (
          'LinesToIgnore' => true,
          'CollectorData' => true,
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
          'php_version_id' => 'PHP_VERSION_ID',
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '8697c64c0ce91d8449a220737d9f3441' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'PHPStan\\Analyser\\ResultCache',
         'uses' => 
        array (
          'neon' => 'Nette\\Neon\\Neon',
          'analyserresult' => 'PHPStan\\Analyser\\AnalyserResult',
          'error' => 'PHPStan\\Analyser\\Error',
          'fileanalyserresult' => 'PHPStan\\Analyser\\FileAnalyserResult',
          'collecteddata' => 'PHPStan\\Collectors\\CollectedData',
          'output' => 'PHPStan\\Command\\Output',
          'exportedtraitnode' => 'PHPStan\\Dependency\\ExportedNode\\ExportedTraitNode',
          'exportednodefetcher' => 'PHPStan\\Dependency\\ExportedNodeFetcher',
          'rootexportednode' => 'PHPStan\\Dependency\\RootExportedNode',
          'autowiredparameter' => 'PHPStan\\DependencyInjection\\AutowiredParameter',
          'container' => 'PHPStan\\DependencyInjection\\Container',
          'generatefactory' => 'PHPStan\\DependencyInjection\\GenerateFactory',
          'projectconfighelper' => 'PHPStan\\DependencyInjection\\ProjectConfigHelper',
          'couldnotreadfileexception' => 'PHPStan\\File\\CouldNotReadFileException',
          'filefinder' => 'PHPStan\\File\\FileFinder',
          'filehelper' => 'PHPStan\\File\\FileHelper',
          'filewriter' => 'PHPStan\\File\\FileWriter',
          'arrayhelper' => 'PHPStan\\Internal\\ArrayHelper',
          'composerhelper' => 'PHPStan\\Internal\\ComposerHelper',
          'stubfilesprovider' => 'PHPStan\\PhpDoc\\StubFilesProvider',
          'shouldnothappenexception' => 'PHPStan\\ShouldNotHappenException',
          'reflectionclass' => 'ReflectionClass',
          'reflectionexception' => 'ReflectionException',
          'throwable' => 'Throwable',
        ),
         'className' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheManager',
         'functionName' => '__construct',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'PHPStan\\Analyser\\ResultCache',
           'uses' => 
          array (
            'neon' => 'Nette\\Neon\\Neon',
            'analyserresult' => 'PHPStan\\Analyser\\AnalyserResult',
            'error' => 'PHPStan\\Analyser\\Error',
            'fileanalyserresult' => 'PHPStan\\Analyser\\FileAnalyserResult',
            'collecteddata' => 'PHPStan\\Collectors\\CollectedData',
            'output' => 'PHPStan\\Command\\Output',
            'exportedtraitnode' => 'PHPStan\\Dependency\\ExportedNode\\ExportedTraitNode',
            'exportednodefetcher' => 'PHPStan\\Dependency\\ExportedNodeFetcher',
            'rootexportednode' => 'PHPStan\\Dependency\\RootExportedNode',
            'autowiredparameter' => 'PHPStan\\DependencyInjection\\AutowiredParameter',
            'container' => 'PHPStan\\DependencyInjection\\Container',
            'generatefactory' => 'PHPStan\\DependencyInjection\\GenerateFactory',
            'projectconfighelper' => 'PHPStan\\DependencyInjection\\ProjectConfigHelper',
            'couldnotreadfileexception' => 'PHPStan\\File\\CouldNotReadFileException',
            'filefinder' => 'PHPStan\\File\\FileFinder',
            'filehelper' => 'PHPStan\\File\\FileHelper',
            'filewriter' => 'PHPStan\\File\\FileWriter',
            'arrayhelper' => 'PHPStan\\Internal\\ArrayHelper',
            'composerhelper' => 'PHPStan\\Internal\\ComposerHelper',
            'stubfilesprovider' => 'PHPStan\\PhpDoc\\StubFilesProvider',
            'shouldnothappenexception' => 'PHPStan\\ShouldNotHappenException',
            'reflectionclass' => 'ReflectionClass',
            'reflectionexception' => 'ReflectionException',
            'throwable' => 'Throwable',
          ),
           'className' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheManager',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
            'LinesToIgnore' => true,
            'CollectorData' => true,
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
            'php_version_id' => 'PHP_VERSION_ID',
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
          'LinesToIgnore' => true,
          'CollectorData' => true,
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
          'php_version_id' => 'PHP_VERSION_ID',
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '30c0153b66e6ceeeaeb440cd2add59e7' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'PHPStan\\Analyser\\ResultCache',
         'uses' => 
        array (
          'neon' => 'Nette\\Neon\\Neon',
          'analyserresult' => 'PHPStan\\Analyser\\AnalyserResult',
          'error' => 'PHPStan\\Analyser\\Error',
          'fileanalyserresult' => 'PHPStan\\Analyser\\FileAnalyserResult',
          'collecteddata' => 'PHPStan\\Collectors\\CollectedData',
          'output' => 'PHPStan\\Command\\Output',
          'exportedtraitnode' => 'PHPStan\\Dependency\\ExportedNode\\ExportedTraitNode',
          'exportednodefetcher' => 'PHPStan\\Dependency\\ExportedNodeFetcher',
          'rootexportednode' => 'PHPStan\\Dependency\\RootExportedNode',
          'autowiredparameter' => 'PHPStan\\DependencyInjection\\AutowiredParameter',
          'container' => 'PHPStan\\DependencyInjection\\Container',
          'generatefactory' => 'PHPStan\\DependencyInjection\\GenerateFactory',
          'projectconfighelper' => 'PHPStan\\DependencyInjection\\ProjectConfigHelper',
          'couldnotreadfileexception' => 'PHPStan\\File\\CouldNotReadFileException',
          'filefinder' => 'PHPStan\\File\\FileFinder',
          'filehelper' => 'PHPStan\\File\\FileHelper',
          'filewriter' => 'PHPStan\\File\\FileWriter',
          'arrayhelper' => 'PHPStan\\Internal\\ArrayHelper',
          'composerhelper' => 'PHPStan\\Internal\\ComposerHelper',
          'stubfilesprovider' => 'PHPStan\\PhpDoc\\StubFilesProvider',
          'shouldnothappenexception' => 'PHPStan\\ShouldNotHappenException',
          'reflectionclass' => 'ReflectionClass',
          'reflectionexception' => 'ReflectionException',
          'throwable' => 'Throwable',
        ),
         'className' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheManager',
         'functionName' => 'restore',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'PHPStan\\Analyser\\ResultCache',
           'uses' => 
          array (
            'neon' => 'Nette\\Neon\\Neon',
            'analyserresult' => 'PHPStan\\Analyser\\AnalyserResult',
            'error' => 'PHPStan\\Analyser\\Error',
            'fileanalyserresult' => 'PHPStan\\Analyser\\FileAnalyserResult',
            'collecteddata' => 'PHPStan\\Collectors\\CollectedData',
            'output' => 'PHPStan\\Command\\Output',
            'exportedtraitnode' => 'PHPStan\\Dependency\\ExportedNode\\ExportedTraitNode',
            'exportednodefetcher' => 'PHPStan\\Dependency\\ExportedNodeFetcher',
            'rootexportednode' => 'PHPStan\\Dependency\\RootExportedNode',
            'autowiredparameter' => 'PHPStan\\DependencyInjection\\AutowiredParameter',
            'container' => 'PHPStan\\DependencyInjection\\Container',
            'generatefactory' => 'PHPStan\\DependencyInjection\\GenerateFactory',
            'projectconfighelper' => 'PHPStan\\DependencyInjection\\ProjectConfigHelper',
            'couldnotreadfileexception' => 'PHPStan\\File\\CouldNotReadFileException',
            'filefinder' => 'PHPStan\\File\\FileFinder',
            'filehelper' => 'PHPStan\\File\\FileHelper',
            'filewriter' => 'PHPStan\\File\\FileWriter',
            'arrayhelper' => 'PHPStan\\Internal\\ArrayHelper',
            'composerhelper' => 'PHPStan\\Internal\\ComposerHelper',
            'stubfilesprovider' => 'PHPStan\\PhpDoc\\StubFilesProvider',
            'shouldnothappenexception' => 'PHPStan\\ShouldNotHappenException',
            'reflectionclass' => 'ReflectionClass',
            'reflectionexception' => 'ReflectionException',
            'throwable' => 'Throwable',
          ),
           'className' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheManager',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
            'LinesToIgnore' => true,
            'CollectorData' => true,
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
            'php_version_id' => 'PHP_VERSION_ID',
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
          'LinesToIgnore' => true,
          'CollectorData' => true,
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
          'php_version_id' => 'PHP_VERSION_ID',
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '9d5be39cefc5ec57e14a0ae42c632405' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'PHPStan\\Analyser\\ResultCache',
         'uses' => 
        array (
          'neon' => 'Nette\\Neon\\Neon',
          'analyserresult' => 'PHPStan\\Analyser\\AnalyserResult',
          'error' => 'PHPStan\\Analyser\\Error',
          'fileanalyserresult' => 'PHPStan\\Analyser\\FileAnalyserResult',
          'collecteddata' => 'PHPStan\\Collectors\\CollectedData',
          'output' => 'PHPStan\\Command\\Output',
          'exportedtraitnode' => 'PHPStan\\Dependency\\ExportedNode\\ExportedTraitNode',
          'exportednodefetcher' => 'PHPStan\\Dependency\\ExportedNodeFetcher',
          'rootexportednode' => 'PHPStan\\Dependency\\RootExportedNode',
          'autowiredparameter' => 'PHPStan\\DependencyInjection\\AutowiredParameter',
          'container' => 'PHPStan\\DependencyInjection\\Container',
          'generatefactory' => 'PHPStan\\DependencyInjection\\GenerateFactory',
          'projectconfighelper' => 'PHPStan\\DependencyInjection\\ProjectConfigHelper',
          'couldnotreadfileexception' => 'PHPStan\\File\\CouldNotReadFileException',
          'filefinder' => 'PHPStan\\File\\FileFinder',
          'filehelper' => 'PHPStan\\File\\FileHelper',
          'filewriter' => 'PHPStan\\File\\FileWriter',
          'arrayhelper' => 'PHPStan\\Internal\\ArrayHelper',
          'composerhelper' => 'PHPStan\\Internal\\ComposerHelper',
          'stubfilesprovider' => 'PHPStan\\PhpDoc\\StubFilesProvider',
          'shouldnothappenexception' => 'PHPStan\\ShouldNotHappenException',
          'reflectionclass' => 'ReflectionClass',
          'reflectionexception' => 'ReflectionException',
          'throwable' => 'Throwable',
        ),
         'className' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheManager',
         'functionName' => 'isMetaDifferent',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'PHPStan\\Analyser\\ResultCache',
           'uses' => 
          array (
            'neon' => 'Nette\\Neon\\Neon',
            'analyserresult' => 'PHPStan\\Analyser\\AnalyserResult',
            'error' => 'PHPStan\\Analyser\\Error',
            'fileanalyserresult' => 'PHPStan\\Analyser\\FileAnalyserResult',
            'collecteddata' => 'PHPStan\\Collectors\\CollectedData',
            'output' => 'PHPStan\\Command\\Output',
            'exportedtraitnode' => 'PHPStan\\Dependency\\ExportedNode\\ExportedTraitNode',
            'exportednodefetcher' => 'PHPStan\\Dependency\\ExportedNodeFetcher',
            'rootexportednode' => 'PHPStan\\Dependency\\RootExportedNode',
            'autowiredparameter' => 'PHPStan\\DependencyInjection\\AutowiredParameter',
            'container' => 'PHPStan\\DependencyInjection\\Container',
            'generatefactory' => 'PHPStan\\DependencyInjection\\GenerateFactory',
            'projectconfighelper' => 'PHPStan\\DependencyInjection\\ProjectConfigHelper',
            'couldnotreadfileexception' => 'PHPStan\\File\\CouldNotReadFileException',
            'filefinder' => 'PHPStan\\File\\FileFinder',
            'filehelper' => 'PHPStan\\File\\FileHelper',
            'filewriter' => 'PHPStan\\File\\FileWriter',
            'arrayhelper' => 'PHPStan\\Internal\\ArrayHelper',
            'composerhelper' => 'PHPStan\\Internal\\ComposerHelper',
            'stubfilesprovider' => 'PHPStan\\PhpDoc\\StubFilesProvider',
            'shouldnothappenexception' => 'PHPStan\\ShouldNotHappenException',
            'reflectionclass' => 'ReflectionClass',
            'reflectionexception' => 'ReflectionException',
            'throwable' => 'Throwable',
          ),
           'className' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheManager',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
            'LinesToIgnore' => true,
            'CollectorData' => true,
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
            'php_version_id' => 'PHP_VERSION_ID',
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
          'LinesToIgnore' => true,
          'CollectorData' => true,
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
          'php_version_id' => 'PHP_VERSION_ID',
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      'b3fdd7149fefbb07be3c49aa7b03368e' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'PHPStan\\Analyser\\ResultCache',
         'uses' => 
        array (
          'neon' => 'Nette\\Neon\\Neon',
          'analyserresult' => 'PHPStan\\Analyser\\AnalyserResult',
          'error' => 'PHPStan\\Analyser\\Error',
          'fileanalyserresult' => 'PHPStan\\Analyser\\FileAnalyserResult',
          'collecteddata' => 'PHPStan\\Collectors\\CollectedData',
          'output' => 'PHPStan\\Command\\Output',
          'exportedtraitnode' => 'PHPStan\\Dependency\\ExportedNode\\ExportedTraitNode',
          'exportednodefetcher' => 'PHPStan\\Dependency\\ExportedNodeFetcher',
          'rootexportednode' => 'PHPStan\\Dependency\\RootExportedNode',
          'autowiredparameter' => 'PHPStan\\DependencyInjection\\AutowiredParameter',
          'container' => 'PHPStan\\DependencyInjection\\Container',
          'generatefactory' => 'PHPStan\\DependencyInjection\\GenerateFactory',
          'projectconfighelper' => 'PHPStan\\DependencyInjection\\ProjectConfigHelper',
          'couldnotreadfileexception' => 'PHPStan\\File\\CouldNotReadFileException',
          'filefinder' => 'PHPStan\\File\\FileFinder',
          'filehelper' => 'PHPStan\\File\\FileHelper',
          'filewriter' => 'PHPStan\\File\\FileWriter',
          'arrayhelper' => 'PHPStan\\Internal\\ArrayHelper',
          'composerhelper' => 'PHPStan\\Internal\\ComposerHelper',
          'stubfilesprovider' => 'PHPStan\\PhpDoc\\StubFilesProvider',
          'shouldnothappenexception' => 'PHPStan\\ShouldNotHappenException',
          'reflectionclass' => 'ReflectionClass',
          'reflectionexception' => 'ReflectionException',
          'throwable' => 'Throwable',
        ),
         'className' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheManager',
         'functionName' => 'getMetaKeyDifferences',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'PHPStan\\Analyser\\ResultCache',
           'uses' => 
          array (
            'neon' => 'Nette\\Neon\\Neon',
            'analyserresult' => 'PHPStan\\Analyser\\AnalyserResult',
            'error' => 'PHPStan\\Analyser\\Error',
            'fileanalyserresult' => 'PHPStan\\Analyser\\FileAnalyserResult',
            'collecteddata' => 'PHPStan\\Collectors\\CollectedData',
            'output' => 'PHPStan\\Command\\Output',
            'exportedtraitnode' => 'PHPStan\\Dependency\\ExportedNode\\ExportedTraitNode',
            'exportednodefetcher' => 'PHPStan\\Dependency\\ExportedNodeFetcher',
            'rootexportednode' => 'PHPStan\\Dependency\\RootExportedNode',
            'autowiredparameter' => 'PHPStan\\DependencyInjection\\AutowiredParameter',
            'container' => 'PHPStan\\DependencyInjection\\Container',
            'generatefactory' => 'PHPStan\\DependencyInjection\\GenerateFactory',
            'projectconfighelper' => 'PHPStan\\DependencyInjection\\ProjectConfigHelper',
            'couldnotreadfileexception' => 'PHPStan\\File\\CouldNotReadFileException',
            'filefinder' => 'PHPStan\\File\\FileFinder',
            'filehelper' => 'PHPStan\\File\\FileHelper',
            'filewriter' => 'PHPStan\\File\\FileWriter',
            'arrayhelper' => 'PHPStan\\Internal\\ArrayHelper',
            'composerhelper' => 'PHPStan\\Internal\\ComposerHelper',
            'stubfilesprovider' => 'PHPStan\\PhpDoc\\StubFilesProvider',
            'shouldnothappenexception' => 'PHPStan\\ShouldNotHappenException',
            'reflectionclass' => 'ReflectionClass',
            'reflectionexception' => 'ReflectionException',
            'throwable' => 'Throwable',
          ),
           'className' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheManager',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
            'LinesToIgnore' => true,
            'CollectorData' => true,
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
            'php_version_id' => 'PHP_VERSION_ID',
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
          'LinesToIgnore' => true,
          'CollectorData' => true,
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
          'php_version_id' => 'PHP_VERSION_ID',
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '97fa8063c3eed20dd1fb2f108b76e7f9' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'PHPStan\\Analyser\\ResultCache',
         'uses' => 
        array (
          'neon' => 'Nette\\Neon\\Neon',
          'analyserresult' => 'PHPStan\\Analyser\\AnalyserResult',
          'error' => 'PHPStan\\Analyser\\Error',
          'fileanalyserresult' => 'PHPStan\\Analyser\\FileAnalyserResult',
          'collecteddata' => 'PHPStan\\Collectors\\CollectedData',
          'output' => 'PHPStan\\Command\\Output',
          'exportedtraitnode' => 'PHPStan\\Dependency\\ExportedNode\\ExportedTraitNode',
          'exportednodefetcher' => 'PHPStan\\Dependency\\ExportedNodeFetcher',
          'rootexportednode' => 'PHPStan\\Dependency\\RootExportedNode',
          'autowiredparameter' => 'PHPStan\\DependencyInjection\\AutowiredParameter',
          'container' => 'PHPStan\\DependencyInjection\\Container',
          'generatefactory' => 'PHPStan\\DependencyInjection\\GenerateFactory',
          'projectconfighelper' => 'PHPStan\\DependencyInjection\\ProjectConfigHelper',
          'couldnotreadfileexception' => 'PHPStan\\File\\CouldNotReadFileException',
          'filefinder' => 'PHPStan\\File\\FileFinder',
          'filehelper' => 'PHPStan\\File\\FileHelper',
          'filewriter' => 'PHPStan\\File\\FileWriter',
          'arrayhelper' => 'PHPStan\\Internal\\ArrayHelper',
          'composerhelper' => 'PHPStan\\Internal\\ComposerHelper',
          'stubfilesprovider' => 'PHPStan\\PhpDoc\\StubFilesProvider',
          'shouldnothappenexception' => 'PHPStan\\ShouldNotHappenException',
          'reflectionclass' => 'ReflectionClass',
          'reflectionexception' => 'ReflectionException',
          'throwable' => 'Throwable',
        ),
         'className' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheManager',
         'functionName' => 'exportedNodesChanged',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'PHPStan\\Analyser\\ResultCache',
           'uses' => 
          array (
            'neon' => 'Nette\\Neon\\Neon',
            'analyserresult' => 'PHPStan\\Analyser\\AnalyserResult',
            'error' => 'PHPStan\\Analyser\\Error',
            'fileanalyserresult' => 'PHPStan\\Analyser\\FileAnalyserResult',
            'collecteddata' => 'PHPStan\\Collectors\\CollectedData',
            'output' => 'PHPStan\\Command\\Output',
            'exportedtraitnode' => 'PHPStan\\Dependency\\ExportedNode\\ExportedTraitNode',
            'exportednodefetcher' => 'PHPStan\\Dependency\\ExportedNodeFetcher',
            'rootexportednode' => 'PHPStan\\Dependency\\RootExportedNode',
            'autowiredparameter' => 'PHPStan\\DependencyInjection\\AutowiredParameter',
            'container' => 'PHPStan\\DependencyInjection\\Container',
            'generatefactory' => 'PHPStan\\DependencyInjection\\GenerateFactory',
            'projectconfighelper' => 'PHPStan\\DependencyInjection\\ProjectConfigHelper',
            'couldnotreadfileexception' => 'PHPStan\\File\\CouldNotReadFileException',
            'filefinder' => 'PHPStan\\File\\FileFinder',
            'filehelper' => 'PHPStan\\File\\FileHelper',
            'filewriter' => 'PHPStan\\File\\FileWriter',
            'arrayhelper' => 'PHPStan\\Internal\\ArrayHelper',
            'composerhelper' => 'PHPStan\\Internal\\ComposerHelper',
            'stubfilesprovider' => 'PHPStan\\PhpDoc\\StubFilesProvider',
            'shouldnothappenexception' => 'PHPStan\\ShouldNotHappenException',
            'reflectionclass' => 'ReflectionClass',
            'reflectionexception' => 'ReflectionException',
            'throwable' => 'Throwable',
          ),
           'className' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheManager',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
            'LinesToIgnore' => true,
            'CollectorData' => true,
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
            'php_version_id' => 'PHP_VERSION_ID',
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
          'LinesToIgnore' => true,
          'CollectorData' => true,
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
          'php_version_id' => 'PHP_VERSION_ID',
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '8d723127edfda9e1068b3d99ca7c8a2f' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'PHPStan\\Analyser\\ResultCache',
         'uses' => 
        array (
          'neon' => 'Nette\\Neon\\Neon',
          'analyserresult' => 'PHPStan\\Analyser\\AnalyserResult',
          'error' => 'PHPStan\\Analyser\\Error',
          'fileanalyserresult' => 'PHPStan\\Analyser\\FileAnalyserResult',
          'collecteddata' => 'PHPStan\\Collectors\\CollectedData',
          'output' => 'PHPStan\\Command\\Output',
          'exportedtraitnode' => 'PHPStan\\Dependency\\ExportedNode\\ExportedTraitNode',
          'exportednodefetcher' => 'PHPStan\\Dependency\\ExportedNodeFetcher',
          'rootexportednode' => 'PHPStan\\Dependency\\RootExportedNode',
          'autowiredparameter' => 'PHPStan\\DependencyInjection\\AutowiredParameter',
          'container' => 'PHPStan\\DependencyInjection\\Container',
          'generatefactory' => 'PHPStan\\DependencyInjection\\GenerateFactory',
          'projectconfighelper' => 'PHPStan\\DependencyInjection\\ProjectConfigHelper',
          'couldnotreadfileexception' => 'PHPStan\\File\\CouldNotReadFileException',
          'filefinder' => 'PHPStan\\File\\FileFinder',
          'filehelper' => 'PHPStan\\File\\FileHelper',
          'filewriter' => 'PHPStan\\File\\FileWriter',
          'arrayhelper' => 'PHPStan\\Internal\\ArrayHelper',
          'composerhelper' => 'PHPStan\\Internal\\ComposerHelper',
          'stubfilesprovider' => 'PHPStan\\PhpDoc\\StubFilesProvider',
          'shouldnothappenexception' => 'PHPStan\\ShouldNotHappenException',
          'reflectionclass' => 'ReflectionClass',
          'reflectionexception' => 'ReflectionException',
          'throwable' => 'Throwable',
        ),
         'className' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheManager',
         'functionName' => 'process',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'PHPStan\\Analyser\\ResultCache',
           'uses' => 
          array (
            'neon' => 'Nette\\Neon\\Neon',
            'analyserresult' => 'PHPStan\\Analyser\\AnalyserResult',
            'error' => 'PHPStan\\Analyser\\Error',
            'fileanalyserresult' => 'PHPStan\\Analyser\\FileAnalyserResult',
            'collecteddata' => 'PHPStan\\Collectors\\CollectedData',
            'output' => 'PHPStan\\Command\\Output',
            'exportedtraitnode' => 'PHPStan\\Dependency\\ExportedNode\\ExportedTraitNode',
            'exportednodefetcher' => 'PHPStan\\Dependency\\ExportedNodeFetcher',
            'rootexportednode' => 'PHPStan\\Dependency\\RootExportedNode',
            'autowiredparameter' => 'PHPStan\\DependencyInjection\\AutowiredParameter',
            'container' => 'PHPStan\\DependencyInjection\\Container',
            'generatefactory' => 'PHPStan\\DependencyInjection\\GenerateFactory',
            'projectconfighelper' => 'PHPStan\\DependencyInjection\\ProjectConfigHelper',
            'couldnotreadfileexception' => 'PHPStan\\File\\CouldNotReadFileException',
            'filefinder' => 'PHPStan\\File\\FileFinder',
            'filehelper' => 'PHPStan\\File\\FileHelper',
            'filewriter' => 'PHPStan\\File\\FileWriter',
            'arrayhelper' => 'PHPStan\\Internal\\ArrayHelper',
            'composerhelper' => 'PHPStan\\Internal\\ComposerHelper',
            'stubfilesprovider' => 'PHPStan\\PhpDoc\\StubFilesProvider',
            'shouldnothappenexception' => 'PHPStan\\ShouldNotHappenException',
            'reflectionclass' => 'ReflectionClass',
            'reflectionexception' => 'ReflectionException',
            'throwable' => 'Throwable',
          ),
           'className' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheManager',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
            'LinesToIgnore' => true,
            'CollectorData' => true,
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
            'php_version_id' => 'PHP_VERSION_ID',
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
          'LinesToIgnore' => true,
          'CollectorData' => true,
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
          'php_version_id' => 'PHP_VERSION_ID',
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      'c1bd1e666659e609201cebbadd778a86' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'PHPStan\\Analyser\\ResultCache',
         'uses' => 
        array (
          'neon' => 'Nette\\Neon\\Neon',
          'analyserresult' => 'PHPStan\\Analyser\\AnalyserResult',
          'error' => 'PHPStan\\Analyser\\Error',
          'fileanalyserresult' => 'PHPStan\\Analyser\\FileAnalyserResult',
          'collecteddata' => 'PHPStan\\Collectors\\CollectedData',
          'output' => 'PHPStan\\Command\\Output',
          'exportedtraitnode' => 'PHPStan\\Dependency\\ExportedNode\\ExportedTraitNode',
          'exportednodefetcher' => 'PHPStan\\Dependency\\ExportedNodeFetcher',
          'rootexportednode' => 'PHPStan\\Dependency\\RootExportedNode',
          'autowiredparameter' => 'PHPStan\\DependencyInjection\\AutowiredParameter',
          'container' => 'PHPStan\\DependencyInjection\\Container',
          'generatefactory' => 'PHPStan\\DependencyInjection\\GenerateFactory',
          'projectconfighelper' => 'PHPStan\\DependencyInjection\\ProjectConfigHelper',
          'couldnotreadfileexception' => 'PHPStan\\File\\CouldNotReadFileException',
          'filefinder' => 'PHPStan\\File\\FileFinder',
          'filehelper' => 'PHPStan\\File\\FileHelper',
          'filewriter' => 'PHPStan\\File\\FileWriter',
          'arrayhelper' => 'PHPStan\\Internal\\ArrayHelper',
          'composerhelper' => 'PHPStan\\Internal\\ComposerHelper',
          'stubfilesprovider' => 'PHPStan\\PhpDoc\\StubFilesProvider',
          'shouldnothappenexception' => 'PHPStan\\ShouldNotHappenException',
          'reflectionclass' => 'ReflectionClass',
          'reflectionexception' => 'ReflectionException',
          'throwable' => 'Throwable',
        ),
         'className' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheManager',
         'functionName' => 'mergeErrors',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'PHPStan\\Analyser\\ResultCache',
           'uses' => 
          array (
            'neon' => 'Nette\\Neon\\Neon',
            'analyserresult' => 'PHPStan\\Analyser\\AnalyserResult',
            'error' => 'PHPStan\\Analyser\\Error',
            'fileanalyserresult' => 'PHPStan\\Analyser\\FileAnalyserResult',
            'collecteddata' => 'PHPStan\\Collectors\\CollectedData',
            'output' => 'PHPStan\\Command\\Output',
            'exportedtraitnode' => 'PHPStan\\Dependency\\ExportedNode\\ExportedTraitNode',
            'exportednodefetcher' => 'PHPStan\\Dependency\\ExportedNodeFetcher',
            'rootexportednode' => 'PHPStan\\Dependency\\RootExportedNode',
            'autowiredparameter' => 'PHPStan\\DependencyInjection\\AutowiredParameter',
            'container' => 'PHPStan\\DependencyInjection\\Container',
            'generatefactory' => 'PHPStan\\DependencyInjection\\GenerateFactory',
            'projectconfighelper' => 'PHPStan\\DependencyInjection\\ProjectConfigHelper',
            'couldnotreadfileexception' => 'PHPStan\\File\\CouldNotReadFileException',
            'filefinder' => 'PHPStan\\File\\FileFinder',
            'filehelper' => 'PHPStan\\File\\FileHelper',
            'filewriter' => 'PHPStan\\File\\FileWriter',
            'arrayhelper' => 'PHPStan\\Internal\\ArrayHelper',
            'composerhelper' => 'PHPStan\\Internal\\ComposerHelper',
            'stubfilesprovider' => 'PHPStan\\PhpDoc\\StubFilesProvider',
            'shouldnothappenexception' => 'PHPStan\\ShouldNotHappenException',
            'reflectionclass' => 'ReflectionClass',
            'reflectionexception' => 'ReflectionException',
            'throwable' => 'Throwable',
          ),
           'className' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheManager',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
            'LinesToIgnore' => true,
            'CollectorData' => true,
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
            'php_version_id' => 'PHP_VERSION_ID',
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
          'LinesToIgnore' => true,
          'CollectorData' => true,
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
          'php_version_id' => 'PHP_VERSION_ID',
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '83513d9d210fa7f1de26c236bfe02872' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'PHPStan\\Analyser\\ResultCache',
         'uses' => 
        array (
          'neon' => 'Nette\\Neon\\Neon',
          'analyserresult' => 'PHPStan\\Analyser\\AnalyserResult',
          'error' => 'PHPStan\\Analyser\\Error',
          'fileanalyserresult' => 'PHPStan\\Analyser\\FileAnalyserResult',
          'collecteddata' => 'PHPStan\\Collectors\\CollectedData',
          'output' => 'PHPStan\\Command\\Output',
          'exportedtraitnode' => 'PHPStan\\Dependency\\ExportedNode\\ExportedTraitNode',
          'exportednodefetcher' => 'PHPStan\\Dependency\\ExportedNodeFetcher',
          'rootexportednode' => 'PHPStan\\Dependency\\RootExportedNode',
          'autowiredparameter' => 'PHPStan\\DependencyInjection\\AutowiredParameter',
          'container' => 'PHPStan\\DependencyInjection\\Container',
          'generatefactory' => 'PHPStan\\DependencyInjection\\GenerateFactory',
          'projectconfighelper' => 'PHPStan\\DependencyInjection\\ProjectConfigHelper',
          'couldnotreadfileexception' => 'PHPStan\\File\\CouldNotReadFileException',
          'filefinder' => 'PHPStan\\File\\FileFinder',
          'filehelper' => 'PHPStan\\File\\FileHelper',
          'filewriter' => 'PHPStan\\File\\FileWriter',
          'arrayhelper' => 'PHPStan\\Internal\\ArrayHelper',
          'composerhelper' => 'PHPStan\\Internal\\ComposerHelper',
          'stubfilesprovider' => 'PHPStan\\PhpDoc\\StubFilesProvider',
          'shouldnothappenexception' => 'PHPStan\\ShouldNotHappenException',
          'reflectionclass' => 'ReflectionClass',
          'reflectionexception' => 'ReflectionException',
          'throwable' => 'Throwable',
        ),
         'className' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheManager',
         'functionName' => 'mergeLocallyIgnoredErrors',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'PHPStan\\Analyser\\ResultCache',
           'uses' => 
          array (
            'neon' => 'Nette\\Neon\\Neon',
            'analyserresult' => 'PHPStan\\Analyser\\AnalyserResult',
            'error' => 'PHPStan\\Analyser\\Error',
            'fileanalyserresult' => 'PHPStan\\Analyser\\FileAnalyserResult',
            'collecteddata' => 'PHPStan\\Collectors\\CollectedData',
            'output' => 'PHPStan\\Command\\Output',
            'exportedtraitnode' => 'PHPStan\\Dependency\\ExportedNode\\ExportedTraitNode',
            'exportednodefetcher' => 'PHPStan\\Dependency\\ExportedNodeFetcher',
            'rootexportednode' => 'PHPStan\\Dependency\\RootExportedNode',
            'autowiredparameter' => 'PHPStan\\DependencyInjection\\AutowiredParameter',
            'container' => 'PHPStan\\DependencyInjection\\Container',
            'generatefactory' => 'PHPStan\\DependencyInjection\\GenerateFactory',
            'projectconfighelper' => 'PHPStan\\DependencyInjection\\ProjectConfigHelper',
            'couldnotreadfileexception' => 'PHPStan\\File\\CouldNotReadFileException',
            'filefinder' => 'PHPStan\\File\\FileFinder',
            'filehelper' => 'PHPStan\\File\\FileHelper',
            'filewriter' => 'PHPStan\\File\\FileWriter',
            'arrayhelper' => 'PHPStan\\Internal\\ArrayHelper',
            'composerhelper' => 'PHPStan\\Internal\\ComposerHelper',
            'stubfilesprovider' => 'PHPStan\\PhpDoc\\StubFilesProvider',
            'shouldnothappenexception' => 'PHPStan\\ShouldNotHappenException',
            'reflectionclass' => 'ReflectionClass',
            'reflectionexception' => 'ReflectionException',
            'throwable' => 'Throwable',
          ),
           'className' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheManager',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
            'LinesToIgnore' => true,
            'CollectorData' => true,
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
            'php_version_id' => 'PHP_VERSION_ID',
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
          'LinesToIgnore' => true,
          'CollectorData' => true,
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
          'php_version_id' => 'PHP_VERSION_ID',
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '82c5fe3215c1b7f533d6098e619566c1' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'PHPStan\\Analyser\\ResultCache',
         'uses' => 
        array (
          'neon' => 'Nette\\Neon\\Neon',
          'analyserresult' => 'PHPStan\\Analyser\\AnalyserResult',
          'error' => 'PHPStan\\Analyser\\Error',
          'fileanalyserresult' => 'PHPStan\\Analyser\\FileAnalyserResult',
          'collecteddata' => 'PHPStan\\Collectors\\CollectedData',
          'output' => 'PHPStan\\Command\\Output',
          'exportedtraitnode' => 'PHPStan\\Dependency\\ExportedNode\\ExportedTraitNode',
          'exportednodefetcher' => 'PHPStan\\Dependency\\ExportedNodeFetcher',
          'rootexportednode' => 'PHPStan\\Dependency\\RootExportedNode',
          'autowiredparameter' => 'PHPStan\\DependencyInjection\\AutowiredParameter',
          'container' => 'PHPStan\\DependencyInjection\\Container',
          'generatefactory' => 'PHPStan\\DependencyInjection\\GenerateFactory',
          'projectconfighelper' => 'PHPStan\\DependencyInjection\\ProjectConfigHelper',
          'couldnotreadfileexception' => 'PHPStan\\File\\CouldNotReadFileException',
          'filefinder' => 'PHPStan\\File\\FileFinder',
          'filehelper' => 'PHPStan\\File\\FileHelper',
          'filewriter' => 'PHPStan\\File\\FileWriter',
          'arrayhelper' => 'PHPStan\\Internal\\ArrayHelper',
          'composerhelper' => 'PHPStan\\Internal\\ComposerHelper',
          'stubfilesprovider' => 'PHPStan\\PhpDoc\\StubFilesProvider',
          'shouldnothappenexception' => 'PHPStan\\ShouldNotHappenException',
          'reflectionclass' => 'ReflectionClass',
          'reflectionexception' => 'ReflectionException',
          'throwable' => 'Throwable',
        ),
         'className' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheManager',
         'functionName' => 'mergeCollectedData',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'PHPStan\\Analyser\\ResultCache',
           'uses' => 
          array (
            'neon' => 'Nette\\Neon\\Neon',
            'analyserresult' => 'PHPStan\\Analyser\\AnalyserResult',
            'error' => 'PHPStan\\Analyser\\Error',
            'fileanalyserresult' => 'PHPStan\\Analyser\\FileAnalyserResult',
            'collecteddata' => 'PHPStan\\Collectors\\CollectedData',
            'output' => 'PHPStan\\Command\\Output',
            'exportedtraitnode' => 'PHPStan\\Dependency\\ExportedNode\\ExportedTraitNode',
            'exportednodefetcher' => 'PHPStan\\Dependency\\ExportedNodeFetcher',
            'rootexportednode' => 'PHPStan\\Dependency\\RootExportedNode',
            'autowiredparameter' => 'PHPStan\\DependencyInjection\\AutowiredParameter',
            'container' => 'PHPStan\\DependencyInjection\\Container',
            'generatefactory' => 'PHPStan\\DependencyInjection\\GenerateFactory',
            'projectconfighelper' => 'PHPStan\\DependencyInjection\\ProjectConfigHelper',
            'couldnotreadfileexception' => 'PHPStan\\File\\CouldNotReadFileException',
            'filefinder' => 'PHPStan\\File\\FileFinder',
            'filehelper' => 'PHPStan\\File\\FileHelper',
            'filewriter' => 'PHPStan\\File\\FileWriter',
            'arrayhelper' => 'PHPStan\\Internal\\ArrayHelper',
            'composerhelper' => 'PHPStan\\Internal\\ComposerHelper',
            'stubfilesprovider' => 'PHPStan\\PhpDoc\\StubFilesProvider',
            'shouldnothappenexception' => 'PHPStan\\ShouldNotHappenException',
            'reflectionclass' => 'ReflectionClass',
            'reflectionexception' => 'ReflectionException',
            'throwable' => 'Throwable',
          ),
           'className' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheManager',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
            'LinesToIgnore' => true,
            'CollectorData' => true,
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
            'php_version_id' => 'PHP_VERSION_ID',
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
          'LinesToIgnore' => true,
          'CollectorData' => true,
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
          'php_version_id' => 'PHP_VERSION_ID',
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '900e09c715ce66190d77ce38b6c7394d' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'PHPStan\\Analyser\\ResultCache',
         'uses' => 
        array (
          'neon' => 'Nette\\Neon\\Neon',
          'analyserresult' => 'PHPStan\\Analyser\\AnalyserResult',
          'error' => 'PHPStan\\Analyser\\Error',
          'fileanalyserresult' => 'PHPStan\\Analyser\\FileAnalyserResult',
          'collecteddata' => 'PHPStan\\Collectors\\CollectedData',
          'output' => 'PHPStan\\Command\\Output',
          'exportedtraitnode' => 'PHPStan\\Dependency\\ExportedNode\\ExportedTraitNode',
          'exportednodefetcher' => 'PHPStan\\Dependency\\ExportedNodeFetcher',
          'rootexportednode' => 'PHPStan\\Dependency\\RootExportedNode',
          'autowiredparameter' => 'PHPStan\\DependencyInjection\\AutowiredParameter',
          'container' => 'PHPStan\\DependencyInjection\\Container',
          'generatefactory' => 'PHPStan\\DependencyInjection\\GenerateFactory',
          'projectconfighelper' => 'PHPStan\\DependencyInjection\\ProjectConfigHelper',
          'couldnotreadfileexception' => 'PHPStan\\File\\CouldNotReadFileException',
          'filefinder' => 'PHPStan\\File\\FileFinder',
          'filehelper' => 'PHPStan\\File\\FileHelper',
          'filewriter' => 'PHPStan\\File\\FileWriter',
          'arrayhelper' => 'PHPStan\\Internal\\ArrayHelper',
          'composerhelper' => 'PHPStan\\Internal\\ComposerHelper',
          'stubfilesprovider' => 'PHPStan\\PhpDoc\\StubFilesProvider',
          'shouldnothappenexception' => 'PHPStan\\ShouldNotHappenException',
          'reflectionclass' => 'ReflectionClass',
          'reflectionexception' => 'ReflectionException',
          'throwable' => 'Throwable',
        ),
         'className' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheManager',
         'functionName' => 'mergeDependencies',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'PHPStan\\Analyser\\ResultCache',
           'uses' => 
          array (
            'neon' => 'Nette\\Neon\\Neon',
            'analyserresult' => 'PHPStan\\Analyser\\AnalyserResult',
            'error' => 'PHPStan\\Analyser\\Error',
            'fileanalyserresult' => 'PHPStan\\Analyser\\FileAnalyserResult',
            'collecteddata' => 'PHPStan\\Collectors\\CollectedData',
            'output' => 'PHPStan\\Command\\Output',
            'exportedtraitnode' => 'PHPStan\\Dependency\\ExportedNode\\ExportedTraitNode',
            'exportednodefetcher' => 'PHPStan\\Dependency\\ExportedNodeFetcher',
            'rootexportednode' => 'PHPStan\\Dependency\\RootExportedNode',
            'autowiredparameter' => 'PHPStan\\DependencyInjection\\AutowiredParameter',
            'container' => 'PHPStan\\DependencyInjection\\Container',
            'generatefactory' => 'PHPStan\\DependencyInjection\\GenerateFactory',
            'projectconfighelper' => 'PHPStan\\DependencyInjection\\ProjectConfigHelper',
            'couldnotreadfileexception' => 'PHPStan\\File\\CouldNotReadFileException',
            'filefinder' => 'PHPStan\\File\\FileFinder',
            'filehelper' => 'PHPStan\\File\\FileHelper',
            'filewriter' => 'PHPStan\\File\\FileWriter',
            'arrayhelper' => 'PHPStan\\Internal\\ArrayHelper',
            'composerhelper' => 'PHPStan\\Internal\\ComposerHelper',
            'stubfilesprovider' => 'PHPStan\\PhpDoc\\StubFilesProvider',
            'shouldnothappenexception' => 'PHPStan\\ShouldNotHappenException',
            'reflectionclass' => 'ReflectionClass',
            'reflectionexception' => 'ReflectionException',
            'throwable' => 'Throwable',
          ),
           'className' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheManager',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
            'LinesToIgnore' => true,
            'CollectorData' => true,
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
            'php_version_id' => 'PHP_VERSION_ID',
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
          'LinesToIgnore' => true,
          'CollectorData' => true,
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
          'php_version_id' => 'PHP_VERSION_ID',
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '920d788b9057d339a1d15cc69d63e430' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'PHPStan\\Analyser\\ResultCache',
         'uses' => 
        array (
          'neon' => 'Nette\\Neon\\Neon',
          'analyserresult' => 'PHPStan\\Analyser\\AnalyserResult',
          'error' => 'PHPStan\\Analyser\\Error',
          'fileanalyserresult' => 'PHPStan\\Analyser\\FileAnalyserResult',
          'collecteddata' => 'PHPStan\\Collectors\\CollectedData',
          'output' => 'PHPStan\\Command\\Output',
          'exportedtraitnode' => 'PHPStan\\Dependency\\ExportedNode\\ExportedTraitNode',
          'exportednodefetcher' => 'PHPStan\\Dependency\\ExportedNodeFetcher',
          'rootexportednode' => 'PHPStan\\Dependency\\RootExportedNode',
          'autowiredparameter' => 'PHPStan\\DependencyInjection\\AutowiredParameter',
          'container' => 'PHPStan\\DependencyInjection\\Container',
          'generatefactory' => 'PHPStan\\DependencyInjection\\GenerateFactory',
          'projectconfighelper' => 'PHPStan\\DependencyInjection\\ProjectConfigHelper',
          'couldnotreadfileexception' => 'PHPStan\\File\\CouldNotReadFileException',
          'filefinder' => 'PHPStan\\File\\FileFinder',
          'filehelper' => 'PHPStan\\File\\FileHelper',
          'filewriter' => 'PHPStan\\File\\FileWriter',
          'arrayhelper' => 'PHPStan\\Internal\\ArrayHelper',
          'composerhelper' => 'PHPStan\\Internal\\ComposerHelper',
          'stubfilesprovider' => 'PHPStan\\PhpDoc\\StubFilesProvider',
          'shouldnothappenexception' => 'PHPStan\\ShouldNotHappenException',
          'reflectionclass' => 'ReflectionClass',
          'reflectionexception' => 'ReflectionException',
          'throwable' => 'Throwable',
        ),
         'className' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheManager',
         'functionName' => 'mergeExportedNodes',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'PHPStan\\Analyser\\ResultCache',
           'uses' => 
          array (
            'neon' => 'Nette\\Neon\\Neon',
            'analyserresult' => 'PHPStan\\Analyser\\AnalyserResult',
            'error' => 'PHPStan\\Analyser\\Error',
            'fileanalyserresult' => 'PHPStan\\Analyser\\FileAnalyserResult',
            'collecteddata' => 'PHPStan\\Collectors\\CollectedData',
            'output' => 'PHPStan\\Command\\Output',
            'exportedtraitnode' => 'PHPStan\\Dependency\\ExportedNode\\ExportedTraitNode',
            'exportednodefetcher' => 'PHPStan\\Dependency\\ExportedNodeFetcher',
            'rootexportednode' => 'PHPStan\\Dependency\\RootExportedNode',
            'autowiredparameter' => 'PHPStan\\DependencyInjection\\AutowiredParameter',
            'container' => 'PHPStan\\DependencyInjection\\Container',
            'generatefactory' => 'PHPStan\\DependencyInjection\\GenerateFactory',
            'projectconfighelper' => 'PHPStan\\DependencyInjection\\ProjectConfigHelper',
            'couldnotreadfileexception' => 'PHPStan\\File\\CouldNotReadFileException',
            'filefinder' => 'PHPStan\\File\\FileFinder',
            'filehelper' => 'PHPStan\\File\\FileHelper',
            'filewriter' => 'PHPStan\\File\\FileWriter',
            'arrayhelper' => 'PHPStan\\Internal\\ArrayHelper',
            'composerhelper' => 'PHPStan\\Internal\\ComposerHelper',
            'stubfilesprovider' => 'PHPStan\\PhpDoc\\StubFilesProvider',
            'shouldnothappenexception' => 'PHPStan\\ShouldNotHappenException',
            'reflectionclass' => 'ReflectionClass',
            'reflectionexception' => 'ReflectionException',
            'throwable' => 'Throwable',
          ),
           'className' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheManager',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
            'LinesToIgnore' => true,
            'CollectorData' => true,
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
            'php_version_id' => 'PHP_VERSION_ID',
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
          'LinesToIgnore' => true,
          'CollectorData' => true,
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
          'php_version_id' => 'PHP_VERSION_ID',
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '22fa722fac435249e64dbf868095f196' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'PHPStan\\Analyser\\ResultCache',
         'uses' => 
        array (
          'neon' => 'Nette\\Neon\\Neon',
          'analyserresult' => 'PHPStan\\Analyser\\AnalyserResult',
          'error' => 'PHPStan\\Analyser\\Error',
          'fileanalyserresult' => 'PHPStan\\Analyser\\FileAnalyserResult',
          'collecteddata' => 'PHPStan\\Collectors\\CollectedData',
          'output' => 'PHPStan\\Command\\Output',
          'exportedtraitnode' => 'PHPStan\\Dependency\\ExportedNode\\ExportedTraitNode',
          'exportednodefetcher' => 'PHPStan\\Dependency\\ExportedNodeFetcher',
          'rootexportednode' => 'PHPStan\\Dependency\\RootExportedNode',
          'autowiredparameter' => 'PHPStan\\DependencyInjection\\AutowiredParameter',
          'container' => 'PHPStan\\DependencyInjection\\Container',
          'generatefactory' => 'PHPStan\\DependencyInjection\\GenerateFactory',
          'projectconfighelper' => 'PHPStan\\DependencyInjection\\ProjectConfigHelper',
          'couldnotreadfileexception' => 'PHPStan\\File\\CouldNotReadFileException',
          'filefinder' => 'PHPStan\\File\\FileFinder',
          'filehelper' => 'PHPStan\\File\\FileHelper',
          'filewriter' => 'PHPStan\\File\\FileWriter',
          'arrayhelper' => 'PHPStan\\Internal\\ArrayHelper',
          'composerhelper' => 'PHPStan\\Internal\\ComposerHelper',
          'stubfilesprovider' => 'PHPStan\\PhpDoc\\StubFilesProvider',
          'shouldnothappenexception' => 'PHPStan\\ShouldNotHappenException',
          'reflectionclass' => 'ReflectionClass',
          'reflectionexception' => 'ReflectionException',
          'throwable' => 'Throwable',
        ),
         'className' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheManager',
         'functionName' => 'mergeLinesToIgnore',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'PHPStan\\Analyser\\ResultCache',
           'uses' => 
          array (
            'neon' => 'Nette\\Neon\\Neon',
            'analyserresult' => 'PHPStan\\Analyser\\AnalyserResult',
            'error' => 'PHPStan\\Analyser\\Error',
            'fileanalyserresult' => 'PHPStan\\Analyser\\FileAnalyserResult',
            'collecteddata' => 'PHPStan\\Collectors\\CollectedData',
            'output' => 'PHPStan\\Command\\Output',
            'exportedtraitnode' => 'PHPStan\\Dependency\\ExportedNode\\ExportedTraitNode',
            'exportednodefetcher' => 'PHPStan\\Dependency\\ExportedNodeFetcher',
            'rootexportednode' => 'PHPStan\\Dependency\\RootExportedNode',
            'autowiredparameter' => 'PHPStan\\DependencyInjection\\AutowiredParameter',
            'container' => 'PHPStan\\DependencyInjection\\Container',
            'generatefactory' => 'PHPStan\\DependencyInjection\\GenerateFactory',
            'projectconfighelper' => 'PHPStan\\DependencyInjection\\ProjectConfigHelper',
            'couldnotreadfileexception' => 'PHPStan\\File\\CouldNotReadFileException',
            'filefinder' => 'PHPStan\\File\\FileFinder',
            'filehelper' => 'PHPStan\\File\\FileHelper',
            'filewriter' => 'PHPStan\\File\\FileWriter',
            'arrayhelper' => 'PHPStan\\Internal\\ArrayHelper',
            'composerhelper' => 'PHPStan\\Internal\\ComposerHelper',
            'stubfilesprovider' => 'PHPStan\\PhpDoc\\StubFilesProvider',
            'shouldnothappenexception' => 'PHPStan\\ShouldNotHappenException',
            'reflectionclass' => 'ReflectionClass',
            'reflectionexception' => 'ReflectionException',
            'throwable' => 'Throwable',
          ),
           'className' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheManager',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
            'LinesToIgnore' => true,
            'CollectorData' => true,
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
            'php_version_id' => 'PHP_VERSION_ID',
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
          'LinesToIgnore' => true,
          'CollectorData' => true,
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
          'php_version_id' => 'PHP_VERSION_ID',
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '5e005bdc225d97a2454b4ff8f27669f9' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'PHPStan\\Analyser\\ResultCache',
         'uses' => 
        array (
          'neon' => 'Nette\\Neon\\Neon',
          'analyserresult' => 'PHPStan\\Analyser\\AnalyserResult',
          'error' => 'PHPStan\\Analyser\\Error',
          'fileanalyserresult' => 'PHPStan\\Analyser\\FileAnalyserResult',
          'collecteddata' => 'PHPStan\\Collectors\\CollectedData',
          'output' => 'PHPStan\\Command\\Output',
          'exportedtraitnode' => 'PHPStan\\Dependency\\ExportedNode\\ExportedTraitNode',
          'exportednodefetcher' => 'PHPStan\\Dependency\\ExportedNodeFetcher',
          'rootexportednode' => 'PHPStan\\Dependency\\RootExportedNode',
          'autowiredparameter' => 'PHPStan\\DependencyInjection\\AutowiredParameter',
          'container' => 'PHPStan\\DependencyInjection\\Container',
          'generatefactory' => 'PHPStan\\DependencyInjection\\GenerateFactory',
          'projectconfighelper' => 'PHPStan\\DependencyInjection\\ProjectConfigHelper',
          'couldnotreadfileexception' => 'PHPStan\\File\\CouldNotReadFileException',
          'filefinder' => 'PHPStan\\File\\FileFinder',
          'filehelper' => 'PHPStan\\File\\FileHelper',
          'filewriter' => 'PHPStan\\File\\FileWriter',
          'arrayhelper' => 'PHPStan\\Internal\\ArrayHelper',
          'composerhelper' => 'PHPStan\\Internal\\ComposerHelper',
          'stubfilesprovider' => 'PHPStan\\PhpDoc\\StubFilesProvider',
          'shouldnothappenexception' => 'PHPStan\\ShouldNotHappenException',
          'reflectionclass' => 'ReflectionClass',
          'reflectionexception' => 'ReflectionException',
          'throwable' => 'Throwable',
        ),
         'className' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheManager',
         'functionName' => 'mergeUnmatchedLineIgnores',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'PHPStan\\Analyser\\ResultCache',
           'uses' => 
          array (
            'neon' => 'Nette\\Neon\\Neon',
            'analyserresult' => 'PHPStan\\Analyser\\AnalyserResult',
            'error' => 'PHPStan\\Analyser\\Error',
            'fileanalyserresult' => 'PHPStan\\Analyser\\FileAnalyserResult',
            'collecteddata' => 'PHPStan\\Collectors\\CollectedData',
            'output' => 'PHPStan\\Command\\Output',
            'exportedtraitnode' => 'PHPStan\\Dependency\\ExportedNode\\ExportedTraitNode',
            'exportednodefetcher' => 'PHPStan\\Dependency\\ExportedNodeFetcher',
            'rootexportednode' => 'PHPStan\\Dependency\\RootExportedNode',
            'autowiredparameter' => 'PHPStan\\DependencyInjection\\AutowiredParameter',
            'container' => 'PHPStan\\DependencyInjection\\Container',
            'generatefactory' => 'PHPStan\\DependencyInjection\\GenerateFactory',
            'projectconfighelper' => 'PHPStan\\DependencyInjection\\ProjectConfigHelper',
            'couldnotreadfileexception' => 'PHPStan\\File\\CouldNotReadFileException',
            'filefinder' => 'PHPStan\\File\\FileFinder',
            'filehelper' => 'PHPStan\\File\\FileHelper',
            'filewriter' => 'PHPStan\\File\\FileWriter',
            'arrayhelper' => 'PHPStan\\Internal\\ArrayHelper',
            'composerhelper' => 'PHPStan\\Internal\\ComposerHelper',
            'stubfilesprovider' => 'PHPStan\\PhpDoc\\StubFilesProvider',
            'shouldnothappenexception' => 'PHPStan\\ShouldNotHappenException',
            'reflectionclass' => 'ReflectionClass',
            'reflectionexception' => 'ReflectionException',
            'throwable' => 'Throwable',
          ),
           'className' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheManager',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
            'LinesToIgnore' => true,
            'CollectorData' => true,
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
            'php_version_id' => 'PHP_VERSION_ID',
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
          'LinesToIgnore' => true,
          'CollectorData' => true,
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
          'php_version_id' => 'PHP_VERSION_ID',
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      'e2e6ef96069648cf3773a462da9bf52e' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'PHPStan\\Analyser\\ResultCache',
         'uses' => 
        array (
          'neon' => 'Nette\\Neon\\Neon',
          'analyserresult' => 'PHPStan\\Analyser\\AnalyserResult',
          'error' => 'PHPStan\\Analyser\\Error',
          'fileanalyserresult' => 'PHPStan\\Analyser\\FileAnalyserResult',
          'collecteddata' => 'PHPStan\\Collectors\\CollectedData',
          'output' => 'PHPStan\\Command\\Output',
          'exportedtraitnode' => 'PHPStan\\Dependency\\ExportedNode\\ExportedTraitNode',
          'exportednodefetcher' => 'PHPStan\\Dependency\\ExportedNodeFetcher',
          'rootexportednode' => 'PHPStan\\Dependency\\RootExportedNode',
          'autowiredparameter' => 'PHPStan\\DependencyInjection\\AutowiredParameter',
          'container' => 'PHPStan\\DependencyInjection\\Container',
          'generatefactory' => 'PHPStan\\DependencyInjection\\GenerateFactory',
          'projectconfighelper' => 'PHPStan\\DependencyInjection\\ProjectConfigHelper',
          'couldnotreadfileexception' => 'PHPStan\\File\\CouldNotReadFileException',
          'filefinder' => 'PHPStan\\File\\FileFinder',
          'filehelper' => 'PHPStan\\File\\FileHelper',
          'filewriter' => 'PHPStan\\File\\FileWriter',
          'arrayhelper' => 'PHPStan\\Internal\\ArrayHelper',
          'composerhelper' => 'PHPStan\\Internal\\ComposerHelper',
          'stubfilesprovider' => 'PHPStan\\PhpDoc\\StubFilesProvider',
          'shouldnothappenexception' => 'PHPStan\\ShouldNotHappenException',
          'reflectionclass' => 'ReflectionClass',
          'reflectionexception' => 'ReflectionException',
          'throwable' => 'Throwable',
        ),
         'className' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheManager',
         'functionName' => 'save',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'PHPStan\\Analyser\\ResultCache',
           'uses' => 
          array (
            'neon' => 'Nette\\Neon\\Neon',
            'analyserresult' => 'PHPStan\\Analyser\\AnalyserResult',
            'error' => 'PHPStan\\Analyser\\Error',
            'fileanalyserresult' => 'PHPStan\\Analyser\\FileAnalyserResult',
            'collecteddata' => 'PHPStan\\Collectors\\CollectedData',
            'output' => 'PHPStan\\Command\\Output',
            'exportedtraitnode' => 'PHPStan\\Dependency\\ExportedNode\\ExportedTraitNode',
            'exportednodefetcher' => 'PHPStan\\Dependency\\ExportedNodeFetcher',
            'rootexportednode' => 'PHPStan\\Dependency\\RootExportedNode',
            'autowiredparameter' => 'PHPStan\\DependencyInjection\\AutowiredParameter',
            'container' => 'PHPStan\\DependencyInjection\\Container',
            'generatefactory' => 'PHPStan\\DependencyInjection\\GenerateFactory',
            'projectconfighelper' => 'PHPStan\\DependencyInjection\\ProjectConfigHelper',
            'couldnotreadfileexception' => 'PHPStan\\File\\CouldNotReadFileException',
            'filefinder' => 'PHPStan\\File\\FileFinder',
            'filehelper' => 'PHPStan\\File\\FileHelper',
            'filewriter' => 'PHPStan\\File\\FileWriter',
            'arrayhelper' => 'PHPStan\\Internal\\ArrayHelper',
            'composerhelper' => 'PHPStan\\Internal\\ComposerHelper',
            'stubfilesprovider' => 'PHPStan\\PhpDoc\\StubFilesProvider',
            'shouldnothappenexception' => 'PHPStan\\ShouldNotHappenException',
            'reflectionclass' => 'ReflectionClass',
            'reflectionexception' => 'ReflectionException',
            'throwable' => 'Throwable',
          ),
           'className' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheManager',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
            'LinesToIgnore' => true,
            'CollectorData' => true,
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
            'php_version_id' => 'PHP_VERSION_ID',
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
          'LinesToIgnore' => true,
          'CollectorData' => true,
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
          'php_version_id' => 'PHP_VERSION_ID',
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '5cd88ea2813357f4a59e75ef1baa9023' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'PHPStan\\Analyser\\ResultCache',
         'uses' => 
        array (
          'neon' => 'Nette\\Neon\\Neon',
          'analyserresult' => 'PHPStan\\Analyser\\AnalyserResult',
          'error' => 'PHPStan\\Analyser\\Error',
          'fileanalyserresult' => 'PHPStan\\Analyser\\FileAnalyserResult',
          'collecteddata' => 'PHPStan\\Collectors\\CollectedData',
          'output' => 'PHPStan\\Command\\Output',
          'exportedtraitnode' => 'PHPStan\\Dependency\\ExportedNode\\ExportedTraitNode',
          'exportednodefetcher' => 'PHPStan\\Dependency\\ExportedNodeFetcher',
          'rootexportednode' => 'PHPStan\\Dependency\\RootExportedNode',
          'autowiredparameter' => 'PHPStan\\DependencyInjection\\AutowiredParameter',
          'container' => 'PHPStan\\DependencyInjection\\Container',
          'generatefactory' => 'PHPStan\\DependencyInjection\\GenerateFactory',
          'projectconfighelper' => 'PHPStan\\DependencyInjection\\ProjectConfigHelper',
          'couldnotreadfileexception' => 'PHPStan\\File\\CouldNotReadFileException',
          'filefinder' => 'PHPStan\\File\\FileFinder',
          'filehelper' => 'PHPStan\\File\\FileHelper',
          'filewriter' => 'PHPStan\\File\\FileWriter',
          'arrayhelper' => 'PHPStan\\Internal\\ArrayHelper',
          'composerhelper' => 'PHPStan\\Internal\\ComposerHelper',
          'stubfilesprovider' => 'PHPStan\\PhpDoc\\StubFilesProvider',
          'shouldnothappenexception' => 'PHPStan\\ShouldNotHappenException',
          'reflectionclass' => 'ReflectionClass',
          'reflectionexception' => 'ReflectionException',
          'throwable' => 'Throwable',
        ),
         'className' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheManager',
         'functionName' => 'getProjectExtensionFiles',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'PHPStan\\Analyser\\ResultCache',
           'uses' => 
          array (
            'neon' => 'Nette\\Neon\\Neon',
            'analyserresult' => 'PHPStan\\Analyser\\AnalyserResult',
            'error' => 'PHPStan\\Analyser\\Error',
            'fileanalyserresult' => 'PHPStan\\Analyser\\FileAnalyserResult',
            'collecteddata' => 'PHPStan\\Collectors\\CollectedData',
            'output' => 'PHPStan\\Command\\Output',
            'exportedtraitnode' => 'PHPStan\\Dependency\\ExportedNode\\ExportedTraitNode',
            'exportednodefetcher' => 'PHPStan\\Dependency\\ExportedNodeFetcher',
            'rootexportednode' => 'PHPStan\\Dependency\\RootExportedNode',
            'autowiredparameter' => 'PHPStan\\DependencyInjection\\AutowiredParameter',
            'container' => 'PHPStan\\DependencyInjection\\Container',
            'generatefactory' => 'PHPStan\\DependencyInjection\\GenerateFactory',
            'projectconfighelper' => 'PHPStan\\DependencyInjection\\ProjectConfigHelper',
            'couldnotreadfileexception' => 'PHPStan\\File\\CouldNotReadFileException',
            'filefinder' => 'PHPStan\\File\\FileFinder',
            'filehelper' => 'PHPStan\\File\\FileHelper',
            'filewriter' => 'PHPStan\\File\\FileWriter',
            'arrayhelper' => 'PHPStan\\Internal\\ArrayHelper',
            'composerhelper' => 'PHPStan\\Internal\\ComposerHelper',
            'stubfilesprovider' => 'PHPStan\\PhpDoc\\StubFilesProvider',
            'shouldnothappenexception' => 'PHPStan\\ShouldNotHappenException',
            'reflectionclass' => 'ReflectionClass',
            'reflectionexception' => 'ReflectionException',
            'throwable' => 'Throwable',
          ),
           'className' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheManager',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
            'LinesToIgnore' => true,
            'CollectorData' => true,
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
            'php_version_id' => 'PHP_VERSION_ID',
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
          'LinesToIgnore' => true,
          'CollectorData' => true,
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
          'php_version_id' => 'PHP_VERSION_ID',
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '38a3c30827151bd5364cbe67489569e1' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'PHPStan\\Analyser\\ResultCache',
         'uses' => 
        array (
          'neon' => 'Nette\\Neon\\Neon',
          'analyserresult' => 'PHPStan\\Analyser\\AnalyserResult',
          'error' => 'PHPStan\\Analyser\\Error',
          'fileanalyserresult' => 'PHPStan\\Analyser\\FileAnalyserResult',
          'collecteddata' => 'PHPStan\\Collectors\\CollectedData',
          'output' => 'PHPStan\\Command\\Output',
          'exportedtraitnode' => 'PHPStan\\Dependency\\ExportedNode\\ExportedTraitNode',
          'exportednodefetcher' => 'PHPStan\\Dependency\\ExportedNodeFetcher',
          'rootexportednode' => 'PHPStan\\Dependency\\RootExportedNode',
          'autowiredparameter' => 'PHPStan\\DependencyInjection\\AutowiredParameter',
          'container' => 'PHPStan\\DependencyInjection\\Container',
          'generatefactory' => 'PHPStan\\DependencyInjection\\GenerateFactory',
          'projectconfighelper' => 'PHPStan\\DependencyInjection\\ProjectConfigHelper',
          'couldnotreadfileexception' => 'PHPStan\\File\\CouldNotReadFileException',
          'filefinder' => 'PHPStan\\File\\FileFinder',
          'filehelper' => 'PHPStan\\File\\FileHelper',
          'filewriter' => 'PHPStan\\File\\FileWriter',
          'arrayhelper' => 'PHPStan\\Internal\\ArrayHelper',
          'composerhelper' => 'PHPStan\\Internal\\ComposerHelper',
          'stubfilesprovider' => 'PHPStan\\PhpDoc\\StubFilesProvider',
          'shouldnothappenexception' => 'PHPStan\\ShouldNotHappenException',
          'reflectionclass' => 'ReflectionClass',
          'reflectionexception' => 'ReflectionException',
          'throwable' => 'Throwable',
        ),
         'className' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheManager',
         'functionName' => 'getAllDependencies',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'PHPStan\\Analyser\\ResultCache',
           'uses' => 
          array (
            'neon' => 'Nette\\Neon\\Neon',
            'analyserresult' => 'PHPStan\\Analyser\\AnalyserResult',
            'error' => 'PHPStan\\Analyser\\Error',
            'fileanalyserresult' => 'PHPStan\\Analyser\\FileAnalyserResult',
            'collecteddata' => 'PHPStan\\Collectors\\CollectedData',
            'output' => 'PHPStan\\Command\\Output',
            'exportedtraitnode' => 'PHPStan\\Dependency\\ExportedNode\\ExportedTraitNode',
            'exportednodefetcher' => 'PHPStan\\Dependency\\ExportedNodeFetcher',
            'rootexportednode' => 'PHPStan\\Dependency\\RootExportedNode',
            'autowiredparameter' => 'PHPStan\\DependencyInjection\\AutowiredParameter',
            'container' => 'PHPStan\\DependencyInjection\\Container',
            'generatefactory' => 'PHPStan\\DependencyInjection\\GenerateFactory',
            'projectconfighelper' => 'PHPStan\\DependencyInjection\\ProjectConfigHelper',
            'couldnotreadfileexception' => 'PHPStan\\File\\CouldNotReadFileException',
            'filefinder' => 'PHPStan\\File\\FileFinder',
            'filehelper' => 'PHPStan\\File\\FileHelper',
            'filewriter' => 'PHPStan\\File\\FileWriter',
            'arrayhelper' => 'PHPStan\\Internal\\ArrayHelper',
            'composerhelper' => 'PHPStan\\Internal\\ComposerHelper',
            'stubfilesprovider' => 'PHPStan\\PhpDoc\\StubFilesProvider',
            'shouldnothappenexception' => 'PHPStan\\ShouldNotHappenException',
            'reflectionclass' => 'ReflectionClass',
            'reflectionexception' => 'ReflectionException',
            'throwable' => 'Throwable',
          ),
           'className' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheManager',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
            'LinesToIgnore' => true,
            'CollectorData' => true,
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
            'php_version_id' => 'PHP_VERSION_ID',
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
          'LinesToIgnore' => true,
          'CollectorData' => true,
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
          'php_version_id' => 'PHP_VERSION_ID',
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      'd0758bf81b874da5247056d215027ba1' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'PHPStan\\Analyser\\ResultCache',
         'uses' => 
        array (
          'neon' => 'Nette\\Neon\\Neon',
          'analyserresult' => 'PHPStan\\Analyser\\AnalyserResult',
          'error' => 'PHPStan\\Analyser\\Error',
          'fileanalyserresult' => 'PHPStan\\Analyser\\FileAnalyserResult',
          'collecteddata' => 'PHPStan\\Collectors\\CollectedData',
          'output' => 'PHPStan\\Command\\Output',
          'exportedtraitnode' => 'PHPStan\\Dependency\\ExportedNode\\ExportedTraitNode',
          'exportednodefetcher' => 'PHPStan\\Dependency\\ExportedNodeFetcher',
          'rootexportednode' => 'PHPStan\\Dependency\\RootExportedNode',
          'autowiredparameter' => 'PHPStan\\DependencyInjection\\AutowiredParameter',
          'container' => 'PHPStan\\DependencyInjection\\Container',
          'generatefactory' => 'PHPStan\\DependencyInjection\\GenerateFactory',
          'projectconfighelper' => 'PHPStan\\DependencyInjection\\ProjectConfigHelper',
          'couldnotreadfileexception' => 'PHPStan\\File\\CouldNotReadFileException',
          'filefinder' => 'PHPStan\\File\\FileFinder',
          'filehelper' => 'PHPStan\\File\\FileHelper',
          'filewriter' => 'PHPStan\\File\\FileWriter',
          'arrayhelper' => 'PHPStan\\Internal\\ArrayHelper',
          'composerhelper' => 'PHPStan\\Internal\\ComposerHelper',
          'stubfilesprovider' => 'PHPStan\\PhpDoc\\StubFilesProvider',
          'shouldnothappenexception' => 'PHPStan\\ShouldNotHappenException',
          'reflectionclass' => 'ReflectionClass',
          'reflectionexception' => 'ReflectionException',
          'throwable' => 'Throwable',
        ),
         'className' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheManager',
         'functionName' => 'getMeta',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'PHPStan\\Analyser\\ResultCache',
           'uses' => 
          array (
            'neon' => 'Nette\\Neon\\Neon',
            'analyserresult' => 'PHPStan\\Analyser\\AnalyserResult',
            'error' => 'PHPStan\\Analyser\\Error',
            'fileanalyserresult' => 'PHPStan\\Analyser\\FileAnalyserResult',
            'collecteddata' => 'PHPStan\\Collectors\\CollectedData',
            'output' => 'PHPStan\\Command\\Output',
            'exportedtraitnode' => 'PHPStan\\Dependency\\ExportedNode\\ExportedTraitNode',
            'exportednodefetcher' => 'PHPStan\\Dependency\\ExportedNodeFetcher',
            'rootexportednode' => 'PHPStan\\Dependency\\RootExportedNode',
            'autowiredparameter' => 'PHPStan\\DependencyInjection\\AutowiredParameter',
            'container' => 'PHPStan\\DependencyInjection\\Container',
            'generatefactory' => 'PHPStan\\DependencyInjection\\GenerateFactory',
            'projectconfighelper' => 'PHPStan\\DependencyInjection\\ProjectConfigHelper',
            'couldnotreadfileexception' => 'PHPStan\\File\\CouldNotReadFileException',
            'filefinder' => 'PHPStan\\File\\FileFinder',
            'filehelper' => 'PHPStan\\File\\FileHelper',
            'filewriter' => 'PHPStan\\File\\FileWriter',
            'arrayhelper' => 'PHPStan\\Internal\\ArrayHelper',
            'composerhelper' => 'PHPStan\\Internal\\ComposerHelper',
            'stubfilesprovider' => 'PHPStan\\PhpDoc\\StubFilesProvider',
            'shouldnothappenexception' => 'PHPStan\\ShouldNotHappenException',
            'reflectionclass' => 'ReflectionClass',
            'reflectionexception' => 'ReflectionException',
            'throwable' => 'Throwable',
          ),
           'className' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheManager',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
            'LinesToIgnore' => true,
            'CollectorData' => true,
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
            'php_version_id' => 'PHP_VERSION_ID',
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
          'LinesToIgnore' => true,
          'CollectorData' => true,
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
          'php_version_id' => 'PHP_VERSION_ID',
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      'f6d17941a961b658347b43b87eff11ac' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'PHPStan\\Analyser\\ResultCache',
         'uses' => 
        array (
          'neon' => 'Nette\\Neon\\Neon',
          'analyserresult' => 'PHPStan\\Analyser\\AnalyserResult',
          'error' => 'PHPStan\\Analyser\\Error',
          'fileanalyserresult' => 'PHPStan\\Analyser\\FileAnalyserResult',
          'collecteddata' => 'PHPStan\\Collectors\\CollectedData',
          'output' => 'PHPStan\\Command\\Output',
          'exportedtraitnode' => 'PHPStan\\Dependency\\ExportedNode\\ExportedTraitNode',
          'exportednodefetcher' => 'PHPStan\\Dependency\\ExportedNodeFetcher',
          'rootexportednode' => 'PHPStan\\Dependency\\RootExportedNode',
          'autowiredparameter' => 'PHPStan\\DependencyInjection\\AutowiredParameter',
          'container' => 'PHPStan\\DependencyInjection\\Container',
          'generatefactory' => 'PHPStan\\DependencyInjection\\GenerateFactory',
          'projectconfighelper' => 'PHPStan\\DependencyInjection\\ProjectConfigHelper',
          'couldnotreadfileexception' => 'PHPStan\\File\\CouldNotReadFileException',
          'filefinder' => 'PHPStan\\File\\FileFinder',
          'filehelper' => 'PHPStan\\File\\FileHelper',
          'filewriter' => 'PHPStan\\File\\FileWriter',
          'arrayhelper' => 'PHPStan\\Internal\\ArrayHelper',
          'composerhelper' => 'PHPStan\\Internal\\ComposerHelper',
          'stubfilesprovider' => 'PHPStan\\PhpDoc\\StubFilesProvider',
          'shouldnothappenexception' => 'PHPStan\\ShouldNotHappenException',
          'reflectionclass' => 'ReflectionClass',
          'reflectionexception' => 'ReflectionException',
          'throwable' => 'Throwable',
        ),
         'className' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheManager',
         'functionName' => 'getFileHash',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'PHPStan\\Analyser\\ResultCache',
           'uses' => 
          array (
            'neon' => 'Nette\\Neon\\Neon',
            'analyserresult' => 'PHPStan\\Analyser\\AnalyserResult',
            'error' => 'PHPStan\\Analyser\\Error',
            'fileanalyserresult' => 'PHPStan\\Analyser\\FileAnalyserResult',
            'collecteddata' => 'PHPStan\\Collectors\\CollectedData',
            'output' => 'PHPStan\\Command\\Output',
            'exportedtraitnode' => 'PHPStan\\Dependency\\ExportedNode\\ExportedTraitNode',
            'exportednodefetcher' => 'PHPStan\\Dependency\\ExportedNodeFetcher',
            'rootexportednode' => 'PHPStan\\Dependency\\RootExportedNode',
            'autowiredparameter' => 'PHPStan\\DependencyInjection\\AutowiredParameter',
            'container' => 'PHPStan\\DependencyInjection\\Container',
            'generatefactory' => 'PHPStan\\DependencyInjection\\GenerateFactory',
            'projectconfighelper' => 'PHPStan\\DependencyInjection\\ProjectConfigHelper',
            'couldnotreadfileexception' => 'PHPStan\\File\\CouldNotReadFileException',
            'filefinder' => 'PHPStan\\File\\FileFinder',
            'filehelper' => 'PHPStan\\File\\FileHelper',
            'filewriter' => 'PHPStan\\File\\FileWriter',
            'arrayhelper' => 'PHPStan\\Internal\\ArrayHelper',
            'composerhelper' => 'PHPStan\\Internal\\ComposerHelper',
            'stubfilesprovider' => 'PHPStan\\PhpDoc\\StubFilesProvider',
            'shouldnothappenexception' => 'PHPStan\\ShouldNotHappenException',
            'reflectionclass' => 'ReflectionClass',
            'reflectionexception' => 'ReflectionException',
            'throwable' => 'Throwable',
          ),
           'className' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheManager',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
            'LinesToIgnore' => true,
            'CollectorData' => true,
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
            'php_version_id' => 'PHP_VERSION_ID',
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
          'LinesToIgnore' => true,
          'CollectorData' => true,
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
          'php_version_id' => 'PHP_VERSION_ID',
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      'b0c4021bba2eba9628e9f57831551c0e' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'PHPStan\\Analyser\\ResultCache',
         'uses' => 
        array (
          'neon' => 'Nette\\Neon\\Neon',
          'analyserresult' => 'PHPStan\\Analyser\\AnalyserResult',
          'error' => 'PHPStan\\Analyser\\Error',
          'fileanalyserresult' => 'PHPStan\\Analyser\\FileAnalyserResult',
          'collecteddata' => 'PHPStan\\Collectors\\CollectedData',
          'output' => 'PHPStan\\Command\\Output',
          'exportedtraitnode' => 'PHPStan\\Dependency\\ExportedNode\\ExportedTraitNode',
          'exportednodefetcher' => 'PHPStan\\Dependency\\ExportedNodeFetcher',
          'rootexportednode' => 'PHPStan\\Dependency\\RootExportedNode',
          'autowiredparameter' => 'PHPStan\\DependencyInjection\\AutowiredParameter',
          'container' => 'PHPStan\\DependencyInjection\\Container',
          'generatefactory' => 'PHPStan\\DependencyInjection\\GenerateFactory',
          'projectconfighelper' => 'PHPStan\\DependencyInjection\\ProjectConfigHelper',
          'couldnotreadfileexception' => 'PHPStan\\File\\CouldNotReadFileException',
          'filefinder' => 'PHPStan\\File\\FileFinder',
          'filehelper' => 'PHPStan\\File\\FileHelper',
          'filewriter' => 'PHPStan\\File\\FileWriter',
          'arrayhelper' => 'PHPStan\\Internal\\ArrayHelper',
          'composerhelper' => 'PHPStan\\Internal\\ComposerHelper',
          'stubfilesprovider' => 'PHPStan\\PhpDoc\\StubFilesProvider',
          'shouldnothappenexception' => 'PHPStan\\ShouldNotHappenException',
          'reflectionclass' => 'ReflectionClass',
          'reflectionexception' => 'ReflectionException',
          'throwable' => 'Throwable',
        ),
         'className' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheManager',
         'functionName' => 'getScannedFiles',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'PHPStan\\Analyser\\ResultCache',
           'uses' => 
          array (
            'neon' => 'Nette\\Neon\\Neon',
            'analyserresult' => 'PHPStan\\Analyser\\AnalyserResult',
            'error' => 'PHPStan\\Analyser\\Error',
            'fileanalyserresult' => 'PHPStan\\Analyser\\FileAnalyserResult',
            'collecteddata' => 'PHPStan\\Collectors\\CollectedData',
            'output' => 'PHPStan\\Command\\Output',
            'exportedtraitnode' => 'PHPStan\\Dependency\\ExportedNode\\ExportedTraitNode',
            'exportednodefetcher' => 'PHPStan\\Dependency\\ExportedNodeFetcher',
            'rootexportednode' => 'PHPStan\\Dependency\\RootExportedNode',
            'autowiredparameter' => 'PHPStan\\DependencyInjection\\AutowiredParameter',
            'container' => 'PHPStan\\DependencyInjection\\Container',
            'generatefactory' => 'PHPStan\\DependencyInjection\\GenerateFactory',
            'projectconfighelper' => 'PHPStan\\DependencyInjection\\ProjectConfigHelper',
            'couldnotreadfileexception' => 'PHPStan\\File\\CouldNotReadFileException',
            'filefinder' => 'PHPStan\\File\\FileFinder',
            'filehelper' => 'PHPStan\\File\\FileHelper',
            'filewriter' => 'PHPStan\\File\\FileWriter',
            'arrayhelper' => 'PHPStan\\Internal\\ArrayHelper',
            'composerhelper' => 'PHPStan\\Internal\\ComposerHelper',
            'stubfilesprovider' => 'PHPStan\\PhpDoc\\StubFilesProvider',
            'shouldnothappenexception' => 'PHPStan\\ShouldNotHappenException',
            'reflectionclass' => 'ReflectionClass',
            'reflectionexception' => 'ReflectionException',
            'throwable' => 'Throwable',
          ),
           'className' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheManager',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
            'LinesToIgnore' => true,
            'CollectorData' => true,
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
            'php_version_id' => 'PHP_VERSION_ID',
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
          'LinesToIgnore' => true,
          'CollectorData' => true,
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
          'php_version_id' => 'PHP_VERSION_ID',
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      'ec02b1629072c256f05ed18fee5e3d68' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'PHPStan\\Analyser\\ResultCache',
         'uses' => 
        array (
          'neon' => 'Nette\\Neon\\Neon',
          'analyserresult' => 'PHPStan\\Analyser\\AnalyserResult',
          'error' => 'PHPStan\\Analyser\\Error',
          'fileanalyserresult' => 'PHPStan\\Analyser\\FileAnalyserResult',
          'collecteddata' => 'PHPStan\\Collectors\\CollectedData',
          'output' => 'PHPStan\\Command\\Output',
          'exportedtraitnode' => 'PHPStan\\Dependency\\ExportedNode\\ExportedTraitNode',
          'exportednodefetcher' => 'PHPStan\\Dependency\\ExportedNodeFetcher',
          'rootexportednode' => 'PHPStan\\Dependency\\RootExportedNode',
          'autowiredparameter' => 'PHPStan\\DependencyInjection\\AutowiredParameter',
          'container' => 'PHPStan\\DependencyInjection\\Container',
          'generatefactory' => 'PHPStan\\DependencyInjection\\GenerateFactory',
          'projectconfighelper' => 'PHPStan\\DependencyInjection\\ProjectConfigHelper',
          'couldnotreadfileexception' => 'PHPStan\\File\\CouldNotReadFileException',
          'filefinder' => 'PHPStan\\File\\FileFinder',
          'filehelper' => 'PHPStan\\File\\FileHelper',
          'filewriter' => 'PHPStan\\File\\FileWriter',
          'arrayhelper' => 'PHPStan\\Internal\\ArrayHelper',
          'composerhelper' => 'PHPStan\\Internal\\ComposerHelper',
          'stubfilesprovider' => 'PHPStan\\PhpDoc\\StubFilesProvider',
          'shouldnothappenexception' => 'PHPStan\\ShouldNotHappenException',
          'reflectionclass' => 'ReflectionClass',
          'reflectionexception' => 'ReflectionException',
          'throwable' => 'Throwable',
        ),
         'className' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheManager',
         'functionName' => 'getExecutedFileHashes',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'PHPStan\\Analyser\\ResultCache',
           'uses' => 
          array (
            'neon' => 'Nette\\Neon\\Neon',
            'analyserresult' => 'PHPStan\\Analyser\\AnalyserResult',
            'error' => 'PHPStan\\Analyser\\Error',
            'fileanalyserresult' => 'PHPStan\\Analyser\\FileAnalyserResult',
            'collecteddata' => 'PHPStan\\Collectors\\CollectedData',
            'output' => 'PHPStan\\Command\\Output',
            'exportedtraitnode' => 'PHPStan\\Dependency\\ExportedNode\\ExportedTraitNode',
            'exportednodefetcher' => 'PHPStan\\Dependency\\ExportedNodeFetcher',
            'rootexportednode' => 'PHPStan\\Dependency\\RootExportedNode',
            'autowiredparameter' => 'PHPStan\\DependencyInjection\\AutowiredParameter',
            'container' => 'PHPStan\\DependencyInjection\\Container',
            'generatefactory' => 'PHPStan\\DependencyInjection\\GenerateFactory',
            'projectconfighelper' => 'PHPStan\\DependencyInjection\\ProjectConfigHelper',
            'couldnotreadfileexception' => 'PHPStan\\File\\CouldNotReadFileException',
            'filefinder' => 'PHPStan\\File\\FileFinder',
            'filehelper' => 'PHPStan\\File\\FileHelper',
            'filewriter' => 'PHPStan\\File\\FileWriter',
            'arrayhelper' => 'PHPStan\\Internal\\ArrayHelper',
            'composerhelper' => 'PHPStan\\Internal\\ComposerHelper',
            'stubfilesprovider' => 'PHPStan\\PhpDoc\\StubFilesProvider',
            'shouldnothappenexception' => 'PHPStan\\ShouldNotHappenException',
            'reflectionclass' => 'ReflectionClass',
            'reflectionexception' => 'ReflectionException',
            'throwable' => 'Throwable',
          ),
           'className' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheManager',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
            'LinesToIgnore' => true,
            'CollectorData' => true,
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
            'php_version_id' => 'PHP_VERSION_ID',
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
          'LinesToIgnore' => true,
          'CollectorData' => true,
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
          'php_version_id' => 'PHP_VERSION_ID',
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '38ccdf15cdc9b76646481f5b879b1b44' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'PHPStan\\Analyser\\ResultCache',
         'uses' => 
        array (
          'neon' => 'Nette\\Neon\\Neon',
          'analyserresult' => 'PHPStan\\Analyser\\AnalyserResult',
          'error' => 'PHPStan\\Analyser\\Error',
          'fileanalyserresult' => 'PHPStan\\Analyser\\FileAnalyserResult',
          'collecteddata' => 'PHPStan\\Collectors\\CollectedData',
          'output' => 'PHPStan\\Command\\Output',
          'exportedtraitnode' => 'PHPStan\\Dependency\\ExportedNode\\ExportedTraitNode',
          'exportednodefetcher' => 'PHPStan\\Dependency\\ExportedNodeFetcher',
          'rootexportednode' => 'PHPStan\\Dependency\\RootExportedNode',
          'autowiredparameter' => 'PHPStan\\DependencyInjection\\AutowiredParameter',
          'container' => 'PHPStan\\DependencyInjection\\Container',
          'generatefactory' => 'PHPStan\\DependencyInjection\\GenerateFactory',
          'projectconfighelper' => 'PHPStan\\DependencyInjection\\ProjectConfigHelper',
          'couldnotreadfileexception' => 'PHPStan\\File\\CouldNotReadFileException',
          'filefinder' => 'PHPStan\\File\\FileFinder',
          'filehelper' => 'PHPStan\\File\\FileHelper',
          'filewriter' => 'PHPStan\\File\\FileWriter',
          'arrayhelper' => 'PHPStan\\Internal\\ArrayHelper',
          'composerhelper' => 'PHPStan\\Internal\\ComposerHelper',
          'stubfilesprovider' => 'PHPStan\\PhpDoc\\StubFilesProvider',
          'shouldnothappenexception' => 'PHPStan\\ShouldNotHappenException',
          'reflectionclass' => 'ReflectionClass',
          'reflectionexception' => 'ReflectionException',
          'throwable' => 'Throwable',
        ),
         'className' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheManager',
         'functionName' => 'getComposerLocks',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'PHPStan\\Analyser\\ResultCache',
           'uses' => 
          array (
            'neon' => 'Nette\\Neon\\Neon',
            'analyserresult' => 'PHPStan\\Analyser\\AnalyserResult',
            'error' => 'PHPStan\\Analyser\\Error',
            'fileanalyserresult' => 'PHPStan\\Analyser\\FileAnalyserResult',
            'collecteddata' => 'PHPStan\\Collectors\\CollectedData',
            'output' => 'PHPStan\\Command\\Output',
            'exportedtraitnode' => 'PHPStan\\Dependency\\ExportedNode\\ExportedTraitNode',
            'exportednodefetcher' => 'PHPStan\\Dependency\\ExportedNodeFetcher',
            'rootexportednode' => 'PHPStan\\Dependency\\RootExportedNode',
            'autowiredparameter' => 'PHPStan\\DependencyInjection\\AutowiredParameter',
            'container' => 'PHPStan\\DependencyInjection\\Container',
            'generatefactory' => 'PHPStan\\DependencyInjection\\GenerateFactory',
            'projectconfighelper' => 'PHPStan\\DependencyInjection\\ProjectConfigHelper',
            'couldnotreadfileexception' => 'PHPStan\\File\\CouldNotReadFileException',
            'filefinder' => 'PHPStan\\File\\FileFinder',
            'filehelper' => 'PHPStan\\File\\FileHelper',
            'filewriter' => 'PHPStan\\File\\FileWriter',
            'arrayhelper' => 'PHPStan\\Internal\\ArrayHelper',
            'composerhelper' => 'PHPStan\\Internal\\ComposerHelper',
            'stubfilesprovider' => 'PHPStan\\PhpDoc\\StubFilesProvider',
            'shouldnothappenexception' => 'PHPStan\\ShouldNotHappenException',
            'reflectionclass' => 'ReflectionClass',
            'reflectionexception' => 'ReflectionException',
            'throwable' => 'Throwable',
          ),
           'className' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheManager',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
            'LinesToIgnore' => true,
            'CollectorData' => true,
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
            'php_version_id' => 'PHP_VERSION_ID',
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
          'LinesToIgnore' => true,
          'CollectorData' => true,
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
          'php_version_id' => 'PHP_VERSION_ID',
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '5a13bacbd335eb9ea3ee03e5320a4489' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'PHPStan\\Analyser\\ResultCache',
         'uses' => 
        array (
          'neon' => 'Nette\\Neon\\Neon',
          'analyserresult' => 'PHPStan\\Analyser\\AnalyserResult',
          'error' => 'PHPStan\\Analyser\\Error',
          'fileanalyserresult' => 'PHPStan\\Analyser\\FileAnalyserResult',
          'collecteddata' => 'PHPStan\\Collectors\\CollectedData',
          'output' => 'PHPStan\\Command\\Output',
          'exportedtraitnode' => 'PHPStan\\Dependency\\ExportedNode\\ExportedTraitNode',
          'exportednodefetcher' => 'PHPStan\\Dependency\\ExportedNodeFetcher',
          'rootexportednode' => 'PHPStan\\Dependency\\RootExportedNode',
          'autowiredparameter' => 'PHPStan\\DependencyInjection\\AutowiredParameter',
          'container' => 'PHPStan\\DependencyInjection\\Container',
          'generatefactory' => 'PHPStan\\DependencyInjection\\GenerateFactory',
          'projectconfighelper' => 'PHPStan\\DependencyInjection\\ProjectConfigHelper',
          'couldnotreadfileexception' => 'PHPStan\\File\\CouldNotReadFileException',
          'filefinder' => 'PHPStan\\File\\FileFinder',
          'filehelper' => 'PHPStan\\File\\FileHelper',
          'filewriter' => 'PHPStan\\File\\FileWriter',
          'arrayhelper' => 'PHPStan\\Internal\\ArrayHelper',
          'composerhelper' => 'PHPStan\\Internal\\ComposerHelper',
          'stubfilesprovider' => 'PHPStan\\PhpDoc\\StubFilesProvider',
          'shouldnothappenexception' => 'PHPStan\\ShouldNotHappenException',
          'reflectionclass' => 'ReflectionClass',
          'reflectionexception' => 'ReflectionException',
          'throwable' => 'Throwable',
        ),
         'className' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheManager',
         'functionName' => 'getComposerInstalled',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'PHPStan\\Analyser\\ResultCache',
           'uses' => 
          array (
            'neon' => 'Nette\\Neon\\Neon',
            'analyserresult' => 'PHPStan\\Analyser\\AnalyserResult',
            'error' => 'PHPStan\\Analyser\\Error',
            'fileanalyserresult' => 'PHPStan\\Analyser\\FileAnalyserResult',
            'collecteddata' => 'PHPStan\\Collectors\\CollectedData',
            'output' => 'PHPStan\\Command\\Output',
            'exportedtraitnode' => 'PHPStan\\Dependency\\ExportedNode\\ExportedTraitNode',
            'exportednodefetcher' => 'PHPStan\\Dependency\\ExportedNodeFetcher',
            'rootexportednode' => 'PHPStan\\Dependency\\RootExportedNode',
            'autowiredparameter' => 'PHPStan\\DependencyInjection\\AutowiredParameter',
            'container' => 'PHPStan\\DependencyInjection\\Container',
            'generatefactory' => 'PHPStan\\DependencyInjection\\GenerateFactory',
            'projectconfighelper' => 'PHPStan\\DependencyInjection\\ProjectConfigHelper',
            'couldnotreadfileexception' => 'PHPStan\\File\\CouldNotReadFileException',
            'filefinder' => 'PHPStan\\File\\FileFinder',
            'filehelper' => 'PHPStan\\File\\FileHelper',
            'filewriter' => 'PHPStan\\File\\FileWriter',
            'arrayhelper' => 'PHPStan\\Internal\\ArrayHelper',
            'composerhelper' => 'PHPStan\\Internal\\ComposerHelper',
            'stubfilesprovider' => 'PHPStan\\PhpDoc\\StubFilesProvider',
            'shouldnothappenexception' => 'PHPStan\\ShouldNotHappenException',
            'reflectionclass' => 'ReflectionClass',
            'reflectionexception' => 'ReflectionException',
            'throwable' => 'Throwable',
          ),
           'className' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheManager',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
            'LinesToIgnore' => true,
            'CollectorData' => true,
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
            'php_version_id' => 'PHP_VERSION_ID',
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
          'LinesToIgnore' => true,
          'CollectorData' => true,
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
          'php_version_id' => 'PHP_VERSION_ID',
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '78a9d8f626b7a75172f2a465526ad9e7' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'PHPStan\\Analyser\\ResultCache',
         'uses' => 
        array (
          'neon' => 'Nette\\Neon\\Neon',
          'analyserresult' => 'PHPStan\\Analyser\\AnalyserResult',
          'error' => 'PHPStan\\Analyser\\Error',
          'fileanalyserresult' => 'PHPStan\\Analyser\\FileAnalyserResult',
          'collecteddata' => 'PHPStan\\Collectors\\CollectedData',
          'output' => 'PHPStan\\Command\\Output',
          'exportedtraitnode' => 'PHPStan\\Dependency\\ExportedNode\\ExportedTraitNode',
          'exportednodefetcher' => 'PHPStan\\Dependency\\ExportedNodeFetcher',
          'rootexportednode' => 'PHPStan\\Dependency\\RootExportedNode',
          'autowiredparameter' => 'PHPStan\\DependencyInjection\\AutowiredParameter',
          'container' => 'PHPStan\\DependencyInjection\\Container',
          'generatefactory' => 'PHPStan\\DependencyInjection\\GenerateFactory',
          'projectconfighelper' => 'PHPStan\\DependencyInjection\\ProjectConfigHelper',
          'couldnotreadfileexception' => 'PHPStan\\File\\CouldNotReadFileException',
          'filefinder' => 'PHPStan\\File\\FileFinder',
          'filehelper' => 'PHPStan\\File\\FileHelper',
          'filewriter' => 'PHPStan\\File\\FileWriter',
          'arrayhelper' => 'PHPStan\\Internal\\ArrayHelper',
          'composerhelper' => 'PHPStan\\Internal\\ComposerHelper',
          'stubfilesprovider' => 'PHPStan\\PhpDoc\\StubFilesProvider',
          'shouldnothappenexception' => 'PHPStan\\ShouldNotHappenException',
          'reflectionclass' => 'ReflectionClass',
          'reflectionexception' => 'ReflectionException',
          'throwable' => 'Throwable',
        ),
         'className' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheManager',
         'functionName' => 'getStubFiles',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'PHPStan\\Analyser\\ResultCache',
           'uses' => 
          array (
            'neon' => 'Nette\\Neon\\Neon',
            'analyserresult' => 'PHPStan\\Analyser\\AnalyserResult',
            'error' => 'PHPStan\\Analyser\\Error',
            'fileanalyserresult' => 'PHPStan\\Analyser\\FileAnalyserResult',
            'collecteddata' => 'PHPStan\\Collectors\\CollectedData',
            'output' => 'PHPStan\\Command\\Output',
            'exportedtraitnode' => 'PHPStan\\Dependency\\ExportedNode\\ExportedTraitNode',
            'exportednodefetcher' => 'PHPStan\\Dependency\\ExportedNodeFetcher',
            'rootexportednode' => 'PHPStan\\Dependency\\RootExportedNode',
            'autowiredparameter' => 'PHPStan\\DependencyInjection\\AutowiredParameter',
            'container' => 'PHPStan\\DependencyInjection\\Container',
            'generatefactory' => 'PHPStan\\DependencyInjection\\GenerateFactory',
            'projectconfighelper' => 'PHPStan\\DependencyInjection\\ProjectConfigHelper',
            'couldnotreadfileexception' => 'PHPStan\\File\\CouldNotReadFileException',
            'filefinder' => 'PHPStan\\File\\FileFinder',
            'filehelper' => 'PHPStan\\File\\FileHelper',
            'filewriter' => 'PHPStan\\File\\FileWriter',
            'arrayhelper' => 'PHPStan\\Internal\\ArrayHelper',
            'composerhelper' => 'PHPStan\\Internal\\ComposerHelper',
            'stubfilesprovider' => 'PHPStan\\PhpDoc\\StubFilesProvider',
            'shouldnothappenexception' => 'PHPStan\\ShouldNotHappenException',
            'reflectionclass' => 'ReflectionClass',
            'reflectionexception' => 'ReflectionException',
            'throwable' => 'Throwable',
          ),
           'className' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheManager',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
            'LinesToIgnore' => true,
            'CollectorData' => true,
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
            'php_version_id' => 'PHP_VERSION_ID',
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
          'LinesToIgnore' => true,
          'CollectorData' => true,
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
          'php_version_id' => 'PHP_VERSION_ID',
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      'a05285eb5784b272e9b044b77e5d35dd' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'PHPStan\\Analyser\\ResultCache',
         'uses' => 
        array (
          'neon' => 'Nette\\Neon\\Neon',
          'analyserresult' => 'PHPStan\\Analyser\\AnalyserResult',
          'error' => 'PHPStan\\Analyser\\Error',
          'fileanalyserresult' => 'PHPStan\\Analyser\\FileAnalyserResult',
          'collecteddata' => 'PHPStan\\Collectors\\CollectedData',
          'output' => 'PHPStan\\Command\\Output',
          'exportedtraitnode' => 'PHPStan\\Dependency\\ExportedNode\\ExportedTraitNode',
          'exportednodefetcher' => 'PHPStan\\Dependency\\ExportedNodeFetcher',
          'rootexportednode' => 'PHPStan\\Dependency\\RootExportedNode',
          'autowiredparameter' => 'PHPStan\\DependencyInjection\\AutowiredParameter',
          'container' => 'PHPStan\\DependencyInjection\\Container',
          'generatefactory' => 'PHPStan\\DependencyInjection\\GenerateFactory',
          'projectconfighelper' => 'PHPStan\\DependencyInjection\\ProjectConfigHelper',
          'couldnotreadfileexception' => 'PHPStan\\File\\CouldNotReadFileException',
          'filefinder' => 'PHPStan\\File\\FileFinder',
          'filehelper' => 'PHPStan\\File\\FileHelper',
          'filewriter' => 'PHPStan\\File\\FileWriter',
          'arrayhelper' => 'PHPStan\\Internal\\ArrayHelper',
          'composerhelper' => 'PHPStan\\Internal\\ComposerHelper',
          'stubfilesprovider' => 'PHPStan\\PhpDoc\\StubFilesProvider',
          'shouldnothappenexception' => 'PHPStan\\ShouldNotHappenException',
          'reflectionclass' => 'ReflectionClass',
          'reflectionexception' => 'ReflectionException',
          'throwable' => 'Throwable',
        ),
         'className' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheManager',
         'functionName' => 'getMetaFromPhpStanExtensions',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'PHPStan\\Analyser\\ResultCache',
           'uses' => 
          array (
            'neon' => 'Nette\\Neon\\Neon',
            'analyserresult' => 'PHPStan\\Analyser\\AnalyserResult',
            'error' => 'PHPStan\\Analyser\\Error',
            'fileanalyserresult' => 'PHPStan\\Analyser\\FileAnalyserResult',
            'collecteddata' => 'PHPStan\\Collectors\\CollectedData',
            'output' => 'PHPStan\\Command\\Output',
            'exportedtraitnode' => 'PHPStan\\Dependency\\ExportedNode\\ExportedTraitNode',
            'exportednodefetcher' => 'PHPStan\\Dependency\\ExportedNodeFetcher',
            'rootexportednode' => 'PHPStan\\Dependency\\RootExportedNode',
            'autowiredparameter' => 'PHPStan\\DependencyInjection\\AutowiredParameter',
            'container' => 'PHPStan\\DependencyInjection\\Container',
            'generatefactory' => 'PHPStan\\DependencyInjection\\GenerateFactory',
            'projectconfighelper' => 'PHPStan\\DependencyInjection\\ProjectConfigHelper',
            'couldnotreadfileexception' => 'PHPStan\\File\\CouldNotReadFileException',
            'filefinder' => 'PHPStan\\File\\FileFinder',
            'filehelper' => 'PHPStan\\File\\FileHelper',
            'filewriter' => 'PHPStan\\File\\FileWriter',
            'arrayhelper' => 'PHPStan\\Internal\\ArrayHelper',
            'composerhelper' => 'PHPStan\\Internal\\ComposerHelper',
            'stubfilesprovider' => 'PHPStan\\PhpDoc\\StubFilesProvider',
            'shouldnothappenexception' => 'PHPStan\\ShouldNotHappenException',
            'reflectionclass' => 'ReflectionClass',
            'reflectionexception' => 'ReflectionException',
            'throwable' => 'Throwable',
          ),
           'className' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheManager',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
            'LinesToIgnore' => true,
            'CollectorData' => true,
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
            'php_version_id' => 'PHP_VERSION_ID',
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
          'LinesToIgnore' => true,
          'CollectorData' => true,
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
          'php_version_id' => 'PHP_VERSION_ID',
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
    ),
    1 => 
    array (
      '/home/runner/work/phpstan-src/phpstan-src/src/Analyser/ResultCache/ResultCacheManager.php' => 'afccaeef6faafad240ed4c67f41ba64388ed9517defcf53ccc69b13ed714f254',
    ),
  ),
));