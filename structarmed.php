<?php

declare(strict_types=1);

use Boundwize\StructArmed\Architecture;
use Boundwize\StructArmed\Preset\Preset;

return Architecture::define()
    ->withPresets(Preset::PSR4(), Preset::CODEQUALITY())

    // Marker attributes and service-registration contracts referenced from every layer.
    ->layer('TurboMarker', [
        'src/Turbo/ReferencedByTurboExtension.php',
        'src/Turbo/ShadowedByTurboExtension.php',
    ])
    ->layer('Autowiring', [
        'src/DependencyInjection/AutowiredExtensions.php',
        'src/DependencyInjection/AutowiredParameter.php',
        'src/DependencyInjection/AutowiredService.php',
        'src/DependencyInjection/Conditional.php',
        'src/DependencyInjection/Container.php',
        'src/DependencyInjection/ExtensionInterface.php',
        'src/DependencyInjection/ExtensionsCollection.php',
        'src/DependencyInjection/GenerateFactory.php',
        'src/DependencyInjection/NonAutowiredService.php',
        'src/DependencyInjection/RegisteredCollector.php',
        'src/DependencyInjection/RegisteredRule.php',
        'src/DependencyInjection/ValidatesStubFiles.php',
    ])
    ->layer('FeatureToggle', [
        'src/DependencyInjection/BleedingEdgeToggle.php',
        'src/DependencyInjection/ReportUnsafeArrayStringKeyCastingToggle.php',
    ])
    ->layer('Core', [
        'src/AnalysedCodeException.php',
        'src/NeverException.php',
        'src/ShouldNotHappenException.php',
    ])

    // Infrastructure.
    ->layer('Internal', 'src/Internal/')
    ->layer('File', 'src/File/')
    ->layer('Cache', 'src/Cache/')
    ->layer('Neon', 'src/Neon/')
    ->layer('Php', 'src/Php/')
    ->layer('Broker', 'src/Broker/')

    // Analysis engine.
    ->layer('Parser', 'src/Parser/')
    ->layer('Node', 'src/Node/')
    ->layer('Type', ['src/Type/', 'src/TrinaryLogic.php'])
    ->layer('Reflection', 'src/Reflection/')
    ->layer('PhpDoc', 'src/PhpDoc/')
    ->layer('Analyser', 'src/Analyser/')
    ->layer('Dependency', 'src/Dependency/')
    ->layer('Collectors', 'src/Collectors/')
    ->layer('Fixable', 'src/Fixable/')

    // Checks built on top of the engine.
    ->layer('Classes', 'src/Classes/')
    ->layer('Rules', 'src/Rules/')

    // Application: processes, CLI and the composition root.
    ->layer('Diagnose', 'src/Diagnose/')
    ->layer('Process', 'src/Process/')
    ->layer('Turbo', 'src/Turbo/', [
        'src/Turbo/ReferencedByTurboExtension.php',
        'src/Turbo/ShadowedByTurboExtension.php',
    ])
    ->layer('Parallel', 'src/Parallel/')
    ->layer('Command', 'src/Command/')
    ->layer('DependencyInjection', 'src/DependencyInjection/', [
        'src/DependencyInjection/AutowiredExtensions.php',
        'src/DependencyInjection/AutowiredParameter.php',
        'src/DependencyInjection/AutowiredService.php',
        'src/DependencyInjection/BleedingEdgeToggle.php',
        'src/DependencyInjection/Conditional.php',
        'src/DependencyInjection/Container.php',
        'src/DependencyInjection/ExtensionInterface.php',
        'src/DependencyInjection/ExtensionsCollection.php',
        'src/DependencyInjection/GenerateFactory.php',
        'src/DependencyInjection/NonAutowiredService.php',
        'src/DependencyInjection/RegisteredCollector.php',
        'src/DependencyInjection/RegisteredRule.php',
        'src/DependencyInjection/ReportUnsafeArrayStringKeyCastingToggle.php',
        'src/DependencyInjection/ValidatesStubFiles.php',
    ])
    ->layer('Testing', 'src/Testing/')

    ->ruleset([
        'TurboMarker'   => [],
        'Autowiring'    => ['+Core'],
        'FeatureToggle' => ['+Core'],
        'Core'          => ['TurboMarker'],

        'Internal' => ['+Core', 'Autowiring', 'File'],
        'File'     => ['+Internal', 'Analyser'],
        'Cache'    => ['+File'],
        'Neon'     => ['Autowiring'],
        'Php'      => ['+Internal', 'Type', 'Analyser'],
        'Broker'   => ['+File', 'Parser'],

        // Parser, Node, Type, Reflection, PhpDoc and Analyser are mutually dependent.
        'Parser'     => ['+Php', 'Node', 'Reflection', 'Analyser'],
        'Node'       => ['+Core', 'Autowiring', 'Type', 'Reflection', 'Analyser', 'Collectors', 'PhpDoc', 'Rules'],
        'Type'       => ['+Cache', '+Php', '+Broker', 'FeatureToggle', 'Node', 'Reflection', 'PhpDoc', 'Analyser', 'Rules', 'Turbo'],
        'Reflection' => ['+Type', 'Command', 'DependencyInjection'],
        'PhpDoc'     => ['+Type', 'Collectors', 'DependencyInjection'],
        'Analyser'   => ['+Type', 'Dependency', 'Collectors', 'Fixable', 'Rules', 'Neon', 'Command', 'DependencyInjection'],
        'Dependency' => ['+Type'],
        'Collectors' => ['Autowiring', 'Type', 'Analyser'],
        'Fixable'    => ['+Core', 'Autowiring', 'File', 'Node', 'Analyser', 'Rules'],

        'Classes' => ['Autowiring'],
        'Rules'   => ['+Type', 'Collectors', 'Classes', 'DependencyInjection'],

        'Diagnose' => ['+Php', 'Process', 'Command'],
        'Process'  => ['+Core', 'Autowiring', 'Diagnose', 'Turbo', 'Command'],
        'Turbo'    => ['+Process', 'Php'],
        'Parallel' => ['+Command'],
        'Command'  => ['+Analyser', '+Diagnose', 'Neon', 'Turbo', 'Parallel', 'DependencyInjection'],

        // Composition root: wires every layer together.
        'DependencyInjection' => ['+Command'],
        'Testing'             => ['+DependencyInjection'],
    ]);
