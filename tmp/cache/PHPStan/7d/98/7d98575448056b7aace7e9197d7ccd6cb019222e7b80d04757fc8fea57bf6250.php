<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../phpunit/phpunit/src/
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v1-enums',
   'data' => 
  array (
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Dispatcher/CollectingDispatcher.php' => 
    array (
      0 => 'c940975e1fda938a1c828b295b4a5bf6436afdde1bf4d2dafeef6d8051776416',
      1 => 
      array (
        0 => 'phpunit\\event\\collectingdispatcher',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\__construct',
        1 => 'phpunit\\event\\dispatch',
        2 => 'phpunit\\event\\flush',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Dispatcher/DeferringDispatcher.php' => 
    array (
      0 => 'eba5bd9220e45dff6b05db83baaaa2e1925e2230e43239f526fc2f0d06b13d98',
      1 => 
      array (
        0 => 'phpunit\\event\\deferringdispatcher',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\__construct',
        1 => 'phpunit\\event\\registertracer',
        2 => 'phpunit\\event\\registersubscriber',
        3 => 'phpunit\\event\\dispatch',
        4 => 'phpunit\\event\\flush',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Dispatcher/DirectDispatcher.php' => 
    array (
      0 => '87ce84283239de5005d9c8305d5193864a4779c3847b5ac31f9500123c204472',
      1 => 
      array (
        0 => 'phpunit\\event\\directdispatcher',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\__construct',
        1 => 'phpunit\\event\\registertracer',
        2 => 'phpunit\\event\\registersubscriber',
        3 => 'phpunit\\event\\dispatch',
        4 => 'phpunit\\event\\handlethrowable',
        5 => 'phpunit\\event\\isthrowablefromthirdpartysubscriber',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Dispatcher/Dispatcher.php' => 
    array (
      0 => 'fd2c5f118040641643094a68bb1b7e6ae79e69617e4faf45b40517f33a14528f',
      1 => 
      array (
        0 => 'phpunit\\event\\dispatcher',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\dispatch',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Dispatcher/SubscribableDispatcher.php' => 
    array (
      0 => '485b11c61e0c30ab1b3865537b346edaa00b4336bb08418820864601e66291ea',
      1 => 
      array (
        0 => 'phpunit\\event\\subscribabledispatcher',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\registersubscriber',
        1 => 'phpunit\\event\\registertracer',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Emitter/DispatchingEmitter.php' => 
    array (
      0 => '4d4653764db63cbf3e5d73dff2b40880828e5311c001fcc374d142c9d92ab940',
      1 => 
      array (
        0 => 'phpunit\\event\\dispatchingemitter',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\__construct',
        1 => 'phpunit\\event\\applicationstarted',
        2 => 'phpunit\\event\\testrunnerstarted',
        3 => 'phpunit\\event\\testrunnerconfigured',
        4 => 'phpunit\\event\\testrunnerbootstrapfinished',
        5 => 'phpunit\\event\\testrunnerloadedextensionfromphar',
        6 => 'phpunit\\event\\testrunnerbootstrappedextension',
        7 => 'phpunit\\event\\dataprovidermethodcalled',
        8 => 'phpunit\\event\\dataprovidermethodfinished',
        9 => 'phpunit\\event\\testsuiteloaded',
        10 => 'phpunit\\event\\testsuitefiltered',
        11 => 'phpunit\\event\\testsuitesorted',
        12 => 'phpunit\\event\\testrunnereventfacadesealed',
        13 => 'phpunit\\event\\testrunnerexecutionstarted',
        14 => 'phpunit\\event\\testrunnerdisabledgarbagecollection',
        15 => 'phpunit\\event\\testrunnertriggeredgarbagecollection',
        16 => 'phpunit\\event\\testrunnerstartedchildprocess',
        17 => 'phpunit\\event\\testrunnerfinishedchildprocess',
        18 => 'phpunit\\event\\testsuiteskipped',
        19 => 'phpunit\\event\\testsuitestarted',
        20 => 'phpunit\\event\\testpreparationstarted',
        21 => 'phpunit\\event\\testpreparationfailed',
        22 => 'phpunit\\event\\beforefirsttestmethodcalled',
        23 => 'phpunit\\event\\beforefirsttestmethoderrored',
        24 => 'phpunit\\event\\beforefirsttestmethodfinished',
        25 => 'phpunit\\event\\beforetestmethodcalled',
        26 => 'phpunit\\event\\beforetestmethoderrored',
        27 => 'phpunit\\event\\beforetestmethodfinished',
        28 => 'phpunit\\event\\preconditioncalled',
        29 => 'phpunit\\event\\preconditionerrored',
        30 => 'phpunit\\event\\preconditionfinished',
        31 => 'phpunit\\event\\testprepared',
        32 => 'phpunit\\event\\testregisteredcomparator',
        33 => 'phpunit\\event\\testcreatedmockobject',
        34 => 'phpunit\\event\\testcreatedmockobjectforintersectionofinterfaces',
        35 => 'phpunit\\event\\testcreatedmockobjectfortrait',
        36 => 'phpunit\\event\\testcreatedmockobjectforabstractclass',
        37 => 'phpunit\\event\\testcreatedmockobjectfromwsdl',
        38 => 'phpunit\\event\\testcreatedpartialmockobject',
        39 => 'phpunit\\event\\testcreatedtestproxy',
        40 => 'phpunit\\event\\testcreatedstub',
        41 => 'phpunit\\event\\testcreatedstubforintersectionofinterfaces',
        42 => 'phpunit\\event\\testerrored',
        43 => 'phpunit\\event\\testfailed',
        44 => 'phpunit\\event\\testpassed',
        45 => 'phpunit\\event\\testconsideredrisky',
        46 => 'phpunit\\event\\testmarkedasincomplete',
        47 => 'phpunit\\event\\testskipped',
        48 => 'phpunit\\event\\testtriggeredphpunitdeprecation',
        49 => 'phpunit\\event\\testtriggeredphpdeprecation',
        50 => 'phpunit\\event\\testtriggereddeprecation',
        51 => 'phpunit\\event\\testtriggerederror',
        52 => 'phpunit\\event\\testtriggerednotice',
        53 => 'phpunit\\event\\testtriggeredphpnotice',
        54 => 'phpunit\\event\\testtriggeredwarning',
        55 => 'phpunit\\event\\testtriggeredphpwarning',
        56 => 'phpunit\\event\\testtriggeredphpuniterror',
        57 => 'phpunit\\event\\testtriggeredphpunitwarning',
        58 => 'phpunit\\event\\testprintedunexpectedoutput',
        59 => 'phpunit\\event\\testfinished',
        60 => 'phpunit\\event\\postconditioncalled',
        61 => 'phpunit\\event\\postconditionerrored',
        62 => 'phpunit\\event\\postconditionfinished',
        63 => 'phpunit\\event\\aftertestmethodcalled',
        64 => 'phpunit\\event\\aftertestmethoderrored',
        65 => 'phpunit\\event\\aftertestmethodfinished',
        66 => 'phpunit\\event\\afterlasttestmethodcalled',
        67 => 'phpunit\\event\\afterlasttestmethoderrored',
        68 => 'phpunit\\event\\afterlasttestmethodfinished',
        69 => 'phpunit\\event\\testsuitefinished',
        70 => 'phpunit\\event\\testrunnertriggeredphpunitdeprecation',
        71 => 'phpunit\\event\\testrunnertriggeredphpunitwarning',
        72 => 'phpunit\\event\\testrunnerenabledgarbagecollection',
        73 => 'phpunit\\event\\testrunnerexecutionaborted',
        74 => 'phpunit\\event\\testrunnerexecutionfinished',
        75 => 'phpunit\\event\\testrunnerfinished',
        76 => 'phpunit\\event\\applicationfinished',
        77 => 'phpunit\\event\\telemetryinfo',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Emitter/Emitter.php' => 
    array (
      0 => '585b76636ec6e32b5e0ea89a701b5c66738a04c2944e4c26d3911cd0d2ab5160',
      1 => 
      array (
        0 => 'phpunit\\event\\emitter',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\applicationstarted',
        1 => 'phpunit\\event\\testrunnerstarted',
        2 => 'phpunit\\event\\testrunnerconfigured',
        3 => 'phpunit\\event\\testrunnerbootstrapfinished',
        4 => 'phpunit\\event\\testrunnerloadedextensionfromphar',
        5 => 'phpunit\\event\\testrunnerbootstrappedextension',
        6 => 'phpunit\\event\\dataprovidermethodcalled',
        7 => 'phpunit\\event\\dataprovidermethodfinished',
        8 => 'phpunit\\event\\testsuiteloaded',
        9 => 'phpunit\\event\\testsuitefiltered',
        10 => 'phpunit\\event\\testsuitesorted',
        11 => 'phpunit\\event\\testrunnereventfacadesealed',
        12 => 'phpunit\\event\\testrunnerexecutionstarted',
        13 => 'phpunit\\event\\testrunnerdisabledgarbagecollection',
        14 => 'phpunit\\event\\testrunnertriggeredgarbagecollection',
        15 => 'phpunit\\event\\testsuiteskipped',
        16 => 'phpunit\\event\\testsuitestarted',
        17 => 'phpunit\\event\\testpreparationstarted',
        18 => 'phpunit\\event\\testpreparationfailed',
        19 => 'phpunit\\event\\beforefirsttestmethodcalled',
        20 => 'phpunit\\event\\beforefirsttestmethoderrored',
        21 => 'phpunit\\event\\beforefirsttestmethodfinished',
        22 => 'phpunit\\event\\beforetestmethodcalled',
        23 => 'phpunit\\event\\beforetestmethoderrored',
        24 => 'phpunit\\event\\beforetestmethodfinished',
        25 => 'phpunit\\event\\preconditioncalled',
        26 => 'phpunit\\event\\preconditionerrored',
        27 => 'phpunit\\event\\preconditionfinished',
        28 => 'phpunit\\event\\testprepared',
        29 => 'phpunit\\event\\testregisteredcomparator',
        30 => 'phpunit\\event\\testcreatedmockobject',
        31 => 'phpunit\\event\\testcreatedmockobjectforintersectionofinterfaces',
        32 => 'phpunit\\event\\testcreatedmockobjectfortrait',
        33 => 'phpunit\\event\\testcreatedmockobjectforabstractclass',
        34 => 'phpunit\\event\\testcreatedmockobjectfromwsdl',
        35 => 'phpunit\\event\\testcreatedpartialmockobject',
        36 => 'phpunit\\event\\testcreatedtestproxy',
        37 => 'phpunit\\event\\testcreatedstub',
        38 => 'phpunit\\event\\testcreatedstubforintersectionofinterfaces',
        39 => 'phpunit\\event\\testerrored',
        40 => 'phpunit\\event\\testfailed',
        41 => 'phpunit\\event\\testpassed',
        42 => 'phpunit\\event\\testconsideredrisky',
        43 => 'phpunit\\event\\testmarkedasincomplete',
        44 => 'phpunit\\event\\testskipped',
        45 => 'phpunit\\event\\testtriggeredphpunitdeprecation',
        46 => 'phpunit\\event\\testtriggeredphpdeprecation',
        47 => 'phpunit\\event\\testtriggereddeprecation',
        48 => 'phpunit\\event\\testtriggerederror',
        49 => 'phpunit\\event\\testtriggerednotice',
        50 => 'phpunit\\event\\testtriggeredphpnotice',
        51 => 'phpunit\\event\\testtriggeredwarning',
        52 => 'phpunit\\event\\testtriggeredphpwarning',
        53 => 'phpunit\\event\\testtriggeredphpuniterror',
        54 => 'phpunit\\event\\testtriggeredphpunitwarning',
        55 => 'phpunit\\event\\testprintedunexpectedoutput',
        56 => 'phpunit\\event\\testfinished',
        57 => 'phpunit\\event\\postconditioncalled',
        58 => 'phpunit\\event\\postconditionerrored',
        59 => 'phpunit\\event\\postconditionfinished',
        60 => 'phpunit\\event\\aftertestmethodcalled',
        61 => 'phpunit\\event\\aftertestmethoderrored',
        62 => 'phpunit\\event\\aftertestmethodfinished',
        63 => 'phpunit\\event\\afterlasttestmethodcalled',
        64 => 'phpunit\\event\\afterlasttestmethoderrored',
        65 => 'phpunit\\event\\afterlasttestmethodfinished',
        66 => 'phpunit\\event\\testsuitefinished',
        67 => 'phpunit\\event\\testrunnerstartedchildprocess',
        68 => 'phpunit\\event\\testrunnerfinishedchildprocess',
        69 => 'phpunit\\event\\testrunnertriggeredphpunitdeprecation',
        70 => 'phpunit\\event\\testrunnertriggeredphpunitwarning',
        71 => 'phpunit\\event\\testrunnerenabledgarbagecollection',
        72 => 'phpunit\\event\\testrunnerexecutionaborted',
        73 => 'phpunit\\event\\testrunnerexecutionfinished',
        74 => 'phpunit\\event\\testrunnerfinished',
        75 => 'phpunit\\event\\applicationfinished',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Application/Finished.php' => 
    array (
      0 => '4d9734f0b7ec2da4463de5d52221dcb9057e1b04726084b945780a5afb7ba55d',
      1 => 
      array (
        0 => 'phpunit\\event\\application\\finished',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\application\\__construct',
        1 => 'phpunit\\event\\application\\telemetryinfo',
        2 => 'phpunit\\event\\application\\shellexitcode',
        3 => 'phpunit\\event\\application\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Application/FinishedSubscriber.php' => 
    array (
      0 => '8ddbc10ea586ba4783197eeabb155955bc5d551a563b6be6353d06009657f876',
      1 => 
      array (
        0 => 'phpunit\\event\\application\\finishedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\application\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Application/Started.php' => 
    array (
      0 => '958f7fe0356a6c0f082f34616da8b1cf272d93f892669ff5cd7c03b513f52dbf',
      1 => 
      array (
        0 => 'phpunit\\event\\application\\started',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\application\\__construct',
        1 => 'phpunit\\event\\application\\telemetryinfo',
        2 => 'phpunit\\event\\application\\runtime',
        3 => 'phpunit\\event\\application\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Application/StartedSubscriber.php' => 
    array (
      0 => '706acf4dd0d16ae48af8954fa76bcea611810c6c4640439667fa4096ffcbbf22',
      1 => 
      array (
        0 => 'phpunit\\event\\application\\startedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\application\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Event.php' => 
    array (
      0 => '8f1f089c254b912cb93f2baa7e2149560a7f9c0d4bad30998962712596eeea44',
      1 => 
      array (
        0 => 'phpunit\\event\\event',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\telemetryinfo',
        1 => 'phpunit\\event\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/EventCollection.php' => 
    array (
      0 => '4b221c7c0c81f22bc09a8fa161cd556314c12af1b78da0d9978e9c11fdc0c259',
      1 => 
      array (
        0 => 'phpunit\\event\\eventcollection',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\add',
        1 => 'phpunit\\event\\asarray',
        2 => 'phpunit\\event\\count',
        3 => 'phpunit\\event\\isempty',
        4 => 'phpunit\\event\\isnotempty',
        5 => 'phpunit\\event\\getiterator',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/EventCollectionIterator.php' => 
    array (
      0 => '045dd88f20241d9115ae117323975805e25b6ebed2749776bf0394165b6bbaae',
      1 => 
      array (
        0 => 'phpunit\\event\\eventcollectioniterator',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\__construct',
        1 => 'phpunit\\event\\rewind',
        2 => 'phpunit\\event\\valid',
        3 => 'phpunit\\event\\key',
        4 => 'phpunit\\event\\current',
        5 => 'phpunit\\event\\next',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/ComparatorRegistered.php' => 
    array (
      0 => '4be1550ed1a9228d493a93a8e97424ba033bddcb4ee0dac14b3d052a11695c89',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\comparatorregistered',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\__construct',
        1 => 'phpunit\\event\\test\\telemetryinfo',
        2 => 'phpunit\\event\\test\\classname',
        3 => 'phpunit\\event\\test\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/ComparatorRegisteredSubscriber.php' => 
    array (
      0 => '35fba31daa247b9ef83ecdacb51f4cca6a9548ee43ab8486ceb7f92ac856e1e0',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\comparatorregisteredsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/HookMethod/AfterLastTestMethodCalled.php' => 
    array (
      0 => '3dbdcf21549b82873cc6e8cd02a596d66e792fe0afb6e8d33288073fefb978c2',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\afterlasttestmethodcalled',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\__construct',
        1 => 'phpunit\\event\\test\\telemetryinfo',
        2 => 'phpunit\\event\\test\\testclassname',
        3 => 'phpunit\\event\\test\\calledmethod',
        4 => 'phpunit\\event\\test\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/HookMethod/AfterLastTestMethodCalledSubscriber.php' => 
    array (
      0 => '3f0ec97213a598224294263052bbfab17f8e6ec9c97051d93daccb45eaddbdb9',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\afterlasttestmethodcalledsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/HookMethod/AfterLastTestMethodErrored.php' => 
    array (
      0 => 'b0e4f069f2a301885abc0ffd9a49cd07c7569ae0beaef544db0110f486ad3103',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\afterlasttestmethoderrored',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\__construct',
        1 => 'phpunit\\event\\test\\telemetryinfo',
        2 => 'phpunit\\event\\test\\testclassname',
        3 => 'phpunit\\event\\test\\calledmethod',
        4 => 'phpunit\\event\\test\\throwable',
        5 => 'phpunit\\event\\test\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/HookMethod/AfterLastTestMethodErroredSubscriber.php' => 
    array (
      0 => 'a0748e04449229440809d3156490a46ce94ec760955e949a00811abfe95104ec',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\afterlasttestmethoderroredsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/HookMethod/AfterLastTestMethodFinished.php' => 
    array (
      0 => 'db1720769706ac3c48f41529b1cb130f7825037bceff59231ae0903223d8ec3a',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\afterlasttestmethodfinished',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\__construct',
        1 => 'phpunit\\event\\test\\telemetryinfo',
        2 => 'phpunit\\event\\test\\testclassname',
        3 => 'phpunit\\event\\test\\calledmethods',
        4 => 'phpunit\\event\\test\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/HookMethod/AfterLastTestMethodFinishedSubscriber.php' => 
    array (
      0 => 'd1e2184747488bb69ed81683ca6fac2deb36efecdf1a30af6d32232d02d32111',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\afterlasttestmethodfinishedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/HookMethod/AfterTestMethodCalled.php' => 
    array (
      0 => 'f6f089785bd98503e93c537b8c6e3ad12dab54fa8aeea00f68a75d03cd24e90b',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\aftertestmethodcalled',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\__construct',
        1 => 'phpunit\\event\\test\\telemetryinfo',
        2 => 'phpunit\\event\\test\\testclassname',
        3 => 'phpunit\\event\\test\\calledmethod',
        4 => 'phpunit\\event\\test\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/HookMethod/AfterTestMethodCalledSubscriber.php' => 
    array (
      0 => '970845fd842f7a31e74fead6c9226e88a565c6b094938ad968a08283348f7528',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\aftertestmethodcalledsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/HookMethod/AfterTestMethodErrored.php' => 
    array (
      0 => '5382d1d18d606296affcea2019d7de5c7e8d2355cff14c48421a5beb034a4a8f',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\aftertestmethoderrored',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\__construct',
        1 => 'phpunit\\event\\test\\telemetryinfo',
        2 => 'phpunit\\event\\test\\testclassname',
        3 => 'phpunit\\event\\test\\calledmethod',
        4 => 'phpunit\\event\\test\\throwable',
        5 => 'phpunit\\event\\test\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/HookMethod/AfterTestMethodErroredSubscriber.php' => 
    array (
      0 => '067c292b764a400b88a5f384b9934736a2040d234fba5f2122347978572946dc',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\aftertestmethoderroredsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/HookMethod/AfterTestMethodFinished.php' => 
    array (
      0 => '7c6ad6d72af4ac1da61a61ada1c0216b95429ccc42ec195f6e7b10080f47a864',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\aftertestmethodfinished',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\__construct',
        1 => 'phpunit\\event\\test\\telemetryinfo',
        2 => 'phpunit\\event\\test\\testclassname',
        3 => 'phpunit\\event\\test\\calledmethods',
        4 => 'phpunit\\event\\test\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/HookMethod/AfterTestMethodFinishedSubscriber.php' => 
    array (
      0 => '5cab4e6197bfe9033c976e660d514ceb53509d1c072f680377e19242888c8e00',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\aftertestmethodfinishedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/HookMethod/BeforeFirstTestMethodCalled.php' => 
    array (
      0 => 'cc88146bdbdc52fac91c82cf03e8a5f2eaa8fc2064a8bc6dd8f6870221bf9a2f',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\beforefirsttestmethodcalled',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\__construct',
        1 => 'phpunit\\event\\test\\telemetryinfo',
        2 => 'phpunit\\event\\test\\testclassname',
        3 => 'phpunit\\event\\test\\calledmethod',
        4 => 'phpunit\\event\\test\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/HookMethod/BeforeFirstTestMethodCalledSubscriber.php' => 
    array (
      0 => '5321560b154f1aad490644a56479ba235276c5d86b38888c6bf3b5ad90f96386',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\beforefirsttestmethodcalledsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/HookMethod/BeforeFirstTestMethodErrored.php' => 
    array (
      0 => '10d9d17adebcb7930a39311b60bcb4d0c3f2150a40eb7dcfa01e1baa28dfec70',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\beforefirsttestmethoderrored',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\__construct',
        1 => 'phpunit\\event\\test\\telemetryinfo',
        2 => 'phpunit\\event\\test\\testclassname',
        3 => 'phpunit\\event\\test\\calledmethod',
        4 => 'phpunit\\event\\test\\throwable',
        5 => 'phpunit\\event\\test\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/HookMethod/BeforeFirstTestMethodErroredSubscriber.php' => 
    array (
      0 => 'a29510afd352ceb92f88d2b415e63ad959679729c6ac4375994d211e24b1bef7',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\beforefirsttestmethoderroredsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/HookMethod/BeforeFirstTestMethodFinished.php' => 
    array (
      0 => 'edcf9c835bb773c845851c0b649aff2a0eb30143dfbf93fdc28b17a1ee22999f',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\beforefirsttestmethodfinished',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\__construct',
        1 => 'phpunit\\event\\test\\telemetryinfo',
        2 => 'phpunit\\event\\test\\testclassname',
        3 => 'phpunit\\event\\test\\calledmethods',
        4 => 'phpunit\\event\\test\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/HookMethod/BeforeFirstTestMethodFinishedSubscriber.php' => 
    array (
      0 => '3f93327580166d9047939a51d06ff77673816475daa4a3df9dd3bae695e8f9ea',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\beforefirsttestmethodfinishedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/HookMethod/BeforeTestMethodCalled.php' => 
    array (
      0 => '02b02f832f122934d4954351680a3387cc451371a0ee3384ed463e4fbe16f827',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\beforetestmethodcalled',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\__construct',
        1 => 'phpunit\\event\\test\\telemetryinfo',
        2 => 'phpunit\\event\\test\\testclassname',
        3 => 'phpunit\\event\\test\\calledmethod',
        4 => 'phpunit\\event\\test\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/HookMethod/BeforeTestMethodCalledSubscriber.php' => 
    array (
      0 => 'f9658af861c9ad9bf4baeb3e8bb343418c5f863afce58c1e290d43c25bd4b105',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\beforetestmethodcalledsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/HookMethod/BeforeTestMethodErrored.php' => 
    array (
      0 => 'ac15c659015aa5e02514a142756b78d762b0567a3d496a22a7181f8153f1496e',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\beforetestmethoderrored',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\__construct',
        1 => 'phpunit\\event\\test\\telemetryinfo',
        2 => 'phpunit\\event\\test\\testclassname',
        3 => 'phpunit\\event\\test\\calledmethod',
        4 => 'phpunit\\event\\test\\throwable',
        5 => 'phpunit\\event\\test\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/HookMethod/BeforeTestMethodErroredSubscriber.php' => 
    array (
      0 => 'cb2cf8983983e0efbdbc5deec0d66c810c0ca996c82a5b65fd064ed502021915',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\beforetestmethoderroredsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/HookMethod/BeforeTestMethodFinished.php' => 
    array (
      0 => '8639f84f308163fbaf33132b00e4c1a5f0b0b628f4b21342c10dca2792944cd2',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\beforetestmethodfinished',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\__construct',
        1 => 'phpunit\\event\\test\\telemetryinfo',
        2 => 'phpunit\\event\\test\\testclassname',
        3 => 'phpunit\\event\\test\\calledmethods',
        4 => 'phpunit\\event\\test\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/HookMethod/BeforeTestMethodFinishedSubscriber.php' => 
    array (
      0 => 'c9daba45b4ddf539240b63a4d5fbd636e731036a4d653065959d6b7ab8d79252',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\beforetestmethodfinishedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/HookMethod/PostConditionCalled.php' => 
    array (
      0 => 'f8673719b675f6945b914855d6b1522d58a28ad4d48b54c456f979f33091ee37',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\postconditioncalled',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\__construct',
        1 => 'phpunit\\event\\test\\telemetryinfo',
        2 => 'phpunit\\event\\test\\testclassname',
        3 => 'phpunit\\event\\test\\calledmethod',
        4 => 'phpunit\\event\\test\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/HookMethod/PostConditionCalledSubscriber.php' => 
    array (
      0 => '959980b453c3945d6c7661438d10b6cedd6a6eb145ef549ac4027aad37054a46',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\postconditioncalledsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/HookMethod/PostConditionErrored.php' => 
    array (
      0 => '41021d81e1dc84fcb1acf439b29c54f80541aaef9dac247b29d5bd5ebf78ff9c',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\postconditionerrored',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\__construct',
        1 => 'phpunit\\event\\test\\telemetryinfo',
        2 => 'phpunit\\event\\test\\testclassname',
        3 => 'phpunit\\event\\test\\calledmethod',
        4 => 'phpunit\\event\\test\\throwable',
        5 => 'phpunit\\event\\test\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/HookMethod/PostConditionErroredSubscriber.php' => 
    array (
      0 => '6a40b6650dade602e0403bb672ff0be3c2c76a2a313a583e002fb891cdde6a38',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\postconditionerroredsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/HookMethod/PostConditionFinished.php' => 
    array (
      0 => '931c236868486b30d55b6b68ab3528f8d5ef377af577204ca232eaae15080656',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\postconditionfinished',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\__construct',
        1 => 'phpunit\\event\\test\\telemetryinfo',
        2 => 'phpunit\\event\\test\\testclassname',
        3 => 'phpunit\\event\\test\\calledmethods',
        4 => 'phpunit\\event\\test\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/HookMethod/PostConditionFinishedSubscriber.php' => 
    array (
      0 => '1ab59e4c54d959c43fb6c3c7074a1bbfbfd2c625fd94d25db36c001a76fe4a63',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\postconditionfinishedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/HookMethod/PreConditionCalled.php' => 
    array (
      0 => '73c61684bb123bf1d6e60f02933fd9e47e73ea323c6055163b06810bb31d4c22',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\preconditioncalled',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\__construct',
        1 => 'phpunit\\event\\test\\telemetryinfo',
        2 => 'phpunit\\event\\test\\testclassname',
        3 => 'phpunit\\event\\test\\calledmethod',
        4 => 'phpunit\\event\\test\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/HookMethod/PreConditionCalledSubscriber.php' => 
    array (
      0 => '19762d35a85585c328dddbf4754d5780bcecc546312ecfb5feb6965bb02bbd90',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\preconditioncalledsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/HookMethod/PreConditionErrored.php' => 
    array (
      0 => '1ce1d0021b63f0cde026f87121c0f5f91fafa1c33fdb930b43ccc347a66203e6',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\preconditionerrored',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\__construct',
        1 => 'phpunit\\event\\test\\telemetryinfo',
        2 => 'phpunit\\event\\test\\testclassname',
        3 => 'phpunit\\event\\test\\calledmethod',
        4 => 'phpunit\\event\\test\\throwable',
        5 => 'phpunit\\event\\test\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/HookMethod/PreConditionErroredSubscriber.php' => 
    array (
      0 => '5fd70209f46dfdc5a7ae64e119df86ea35b9919ee38802af71192a5230d650eb',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\preconditionerroredsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/HookMethod/PreConditionFinished.php' => 
    array (
      0 => 'eee80f5d122e5cee844235a4f9e653775d96d2508603e8a20e4c96c2ac081c8b',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\preconditionfinished',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\__construct',
        1 => 'phpunit\\event\\test\\telemetryinfo',
        2 => 'phpunit\\event\\test\\testclassname',
        3 => 'phpunit\\event\\test\\calledmethods',
        4 => 'phpunit\\event\\test\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/HookMethod/PreConditionFinishedSubscriber.php' => 
    array (
      0 => '03245950a75a40fcd51978e9e79c65aa2cb05a146ae658e3a76e20e6f60ea3cb',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\preconditionfinishedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/Issue/ConsideredRisky.php' => 
    array (
      0 => '32100b26f625e5cbc50e0b271f0b584f2053806f43726a95930e4e1d76f483ce',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\consideredrisky',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\__construct',
        1 => 'phpunit\\event\\test\\telemetryinfo',
        2 => 'phpunit\\event\\test\\test',
        3 => 'phpunit\\event\\test\\message',
        4 => 'phpunit\\event\\test\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/Issue/ConsideredRiskySubscriber.php' => 
    array (
      0 => '9fe00d60ac173defc4e03ea211eb4f63e35885e08b7e52f91bb2f442d4df9e96',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\consideredriskysubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/Issue/DeprecationTriggered.php' => 
    array (
      0 => '86869bac6b97f629d6eb224c5b2f182718b89a8404f7cf7123a1880547eaaa1f',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\deprecationtriggered',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\__construct',
        1 => 'phpunit\\event\\test\\telemetryinfo',
        2 => 'phpunit\\event\\test\\test',
        3 => 'phpunit\\event\\test\\message',
        4 => 'phpunit\\event\\test\\file',
        5 => 'phpunit\\event\\test\\line',
        6 => 'phpunit\\event\\test\\wassuppressed',
        7 => 'phpunit\\event\\test\\ignoredbybaseline',
        8 => 'phpunit\\event\\test\\ignoredbytest',
        9 => 'phpunit\\event\\test\\trigger',
        10 => 'phpunit\\event\\test\\stacktrace',
        11 => 'phpunit\\event\\test\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/Issue/DeprecationTriggeredSubscriber.php' => 
    array (
      0 => '7ef4e0fb0f7e097128fd8995fd4dc97e351357218d7b13526b48d9a698555ace',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\deprecationtriggeredsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/Issue/ErrorTriggered.php' => 
    array (
      0 => '7a4550d622351d02a7834d3cd99cd796c1d028c96fa1b959b48ce92394c5eed4',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\errortriggered',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\__construct',
        1 => 'phpunit\\event\\test\\telemetryinfo',
        2 => 'phpunit\\event\\test\\test',
        3 => 'phpunit\\event\\test\\message',
        4 => 'phpunit\\event\\test\\file',
        5 => 'phpunit\\event\\test\\line',
        6 => 'phpunit\\event\\test\\wassuppressed',
        7 => 'phpunit\\event\\test\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/Issue/ErrorTriggeredSubscriber.php' => 
    array (
      0 => 'bba4b59b56af864a22fdc76eb9dfda2d737a5a49632c5b209386d608cf216e9e',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\errortriggeredsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/Issue/NoticeTriggered.php' => 
    array (
      0 => 'd2297d7579d288ab81998ca8e12236d43a60eecf372a0d26acd7a84181f1bf0f',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\noticetriggered',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\__construct',
        1 => 'phpunit\\event\\test\\telemetryinfo',
        2 => 'phpunit\\event\\test\\test',
        3 => 'phpunit\\event\\test\\message',
        4 => 'phpunit\\event\\test\\file',
        5 => 'phpunit\\event\\test\\line',
        6 => 'phpunit\\event\\test\\wassuppressed',
        7 => 'phpunit\\event\\test\\ignoredbybaseline',
        8 => 'phpunit\\event\\test\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/Issue/NoticeTriggeredSubscriber.php' => 
    array (
      0 => 'c5869f76f3f534b26d7fb0a1b81d5d92adcdb4010e6d75694e3b8c4ed3572b22',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\noticetriggeredsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/Issue/PhpDeprecationTriggered.php' => 
    array (
      0 => '8c3e17a87bf6ba688f85776b19e1a2fea65775fe3a851c25592fb1bed54f70e6',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\phpdeprecationtriggered',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\__construct',
        1 => 'phpunit\\event\\test\\telemetryinfo',
        2 => 'phpunit\\event\\test\\test',
        3 => 'phpunit\\event\\test\\message',
        4 => 'phpunit\\event\\test\\file',
        5 => 'phpunit\\event\\test\\line',
        6 => 'phpunit\\event\\test\\wassuppressed',
        7 => 'phpunit\\event\\test\\ignoredbybaseline',
        8 => 'phpunit\\event\\test\\ignoredbytest',
        9 => 'phpunit\\event\\test\\trigger',
        10 => 'phpunit\\event\\test\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/Issue/PhpDeprecationTriggeredSubscriber.php' => 
    array (
      0 => 'dd68ebf15937fe607344038a0251080a95a9ad9c674959e42e00cc1cf171a444',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\phpdeprecationtriggeredsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/Issue/PhpNoticeTriggered.php' => 
    array (
      0 => '623429ae17d86178c6b65d84bb1e7a71419e68851aecb255a94cd7df17ab4c8d',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\phpnoticetriggered',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\__construct',
        1 => 'phpunit\\event\\test\\telemetryinfo',
        2 => 'phpunit\\event\\test\\test',
        3 => 'phpunit\\event\\test\\message',
        4 => 'phpunit\\event\\test\\file',
        5 => 'phpunit\\event\\test\\line',
        6 => 'phpunit\\event\\test\\wassuppressed',
        7 => 'phpunit\\event\\test\\ignoredbybaseline',
        8 => 'phpunit\\event\\test\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/Issue/PhpNoticeTriggeredSubscriber.php' => 
    array (
      0 => '791d840cbdbfe5ffa349723ff56fe51fcde8af87ce64a54df1e6e8242104d960',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\phpnoticetriggeredsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/Issue/PhpWarningTriggered.php' => 
    array (
      0 => '2677ea02e24415d5f3fda5b410d312bfb283913ea6565ebf9ff6d9185a90e440',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\phpwarningtriggered',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\__construct',
        1 => 'phpunit\\event\\test\\telemetryinfo',
        2 => 'phpunit\\event\\test\\test',
        3 => 'phpunit\\event\\test\\message',
        4 => 'phpunit\\event\\test\\file',
        5 => 'phpunit\\event\\test\\line',
        6 => 'phpunit\\event\\test\\wassuppressed',
        7 => 'phpunit\\event\\test\\ignoredbybaseline',
        8 => 'phpunit\\event\\test\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/Issue/PhpWarningTriggeredSubscriber.php' => 
    array (
      0 => 'b403897c897c5d09185c5737e7d729f6c81ea855f7fd86dce3411c11bffd257f',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\phpwarningtriggeredsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/Issue/PhpunitDeprecationTriggered.php' => 
    array (
      0 => '044425662aee857adb67ade4bf5ccdc5f9e149eb7846e522ccc415f4ddbf280d',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\phpunitdeprecationtriggered',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\__construct',
        1 => 'phpunit\\event\\test\\telemetryinfo',
        2 => 'phpunit\\event\\test\\test',
        3 => 'phpunit\\event\\test\\message',
        4 => 'phpunit\\event\\test\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/Issue/PhpunitDeprecationTriggeredSubscriber.php' => 
    array (
      0 => 'a133bd14c6c4bb00caff612de91a486153749f8de46f539d85c4fb11220f4bed',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\phpunitdeprecationtriggeredsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/Issue/PhpunitErrorTriggered.php' => 
    array (
      0 => '43e433cac54df15838d4c9fea824c8792f2c47ea3d93d39a31fa5eb5d5048b11',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\phpuniterrortriggered',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\__construct',
        1 => 'phpunit\\event\\test\\telemetryinfo',
        2 => 'phpunit\\event\\test\\test',
        3 => 'phpunit\\event\\test\\message',
        4 => 'phpunit\\event\\test\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/Issue/PhpunitErrorTriggeredSubscriber.php' => 
    array (
      0 => 'b0585538c1acf53f5573095aa61174500897a23654d9a9d6ab41ee894d272f1a',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\phpuniterrortriggeredsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/Issue/PhpunitWarningTriggered.php' => 
    array (
      0 => '725d7a97a29dada8c2031d32987662816c3ec5107ce404f3121ac767719416c5',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\phpunitwarningtriggered',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\__construct',
        1 => 'phpunit\\event\\test\\telemetryinfo',
        2 => 'phpunit\\event\\test\\test',
        3 => 'phpunit\\event\\test\\message',
        4 => 'phpunit\\event\\test\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/Issue/PhpunitWarningTriggeredSubscriber.php' => 
    array (
      0 => 'bd277cd6e4bff4eff73bbf0811b12c16d1ab436a93997fefd5fb8328d07aab07',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\phpunitwarningtriggeredsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/Issue/WarningTriggered.php' => 
    array (
      0 => 'a418e512f03690a85f8fad6642d66ff9d15616155d52483ea5b43cd443fc651a',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\warningtriggered',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\__construct',
        1 => 'phpunit\\event\\test\\telemetryinfo',
        2 => 'phpunit\\event\\test\\test',
        3 => 'phpunit\\event\\test\\message',
        4 => 'phpunit\\event\\test\\file',
        5 => 'phpunit\\event\\test\\line',
        6 => 'phpunit\\event\\test\\wassuppressed',
        7 => 'phpunit\\event\\test\\ignoredbybaseline',
        8 => 'phpunit\\event\\test\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/Issue/WarningTriggeredSubscriber.php' => 
    array (
      0 => '8104233b64e184ee56a38b3a259817cc680ff8bb5f31d7d77fe591eb3570305a',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\warningtriggeredsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/Lifecycle/DataProviderMethodCalled.php' => 
    array (
      0 => 'b0bbb7054e58396413c39f1c52050c3c2402c53ded7ad3f3d3af613405f25112',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\dataprovidermethodcalled',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\__construct',
        1 => 'phpunit\\event\\test\\telemetryinfo',
        2 => 'phpunit\\event\\test\\testmethod',
        3 => 'phpunit\\event\\test\\dataprovidermethod',
        4 => 'phpunit\\event\\test\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/Lifecycle/DataProviderMethodCalledSubscriber.php' => 
    array (
      0 => '87cc201e88e0a908a51bb6baad3d8a87063818d8673a5efc7e8c08a364fd775d',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\dataprovidermethodcalledsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/Lifecycle/DataProviderMethodFinished.php' => 
    array (
      0 => 'afdf54e3048da13c4ec278f8b18bf80294a9256302ff095a494b47e10372f4dd',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\dataprovidermethodfinished',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\__construct',
        1 => 'phpunit\\event\\test\\telemetryinfo',
        2 => 'phpunit\\event\\test\\testmethod',
        3 => 'phpunit\\event\\test\\calledmethods',
        4 => 'phpunit\\event\\test\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/Lifecycle/DataProviderMethodFinishedSubscriber.php' => 
    array (
      0 => '818712f9efcbcfbfbc46606e0c559902a8691001f8eb089efd1da9d272b482c4',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\dataprovidermethodfinishedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/Lifecycle/Finished.php' => 
    array (
      0 => '9064d4aab1ed2fd6a4a783deef17307c1584b3be63822a806965322a5379b378',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\finished',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\__construct',
        1 => 'phpunit\\event\\test\\telemetryinfo',
        2 => 'phpunit\\event\\test\\test',
        3 => 'phpunit\\event\\test\\numberofassertionsperformed',
        4 => 'phpunit\\event\\test\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/Lifecycle/FinishedSubscriber.php' => 
    array (
      0 => '160d177fe4ec2f6630b1601c083eb0c61b56359ea281f728edbec16a492bf1bf',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\finishedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/Lifecycle/PreparationFailed.php' => 
    array (
      0 => '948dc19cdefa5e29f91da53b9d209d6833d1158f80e7427c29843f4b3bc29998',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\preparationfailed',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\__construct',
        1 => 'phpunit\\event\\test\\telemetryinfo',
        2 => 'phpunit\\event\\test\\test',
        3 => 'phpunit\\event\\test\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/Lifecycle/PreparationFailedSubscriber.php' => 
    array (
      0 => '0b78c2f577e9bc3da673c71bad61c3a5c247f97788bab7208a1234b5592d05ca',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\preparationfailedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/Lifecycle/PreparationStarted.php' => 
    array (
      0 => '9323a1fb4ab1ed137e0acef1daa7105dd6373f3e7b00b0ce465457225d5f5366',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\preparationstarted',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\__construct',
        1 => 'phpunit\\event\\test\\telemetryinfo',
        2 => 'phpunit\\event\\test\\test',
        3 => 'phpunit\\event\\test\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/Lifecycle/PreparationStartedSubscriber.php' => 
    array (
      0 => '4d060a24aefb8546be1549f0f031e6d059f73a9257dccb798a82b80bb3a9fcba',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\preparationstartedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/Lifecycle/Prepared.php' => 
    array (
      0 => 'de409e05020d8d279ab13b9f5f28080750c54ef774c7d59354e1f382763c8b34',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\prepared',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\__construct',
        1 => 'phpunit\\event\\test\\telemetryinfo',
        2 => 'phpunit\\event\\test\\test',
        3 => 'phpunit\\event\\test\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/Lifecycle/PreparedSubscriber.php' => 
    array (
      0 => 'b8e4edb81f7c19192f83099d4051d254029be8f8110f29d7d4626a42f072f833',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\preparedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/Outcome/Errored.php' => 
    array (
      0 => '8a37d3690d418f0e446b95dffe3aca14e2b240ae74a3140c700cc6e9edba95d0',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\errored',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\__construct',
        1 => 'phpunit\\event\\test\\telemetryinfo',
        2 => 'phpunit\\event\\test\\test',
        3 => 'phpunit\\event\\test\\throwable',
        4 => 'phpunit\\event\\test\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/Outcome/ErroredSubscriber.php' => 
    array (
      0 => '62a87e677c62faf7444a2353939360fcdc4479ece86a51459712039cd707a27c',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\erroredsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/Outcome/Failed.php' => 
    array (
      0 => 'a1c0a68c951284bd0f1064dda0971aecc7a21206fe0fa3cc612a32ee08d920de',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\failed',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\__construct',
        1 => 'phpunit\\event\\test\\telemetryinfo',
        2 => 'phpunit\\event\\test\\test',
        3 => 'phpunit\\event\\test\\throwable',
        4 => 'phpunit\\event\\test\\hascomparisonfailure',
        5 => 'phpunit\\event\\test\\comparisonfailure',
        6 => 'phpunit\\event\\test\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/Outcome/FailedSubscriber.php' => 
    array (
      0 => 'c77d826395fa2118354d2beec28ff5d91e0cfa3a2d498cb80a8643383ca9c653',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\failedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/Outcome/MarkedIncomplete.php' => 
    array (
      0 => 'e77dc9ae54e80b92ab02cc470cb8300eca998ca3123c8619883d162475e2838d',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\markedincomplete',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\__construct',
        1 => 'phpunit\\event\\test\\telemetryinfo',
        2 => 'phpunit\\event\\test\\test',
        3 => 'phpunit\\event\\test\\throwable',
        4 => 'phpunit\\event\\test\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/Outcome/MarkedIncompleteSubscriber.php' => 
    array (
      0 => '833871e36ce8046ccd28483261a1b9aff0f223bcd080d537216cc01bd26abbc4',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\markedincompletesubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/Outcome/Passed.php' => 
    array (
      0 => '75320ca8dbf9896b4ff281c5ee589970fcd860c0910d33b38d8c3f63cfd0b49d',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\passed',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\__construct',
        1 => 'phpunit\\event\\test\\telemetryinfo',
        2 => 'phpunit\\event\\test\\test',
        3 => 'phpunit\\event\\test\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/Outcome/PassedSubscriber.php' => 
    array (
      0 => '480a65b58d666847a9b22e8b9824c539b4cca58afee349b95bda56067fa6d9c4',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\passedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/Outcome/Skipped.php' => 
    array (
      0 => 'f049e19d117da3f0cba7be7eca71560356cb4bd6da2e7a0e2f022be388daa490',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\skipped',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\__construct',
        1 => 'phpunit\\event\\test\\telemetryinfo',
        2 => 'phpunit\\event\\test\\test',
        3 => 'phpunit\\event\\test\\message',
        4 => 'phpunit\\event\\test\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/Outcome/SkippedSubscriber.php' => 
    array (
      0 => '9a5fd6018c085270f0940be68a03b5011040a84dbd57b386839a44ea3b11a057',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\skippedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/PrintedUnexpectedOutput.php' => 
    array (
      0 => '750624262605ef796936240138699b7bcb836874b475ecac8141728639788f0b',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\printedunexpectedoutput',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\__construct',
        1 => 'phpunit\\event\\test\\telemetryinfo',
        2 => 'phpunit\\event\\test\\output',
        3 => 'phpunit\\event\\test\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/PrintedUnexpectedOutputSubscriber.php' => 
    array (
      0 => 'dcb01204744b38a12e8a1a23a1c06dbcc36974b770ad9fe26e17393cfd8d831c',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\printedunexpectedoutputsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/TestDouble/MockObjectCreated.php' => 
    array (
      0 => '5c0c78e1d831e2b1414dc326a1ae1d24d0887ed640a04dcb3a4ac9a2c6767d7d',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\mockobjectcreated',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\__construct',
        1 => 'phpunit\\event\\test\\telemetryinfo',
        2 => 'phpunit\\event\\test\\classname',
        3 => 'phpunit\\event\\test\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/TestDouble/MockObjectCreatedSubscriber.php' => 
    array (
      0 => '4653f2a4e7f56ecb9a9002b090b7766997217f005d1f69f434ae2eab342b94e5',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\mockobjectcreatedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/TestDouble/MockObjectForAbstractClassCreated.php' => 
    array (
      0 => '436fe4a9e2b1b06c424cd8c705ca2d2a49ccbac3c8916a716b9e2cb23a99dc38',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\mockobjectforabstractclasscreated',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\__construct',
        1 => 'phpunit\\event\\test\\telemetryinfo',
        2 => 'phpunit\\event\\test\\classname',
        3 => 'phpunit\\event\\test\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/TestDouble/MockObjectForAbstractClassCreatedSubscriber.php' => 
    array (
      0 => 'e6d06f9195d2572ac55d79144f4cb9d410b1e3c59f36fab31bd04313f27db2f1',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\mockobjectforabstractclasscreatedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/TestDouble/MockObjectForIntersectionOfInterfacesCreated.php' => 
    array (
      0 => 'aaa616f7af56746dddb6cf4d07a096e923d0fc94fa127c7701f39ca10c709ff8',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\mockobjectforintersectionofinterfacescreated',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\__construct',
        1 => 'phpunit\\event\\test\\telemetryinfo',
        2 => 'phpunit\\event\\test\\interfaces',
        3 => 'phpunit\\event\\test\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/TestDouble/MockObjectForIntersectionOfInterfacesCreatedSubscriber.php' => 
    array (
      0 => '5662a3432200752a40002da25e5c9b757a7705808050e22453c334bb771ec3f0',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\mockobjectforintersectionofinterfacescreatedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/TestDouble/MockObjectForTraitCreated.php' => 
    array (
      0 => 'e26732c177950b09463efcaaca05236e766637643117e29e48bedd8e819be617',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\mockobjectfortraitcreated',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\__construct',
        1 => 'phpunit\\event\\test\\telemetryinfo',
        2 => 'phpunit\\event\\test\\traitname',
        3 => 'phpunit\\event\\test\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/TestDouble/MockObjectForTraitCreatedSubscriber.php' => 
    array (
      0 => '0bfd45ba025d4d4d1ef88dc1d6b8346f9b2e5c6fed9b59a8f52dcd799def9463',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\mockobjectfortraitcreatedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/TestDouble/MockObjectFromWsdlCreated.php' => 
    array (
      0 => 'cc137622b7c2cdb53d6ff5a0ce4b791b307a8c4441af3e73e5469d3db1bd40b3',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\mockobjectfromwsdlcreated',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\__construct',
        1 => 'phpunit\\event\\test\\telemetryinfo',
        2 => 'phpunit\\event\\test\\wsdlfile',
        3 => 'phpunit\\event\\test\\originalclassname',
        4 => 'phpunit\\event\\test\\mockclassname',
        5 => 'phpunit\\event\\test\\methods',
        6 => 'phpunit\\event\\test\\calloriginalconstructor',
        7 => 'phpunit\\event\\test\\options',
        8 => 'phpunit\\event\\test\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/TestDouble/MockObjectFromWsdlCreatedSubscriber.php' => 
    array (
      0 => '53d3b1bbf955c2c2679e374896b8e75669c48f822c33bd17126c2dee31994e71',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\mockobjectfromwsdlcreatedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/TestDouble/PartialMockObjectCreated.php' => 
    array (
      0 => '0cab3dc4c10632bd27d89b3e5dbf340f060d45d5d19a8b4ef227b9afa2626fe2',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\partialmockobjectcreated',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\__construct',
        1 => 'phpunit\\event\\test\\telemetryinfo',
        2 => 'phpunit\\event\\test\\classname',
        3 => 'phpunit\\event\\test\\methodnames',
        4 => 'phpunit\\event\\test\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/TestDouble/PartialMockObjectCreatedSubscriber.php' => 
    array (
      0 => '8fdac516a66e0af6773128cc3a57760caa2aacb8b1f9c38b3d2b27037f24bcac',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\partialmockobjectcreatedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/TestDouble/TestProxyCreated.php' => 
    array (
      0 => '2a7bb3da919487eb6c092e281241bdf5eddfe4705fb29437cd3986d70a7b3429',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\testproxycreated',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\__construct',
        1 => 'phpunit\\event\\test\\telemetryinfo',
        2 => 'phpunit\\event\\test\\classname',
        3 => 'phpunit\\event\\test\\constructorarguments',
        4 => 'phpunit\\event\\test\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/TestDouble/TestProxyCreatedSubscriber.php' => 
    array (
      0 => '2696594bfb5c23b1c210645c2f0cc732989cc8957bff9dddf7975f6541df348a',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\testproxycreatedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/TestDouble/TestStubCreated.php' => 
    array (
      0 => '38814fc54f0a7d7b91134a0077a4a738f55384ac2132863f0b3c53c781def51f',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\teststubcreated',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\__construct',
        1 => 'phpunit\\event\\test\\telemetryinfo',
        2 => 'phpunit\\event\\test\\classname',
        3 => 'phpunit\\event\\test\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/TestDouble/TestStubCreatedSubscriber.php' => 
    array (
      0 => 'eed1a311ce4b708ec142f20019c5f2eda68624deae75b0ff6bab7ba22d49737b',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\teststubcreatedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/TestDouble/TestStubForIntersectionOfInterfacesCreated.php' => 
    array (
      0 => '545e8f7df104e80be86c2d34963eba3b65a6eacc34ad68fa6ee59bf444b70e4a',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\teststubforintersectionofinterfacescreated',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\__construct',
        1 => 'phpunit\\event\\test\\telemetryinfo',
        2 => 'phpunit\\event\\test\\interfaces',
        3 => 'phpunit\\event\\test\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/Test/TestDouble/TestStubForIntersectionOfInterfacesCreatedSubscriber.php' => 
    array (
      0 => 'e1f30d552cd94d31e573f07660304d6867244a0beeaf1ac9f9101ee7c13bd26f',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\teststubforintersectionofinterfacescreatedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\test\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/TestRunner/BootstrapFinished.php' => 
    array (
      0 => 'b1d61bbbd5da593304992272ba501e4a3bf5bd13fb297d8e5d1f2ee145d779d8',
      1 => 
      array (
        0 => 'phpunit\\event\\testrunner\\bootstrapfinished',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\testrunner\\__construct',
        1 => 'phpunit\\event\\testrunner\\telemetryinfo',
        2 => 'phpunit\\event\\testrunner\\filename',
        3 => 'phpunit\\event\\testrunner\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/TestRunner/BootstrapFinishedSubscriber.php' => 
    array (
      0 => '2bb1d25af3c2708efdbe41bbde724ab6d8d58a24d7a2e09e377f9697d3bd01bc',
      1 => 
      array (
        0 => 'phpunit\\event\\testrunner\\bootstrapfinishedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\testrunner\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/TestRunner/ChildProcessFinished.php' => 
    array (
      0 => 'af865c3be5fab3e0ee96b10682beaa498de3c80f61be1cea1088b8084d3f8ff2',
      1 => 
      array (
        0 => 'phpunit\\event\\testrunner\\childprocessfinished',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\testrunner\\__construct',
        1 => 'phpunit\\event\\testrunner\\telemetryinfo',
        2 => 'phpunit\\event\\testrunner\\stdout',
        3 => 'phpunit\\event\\testrunner\\stderr',
        4 => 'phpunit\\event\\testrunner\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/TestRunner/ChildProcessFinishedSubscriber.php' => 
    array (
      0 => '3b20a31fc2ee6e307672bfc9d8d430e3a1e56d2fa05cca44aa1bcda8aa90952f',
      1 => 
      array (
        0 => 'phpunit\\event\\testrunner\\childprocessfinishedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\testrunner\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/TestRunner/ChildProcessStarted.php' => 
    array (
      0 => '3a33e41bc7ed51244e0413ff8528062754f1463b3b270fb95d9727b6ddb10cbe',
      1 => 
      array (
        0 => 'phpunit\\event\\testrunner\\childprocessstarted',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\testrunner\\__construct',
        1 => 'phpunit\\event\\testrunner\\telemetryinfo',
        2 => 'phpunit\\event\\testrunner\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/TestRunner/ChildProcessStartedSubscriber.php' => 
    array (
      0 => '086e055ecf8f4a58afaa150497c2b84f993be59faa7940feea9db74668dd4e8d',
      1 => 
      array (
        0 => 'phpunit\\event\\testrunner\\childprocessstartedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\testrunner\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/TestRunner/Configured.php' => 
    array (
      0 => '85b8ff479042b738dfe4592c8edcb1336d4b5f6061d5e0c8f81f5c7b113994a2',
      1 => 
      array (
        0 => 'phpunit\\event\\testrunner\\configured',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\testrunner\\__construct',
        1 => 'phpunit\\event\\testrunner\\telemetryinfo',
        2 => 'phpunit\\event\\testrunner\\configuration',
        3 => 'phpunit\\event\\testrunner\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/TestRunner/ConfiguredSubscriber.php' => 
    array (
      0 => '069e8dada16c07a3ad2e6402e8172554530dd42a36de67d05286ddaf97069404',
      1 => 
      array (
        0 => 'phpunit\\event\\testrunner\\configuredsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\testrunner\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/TestRunner/DeprecationTriggered.php' => 
    array (
      0 => '030ced9e64e2dcd6747f06f914b44d57dfa88a98a3023c055e23b134473c4324',
      1 => 
      array (
        0 => 'phpunit\\event\\testrunner\\deprecationtriggered',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\testrunner\\__construct',
        1 => 'phpunit\\event\\testrunner\\telemetryinfo',
        2 => 'phpunit\\event\\testrunner\\message',
        3 => 'phpunit\\event\\testrunner\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/TestRunner/DeprecationTriggeredSubscriber.php' => 
    array (
      0 => '3624fc5523b56426c8ff718a071d8192e340052aa3286ca754cd4292d5a235e5',
      1 => 
      array (
        0 => 'phpunit\\event\\testrunner\\deprecationtriggeredsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\testrunner\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/TestRunner/EventFacadeSealed.php' => 
    array (
      0 => 'a79a83a288e28d0c7905cd234c58001c202dcc776f6a051425fdf1debf5c8486',
      1 => 
      array (
        0 => 'phpunit\\event\\testrunner\\eventfacadesealed',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\testrunner\\__construct',
        1 => 'phpunit\\event\\testrunner\\telemetryinfo',
        2 => 'phpunit\\event\\testrunner\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/TestRunner/EventFacadeSealedSubscriber.php' => 
    array (
      0 => '707413b335ebeb21cd6678f8a3f8127de8dfffadf216a7c02836c8eb2f7501a6',
      1 => 
      array (
        0 => 'phpunit\\event\\testrunner\\eventfacadesealedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\testrunner\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/TestRunner/ExecutionAborted.php' => 
    array (
      0 => 'a94be35a49d49d51e12a4081128d27a5c1239ddf644c38826114c5610deb15ef',
      1 => 
      array (
        0 => 'phpunit\\event\\testrunner\\executionaborted',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\testrunner\\__construct',
        1 => 'phpunit\\event\\testrunner\\telemetryinfo',
        2 => 'phpunit\\event\\testrunner\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/TestRunner/ExecutionAbortedSubscriber.php' => 
    array (
      0 => 'd21df51f8e42a3699fa1202ef64042a81efd20c467c090bbf864c2b838380522',
      1 => 
      array (
        0 => 'phpunit\\event\\testrunner\\executionabortedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\testrunner\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/TestRunner/ExecutionFinished.php' => 
    array (
      0 => 'd24c1f1dbcf641ec01931b597ab2f620730637dbe6c47c84d962e983a19bd3b0',
      1 => 
      array (
        0 => 'phpunit\\event\\testrunner\\executionfinished',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\testrunner\\__construct',
        1 => 'phpunit\\event\\testrunner\\telemetryinfo',
        2 => 'phpunit\\event\\testrunner\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/TestRunner/ExecutionFinishedSubscriber.php' => 
    array (
      0 => 'f45932dce205e18198b7f447cbeaddef96420a831f536129fbbb76015298291d',
      1 => 
      array (
        0 => 'phpunit\\event\\testrunner\\executionfinishedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\testrunner\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/TestRunner/ExecutionStarted.php' => 
    array (
      0 => 'fda126d5152e6e81c80c26918ffcf91b66b6e6786231c0db4d98011ad5be21d8',
      1 => 
      array (
        0 => 'phpunit\\event\\testrunner\\executionstarted',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\testrunner\\__construct',
        1 => 'phpunit\\event\\testrunner\\telemetryinfo',
        2 => 'phpunit\\event\\testrunner\\testsuite',
        3 => 'phpunit\\event\\testrunner\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/TestRunner/ExecutionStartedSubscriber.php' => 
    array (
      0 => 'd838807e3c81c2605707259066db2ba32fe66e06183ec47a29c617815700943e',
      1 => 
      array (
        0 => 'phpunit\\event\\testrunner\\executionstartedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\testrunner\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/TestRunner/ExtensionBootstrapped.php' => 
    array (
      0 => '8e1c98d1b44d6a548be8301af22606130b34770778016f3bac5f66ff8c5c4892',
      1 => 
      array (
        0 => 'phpunit\\event\\testrunner\\extensionbootstrapped',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\testrunner\\__construct',
        1 => 'phpunit\\event\\testrunner\\telemetryinfo',
        2 => 'phpunit\\event\\testrunner\\classname',
        3 => 'phpunit\\event\\testrunner\\parameters',
        4 => 'phpunit\\event\\testrunner\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/TestRunner/ExtensionBootstrappedSubscriber.php' => 
    array (
      0 => '767a6cb92f3e169ac5b8dd5119eb79d9eb4be6dfd23e9340f8e301788278d8f2',
      1 => 
      array (
        0 => 'phpunit\\event\\testrunner\\extensionbootstrappedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\testrunner\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/TestRunner/ExtensionLoadedFromPhar.php' => 
    array (
      0 => '01aa5711471b1dadee857a218a9750ea6e39f3f82b60e1a02ec89f12ea645c11',
      1 => 
      array (
        0 => 'phpunit\\event\\testrunner\\extensionloadedfromphar',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\testrunner\\__construct',
        1 => 'phpunit\\event\\testrunner\\telemetryinfo',
        2 => 'phpunit\\event\\testrunner\\filename',
        3 => 'phpunit\\event\\testrunner\\name',
        4 => 'phpunit\\event\\testrunner\\version',
        5 => 'phpunit\\event\\testrunner\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/TestRunner/ExtensionLoadedFromPharSubscriber.php' => 
    array (
      0 => 'ea8b46da8e9f6449441a914db392d6a02e7c78a5fc0627339cb6977416dc3662',
      1 => 
      array (
        0 => 'phpunit\\event\\testrunner\\extensionloadedfrompharsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\testrunner\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/TestRunner/Finished.php' => 
    array (
      0 => 'cbe9e34eaac2d845e5e820ffbea78e17f7108808e60ae5809cefe3dce0fd525f',
      1 => 
      array (
        0 => 'phpunit\\event\\testrunner\\finished',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\testrunner\\__construct',
        1 => 'phpunit\\event\\testrunner\\telemetryinfo',
        2 => 'phpunit\\event\\testrunner\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/TestRunner/FinishedSubscriber.php' => 
    array (
      0 => '733d9e69bd6d5af87cefd67cb66f197b268577916fe1d3248b57e5baca74034a',
      1 => 
      array (
        0 => 'phpunit\\event\\testrunner\\finishedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\testrunner\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/TestRunner/GarbageCollectionDisabled.php' => 
    array (
      0 => '6a3c6bb769a949ee6aea7a6a1640c833b482cb1c18c9b4e7793e3aa5336bc2c9',
      1 => 
      array (
        0 => 'phpunit\\event\\testrunner\\garbagecollectiondisabled',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\testrunner\\__construct',
        1 => 'phpunit\\event\\testrunner\\telemetryinfo',
        2 => 'phpunit\\event\\testrunner\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/TestRunner/GarbageCollectionDisabledSubscriber.php' => 
    array (
      0 => 'f74a1b085e74883b6ec582ee29d03b9e1a7c2eae78c394f5c13d4f4cdfc888cd',
      1 => 
      array (
        0 => 'phpunit\\event\\testrunner\\garbagecollectiondisabledsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\testrunner\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/TestRunner/GarbageCollectionEnabled.php' => 
    array (
      0 => '93a0cbac072b492a69c8b6b053fa861438c9951f036f0fa86ddd8394a38553b9',
      1 => 
      array (
        0 => 'phpunit\\event\\testrunner\\garbagecollectionenabled',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\testrunner\\__construct',
        1 => 'phpunit\\event\\testrunner\\telemetryinfo',
        2 => 'phpunit\\event\\testrunner\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/TestRunner/GarbageCollectionEnabledSubscriber.php' => 
    array (
      0 => '7ffd4a2c92e09daa89aa40afa7ceb9ee799b0a9f49bbe92063a00411c834033a',
      1 => 
      array (
        0 => 'phpunit\\event\\testrunner\\garbagecollectionenabledsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\testrunner\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/TestRunner/GarbageCollectionTriggered.php' => 
    array (
      0 => '4314cb6e753e0e314dfca7365c9e264edd350f9fc79c35dd5c985663dc2b74c8',
      1 => 
      array (
        0 => 'phpunit\\event\\testrunner\\garbagecollectiontriggered',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\testrunner\\__construct',
        1 => 'phpunit\\event\\testrunner\\telemetryinfo',
        2 => 'phpunit\\event\\testrunner\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/TestRunner/GarbageCollectionTriggeredSubscriber.php' => 
    array (
      0 => '4f274d23b0b861fbb963a0ba180922379d017a6e35cb46a3f8b72097eacccb8b',
      1 => 
      array (
        0 => 'phpunit\\event\\testrunner\\garbagecollectiontriggeredsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\testrunner\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/TestRunner/Started.php' => 
    array (
      0 => '777ef985e2ac1b1000d518c917c1e028c8f695d09f0e1cd084e00aba3f90fe41',
      1 => 
      array (
        0 => 'phpunit\\event\\testrunner\\started',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\testrunner\\__construct',
        1 => 'phpunit\\event\\testrunner\\telemetryinfo',
        2 => 'phpunit\\event\\testrunner\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/TestRunner/StartedSubscriber.php' => 
    array (
      0 => '4073bce60536c3a82065fdd62f2d7735c12ff2ea8ded997d282d82206f22bf64',
      1 => 
      array (
        0 => 'phpunit\\event\\testrunner\\startedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\testrunner\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/TestRunner/WarningTriggered.php' => 
    array (
      0 => 'f6b32da2546f028fbde06cd5397ba2e64b4e902e9e5bd095be48ad0748aacbc1',
      1 => 
      array (
        0 => 'phpunit\\event\\testrunner\\warningtriggered',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\testrunner\\__construct',
        1 => 'phpunit\\event\\testrunner\\telemetryinfo',
        2 => 'phpunit\\event\\testrunner\\message',
        3 => 'phpunit\\event\\testrunner\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/TestRunner/WarningTriggeredSubscriber.php' => 
    array (
      0 => '3de368cb791aeff78ee1b393130171c470fc66ad04cdebaea36ca091e81fde39',
      1 => 
      array (
        0 => 'phpunit\\event\\testrunner\\warningtriggeredsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\testrunner\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/TestSuite/Filtered.php' => 
    array (
      0 => '238b099e050784c86d8c70f29b126a3e3f37c84fe180f9b787e1774fa3c77ced',
      1 => 
      array (
        0 => 'phpunit\\event\\testsuite\\filtered',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\testsuite\\__construct',
        1 => 'phpunit\\event\\testsuite\\telemetryinfo',
        2 => 'phpunit\\event\\testsuite\\testsuite',
        3 => 'phpunit\\event\\testsuite\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/TestSuite/FilteredSubscriber.php' => 
    array (
      0 => '1e8db7ffc46e563caba8f82da81f514a8110bde95ef4156eec3d19b70b29e071',
      1 => 
      array (
        0 => 'phpunit\\event\\testsuite\\filteredsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\testsuite\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/TestSuite/Finished.php' => 
    array (
      0 => 'fc27125a64b1544604dc64c4cf61270f95e0bd51141bf5a0e60d4ffb68ed3d56',
      1 => 
      array (
        0 => 'phpunit\\event\\testsuite\\finished',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\testsuite\\__construct',
        1 => 'phpunit\\event\\testsuite\\telemetryinfo',
        2 => 'phpunit\\event\\testsuite\\testsuite',
        3 => 'phpunit\\event\\testsuite\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/TestSuite/FinishedSubscriber.php' => 
    array (
      0 => '67931556bbaeb0481cda18705c07de7299bfb4e20f25869f3ffeb27b8568f382',
      1 => 
      array (
        0 => 'phpunit\\event\\testsuite\\finishedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\testsuite\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/TestSuite/Loaded.php' => 
    array (
      0 => '1727e85b5efa1135bf9241ff55325e353428fa6c685157c66c1999d412f0c5e1',
      1 => 
      array (
        0 => 'phpunit\\event\\testsuite\\loaded',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\testsuite\\__construct',
        1 => 'phpunit\\event\\testsuite\\telemetryinfo',
        2 => 'phpunit\\event\\testsuite\\testsuite',
        3 => 'phpunit\\event\\testsuite\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/TestSuite/LoadedSubscriber.php' => 
    array (
      0 => '72ef836f2428bc18a4b8bd62c06664a401c2c46e278d6bfa22adb830d273b30d',
      1 => 
      array (
        0 => 'phpunit\\event\\testsuite\\loadedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\testsuite\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/TestSuite/Skipped.php' => 
    array (
      0 => 'f3b89bcdfbe1a65fb802b030084d7394139ee7c4359902b1c4673406368dabee',
      1 => 
      array (
        0 => 'phpunit\\event\\testsuite\\skipped',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\testsuite\\__construct',
        1 => 'phpunit\\event\\testsuite\\telemetryinfo',
        2 => 'phpunit\\event\\testsuite\\testsuite',
        3 => 'phpunit\\event\\testsuite\\message',
        4 => 'phpunit\\event\\testsuite\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/TestSuite/SkippedSubscriber.php' => 
    array (
      0 => '98b03ac545f2a86882c6ddcf5eab910e58a97349d79c0f772f47c5718f2b3a36',
      1 => 
      array (
        0 => 'phpunit\\event\\testsuite\\skippedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\testsuite\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/TestSuite/Sorted.php' => 
    array (
      0 => '5d7b3262679a711a9e640149deeb0e9a7e82f3d38a515fa2b4a2e63edfea585a',
      1 => 
      array (
        0 => 'phpunit\\event\\testsuite\\sorted',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\testsuite\\__construct',
        1 => 'phpunit\\event\\testsuite\\telemetryinfo',
        2 => 'phpunit\\event\\testsuite\\executionorder',
        3 => 'phpunit\\event\\testsuite\\executionorderdefects',
        4 => 'phpunit\\event\\testsuite\\resolvedependencies',
        5 => 'phpunit\\event\\testsuite\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/TestSuite/SortedSubscriber.php' => 
    array (
      0 => '307febc70a58601a39ad91d3af540629bfd7686149b9caeb4763b26eb2c4948f',
      1 => 
      array (
        0 => 'phpunit\\event\\testsuite\\sortedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\testsuite\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/TestSuite/Started.php' => 
    array (
      0 => '7449df8c6eed384ba6fea5ba022c1568ce84d8b11f676bdff02f7b78d78a5483',
      1 => 
      array (
        0 => 'phpunit\\event\\testsuite\\started',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\testsuite\\__construct',
        1 => 'phpunit\\event\\testsuite\\telemetryinfo',
        2 => 'phpunit\\event\\testsuite\\testsuite',
        3 => 'phpunit\\event\\testsuite\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Events/TestSuite/StartedSubscriber.php' => 
    array (
      0 => '368a9ec72b24eecbdd418cf7498f4daada785967c0c3db4d2da1bcf30c4f35ca',
      1 => 
      array (
        0 => 'phpunit\\event\\testsuite\\startedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\testsuite\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Exception/EventAlreadyAssignedException.php' => 
    array (
      0 => 'a4660c5a73d683ce6b91ba9c2e123f0e917d80e30e2a438244e36dda95a7da74',
      1 => 
      array (
        0 => 'phpunit\\event\\eventalreadyassignedexception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Exception/EventFacadeIsSealedException.php' => 
    array (
      0 => 'c8aff9bbd8b86191a5a6896377b816515ba05a89100255d15fa673389c54513d',
      1 => 
      array (
        0 => 'phpunit\\event\\eventfacadeissealedexception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Exception/Exception.php' => 
    array (
      0 => '4b1a233d1ecaada5a3c422475eb01a18845ff15196e569da54bc587940d8dff2',
      1 => 
      array (
        0 => 'phpunit\\event\\exception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Exception/InvalidArgumentException.php' => 
    array (
      0 => 'cd804614c2d416fc7f8a28b632bc10b9d097bfdba9c5f4b64bc9a750f241e316',
      1 => 
      array (
        0 => 'phpunit\\event\\invalidargumentexception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Exception/InvalidEventException.php' => 
    array (
      0 => '9d61236e38812b9767aa60c1c52bd91b6e39f2c6e2fc216655f45ae8bb721181',
      1 => 
      array (
        0 => 'phpunit\\event\\invalideventexception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Exception/InvalidSubscriberException.php' => 
    array (
      0 => 'e7f87fe1360584e480774c2306dacc3755c0db87b931887c65b0f9587ab001d8',
      1 => 
      array (
        0 => 'phpunit\\event\\invalidsubscriberexception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Exception/MapError.php' => 
    array (
      0 => '80c9bc5fb03a3b2e5ca41017f95ba57d60b029868b504a170bb125129e85ffbe',
      1 => 
      array (
        0 => 'phpunit\\event\\maperror',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Exception/NoComparisonFailureException.php' => 
    array (
      0 => '3e2e2bd8b75d5ac0ea2061ed4d874ec0d66c031170aacc558514b5e37405ad62',
      1 => 
      array (
        0 => 'phpunit\\event\\test\\nocomparisonfailureexception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Exception/NoDataSetFromDataProviderException.php' => 
    array (
      0 => '307bc7caddb22c21460c8c85734cd1df964bd660967ae901a1dd2dc330a05fe1',
      1 => 
      array (
        0 => 'phpunit\\event\\testdata\\nodatasetfromdataproviderexception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Exception/NoPreviousThrowableException.php' => 
    array (
      0 => '695121b6b80d42ec50720dbc19cdcbbc0352a24d12ae091ccffe48680540751e',
      1 => 
      array (
        0 => 'phpunit\\event\\nopreviousthrowableexception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Exception/NoTestCaseObjectOnCallStackException.php' => 
    array (
      0 => 'fecf8df9b565ee65fa358d5b31dce63802e9b528476d85d8e82b3167b38cdc30',
      1 => 
      array (
        0 => 'phpunit\\event\\code\\notestcaseobjectoncallstackexception',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\code\\__construct',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Exception/RuntimeException.php' => 
    array (
      0 => '61bed66e827779b1d9b87d3adcfb6641b63fcec13a37537b08e35a912adea768',
      1 => 
      array (
        0 => 'phpunit\\event\\runtimeexception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Exception/SubscriberTypeAlreadyRegisteredException.php' => 
    array (
      0 => '213dfa0297bbaf8f948352c86e39b548ade770183ad4c4d40ca74646c5d2d8a8',
      1 => 
      array (
        0 => 'phpunit\\event\\subscribertypealreadyregisteredexception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Exception/UnknownEventException.php' => 
    array (
      0 => 'bec454a2d7a4fd9b4ed95906ec479dc145127db6db18b388f3e2394417215c52',
      1 => 
      array (
        0 => 'phpunit\\event\\unknowneventexception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Exception/UnknownEventTypeException.php' => 
    array (
      0 => '81117d8e9bbab854604d1a58cd4e6dff28c63c327cad68afd08f3b4cfa4b1a26',
      1 => 
      array (
        0 => 'phpunit\\event\\unknowneventtypeexception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Exception/UnknownSubscriberException.php' => 
    array (
      0 => '8ef3c8e88a77645275309b09b8bef14f916601fa2f10a30c196588cb477f860d',
      1 => 
      array (
        0 => 'phpunit\\event\\unknownsubscriberexception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Exception/UnknownSubscriberTypeException.php' => 
    array (
      0 => '6228ed062396c5837b8f01855c9f31fe1f0b8ae3385029d631b5d884980ea911',
      1 => 
      array (
        0 => 'phpunit\\event\\unknownsubscribertypeexception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Facade.php' => 
    array (
      0 => '7535a24621c1be3612826e19273a1bdf24603c5157df8ec4e37bdaf83b0cae32',
      1 => 
      array (
        0 => 'phpunit\\event\\facade',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\instance',
        1 => 'phpunit\\event\\emitter',
        2 => 'phpunit\\event\\__construct',
        3 => 'phpunit\\event\\registersubscribers',
        4 => 'phpunit\\event\\registersubscriber',
        5 => 'phpunit\\event\\registertracer',
        6 => 'phpunit\\event\\initforisolation',
        7 => 'phpunit\\event\\forward',
        8 => 'phpunit\\event\\seal',
        9 => 'phpunit\\event\\createdispatchingemitter',
        10 => 'phpunit\\event\\createtelemetrysystem',
        11 => 'phpunit\\event\\deferreddispatcher',
        12 => 'phpunit\\event\\typemap',
        13 => 'phpunit\\event\\registerdefaulttypes',
        14 => 'phpunit\\event\\garbagecollectorstatusprovider',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Subscriber.php' => 
    array (
      0 => '27bd32ca16646631410a83d8f3225580fe58b10e2fd372d44d68fb9813ba7144',
      1 => 
      array (
        0 => 'phpunit\\event\\subscriber',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Tracer.php' => 
    array (
      0 => '569f2dedf545e2a5094a789c41408a34b8dec0091717b496d648ed1b354571c4',
      1 => 
      array (
        0 => 'phpunit\\event\\tracer\\tracer',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\tracer\\trace',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/TypeMap.php' => 
    array (
      0 => '3f7f4a639c25094928e00aa918368513c6fe1cfab1ce10cf629fc61302bb0c2c',
      1 => 
      array (
        0 => 'phpunit\\event\\typemap',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\addmapping',
        1 => 'phpunit\\event\\isknownsubscribertype',
        2 => 'phpunit\\event\\isknowneventtype',
        3 => 'phpunit\\event\\map',
        4 => 'phpunit\\event\\ensuresubscriberinterfaceexists',
        5 => 'phpunit\\event\\ensureeventclassexists',
        6 => 'phpunit\\event\\ensuresubscriberinterfaceextendsinterface',
        7 => 'phpunit\\event\\ensureeventclassimplementseventinterface',
        8 => 'phpunit\\event\\ensuresubscriberwasnotalreadyregistered',
        9 => 'phpunit\\event\\ensureeventwasnotalreadyassigned',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Value/ClassMethod.php' => 
    array (
      0 => '760141358cd8e86dfe0067beb52cf553dd6acdbde4164dacee5f01aaedd44607',
      1 => 
      array (
        0 => 'phpunit\\event\\code\\classmethod',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\code\\__construct',
        1 => 'phpunit\\event\\code\\classname',
        2 => 'phpunit\\event\\code\\methodname',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Value/ComparisonFailure.php' => 
    array (
      0 => '2cb4a3920cf273594ce452adc33d98295b2c1a1c5ea0b98ef078e44c17e4389e',
      1 => 
      array (
        0 => 'phpunit\\event\\code\\comparisonfailure',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\code\\__construct',
        1 => 'phpunit\\event\\code\\expected',
        2 => 'phpunit\\event\\code\\actual',
        3 => 'phpunit\\event\\code\\diff',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Value/ComparisonFailureBuilder.php' => 
    array (
      0 => '188016d43cee770b0aad20d1da4f2a5146e9d2449022b45bf032cda263f0de9e',
      1 => 
      array (
        0 => 'phpunit\\event\\code\\comparisonfailurebuilder',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\code\\from',
        1 => 'phpunit\\event\\code\\mapscalarvaluetostring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Value/Runtime/OperatingSystem.php' => 
    array (
      0 => '2cbfb667d64a5d36b706dad8867c3ae79cb078b38c24cd106601a84586ad25e9',
      1 => 
      array (
        0 => 'phpunit\\event\\runtime\\operatingsystem',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\runtime\\__construct',
        1 => 'phpunit\\event\\runtime\\operatingsystem',
        2 => 'phpunit\\event\\runtime\\operatingsystemfamily',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Value/Runtime/PHP.php' => 
    array (
      0 => 'cad99acb266e9b007cfbc0c589ff56d298c8a00bc692c0f458de784f9f44f8fd',
      1 => 
      array (
        0 => 'phpunit\\event\\runtime\\php',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\runtime\\__construct',
        1 => 'phpunit\\event\\runtime\\version',
        2 => 'phpunit\\event\\runtime\\sapi',
        3 => 'phpunit\\event\\runtime\\majorversion',
        4 => 'phpunit\\event\\runtime\\minorversion',
        5 => 'phpunit\\event\\runtime\\releaseversion',
        6 => 'phpunit\\event\\runtime\\extraversion',
        7 => 'phpunit\\event\\runtime\\versionid',
        8 => 'phpunit\\event\\runtime\\extensions',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Value/Runtime/PHPUnit.php' => 
    array (
      0 => 'bb8b1e12516a70fe8c366316dfb79286980ba41dd3ed785d46aa03c4fe38070d',
      1 => 
      array (
        0 => 'phpunit\\event\\runtime\\phpunit',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\runtime\\__construct',
        1 => 'phpunit\\event\\runtime\\versionid',
        2 => 'phpunit\\event\\runtime\\releaseseries',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Value/Runtime/Runtime.php' => 
    array (
      0 => 'd1690811f71a0d680bd3d569e22b5db38a62e22747f73fde1b3d9929e34f91ac',
      1 => 
      array (
        0 => 'phpunit\\event\\runtime\\runtime',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\runtime\\__construct',
        1 => 'phpunit\\event\\runtime\\asstring',
        2 => 'phpunit\\event\\runtime\\operatingsystem',
        3 => 'phpunit\\event\\runtime\\php',
        4 => 'phpunit\\event\\runtime\\phpunit',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Value/Telemetry/Duration.php' => 
    array (
      0 => '789672baee1b98340daf3ea1364733db2bd880478d9b22421cb7f5b5216bf5de',
      1 => 
      array (
        0 => 'phpunit\\event\\telemetry\\duration',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\telemetry\\fromsecondsandnanoseconds',
        1 => 'phpunit\\event\\telemetry\\__construct',
        2 => 'phpunit\\event\\telemetry\\seconds',
        3 => 'phpunit\\event\\telemetry\\nanoseconds',
        4 => 'phpunit\\event\\telemetry\\asfloat',
        5 => 'phpunit\\event\\telemetry\\asstring',
        6 => 'phpunit\\event\\telemetry\\equals',
        7 => 'phpunit\\event\\telemetry\\islessthan',
        8 => 'phpunit\\event\\telemetry\\isgreaterthan',
        9 => 'phpunit\\event\\telemetry\\ensurenotnegative',
        10 => 'phpunit\\event\\telemetry\\ensurenanosecondsinrange',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Value/Telemetry/GarbageCollectorStatus.php' => 
    array (
      0 => 'a726ac9111271b0ac0332c54641c1f0f0d0b366e3c3baaefadc0c6f71f0595f6',
      1 => 
      array (
        0 => 'phpunit\\event\\telemetry\\garbagecollectorstatus',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\telemetry\\__construct',
        1 => 'phpunit\\event\\telemetry\\runs',
        2 => 'phpunit\\event\\telemetry\\collected',
        3 => 'phpunit\\event\\telemetry\\threshold',
        4 => 'phpunit\\event\\telemetry\\roots',
        5 => 'phpunit\\event\\telemetry\\hasextendedinformation',
        6 => 'phpunit\\event\\telemetry\\applicationtime',
        7 => 'phpunit\\event\\telemetry\\collectortime',
        8 => 'phpunit\\event\\telemetry\\destructortime',
        9 => 'phpunit\\event\\telemetry\\freetime',
        10 => 'phpunit\\event\\telemetry\\isrunning',
        11 => 'phpunit\\event\\telemetry\\isprotected',
        12 => 'phpunit\\event\\telemetry\\isfull',
        13 => 'phpunit\\event\\telemetry\\buffersize',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Value/Telemetry/GarbageCollectorStatusProvider.php' => 
    array (
      0 => 'b2bf26385d698e3d6a730aea41c03beb176c0228930dd6b05a0a4d1a5d90e6ba',
      1 => 
      array (
        0 => 'phpunit\\event\\telemetry\\garbagecollectorstatusprovider',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\telemetry\\status',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Value/Telemetry/HRTime.php' => 
    array (
      0 => '7dd437ee2c315532f7febd9d433b5f09775ffdda2e0435d4b068257fa7f701cd',
      1 => 
      array (
        0 => 'phpunit\\event\\telemetry\\hrtime',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\telemetry\\fromsecondsandnanoseconds',
        1 => 'phpunit\\event\\telemetry\\__construct',
        2 => 'phpunit\\event\\telemetry\\seconds',
        3 => 'phpunit\\event\\telemetry\\nanoseconds',
        4 => 'phpunit\\event\\telemetry\\duration',
        5 => 'phpunit\\event\\telemetry\\ensurenotnegative',
        6 => 'phpunit\\event\\telemetry\\ensurenanosecondsinrange',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Value/Telemetry/Info.php' => 
    array (
      0 => 'f7f1049b4d96eb2df785746652d32be945d3603e4997d1cc679381877e39db39',
      1 => 
      array (
        0 => 'phpunit\\event\\telemetry\\info',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\telemetry\\__construct',
        1 => 'phpunit\\event\\telemetry\\time',
        2 => 'phpunit\\event\\telemetry\\memoryusage',
        3 => 'phpunit\\event\\telemetry\\peakmemoryusage',
        4 => 'phpunit\\event\\telemetry\\durationsincestart',
        5 => 'phpunit\\event\\telemetry\\memoryusagesincestart',
        6 => 'phpunit\\event\\telemetry\\durationsinceprevious',
        7 => 'phpunit\\event\\telemetry\\memoryusagesinceprevious',
        8 => 'phpunit\\event\\telemetry\\garbagecollectorstatus',
        9 => 'phpunit\\event\\telemetry\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Value/Telemetry/MemoryMeter.php' => 
    array (
      0 => 'b234466d1bc44999bc42255f746143aff13f1aaf1af4879e1ab6253b67287a67',
      1 => 
      array (
        0 => 'phpunit\\event\\telemetry\\memorymeter',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\telemetry\\memoryusage',
        1 => 'phpunit\\event\\telemetry\\peakmemoryusage',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Value/Telemetry/MemoryUsage.php' => 
    array (
      0 => '20610faa9912581e70bb394b4df660fb1314331fed16e3db6681f463c943956a',
      1 => 
      array (
        0 => 'phpunit\\event\\telemetry\\memoryusage',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\telemetry\\frombytes',
        1 => 'phpunit\\event\\telemetry\\__construct',
        2 => 'phpunit\\event\\telemetry\\bytes',
        3 => 'phpunit\\event\\telemetry\\diff',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Value/Telemetry/Php81GarbageCollectorStatusProvider.php' => 
    array (
      0 => '4da38f1a597b154ff98a96bb2b0d802ae0294b6ac5342666c5ea885267cc87aa',
      1 => 
      array (
        0 => 'phpunit\\event\\telemetry\\php81garbagecollectorstatusprovider',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\telemetry\\status',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Value/Telemetry/Php83GarbageCollectorStatusProvider.php' => 
    array (
      0 => '0f1bebd542c81a3e0717553958e12a00addb2619067d251aaa6aba186ee0dbcb',
      1 => 
      array (
        0 => 'phpunit\\event\\telemetry\\php83garbagecollectorstatusprovider',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\telemetry\\status',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Value/Telemetry/Snapshot.php' => 
    array (
      0 => '07e1786fae576cedacd818645ea1267e8de5bc98654889d2e9c420d663c43825',
      1 => 
      array (
        0 => 'phpunit\\event\\telemetry\\snapshot',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\telemetry\\__construct',
        1 => 'phpunit\\event\\telemetry\\time',
        2 => 'phpunit\\event\\telemetry\\memoryusage',
        3 => 'phpunit\\event\\telemetry\\peakmemoryusage',
        4 => 'phpunit\\event\\telemetry\\garbagecollectorstatus',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Value/Telemetry/StopWatch.php' => 
    array (
      0 => '1286366de654358219c0180a1640b6dec73e23a448ebc99407405d969d0472ba',
      1 => 
      array (
        0 => 'phpunit\\event\\telemetry\\stopwatch',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\telemetry\\current',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Value/Telemetry/System.php' => 
    array (
      0 => 'd114b0cbeea66aa9f7462bd119c3669ff6036371ade67eb051f7577ad2e39bb9',
      1 => 
      array (
        0 => 'phpunit\\event\\telemetry\\system',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\telemetry\\__construct',
        1 => 'phpunit\\event\\telemetry\\snapshot',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Value/Telemetry/SystemMemoryMeter.php' => 
    array (
      0 => '0bb86c0c0fff1e239fabe3b79d343ce4f41466f10497019e7b5f8c9258c05583',
      1 => 
      array (
        0 => 'phpunit\\event\\telemetry\\systemmemorymeter',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\telemetry\\memoryusage',
        1 => 'phpunit\\event\\telemetry\\peakmemoryusage',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Value/Telemetry/SystemStopWatch.php' => 
    array (
      0 => '4a79f4f12277bc817961611490a9d7f88cd5d02185de71059e5335b0e31c4264',
      1 => 
      array (
        0 => 'phpunit\\event\\telemetry\\systemstopwatch',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\telemetry\\current',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Value/Telemetry/SystemStopWatchWithOffset.php' => 
    array (
      0 => 'b22fb2fc5b834fa4c2041c7ba7a5c172d3d885cf0cab9ea810e7676a4baf7bdc',
      1 => 
      array (
        0 => 'phpunit\\event\\telemetry\\systemstopwatchwithoffset',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\telemetry\\__construct',
        1 => 'phpunit\\event\\telemetry\\current',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Value/Test/Issue/DirectTrigger.php' => 
    array (
      0 => '902996689b9a59c0ae23ce3b2fe346f29d7bf511c87f2358a9e76aa20bc77ddc',
      1 => 
      array (
        0 => 'phpunit\\event\\code\\issuetrigger\\directtrigger',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\code\\issuetrigger\\isdirect',
        1 => 'phpunit\\event\\code\\issuetrigger\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Value/Test/Issue/IndirectTrigger.php' => 
    array (
      0 => 'd7086fc610e54eb25cffd128e75cbbb0cb6ea38037dd969b1b5b2cfc9e18bd78',
      1 => 
      array (
        0 => 'phpunit\\event\\code\\issuetrigger\\indirecttrigger',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\code\\issuetrigger\\isindirect',
        1 => 'phpunit\\event\\code\\issuetrigger\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Value/Test/Issue/IssueTrigger.php' => 
    array (
      0 => 'f2f54f2c9de9a6333aea1e938d48af561b275f55a6156922217280fb106bbf62',
      1 => 
      array (
        0 => 'phpunit\\event\\code\\issuetrigger\\issuetrigger',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\code\\issuetrigger\\test',
        1 => 'phpunit\\event\\code\\issuetrigger\\self',
        2 => 'phpunit\\event\\code\\issuetrigger\\direct',
        3 => 'phpunit\\event\\code\\issuetrigger\\indirect',
        4 => 'phpunit\\event\\code\\issuetrigger\\unknown',
        5 => 'phpunit\\event\\code\\issuetrigger\\__construct',
        6 => 'phpunit\\event\\code\\issuetrigger\\istest',
        7 => 'phpunit\\event\\code\\issuetrigger\\isself',
        8 => 'phpunit\\event\\code\\issuetrigger\\isdirect',
        9 => 'phpunit\\event\\code\\issuetrigger\\isindirect',
        10 => 'phpunit\\event\\code\\issuetrigger\\isunknown',
        11 => 'phpunit\\event\\code\\issuetrigger\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Value/Test/Issue/SelfTrigger.php' => 
    array (
      0 => 'ecf8f5b648a6ee985e0b56a1fc99ab1df3c0bee2ec8ef0328f8fcb01a2630d01',
      1 => 
      array (
        0 => 'phpunit\\event\\code\\issuetrigger\\selftrigger',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\code\\issuetrigger\\isself',
        1 => 'phpunit\\event\\code\\issuetrigger\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Value/Test/Issue/TestTrigger.php' => 
    array (
      0 => '8dff172637320bc8a89dce686d1f7e21163c949ac3cc5012e3b19735f3ef2183',
      1 => 
      array (
        0 => 'phpunit\\event\\code\\issuetrigger\\testtrigger',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\code\\issuetrigger\\istest',
        1 => 'phpunit\\event\\code\\issuetrigger\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Value/Test/Issue/UnknownTrigger.php' => 
    array (
      0 => 'eee4483e3a049dc0f9fe2210949c0fb71b72e19902ac4903d3efbe6ac9a0fc35',
      1 => 
      array (
        0 => 'phpunit\\event\\code\\issuetrigger\\unknowntrigger',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\code\\issuetrigger\\isunknown',
        1 => 'phpunit\\event\\code\\issuetrigger\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Value/Test/Phpt.php' => 
    array (
      0 => 'bc2d2785faa56321128e1b0615f2b95e3e9d77ce80f50d0a4580507bb3ad7c81',
      1 => 
      array (
        0 => 'phpunit\\event\\code\\phpt',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\code\\isphpt',
        1 => 'phpunit\\event\\code\\id',
        2 => 'phpunit\\event\\code\\name',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Value/Test/Test.php' => 
    array (
      0 => 'a5766418a421098460d58a485cbe06b77b14cd41622cc0d7267e51b4cd842111',
      1 => 
      array (
        0 => 'phpunit\\event\\code\\test',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\code\\__construct',
        1 => 'phpunit\\event\\code\\file',
        2 => 'phpunit\\event\\code\\istestmethod',
        3 => 'phpunit\\event\\code\\isphpt',
        4 => 'phpunit\\event\\code\\id',
        5 => 'phpunit\\event\\code\\name',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Value/Test/TestCollection.php' => 
    array (
      0 => '1ec76861ee1038c698c6f7c7aab5400ae6c0a3c6a025dd4b1b54911008446d95',
      1 => 
      array (
        0 => 'phpunit\\event\\code\\testcollection',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\code\\fromarray',
        1 => 'phpunit\\event\\code\\__construct',
        2 => 'phpunit\\event\\code\\asarray',
        3 => 'phpunit\\event\\code\\count',
        4 => 'phpunit\\event\\code\\getiterator',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Value/Test/TestCollectionIterator.php' => 
    array (
      0 => '9ec05d277fddfabe7c0a2272d25876d3453c9a348e4026e9594076ff3c78c216',
      1 => 
      array (
        0 => 'phpunit\\event\\code\\testcollectioniterator',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\code\\__construct',
        1 => 'phpunit\\event\\code\\rewind',
        2 => 'phpunit\\event\\code\\valid',
        3 => 'phpunit\\event\\code\\key',
        4 => 'phpunit\\event\\code\\current',
        5 => 'phpunit\\event\\code\\next',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Value/Test/TestData/DataFromDataProvider.php' => 
    array (
      0 => '18b60209797cfc8911c6474cf6b569d842b514ed64895a820e988ef0aa687a59',
      1 => 
      array (
        0 => 'phpunit\\event\\testdata\\datafromdataprovider',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\testdata\\from',
        1 => 'phpunit\\event\\testdata\\__construct',
        2 => 'phpunit\\event\\testdata\\datasetname',
        3 => 'phpunit\\event\\testdata\\dataasstringforresultoutput',
        4 => 'phpunit\\event\\testdata\\isfromdataprovider',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Value/Test/TestData/DataFromTestDependency.php' => 
    array (
      0 => '91691dc6221f4efca0f47125a6aeb62532ba5197d13c5f525a341eb5f4c0c225',
      1 => 
      array (
        0 => 'phpunit\\event\\testdata\\datafromtestdependency',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\testdata\\from',
        1 => 'phpunit\\event\\testdata\\isfromtestdependency',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Value/Test/TestData/TestData.php' => 
    array (
      0 => '4638e3ace52884f89ef2b42326d2297cf2d782c1f7063273f14a95f70c3f7c9b',
      1 => 
      array (
        0 => 'phpunit\\event\\testdata\\testdata',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\testdata\\__construct',
        1 => 'phpunit\\event\\testdata\\data',
        2 => 'phpunit\\event\\testdata\\isfromdataprovider',
        3 => 'phpunit\\event\\testdata\\isfromtestdependency',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Value/Test/TestData/TestDataCollection.php' => 
    array (
      0 => '650774d7403353c1914f958424b289be4e33153f436dbc812f7e576eedac9779',
      1 => 
      array (
        0 => 'phpunit\\event\\testdata\\testdatacollection',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\testdata\\fromarray',
        1 => 'phpunit\\event\\testdata\\__construct',
        2 => 'phpunit\\event\\testdata\\asarray',
        3 => 'phpunit\\event\\testdata\\count',
        4 => 'phpunit\\event\\testdata\\hasdatafromdataprovider',
        5 => 'phpunit\\event\\testdata\\datafromdataprovider',
        6 => 'phpunit\\event\\testdata\\getiterator',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Value/Test/TestData/TestDataCollectionIterator.php' => 
    array (
      0 => 'cc65c92e1e8841507c13fb60908a4f36d3a643e1e1094b5dfc571295396ea339',
      1 => 
      array (
        0 => 'phpunit\\event\\testdata\\testdatacollectioniterator',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\testdata\\__construct',
        1 => 'phpunit\\event\\testdata\\rewind',
        2 => 'phpunit\\event\\testdata\\valid',
        3 => 'phpunit\\event\\testdata\\key',
        4 => 'phpunit\\event\\testdata\\current',
        5 => 'phpunit\\event\\testdata\\next',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Value/Test/TestDox.php' => 
    array (
      0 => '9c90324d04a7b2f2070805122e2dadd4846e5c80a0e0e19ad2b8edc3d97a5386',
      1 => 
      array (
        0 => 'phpunit\\event\\code\\testdox',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\code\\__construct',
        1 => 'phpunit\\event\\code\\prettifiedclassname',
        2 => 'phpunit\\event\\code\\prettifiedmethodname',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Value/Test/TestDoxBuilder.php' => 
    array (
      0 => '8e2a7bd546396de14161ea77301867275882a044d6a9258ac9e8b8f85d8d9dc8',
      1 => 
      array (
        0 => 'phpunit\\event\\code\\testdoxbuilder',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\code\\fromtestcase',
        1 => 'phpunit\\event\\code\\fromclassnameandmethodname',
        2 => 'phpunit\\event\\code\\nameprettifier',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Value/Test/TestMethod.php' => 
    array (
      0 => '5ae1c20ea96844fc9e0cdedd5df22787da3a343e7053125fd01cc6b883921c6d',
      1 => 
      array (
        0 => 'phpunit\\event\\code\\testmethod',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\code\\__construct',
        1 => 'phpunit\\event\\code\\classname',
        2 => 'phpunit\\event\\code\\methodname',
        3 => 'phpunit\\event\\code\\line',
        4 => 'phpunit\\event\\code\\testdox',
        5 => 'phpunit\\event\\code\\metadata',
        6 => 'phpunit\\event\\code\\testdata',
        7 => 'phpunit\\event\\code\\istestmethod',
        8 => 'phpunit\\event\\code\\id',
        9 => 'phpunit\\event\\code\\namewithclass',
        10 => 'phpunit\\event\\code\\name',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Value/Test/TestMethodBuilder.php' => 
    array (
      0 => '62578077375170295116e78789aa03533008f5da75412aecee482e4fa9b6bb62',
      1 => 
      array (
        0 => 'phpunit\\event\\code\\testmethodbuilder',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\code\\fromtestcase',
        1 => 'phpunit\\event\\code\\fromcallstack',
        2 => 'phpunit\\event\\code\\datafor',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Value/TestSuite/TestSuite.php' => 
    array (
      0 => '205991f47ade841f8884e7de268aa74f068728f0233b41955b93d179d55571a0',
      1 => 
      array (
        0 => 'phpunit\\event\\testsuite\\testsuite',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\testsuite\\__construct',
        1 => 'phpunit\\event\\testsuite\\name',
        2 => 'phpunit\\event\\testsuite\\count',
        3 => 'phpunit\\event\\testsuite\\tests',
        4 => 'phpunit\\event\\testsuite\\iswithname',
        5 => 'phpunit\\event\\testsuite\\isfortestclass',
        6 => 'phpunit\\event\\testsuite\\isfortestmethodwithdataprovider',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Value/TestSuite/TestSuiteBuilder.php' => 
    array (
      0 => '0dfb601e407778e178b06af4625e4685d65acd1312e48e5584e5fb5fb1b8090a',
      1 => 
      array (
        0 => 'phpunit\\event\\testsuite\\testsuitebuilder',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\testsuite\\from',
        1 => 'phpunit\\event\\testsuite\\process',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Value/TestSuite/TestSuiteForTestClass.php' => 
    array (
      0 => 'e56980501ad08e0f6a3f46c38571a58c8553a06d65b854d5bdb8fce07496cc06',
      1 => 
      array (
        0 => 'phpunit\\event\\testsuite\\testsuitefortestclass',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\testsuite\\__construct',
        1 => 'phpunit\\event\\testsuite\\classname',
        2 => 'phpunit\\event\\testsuite\\file',
        3 => 'phpunit\\event\\testsuite\\line',
        4 => 'phpunit\\event\\testsuite\\isfortestclass',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Value/TestSuite/TestSuiteForTestMethodWithDataProvider.php' => 
    array (
      0 => 'e983f7e3a3f7dc8b5ec9d69432eee49d653baaac855d4bb91090c1d9d52809e8',
      1 => 
      array (
        0 => 'phpunit\\event\\testsuite\\testsuitefortestmethodwithdataprovider',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\testsuite\\__construct',
        1 => 'phpunit\\event\\testsuite\\classname',
        2 => 'phpunit\\event\\testsuite\\methodname',
        3 => 'phpunit\\event\\testsuite\\file',
        4 => 'phpunit\\event\\testsuite\\line',
        5 => 'phpunit\\event\\testsuite\\isfortestmethodwithdataprovider',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Value/TestSuite/TestSuiteWithName.php' => 
    array (
      0 => '9791c761f7834636603b36eaf1238fb22cb99816935649bfce2078709ab0fa55',
      1 => 
      array (
        0 => 'phpunit\\event\\testsuite\\testsuitewithname',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\testsuite\\iswithname',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Value/Throwable.php' => 
    array (
      0 => 'bf757d1d003c4f552065f9683dd1932f037958d318c563013d0edc99658eac5b',
      1 => 
      array (
        0 => 'phpunit\\event\\code\\throwable',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\code\\__construct',
        1 => 'phpunit\\event\\code\\asstring',
        2 => 'phpunit\\event\\code\\classname',
        3 => 'phpunit\\event\\code\\message',
        4 => 'phpunit\\event\\code\\description',
        5 => 'phpunit\\event\\code\\stacktrace',
        6 => 'phpunit\\event\\code\\hasprevious',
        7 => 'phpunit\\event\\code\\previous',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Event/Value/ThrowableBuilder.php' => 
    array (
      0 => '8cfcb4999af79463eca51a42058e502ea4ddc776cba5677bf2f8eb6093e21a5c',
      1 => 
      array (
        0 => 'phpunit\\event\\code\\throwablebuilder',
      ),
      2 => 
      array (
        0 => 'phpunit\\event\\code\\from',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Exception.php' => 
    array (
      0 => '76ce7aeda0d5e0da1c89e19df28597084cdb201fd0e25207138ce89b50472d76',
      1 => 
      array (
        0 => 'phpunit\\exception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Assert.php' => 
    array (
      0 => 'f7de0fec7c02503304bc2da6fbbd788039f77b65a8a523377a000346b2a31839',
      1 => 
      array (
        0 => 'phpunit\\framework\\assert',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\assertarrayisequaltoarrayonlyconsideringlistofkeys',
        1 => 'phpunit\\framework\\assertarrayisequaltoarrayignoringlistofkeys',
        2 => 'phpunit\\framework\\assertarrayisidenticaltoarrayonlyconsideringlistofkeys',
        3 => 'phpunit\\framework\\assertarrayisidenticaltoarrayignoringlistofkeys',
        4 => 'phpunit\\framework\\assertarrayhaskey',
        5 => 'phpunit\\framework\\assertarraynothaskey',
        6 => 'phpunit\\framework\\assertislist',
        7 => 'phpunit\\framework\\assertcontains',
        8 => 'phpunit\\framework\\assertcontainsequals',
        9 => 'phpunit\\framework\\assertnotcontains',
        10 => 'phpunit\\framework\\assertnotcontainsequals',
        11 => 'phpunit\\framework\\assertcontainsonly',
        12 => 'phpunit\\framework\\assertcontainsonlyarray',
        13 => 'phpunit\\framework\\assertcontainsonlybool',
        14 => 'phpunit\\framework\\assertcontainsonlycallable',
        15 => 'phpunit\\framework\\assertcontainsonlyfloat',
        16 => 'phpunit\\framework\\assertcontainsonlyint',
        17 => 'phpunit\\framework\\assertcontainsonlyiterable',
        18 => 'phpunit\\framework\\assertcontainsonlynull',
        19 => 'phpunit\\framework\\assertcontainsonlynumeric',
        20 => 'phpunit\\framework\\assertcontainsonlyobject',
        21 => 'phpunit\\framework\\assertcontainsonlyresource',
        22 => 'phpunit\\framework\\assertcontainsonlyclosedresource',
        23 => 'phpunit\\framework\\assertcontainsonlyscalar',
        24 => 'phpunit\\framework\\assertcontainsonlystring',
        25 => 'phpunit\\framework\\assertcontainsonlyinstancesof',
        26 => 'phpunit\\framework\\assertnotcontainsonly',
        27 => 'phpunit\\framework\\assertcontainsnotonlyarray',
        28 => 'phpunit\\framework\\assertcontainsnotonlybool',
        29 => 'phpunit\\framework\\assertcontainsnotonlycallable',
        30 => 'phpunit\\framework\\assertcontainsnotonlyfloat',
        31 => 'phpunit\\framework\\assertcontainsnotonlyint',
        32 => 'phpunit\\framework\\assertcontainsnotonlyiterable',
        33 => 'phpunit\\framework\\assertcontainsnotonlynull',
        34 => 'phpunit\\framework\\assertcontainsnotonlynumeric',
        35 => 'phpunit\\framework\\assertcontainsnotonlyobject',
        36 => 'phpunit\\framework\\assertcontainsnotonlyresource',
        37 => 'phpunit\\framework\\assertcontainsnotonlyclosedresource',
        38 => 'phpunit\\framework\\assertcontainsnotonlyscalar',
        39 => 'phpunit\\framework\\assertcontainsnotonlystring',
        40 => 'phpunit\\framework\\assertcontainsnotonlyinstancesof',
        41 => 'phpunit\\framework\\assertcount',
        42 => 'phpunit\\framework\\assertnotcount',
        43 => 'phpunit\\framework\\assertequals',
        44 => 'phpunit\\framework\\assertequalscanonicalizing',
        45 => 'phpunit\\framework\\assertequalsignoringcase',
        46 => 'phpunit\\framework\\assertequalswithdelta',
        47 => 'phpunit\\framework\\assertnotequals',
        48 => 'phpunit\\framework\\assertnotequalscanonicalizing',
        49 => 'phpunit\\framework\\assertnotequalsignoringcase',
        50 => 'phpunit\\framework\\assertnotequalswithdelta',
        51 => 'phpunit\\framework\\assertobjectequals',
        52 => 'phpunit\\framework\\assertobjectnotequals',
        53 => 'phpunit\\framework\\assertempty',
        54 => 'phpunit\\framework\\assertnotempty',
        55 => 'phpunit\\framework\\assertgreaterthan',
        56 => 'phpunit\\framework\\assertgreaterthanorequal',
        57 => 'phpunit\\framework\\assertlessthan',
        58 => 'phpunit\\framework\\assertlessthanorequal',
        59 => 'phpunit\\framework\\assertfileequals',
        60 => 'phpunit\\framework\\assertfileequalscanonicalizing',
        61 => 'phpunit\\framework\\assertfileequalsignoringcase',
        62 => 'phpunit\\framework\\assertfilenotequals',
        63 => 'phpunit\\framework\\assertfilenotequalscanonicalizing',
        64 => 'phpunit\\framework\\assertfilenotequalsignoringcase',
        65 => 'phpunit\\framework\\assertstringequalsfile',
        66 => 'phpunit\\framework\\assertstringequalsfilecanonicalizing',
        67 => 'phpunit\\framework\\assertstringequalsfileignoringcase',
        68 => 'phpunit\\framework\\assertstringnotequalsfile',
        69 => 'phpunit\\framework\\assertstringnotequalsfilecanonicalizing',
        70 => 'phpunit\\framework\\assertstringnotequalsfileignoringcase',
        71 => 'phpunit\\framework\\assertisreadable',
        72 => 'phpunit\\framework\\assertisnotreadable',
        73 => 'phpunit\\framework\\assertiswritable',
        74 => 'phpunit\\framework\\assertisnotwritable',
        75 => 'phpunit\\framework\\assertdirectoryexists',
        76 => 'phpunit\\framework\\assertdirectorydoesnotexist',
        77 => 'phpunit\\framework\\assertdirectoryisreadable',
        78 => 'phpunit\\framework\\assertdirectoryisnotreadable',
        79 => 'phpunit\\framework\\assertdirectoryiswritable',
        80 => 'phpunit\\framework\\assertdirectoryisnotwritable',
        81 => 'phpunit\\framework\\assertfileexists',
        82 => 'phpunit\\framework\\assertfiledoesnotexist',
        83 => 'phpunit\\framework\\assertfileisreadable',
        84 => 'phpunit\\framework\\assertfileisnotreadable',
        85 => 'phpunit\\framework\\assertfileiswritable',
        86 => 'phpunit\\framework\\assertfileisnotwritable',
        87 => 'phpunit\\framework\\asserttrue',
        88 => 'phpunit\\framework\\assertnottrue',
        89 => 'phpunit\\framework\\assertfalse',
        90 => 'phpunit\\framework\\assertnotfalse',
        91 => 'phpunit\\framework\\assertnull',
        92 => 'phpunit\\framework\\assertnotnull',
        93 => 'phpunit\\framework\\assertfinite',
        94 => 'phpunit\\framework\\assertinfinite',
        95 => 'phpunit\\framework\\assertnan',
        96 => 'phpunit\\framework\\assertobjecthasproperty',
        97 => 'phpunit\\framework\\assertobjectnothasproperty',
        98 => 'phpunit\\framework\\assertsame',
        99 => 'phpunit\\framework\\assertnotsame',
        100 => 'phpunit\\framework\\assertinstanceof',
        101 => 'phpunit\\framework\\assertnotinstanceof',
        102 => 'phpunit\\framework\\assertisarray',
        103 => 'phpunit\\framework\\assertisbool',
        104 => 'phpunit\\framework\\assertisfloat',
        105 => 'phpunit\\framework\\assertisint',
        106 => 'phpunit\\framework\\assertisnumeric',
        107 => 'phpunit\\framework\\assertisobject',
        108 => 'phpunit\\framework\\assertisresource',
        109 => 'phpunit\\framework\\assertisclosedresource',
        110 => 'phpunit\\framework\\assertisstring',
        111 => 'phpunit\\framework\\assertisscalar',
        112 => 'phpunit\\framework\\assertiscallable',
        113 => 'phpunit\\framework\\assertisiterable',
        114 => 'phpunit\\framework\\assertisnotarray',
        115 => 'phpunit\\framework\\assertisnotbool',
        116 => 'phpunit\\framework\\assertisnotfloat',
        117 => 'phpunit\\framework\\assertisnotint',
        118 => 'phpunit\\framework\\assertisnotnumeric',
        119 => 'phpunit\\framework\\assertisnotobject',
        120 => 'phpunit\\framework\\assertisnotresource',
        121 => 'phpunit\\framework\\assertisnotclosedresource',
        122 => 'phpunit\\framework\\assertisnotstring',
        123 => 'phpunit\\framework\\assertisnotscalar',
        124 => 'phpunit\\framework\\assertisnotcallable',
        125 => 'phpunit\\framework\\assertisnotiterable',
        126 => 'phpunit\\framework\\assertmatchesregularexpression',
        127 => 'phpunit\\framework\\assertdoesnotmatchregularexpression',
        128 => 'phpunit\\framework\\assertsamesize',
        129 => 'phpunit\\framework\\assertnotsamesize',
        130 => 'phpunit\\framework\\assertstringcontainsstringignoringlineendings',
        131 => 'phpunit\\framework\\assertstringequalsstringignoringlineendings',
        132 => 'phpunit\\framework\\assertfilematchesformat',
        133 => 'phpunit\\framework\\assertfilematchesformatfile',
        134 => 'phpunit\\framework\\assertstringmatchesformat',
        135 => 'phpunit\\framework\\assertstringnotmatchesformat',
        136 => 'phpunit\\framework\\assertstringmatchesformatfile',
        137 => 'phpunit\\framework\\assertstringnotmatchesformatfile',
        138 => 'phpunit\\framework\\assertstringstartswith',
        139 => 'phpunit\\framework\\assertstringstartsnotwith',
        140 => 'phpunit\\framework\\assertstringcontainsstring',
        141 => 'phpunit\\framework\\assertstringcontainsstringignoringcase',
        142 => 'phpunit\\framework\\assertstringnotcontainsstring',
        143 => 'phpunit\\framework\\assertstringnotcontainsstringignoringcase',
        144 => 'phpunit\\framework\\assertstringendswith',
        145 => 'phpunit\\framework\\assertstringendsnotwith',
        146 => 'phpunit\\framework\\assertxmlfileequalsxmlfile',
        147 => 'phpunit\\framework\\assertxmlfilenotequalsxmlfile',
        148 => 'phpunit\\framework\\assertxmlstringequalsxmlfile',
        149 => 'phpunit\\framework\\assertxmlstringnotequalsxmlfile',
        150 => 'phpunit\\framework\\assertxmlstringequalsxmlstring',
        151 => 'phpunit\\framework\\assertxmlstringnotequalsxmlstring',
        152 => 'phpunit\\framework\\assertthat',
        153 => 'phpunit\\framework\\assertjson',
        154 => 'phpunit\\framework\\assertjsonstringequalsjsonstring',
        155 => 'phpunit\\framework\\assertjsonstringnotequalsjsonstring',
        156 => 'phpunit\\framework\\assertjsonstringequalsjsonfile',
        157 => 'phpunit\\framework\\assertjsonstringnotequalsjsonfile',
        158 => 'phpunit\\framework\\assertjsonfileequalsjsonfile',
        159 => 'phpunit\\framework\\assertjsonfilenotequalsjsonfile',
        160 => 'phpunit\\framework\\logicaland',
        161 => 'phpunit\\framework\\logicalor',
        162 => 'phpunit\\framework\\logicalnot',
        163 => 'phpunit\\framework\\logicalxor',
        164 => 'phpunit\\framework\\anything',
        165 => 'phpunit\\framework\\istrue',
        166 => 'phpunit\\framework\\callback',
        167 => 'phpunit\\framework\\isfalse',
        168 => 'phpunit\\framework\\isjson',
        169 => 'phpunit\\framework\\isnull',
        170 => 'phpunit\\framework\\isfinite',
        171 => 'phpunit\\framework\\isinfinite',
        172 => 'phpunit\\framework\\isnan',
        173 => 'phpunit\\framework\\containsequal',
        174 => 'phpunit\\framework\\containsidentical',
        175 => 'phpunit\\framework\\containsonly',
        176 => 'phpunit\\framework\\containsonlyarray',
        177 => 'phpunit\\framework\\containsonlybool',
        178 => 'phpunit\\framework\\containsonlycallable',
        179 => 'phpunit\\framework\\containsonlyfloat',
        180 => 'phpunit\\framework\\containsonlyint',
        181 => 'phpunit\\framework\\containsonlyiterable',
        182 => 'phpunit\\framework\\containsonlynull',
        183 => 'phpunit\\framework\\containsonlynumeric',
        184 => 'phpunit\\framework\\containsonlyobject',
        185 => 'phpunit\\framework\\containsonlyresource',
        186 => 'phpunit\\framework\\containsonlyclosedresource',
        187 => 'phpunit\\framework\\containsonlyscalar',
        188 => 'phpunit\\framework\\containsonlystring',
        189 => 'phpunit\\framework\\containsonlyinstancesof',
        190 => 'phpunit\\framework\\arrayhaskey',
        191 => 'phpunit\\framework\\islist',
        192 => 'phpunit\\framework\\equalto',
        193 => 'phpunit\\framework\\equaltocanonicalizing',
        194 => 'phpunit\\framework\\equaltoignoringcase',
        195 => 'phpunit\\framework\\equaltowithdelta',
        196 => 'phpunit\\framework\\isempty',
        197 => 'phpunit\\framework\\iswritable',
        198 => 'phpunit\\framework\\isreadable',
        199 => 'phpunit\\framework\\directoryexists',
        200 => 'phpunit\\framework\\fileexists',
        201 => 'phpunit\\framework\\greaterthan',
        202 => 'phpunit\\framework\\greaterthanorequal',
        203 => 'phpunit\\framework\\identicalto',
        204 => 'phpunit\\framework\\isinstanceof',
        205 => 'phpunit\\framework\\isarray',
        206 => 'phpunit\\framework\\isbool',
        207 => 'phpunit\\framework\\iscallable',
        208 => 'phpunit\\framework\\isfloat',
        209 => 'phpunit\\framework\\isint',
        210 => 'phpunit\\framework\\isiterable',
        211 => 'phpunit\\framework\\isnumeric',
        212 => 'phpunit\\framework\\isobject',
        213 => 'phpunit\\framework\\isresource',
        214 => 'phpunit\\framework\\isclosedresource',
        215 => 'phpunit\\framework\\isscalar',
        216 => 'phpunit\\framework\\isstring',
        217 => 'phpunit\\framework\\istype',
        218 => 'phpunit\\framework\\lessthan',
        219 => 'phpunit\\framework\\lessthanorequal',
        220 => 'phpunit\\framework\\matchesregularexpression',
        221 => 'phpunit\\framework\\matches',
        222 => 'phpunit\\framework\\stringstartswith',
        223 => 'phpunit\\framework\\stringcontains',
        224 => 'phpunit\\framework\\stringendswith',
        225 => 'phpunit\\framework\\stringequalsstringignoringlineendings',
        226 => 'phpunit\\framework\\countof',
        227 => 'phpunit\\framework\\objectequals',
        228 => 'phpunit\\framework\\fail',
        229 => 'phpunit\\framework\\marktestincomplete',
        230 => 'phpunit\\framework\\marktestskipped',
        231 => 'phpunit\\framework\\getcount',
        232 => 'phpunit\\framework\\resetcount',
        233 => 'phpunit\\framework\\isnativetype',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Assert/Functions.php' => 
    array (
      0 => '4d07837721a64f55630417d7e9e0bc36ee9e8fc0a16bf3cfcb2efb017b8d5899',
      1 => 
      array (
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\assertarrayisequaltoarrayonlyconsideringlistofkeys',
        1 => 'phpunit\\framework\\assertarrayisequaltoarrayignoringlistofkeys',
        2 => 'phpunit\\framework\\assertarrayisidenticaltoarrayonlyconsideringlistofkeys',
        3 => 'phpunit\\framework\\assertarrayisidenticaltoarrayignoringlistofkeys',
        4 => 'phpunit\\framework\\assertarrayhaskey',
        5 => 'phpunit\\framework\\assertarraynothaskey',
        6 => 'phpunit\\framework\\assertislist',
        7 => 'phpunit\\framework\\assertcontains',
        8 => 'phpunit\\framework\\assertcontainsequals',
        9 => 'phpunit\\framework\\assertnotcontains',
        10 => 'phpunit\\framework\\assertnotcontainsequals',
        11 => 'phpunit\\framework\\assertcontainsonly',
        12 => 'phpunit\\framework\\assertcontainsonlyarray',
        13 => 'phpunit\\framework\\assertcontainsonlybool',
        14 => 'phpunit\\framework\\assertcontainsonlycallable',
        15 => 'phpunit\\framework\\assertcontainsonlyfloat',
        16 => 'phpunit\\framework\\assertcontainsonlyint',
        17 => 'phpunit\\framework\\assertcontainsonlyiterable',
        18 => 'phpunit\\framework\\assertcontainsonlynull',
        19 => 'phpunit\\framework\\assertcontainsonlynumeric',
        20 => 'phpunit\\framework\\assertcontainsonlyobject',
        21 => 'phpunit\\framework\\assertcontainsonlyresource',
        22 => 'phpunit\\framework\\assertcontainsonlyclosedresource',
        23 => 'phpunit\\framework\\assertcontainsonlyscalar',
        24 => 'phpunit\\framework\\assertcontainsonlystring',
        25 => 'phpunit\\framework\\assertcontainsonlyinstancesof',
        26 => 'phpunit\\framework\\assertnotcontainsonly',
        27 => 'phpunit\\framework\\assertcontainsnotonlyarray',
        28 => 'phpunit\\framework\\assertcontainsnotonlybool',
        29 => 'phpunit\\framework\\assertcontainsnotonlycallable',
        30 => 'phpunit\\framework\\assertcontainsnotonlyfloat',
        31 => 'phpunit\\framework\\assertcontainsnotonlyint',
        32 => 'phpunit\\framework\\assertcontainsnotonlyiterable',
        33 => 'phpunit\\framework\\assertcontainsnotonlynull',
        34 => 'phpunit\\framework\\assertcontainsnotonlynumeric',
        35 => 'phpunit\\framework\\assertcontainsnotonlyobject',
        36 => 'phpunit\\framework\\assertcontainsnotonlyresource',
        37 => 'phpunit\\framework\\assertcontainsnotonlyclosedresource',
        38 => 'phpunit\\framework\\assertcontainsnotonlyscalar',
        39 => 'phpunit\\framework\\assertcontainsnotonlystring',
        40 => 'phpunit\\framework\\assertcontainsnotonlyinstancesof',
        41 => 'phpunit\\framework\\assertcount',
        42 => 'phpunit\\framework\\assertnotcount',
        43 => 'phpunit\\framework\\assertequals',
        44 => 'phpunit\\framework\\assertequalscanonicalizing',
        45 => 'phpunit\\framework\\assertequalsignoringcase',
        46 => 'phpunit\\framework\\assertequalswithdelta',
        47 => 'phpunit\\framework\\assertnotequals',
        48 => 'phpunit\\framework\\assertnotequalscanonicalizing',
        49 => 'phpunit\\framework\\assertnotequalsignoringcase',
        50 => 'phpunit\\framework\\assertnotequalswithdelta',
        51 => 'phpunit\\framework\\assertobjectequals',
        52 => 'phpunit\\framework\\assertobjectnotequals',
        53 => 'phpunit\\framework\\assertempty',
        54 => 'phpunit\\framework\\assertnotempty',
        55 => 'phpunit\\framework\\assertgreaterthan',
        56 => 'phpunit\\framework\\assertgreaterthanorequal',
        57 => 'phpunit\\framework\\assertlessthan',
        58 => 'phpunit\\framework\\assertlessthanorequal',
        59 => 'phpunit\\framework\\assertfileequals',
        60 => 'phpunit\\framework\\assertfileequalscanonicalizing',
        61 => 'phpunit\\framework\\assertfileequalsignoringcase',
        62 => 'phpunit\\framework\\assertfilenotequals',
        63 => 'phpunit\\framework\\assertfilenotequalscanonicalizing',
        64 => 'phpunit\\framework\\assertfilenotequalsignoringcase',
        65 => 'phpunit\\framework\\assertstringequalsfile',
        66 => 'phpunit\\framework\\assertstringequalsfilecanonicalizing',
        67 => 'phpunit\\framework\\assertstringequalsfileignoringcase',
        68 => 'phpunit\\framework\\assertstringnotequalsfile',
        69 => 'phpunit\\framework\\assertstringnotequalsfilecanonicalizing',
        70 => 'phpunit\\framework\\assertstringnotequalsfileignoringcase',
        71 => 'phpunit\\framework\\assertisreadable',
        72 => 'phpunit\\framework\\assertisnotreadable',
        73 => 'phpunit\\framework\\assertiswritable',
        74 => 'phpunit\\framework\\assertisnotwritable',
        75 => 'phpunit\\framework\\assertdirectoryexists',
        76 => 'phpunit\\framework\\assertdirectorydoesnotexist',
        77 => 'phpunit\\framework\\assertdirectoryisreadable',
        78 => 'phpunit\\framework\\assertdirectoryisnotreadable',
        79 => 'phpunit\\framework\\assertdirectoryiswritable',
        80 => 'phpunit\\framework\\assertdirectoryisnotwritable',
        81 => 'phpunit\\framework\\assertfileexists',
        82 => 'phpunit\\framework\\assertfiledoesnotexist',
        83 => 'phpunit\\framework\\assertfileisreadable',
        84 => 'phpunit\\framework\\assertfileisnotreadable',
        85 => 'phpunit\\framework\\assertfileiswritable',
        86 => 'phpunit\\framework\\assertfileisnotwritable',
        87 => 'phpunit\\framework\\asserttrue',
        88 => 'phpunit\\framework\\assertnottrue',
        89 => 'phpunit\\framework\\assertfalse',
        90 => 'phpunit\\framework\\assertnotfalse',
        91 => 'phpunit\\framework\\assertnull',
        92 => 'phpunit\\framework\\assertnotnull',
        93 => 'phpunit\\framework\\assertfinite',
        94 => 'phpunit\\framework\\assertinfinite',
        95 => 'phpunit\\framework\\assertnan',
        96 => 'phpunit\\framework\\assertobjecthasproperty',
        97 => 'phpunit\\framework\\assertobjectnothasproperty',
        98 => 'phpunit\\framework\\assertsame',
        99 => 'phpunit\\framework\\assertnotsame',
        100 => 'phpunit\\framework\\assertinstanceof',
        101 => 'phpunit\\framework\\assertnotinstanceof',
        102 => 'phpunit\\framework\\assertisarray',
        103 => 'phpunit\\framework\\assertisbool',
        104 => 'phpunit\\framework\\assertisfloat',
        105 => 'phpunit\\framework\\assertisint',
        106 => 'phpunit\\framework\\assertisnumeric',
        107 => 'phpunit\\framework\\assertisobject',
        108 => 'phpunit\\framework\\assertisresource',
        109 => 'phpunit\\framework\\assertisclosedresource',
        110 => 'phpunit\\framework\\assertisstring',
        111 => 'phpunit\\framework\\assertisscalar',
        112 => 'phpunit\\framework\\assertiscallable',
        113 => 'phpunit\\framework\\assertisiterable',
        114 => 'phpunit\\framework\\assertisnotarray',
        115 => 'phpunit\\framework\\assertisnotbool',
        116 => 'phpunit\\framework\\assertisnotfloat',
        117 => 'phpunit\\framework\\assertisnotint',
        118 => 'phpunit\\framework\\assertisnotnumeric',
        119 => 'phpunit\\framework\\assertisnotobject',
        120 => 'phpunit\\framework\\assertisnotresource',
        121 => 'phpunit\\framework\\assertisnotclosedresource',
        122 => 'phpunit\\framework\\assertisnotstring',
        123 => 'phpunit\\framework\\assertisnotscalar',
        124 => 'phpunit\\framework\\assertisnotcallable',
        125 => 'phpunit\\framework\\assertisnotiterable',
        126 => 'phpunit\\framework\\assertmatchesregularexpression',
        127 => 'phpunit\\framework\\assertdoesnotmatchregularexpression',
        128 => 'phpunit\\framework\\assertsamesize',
        129 => 'phpunit\\framework\\assertnotsamesize',
        130 => 'phpunit\\framework\\assertstringcontainsstringignoringlineendings',
        131 => 'phpunit\\framework\\assertstringequalsstringignoringlineendings',
        132 => 'phpunit\\framework\\assertfilematchesformat',
        133 => 'phpunit\\framework\\assertfilematchesformatfile',
        134 => 'phpunit\\framework\\assertstringmatchesformat',
        135 => 'phpunit\\framework\\assertstringnotmatchesformat',
        136 => 'phpunit\\framework\\assertstringmatchesformatfile',
        137 => 'phpunit\\framework\\assertstringnotmatchesformatfile',
        138 => 'phpunit\\framework\\assertstringstartswith',
        139 => 'phpunit\\framework\\assertstringstartsnotwith',
        140 => 'phpunit\\framework\\assertstringcontainsstring',
        141 => 'phpunit\\framework\\assertstringcontainsstringignoringcase',
        142 => 'phpunit\\framework\\assertstringnotcontainsstring',
        143 => 'phpunit\\framework\\assertstringnotcontainsstringignoringcase',
        144 => 'phpunit\\framework\\assertstringendswith',
        145 => 'phpunit\\framework\\assertstringendsnotwith',
        146 => 'phpunit\\framework\\assertxmlfileequalsxmlfile',
        147 => 'phpunit\\framework\\assertxmlfilenotequalsxmlfile',
        148 => 'phpunit\\framework\\assertxmlstringequalsxmlfile',
        149 => 'phpunit\\framework\\assertxmlstringnotequalsxmlfile',
        150 => 'phpunit\\framework\\assertxmlstringequalsxmlstring',
        151 => 'phpunit\\framework\\assertxmlstringnotequalsxmlstring',
        152 => 'phpunit\\framework\\assertthat',
        153 => 'phpunit\\framework\\assertjson',
        154 => 'phpunit\\framework\\assertjsonstringequalsjsonstring',
        155 => 'phpunit\\framework\\assertjsonstringnotequalsjsonstring',
        156 => 'phpunit\\framework\\assertjsonstringequalsjsonfile',
        157 => 'phpunit\\framework\\assertjsonstringnotequalsjsonfile',
        158 => 'phpunit\\framework\\assertjsonfileequalsjsonfile',
        159 => 'phpunit\\framework\\assertjsonfilenotequalsjsonfile',
        160 => 'phpunit\\framework\\logicaland',
        161 => 'phpunit\\framework\\logicalor',
        162 => 'phpunit\\framework\\logicalnot',
        163 => 'phpunit\\framework\\logicalxor',
        164 => 'phpunit\\framework\\anything',
        165 => 'phpunit\\framework\\istrue',
        166 => 'phpunit\\framework\\isfalse',
        167 => 'phpunit\\framework\\isjson',
        168 => 'phpunit\\framework\\isnull',
        169 => 'phpunit\\framework\\isfinite',
        170 => 'phpunit\\framework\\isinfinite',
        171 => 'phpunit\\framework\\isnan',
        172 => 'phpunit\\framework\\containsequal',
        173 => 'phpunit\\framework\\containsidentical',
        174 => 'phpunit\\framework\\containsonly',
        175 => 'phpunit\\framework\\containsonlyarray',
        176 => 'phpunit\\framework\\containsonlybool',
        177 => 'phpunit\\framework\\containsonlycallable',
        178 => 'phpunit\\framework\\containsonlyfloat',
        179 => 'phpunit\\framework\\containsonlyint',
        180 => 'phpunit\\framework\\containsonlyiterable',
        181 => 'phpunit\\framework\\containsonlynull',
        182 => 'phpunit\\framework\\containsonlynumeric',
        183 => 'phpunit\\framework\\containsonlyobject',
        184 => 'phpunit\\framework\\containsonlyresource',
        185 => 'phpunit\\framework\\containsonlyclosedresource',
        186 => 'phpunit\\framework\\containsonlyscalar',
        187 => 'phpunit\\framework\\containsonlystring',
        188 => 'phpunit\\framework\\containsonlyinstancesof',
        189 => 'phpunit\\framework\\arrayhaskey',
        190 => 'phpunit\\framework\\islist',
        191 => 'phpunit\\framework\\equalto',
        192 => 'phpunit\\framework\\equaltocanonicalizing',
        193 => 'phpunit\\framework\\equaltoignoringcase',
        194 => 'phpunit\\framework\\equaltowithdelta',
        195 => 'phpunit\\framework\\isempty',
        196 => 'phpunit\\framework\\iswritable',
        197 => 'phpunit\\framework\\isreadable',
        198 => 'phpunit\\framework\\directoryexists',
        199 => 'phpunit\\framework\\fileexists',
        200 => 'phpunit\\framework\\greaterthan',
        201 => 'phpunit\\framework\\greaterthanorequal',
        202 => 'phpunit\\framework\\identicalto',
        203 => 'phpunit\\framework\\isinstanceof',
        204 => 'phpunit\\framework\\isarray',
        205 => 'phpunit\\framework\\isbool',
        206 => 'phpunit\\framework\\iscallable',
        207 => 'phpunit\\framework\\isfloat',
        208 => 'phpunit\\framework\\isint',
        209 => 'phpunit\\framework\\isiterable',
        210 => 'phpunit\\framework\\isnumeric',
        211 => 'phpunit\\framework\\isobject',
        212 => 'phpunit\\framework\\isresource',
        213 => 'phpunit\\framework\\isclosedresource',
        214 => 'phpunit\\framework\\isscalar',
        215 => 'phpunit\\framework\\isstring',
        216 => 'phpunit\\framework\\istype',
        217 => 'phpunit\\framework\\lessthan',
        218 => 'phpunit\\framework\\lessthanorequal',
        219 => 'phpunit\\framework\\matchesregularexpression',
        220 => 'phpunit\\framework\\matches',
        221 => 'phpunit\\framework\\stringstartswith',
        222 => 'phpunit\\framework\\stringcontains',
        223 => 'phpunit\\framework\\stringendswith',
        224 => 'phpunit\\framework\\stringequalsstringignoringlineendings',
        225 => 'phpunit\\framework\\countof',
        226 => 'phpunit\\framework\\objectequals',
        227 => 'phpunit\\framework\\callback',
        228 => 'phpunit\\framework\\any',
        229 => 'phpunit\\framework\\never',
        230 => 'phpunit\\framework\\atleast',
        231 => 'phpunit\\framework\\atleastonce',
        232 => 'phpunit\\framework\\once',
        233 => 'phpunit\\framework\\exactly',
        234 => 'phpunit\\framework\\atmost',
        235 => 'phpunit\\framework\\returnvalue',
        236 => 'phpunit\\framework\\returnvaluemap',
        237 => 'phpunit\\framework\\returnargument',
        238 => 'phpunit\\framework\\returncallback',
        239 => 'phpunit\\framework\\returnself',
        240 => 'phpunit\\framework\\throwexception',
        241 => 'phpunit\\framework\\onconsecutivecalls',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Attributes/After.php' => 
    array (
      0 => '12e73884f4a301b089378bdd24fc9c7e599faebaab567c57bf2debfc6f42d29d',
      1 => 
      array (
        0 => 'phpunit\\framework\\attributes\\after',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\attributes\\__construct',
        1 => 'phpunit\\framework\\attributes\\priority',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Attributes/AfterClass.php' => 
    array (
      0 => 'ec7e563034916452f776f23aec0a7832e4131d4dffeff730ba9a42f5b097bcd1',
      1 => 
      array (
        0 => 'phpunit\\framework\\attributes\\afterclass',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\attributes\\__construct',
        1 => 'phpunit\\framework\\attributes\\priority',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Attributes/BackupGlobals.php' => 
    array (
      0 => '9dc520bb95ca1cccee3081d606239a89b13f7c1e55269740ed9f14f0d516ff13',
      1 => 
      array (
        0 => 'phpunit\\framework\\attributes\\backupglobals',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\attributes\\__construct',
        1 => 'phpunit\\framework\\attributes\\enabled',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Attributes/BackupStaticProperties.php' => 
    array (
      0 => '7695bb805f83b3237ae5472754d75f7ef153a292e2c7d9f6673a7cba13d31e6b',
      1 => 
      array (
        0 => 'phpunit\\framework\\attributes\\backupstaticproperties',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\attributes\\__construct',
        1 => 'phpunit\\framework\\attributes\\enabled',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Attributes/Before.php' => 
    array (
      0 => '9ad79cf50897c19a91dea41de968b30d4bfc6c0ffd191a530fc82ed3f48740e7',
      1 => 
      array (
        0 => 'phpunit\\framework\\attributes\\before',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\attributes\\__construct',
        1 => 'phpunit\\framework\\attributes\\priority',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Attributes/BeforeClass.php' => 
    array (
      0 => 'e624f4975e85a84292f52429c531d7c5c2dc8293f59b1ffa009a625a8665a472',
      1 => 
      array (
        0 => 'phpunit\\framework\\attributes\\beforeclass',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\attributes\\__construct',
        1 => 'phpunit\\framework\\attributes\\priority',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Attributes/CoversClass.php' => 
    array (
      0 => '94a28862fbcbda5da350fc5b0863cf45307766fba746ae818cf540cc9f1000c1',
      1 => 
      array (
        0 => 'phpunit\\framework\\attributes\\coversclass',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\attributes\\__construct',
        1 => 'phpunit\\framework\\attributes\\classname',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Attributes/CoversFunction.php' => 
    array (
      0 => 'e23308f149e6eb4d0f173277ba5614d26011cdad628595cbb0cf37f19cfb39fb',
      1 => 
      array (
        0 => 'phpunit\\framework\\attributes\\coversfunction',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\attributes\\__construct',
        1 => 'phpunit\\framework\\attributes\\functionname',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Attributes/CoversMethod.php' => 
    array (
      0 => '5decd883c2fb400f1b6453825b9d43bb7d08f93ba70ff91461fec4fa7ff443c4',
      1 => 
      array (
        0 => 'phpunit\\framework\\attributes\\coversmethod',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\attributes\\__construct',
        1 => 'phpunit\\framework\\attributes\\classname',
        2 => 'phpunit\\framework\\attributes\\methodname',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Attributes/CoversNothing.php' => 
    array (
      0 => '064a4da005048a3582998af3d29477e8823595c801a49ce8bcc89346760ad50c',
      1 => 
      array (
        0 => 'phpunit\\framework\\attributes\\coversnothing',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Attributes/CoversTrait.php' => 
    array (
      0 => '9d6eeb27c71273b71da0a2864188c9b9b70c2df0e69e8e3fe612fae8ff16be73',
      1 => 
      array (
        0 => 'phpunit\\framework\\attributes\\coverstrait',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\attributes\\__construct',
        1 => 'phpunit\\framework\\attributes\\traitname',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Attributes/DataProvider.php' => 
    array (
      0 => 'e3c532ac0426be9793cdf3ba8cdd83a796a6c158591506e73ea8361ce5b6c481',
      1 => 
      array (
        0 => 'phpunit\\framework\\attributes\\dataprovider',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\attributes\\__construct',
        1 => 'phpunit\\framework\\attributes\\methodname',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Attributes/DataProviderExternal.php' => 
    array (
      0 => 'a70316d18f3f8a488b20fa85ede9fd2cac20e6a05a4898072c9d5cab85cee51f',
      1 => 
      array (
        0 => 'phpunit\\framework\\attributes\\dataproviderexternal',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\attributes\\__construct',
        1 => 'phpunit\\framework\\attributes\\classname',
        2 => 'phpunit\\framework\\attributes\\methodname',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Attributes/Depends.php' => 
    array (
      0 => '376ae75fc50e0db5ff31017bf344e9e8c6bcc08b54108d47428d3fffbb8d11ba',
      1 => 
      array (
        0 => 'phpunit\\framework\\attributes\\depends',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\attributes\\__construct',
        1 => 'phpunit\\framework\\attributes\\methodname',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Attributes/DependsExternal.php' => 
    array (
      0 => '4686a2c2d1014fc7fcd3dae1abeb3c143d3b98b2dbb0e6a9a6aefab0b16542ad',
      1 => 
      array (
        0 => 'phpunit\\framework\\attributes\\dependsexternal',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\attributes\\__construct',
        1 => 'phpunit\\framework\\attributes\\classname',
        2 => 'phpunit\\framework\\attributes\\methodname',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Attributes/DependsExternalUsingDeepClone.php' => 
    array (
      0 => 'ed026de353f0fe8166c80a3d1f1af9de9df5e91e9a2618d8f2ca3998f81c5373',
      1 => 
      array (
        0 => 'phpunit\\framework\\attributes\\dependsexternalusingdeepclone',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\attributes\\__construct',
        1 => 'phpunit\\framework\\attributes\\classname',
        2 => 'phpunit\\framework\\attributes\\methodname',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Attributes/DependsExternalUsingShallowClone.php' => 
    array (
      0 => '95b3b3cc014ba2edd943ab7ff37eb539e356aad2f2c5084a42b74884035f6c32',
      1 => 
      array (
        0 => 'phpunit\\framework\\attributes\\dependsexternalusingshallowclone',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\attributes\\__construct',
        1 => 'phpunit\\framework\\attributes\\classname',
        2 => 'phpunit\\framework\\attributes\\methodname',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Attributes/DependsOnClass.php' => 
    array (
      0 => '912cabdc2b184b9dc23b778468804056b8875f777f87fcc554f849d1336bf2b8',
      1 => 
      array (
        0 => 'phpunit\\framework\\attributes\\dependsonclass',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\attributes\\__construct',
        1 => 'phpunit\\framework\\attributes\\classname',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Attributes/DependsOnClassUsingDeepClone.php' => 
    array (
      0 => '9a414bbc49644376b7ba86e542df120ec2e06bf949221f892095568e70afcf7e',
      1 => 
      array (
        0 => 'phpunit\\framework\\attributes\\dependsonclassusingdeepclone',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\attributes\\__construct',
        1 => 'phpunit\\framework\\attributes\\classname',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Attributes/DependsOnClassUsingShallowClone.php' => 
    array (
      0 => 'a43ae3ab7b92f26bab8a0b8169899083cabd6233b13e18a862bc20a7f9832d43',
      1 => 
      array (
        0 => 'phpunit\\framework\\attributes\\dependsonclassusingshallowclone',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\attributes\\__construct',
        1 => 'phpunit\\framework\\attributes\\classname',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Attributes/DependsUsingDeepClone.php' => 
    array (
      0 => '37c747c6b193a68971b702d7fad6f6899bf5da1997d851a9118bbec44e5f508f',
      1 => 
      array (
        0 => 'phpunit\\framework\\attributes\\dependsusingdeepclone',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\attributes\\__construct',
        1 => 'phpunit\\framework\\attributes\\methodname',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Attributes/DependsUsingShallowClone.php' => 
    array (
      0 => 'c22d6d5d484af15361bdb95ee50110a64d92465b130556188a54f9d2144b1a15',
      1 => 
      array (
        0 => 'phpunit\\framework\\attributes\\dependsusingshallowclone',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\attributes\\__construct',
        1 => 'phpunit\\framework\\attributes\\methodname',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Attributes/DisableReturnValueGenerationForTestDoubles.php' => 
    array (
      0 => 'fe10148dc428edc9a186df06493e8ad51d33ee6d0a1d34118b3e4bc5ca42a3c4',
      1 => 
      array (
        0 => 'phpunit\\framework\\attributes\\disablereturnvaluegenerationfortestdoubles',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Attributes/DoesNotPerformAssertions.php' => 
    array (
      0 => '2938d5c6ae2dcb51349165d6e7d379b46dee0d4b9ad1ae3bc64643b2f5edd778',
      1 => 
      array (
        0 => 'phpunit\\framework\\attributes\\doesnotperformassertions',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Attributes/ExcludeGlobalVariableFromBackup.php' => 
    array (
      0 => 'fb4422a5b868eae82b0660af091c2fa0ff32e5a5980de0df6075e9d4b6bcfc6e',
      1 => 
      array (
        0 => 'phpunit\\framework\\attributes\\excludeglobalvariablefrombackup',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\attributes\\__construct',
        1 => 'phpunit\\framework\\attributes\\globalvariablename',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Attributes/ExcludeStaticPropertyFromBackup.php' => 
    array (
      0 => '0fcbaa07f614158315d3df277e08b4ede9253172c60b7424247a4c88160f9ca5',
      1 => 
      array (
        0 => 'phpunit\\framework\\attributes\\excludestaticpropertyfrombackup',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\attributes\\__construct',
        1 => 'phpunit\\framework\\attributes\\classname',
        2 => 'phpunit\\framework\\attributes\\propertyname',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Attributes/Group.php' => 
    array (
      0 => '83b7a2888968019718ffa35458905d3e4f6de845fb2f009bbbc40d1dbfde676e',
      1 => 
      array (
        0 => 'phpunit\\framework\\attributes\\group',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\attributes\\__construct',
        1 => 'phpunit\\framework\\attributes\\name',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Attributes/IgnoreDeprecations.php' => 
    array (
      0 => '09e5de916be192465861e66af01a9b29321ea2f305570023c4c3fa826ebc9008',
      1 => 
      array (
        0 => 'phpunit\\framework\\attributes\\ignoredeprecations',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Attributes/IgnorePhpunitDeprecations.php' => 
    array (
      0 => '1104cd95d75aa2ff89af90f158203b5f6da6697bf0d287e7d88881d94f2a49b0',
      1 => 
      array (
        0 => 'phpunit\\framework\\attributes\\ignorephpunitdeprecations',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Attributes/Large.php' => 
    array (
      0 => '78794fb8a413a2d0b22e118df3464f1a8873828b9c1b2e7af4aa7160e5089dd3',
      1 => 
      array (
        0 => 'phpunit\\framework\\attributes\\large',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Attributes/Medium.php' => 
    array (
      0 => '4cfaf29b2ecedda0157cb36bce43d1a7b1e8c3fc4801e320ed964bf57d89a757',
      1 => 
      array (
        0 => 'phpunit\\framework\\attributes\\medium',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Attributes/PostCondition.php' => 
    array (
      0 => '09f19fb65b0e8c5ea44b84e9debfd1249455a731fcd96d789451ad1b75e12de5',
      1 => 
      array (
        0 => 'phpunit\\framework\\attributes\\postcondition',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\attributes\\__construct',
        1 => 'phpunit\\framework\\attributes\\priority',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Attributes/PreCondition.php' => 
    array (
      0 => '2119b6bc25109f0927a91d397a28e5c858f4237a14bcfc58aefff7f512fdefce',
      1 => 
      array (
        0 => 'phpunit\\framework\\attributes\\precondition',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\attributes\\__construct',
        1 => 'phpunit\\framework\\attributes\\priority',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Attributes/PreserveGlobalState.php' => 
    array (
      0 => '5358c49b09f7d8cba9241d722274886964a9d1976246a9a58de94bab72adf771',
      1 => 
      array (
        0 => 'phpunit\\framework\\attributes\\preserveglobalstate',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\attributes\\__construct',
        1 => 'phpunit\\framework\\attributes\\enabled',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Attributes/RequiresFunction.php' => 
    array (
      0 => '98c0cfb8b839242c40f66bca4d1f5dd03766fcd0f5d4fa8a89052534afb6c29d',
      1 => 
      array (
        0 => 'phpunit\\framework\\attributes\\requiresfunction',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\attributes\\__construct',
        1 => 'phpunit\\framework\\attributes\\functionname',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Attributes/RequiresMethod.php' => 
    array (
      0 => '63ee34cb942aca334d1741ffa0e670c4d7c6c557f48580bf43a7996359035136',
      1 => 
      array (
        0 => 'phpunit\\framework\\attributes\\requiresmethod',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\attributes\\__construct',
        1 => 'phpunit\\framework\\attributes\\classname',
        2 => 'phpunit\\framework\\attributes\\methodname',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Attributes/RequiresOperatingSystem.php' => 
    array (
      0 => '59ccf1412ef6121b1a75057faf68945e6a2bd471e701cadd698edb90a7d966db',
      1 => 
      array (
        0 => 'phpunit\\framework\\attributes\\requiresoperatingsystem',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\attributes\\__construct',
        1 => 'phpunit\\framework\\attributes\\regularexpression',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Attributes/RequiresOperatingSystemFamily.php' => 
    array (
      0 => 'f88cf6e734e84af9a085f01e2b8922a40f379729745188849d73889724c62c31',
      1 => 
      array (
        0 => 'phpunit\\framework\\attributes\\requiresoperatingsystemfamily',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\attributes\\__construct',
        1 => 'phpunit\\framework\\attributes\\operatingsystemfamily',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Attributes/RequiresPhp.php' => 
    array (
      0 => 'e2330c82574850928dc963b3a66e51b7daa8d8825749806788f5588f680271ed',
      1 => 
      array (
        0 => 'phpunit\\framework\\attributes\\requiresphp',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\attributes\\__construct',
        1 => 'phpunit\\framework\\attributes\\versionrequirement',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Attributes/RequiresPhpExtension.php' => 
    array (
      0 => '7a9d53839ca96085f575632a1787bf87d38a7ef6cbe4b1492fd76cf22a6a3fc1',
      1 => 
      array (
        0 => 'phpunit\\framework\\attributes\\requiresphpextension',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\attributes\\__construct',
        1 => 'phpunit\\framework\\attributes\\extension',
        2 => 'phpunit\\framework\\attributes\\versionrequirement',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Attributes/RequiresPhpunit.php' => 
    array (
      0 => '1d62aee76fa7248e274befb1f312ebcf585e41dbc636f7179cab2989b250ce01',
      1 => 
      array (
        0 => 'phpunit\\framework\\attributes\\requiresphpunit',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\attributes\\__construct',
        1 => 'phpunit\\framework\\attributes\\versionrequirement',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Attributes/RequiresPhpunitExtension.php' => 
    array (
      0 => '918a63a7f439c0f7fbd05b31d48ec236985ab3a5d0195c79b88fca71ffaf3a5f',
      1 => 
      array (
        0 => 'phpunit\\framework\\attributes\\requiresphpunitextension',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\attributes\\__construct',
        1 => 'phpunit\\framework\\attributes\\extensionclass',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Attributes/RequiresSetting.php' => 
    array (
      0 => 'a7c362ea67b1297c478f5731efd73ca41d0cf2ea1393b2b4d04f3713a441d470',
      1 => 
      array (
        0 => 'phpunit\\framework\\attributes\\requiressetting',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\attributes\\__construct',
        1 => 'phpunit\\framework\\attributes\\setting',
        2 => 'phpunit\\framework\\attributes\\value',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Attributes/RunClassInSeparateProcess.php' => 
    array (
      0 => '3ccaf63664c0f52ac7bfdddc6002b066a31f9ac9f9e91b2e7a58fea1fca3e452',
      1 => 
      array (
        0 => 'phpunit\\framework\\attributes\\runclassinseparateprocess',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Attributes/RunInSeparateProcess.php' => 
    array (
      0 => '582fd210c87f9db74d2322f2e30fb3b02dab3d8e70a2ffc7efc34832f48b9eb2',
      1 => 
      array (
        0 => 'phpunit\\framework\\attributes\\runinseparateprocess',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Attributes/RunTestsInSeparateProcesses.php' => 
    array (
      0 => '09ec79ffa61db86d8e2ce22c988de583df498b4e53811e7836897b84258167c8',
      1 => 
      array (
        0 => 'phpunit\\framework\\attributes\\runtestsinseparateprocesses',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Attributes/Small.php' => 
    array (
      0 => '8a630c973130bda650d01ce3432709516d1ca254e28cd6d3f3728cf018648595',
      1 => 
      array (
        0 => 'phpunit\\framework\\attributes\\small',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Attributes/Test.php' => 
    array (
      0 => 'cf2e7ca7c60d06541668f79544ebd4824fe58ad024f711a42716202bb9bc1ede',
      1 => 
      array (
        0 => 'phpunit\\framework\\attributes\\test',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Attributes/TestDox.php' => 
    array (
      0 => '8072b4d3f45008a87211c64e22e11bd8e863edc0e55babc047141f94b4a01634',
      1 => 
      array (
        0 => 'phpunit\\framework\\attributes\\testdox',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\attributes\\__construct',
        1 => 'phpunit\\framework\\attributes\\text',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Attributes/TestWith.php' => 
    array (
      0 => '9ca2a5b891f16c22a67d4a70fcf25b6cb6b155591e3b798ede978d94bebcfda1',
      1 => 
      array (
        0 => 'phpunit\\framework\\attributes\\testwith',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\attributes\\__construct',
        1 => 'phpunit\\framework\\attributes\\data',
        2 => 'phpunit\\framework\\attributes\\name',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Attributes/TestWithJson.php' => 
    array (
      0 => '72fa581ce8ce4fc2fcf23453bd966b4e1cfa12c211b57d9c5377b494ae3fef05',
      1 => 
      array (
        0 => 'phpunit\\framework\\attributes\\testwithjson',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\attributes\\__construct',
        1 => 'phpunit\\framework\\attributes\\json',
        2 => 'phpunit\\framework\\attributes\\name',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Attributes/Ticket.php' => 
    array (
      0 => '8a5975658885e0c2e3f39460ae098c52872702173ead22a53d6b5cff4a37ee54',
      1 => 
      array (
        0 => 'phpunit\\framework\\attributes\\ticket',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\attributes\\__construct',
        1 => 'phpunit\\framework\\attributes\\text',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Attributes/UsesClass.php' => 
    array (
      0 => 'e697aa196a0b38bb6b86ec4775bbd43c8d08f58a60de3e22f1ca4b7e33acf9b0',
      1 => 
      array (
        0 => 'phpunit\\framework\\attributes\\usesclass',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\attributes\\__construct',
        1 => 'phpunit\\framework\\attributes\\classname',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Attributes/UsesFunction.php' => 
    array (
      0 => '7f4deee4317c11f02da9c38d84db63223e164f669352bc5cf622809d2038dbc3',
      1 => 
      array (
        0 => 'phpunit\\framework\\attributes\\usesfunction',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\attributes\\__construct',
        1 => 'phpunit\\framework\\attributes\\functionname',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Attributes/UsesMethod.php' => 
    array (
      0 => '0e7630b66a4e53a9cd099b1ca732a41abf0140da2849e7bbda2591c61cbc5dae',
      1 => 
      array (
        0 => 'phpunit\\framework\\attributes\\usesmethod',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\attributes\\__construct',
        1 => 'phpunit\\framework\\attributes\\classname',
        2 => 'phpunit\\framework\\attributes\\methodname',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Attributes/UsesTrait.php' => 
    array (
      0 => 'bdf041a43bc929a6a9c9d91da2923abae692078f1af2efb72ea8dcfddafde076',
      1 => 
      array (
        0 => 'phpunit\\framework\\attributes\\usestrait',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\attributes\\__construct',
        1 => 'phpunit\\framework\\attributes\\traitname',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Attributes/WithoutErrorHandler.php' => 
    array (
      0 => 'bc11b0cc47e5c29ae6dc351f88ba987ea1ecee8165c14f70d1ed98e3ebacdddc',
      1 => 
      array (
        0 => 'phpunit\\framework\\attributes\\withouterrorhandler',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Constraint/Boolean/IsFalse.php' => 
    array (
      0 => '214640ee69048f2345741f12e13ffc0494e04c86766f7b20cb52c1d1a7f7aa85',
      1 => 
      array (
        0 => 'phpunit\\framework\\constraint\\isfalse',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\constraint\\tostring',
        1 => 'phpunit\\framework\\constraint\\matches',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Constraint/Boolean/IsTrue.php' => 
    array (
      0 => 'ed23d16bf7aef392131cb8a886f856174acff19cbcec0a99d0d4a320ed029b8f',
      1 => 
      array (
        0 => 'phpunit\\framework\\constraint\\istrue',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\constraint\\tostring',
        1 => 'phpunit\\framework\\constraint\\matches',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Constraint/Callback.php' => 
    array (
      0 => 'ed7f135ec73f872e967b731b7f6ef7035bc78f246705482cd337ca44b4b78212',
      1 => 
      array (
        0 => 'phpunit\\framework\\constraint\\callback',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\constraint\\__construct',
        1 => 'phpunit\\framework\\constraint\\tostring',
        2 => 'phpunit\\framework\\constraint\\isvariadic',
        3 => 'phpunit\\framework\\constraint\\matches',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Constraint/Cardinality/Count.php' => 
    array (
      0 => '490fc0ad5f4cc5383fbdd61dda260fb22feee308c601513152d9927687214e94',
      1 => 
      array (
        0 => 'phpunit\\framework\\constraint\\count',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\constraint\\__construct',
        1 => 'phpunit\\framework\\constraint\\tostring',
        2 => 'phpunit\\framework\\constraint\\matches',
        3 => 'phpunit\\framework\\constraint\\getcountof',
        4 => 'phpunit\\framework\\constraint\\failuredescription',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Constraint/Cardinality/GreaterThan.php' => 
    array (
      0 => '5a011e1dcf66b24da1bb1328d0dddf58c6dd286157744531c6b1393674349cc5',
      1 => 
      array (
        0 => 'phpunit\\framework\\constraint\\greaterthan',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\constraint\\__construct',
        1 => 'phpunit\\framework\\constraint\\tostring',
        2 => 'phpunit\\framework\\constraint\\matches',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Constraint/Cardinality/IsEmpty.php' => 
    array (
      0 => '52e53a647f98c0a6fedaab35bf832f7d4101d0025bd4d6a80ed065b4d81b7168',
      1 => 
      array (
        0 => 'phpunit\\framework\\constraint\\isempty',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\constraint\\tostring',
        1 => 'phpunit\\framework\\constraint\\matches',
        2 => 'phpunit\\framework\\constraint\\failuredescription',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Constraint/Cardinality/LessThan.php' => 
    array (
      0 => 'ddb5abfc9c5b34f66bb9f544c3fa42745ba23fc6ca1f2a893093507e9a18413c',
      1 => 
      array (
        0 => 'phpunit\\framework\\constraint\\lessthan',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\constraint\\__construct',
        1 => 'phpunit\\framework\\constraint\\tostring',
        2 => 'phpunit\\framework\\constraint\\matches',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Constraint/Cardinality/SameSize.php' => 
    array (
      0 => '3954d5b9afc3025b5d14038259c96162f8d749c7ff14fc65a65727d14758b265',
      1 => 
      array (
        0 => 'phpunit\\framework\\constraint\\samesize',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\constraint\\__construct',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Constraint/Constraint.php' => 
    array (
      0 => '462850edab59944ce8d69d29f423e37230c2965c0acb7113d6440203178ecf2b',
      1 => 
      array (
        0 => 'phpunit\\framework\\constraint\\constraint',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\constraint\\evaluate',
        1 => 'phpunit\\framework\\constraint\\count',
        2 => 'phpunit\\framework\\constraint\\matches',
        3 => 'phpunit\\framework\\constraint\\fail',
        4 => 'phpunit\\framework\\constraint\\additionalfailuredescription',
        5 => 'phpunit\\framework\\constraint\\failuredescription',
        6 => 'phpunit\\framework\\constraint\\tostringincontext',
        7 => 'phpunit\\framework\\constraint\\failuredescriptionincontext',
        8 => 'phpunit\\framework\\constraint\\reduce',
        9 => 'phpunit\\framework\\constraint\\valuetotypestringfragment',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Constraint/Equality/IsEqual.php' => 
    array (
      0 => '22aedabfcfd2e0376e29ffe10db88bc3429e26ba6a99144acf27a640e9d75e5a',
      1 => 
      array (
        0 => 'phpunit\\framework\\constraint\\isequal',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\constraint\\__construct',
        1 => 'phpunit\\framework\\constraint\\evaluate',
        2 => 'phpunit\\framework\\constraint\\tostring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Constraint/Equality/IsEqualCanonicalizing.php' => 
    array (
      0 => '0d19da661ee0d58b3b3018fd2484832edc4c34db5d3d0c099070630a03df2cf4',
      1 => 
      array (
        0 => 'phpunit\\framework\\constraint\\isequalcanonicalizing',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\constraint\\__construct',
        1 => 'phpunit\\framework\\constraint\\evaluate',
        2 => 'phpunit\\framework\\constraint\\tostring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Constraint/Equality/IsEqualIgnoringCase.php' => 
    array (
      0 => '0ebf7bdef923baa35938d37e18f1a4b0530a2231cec8d933bcf131adcfa9445d',
      1 => 
      array (
        0 => 'phpunit\\framework\\constraint\\isequalignoringcase',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\constraint\\__construct',
        1 => 'phpunit\\framework\\constraint\\evaluate',
        2 => 'phpunit\\framework\\constraint\\tostring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Constraint/Equality/IsEqualWithDelta.php' => 
    array (
      0 => '5bfb01156b6ae02ed46a869c39978a1001dd17726609cf6f73d7aaa8ff77f12a',
      1 => 
      array (
        0 => 'phpunit\\framework\\constraint\\isequalwithdelta',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\constraint\\__construct',
        1 => 'phpunit\\framework\\constraint\\evaluate',
        2 => 'phpunit\\framework\\constraint\\tostring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Constraint/Exception/Exception.php' => 
    array (
      0 => 'ef55bbf205e5c7ade961ebfaa325e94636effcbc81b542133611208decc773db',
      1 => 
      array (
        0 => 'phpunit\\framework\\constraint\\exception',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\constraint\\__construct',
        1 => 'phpunit\\framework\\constraint\\tostring',
        2 => 'phpunit\\framework\\constraint\\matches',
        3 => 'phpunit\\framework\\constraint\\failuredescription',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Constraint/Exception/ExceptionCode.php' => 
    array (
      0 => '17758cd282f7496064c633ecdb4621bd44cdd02b86de7ac8434af165f31753fb',
      1 => 
      array (
        0 => 'phpunit\\framework\\constraint\\exceptioncode',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\constraint\\__construct',
        1 => 'phpunit\\framework\\constraint\\tostring',
        2 => 'phpunit\\framework\\constraint\\matches',
        3 => 'phpunit\\framework\\constraint\\failuredescription',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Constraint/Exception/ExceptionMessageIsOrContains.php' => 
    array (
      0 => 'd19b45b1e4aad73c6aabdc674fe40007dfec1eb1b5ec14b7d641f6f4390d2541',
      1 => 
      array (
        0 => 'phpunit\\framework\\constraint\\exceptionmessageisorcontains',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\constraint\\__construct',
        1 => 'phpunit\\framework\\constraint\\tostring',
        2 => 'phpunit\\framework\\constraint\\matches',
        3 => 'phpunit\\framework\\constraint\\failuredescription',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Constraint/Exception/ExceptionMessageMatchesRegularExpression.php' => 
    array (
      0 => '1ba6c6b069e5b27ef0ddf426415a2fd7f4cc9fe16d1ef68b1dbda7b5c0fc27cc',
      1 => 
      array (
        0 => 'phpunit\\framework\\constraint\\exceptionmessagematchesregularexpression',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\constraint\\__construct',
        1 => 'phpunit\\framework\\constraint\\tostring',
        2 => 'phpunit\\framework\\constraint\\matches',
        3 => 'phpunit\\framework\\constraint\\failuredescription',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Constraint/Filesystem/DirectoryExists.php' => 
    array (
      0 => 'e57ba09700d3159dd1ac1258716a53bdb4ac807e78611ef8818e6b073e9336aa',
      1 => 
      array (
        0 => 'phpunit\\framework\\constraint\\directoryexists',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\constraint\\tostring',
        1 => 'phpunit\\framework\\constraint\\matches',
        2 => 'phpunit\\framework\\constraint\\failuredescription',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Constraint/Filesystem/FileExists.php' => 
    array (
      0 => '1146e4593df4ceb4f3a85fcadbd1c5a3f0f50e068bb93d6d176287b32c6fe6d2',
      1 => 
      array (
        0 => 'phpunit\\framework\\constraint\\fileexists',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\constraint\\tostring',
        1 => 'phpunit\\framework\\constraint\\matches',
        2 => 'phpunit\\framework\\constraint\\failuredescription',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Constraint/Filesystem/IsReadable.php' => 
    array (
      0 => '3daacc41f8da99f1ef1d31223ab08f9d4d4ef2d5d8b6758c8042082ca27679cb',
      1 => 
      array (
        0 => 'phpunit\\framework\\constraint\\isreadable',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\constraint\\tostring',
        1 => 'phpunit\\framework\\constraint\\matches',
        2 => 'phpunit\\framework\\constraint\\failuredescription',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Constraint/Filesystem/IsWritable.php' => 
    array (
      0 => '141c5c3a54b2d36f3c71e23ae621125fbaa257486c50aabb876ba989b8b898f5',
      1 => 
      array (
        0 => 'phpunit\\framework\\constraint\\iswritable',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\constraint\\tostring',
        1 => 'phpunit\\framework\\constraint\\matches',
        2 => 'phpunit\\framework\\constraint\\failuredescription',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Constraint/IsAnything.php' => 
    array (
      0 => '5eb3a5317a926b616ffc8759ab74d387f3b98df645cefc9a1c7cb7f1d467d2aa',
      1 => 
      array (
        0 => 'phpunit\\framework\\constraint\\isanything',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\constraint\\evaluate',
        1 => 'phpunit\\framework\\constraint\\tostring',
        2 => 'phpunit\\framework\\constraint\\count',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Constraint/IsIdentical.php' => 
    array (
      0 => 'e29e57e6e2f52742a7c2e6cbc845c93dd32e72e2a4a6880f28c770dda378fe5e',
      1 => 
      array (
        0 => 'phpunit\\framework\\constraint\\isidentical',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\constraint\\__construct',
        1 => 'phpunit\\framework\\constraint\\evaluate',
        2 => 'phpunit\\framework\\constraint\\tostring',
        3 => 'phpunit\\framework\\constraint\\failuredescription',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Constraint/JsonMatches.php' => 
    array (
      0 => '9ec389a55d715f84af1cfa255de287b5563da5d95487636310590f0dcd9ce062',
      1 => 
      array (
        0 => 'phpunit\\framework\\constraint\\jsonmatches',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\constraint\\__construct',
        1 => 'phpunit\\framework\\constraint\\tostring',
        2 => 'phpunit\\framework\\constraint\\matches',
        3 => 'phpunit\\framework\\constraint\\fail',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Constraint/Math/IsFinite.php' => 
    array (
      0 => 'eaf4625048a0e22a86d2998988b936191e5b1fb7f113077ee14ff02ec8e44a79',
      1 => 
      array (
        0 => 'phpunit\\framework\\constraint\\isfinite',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\constraint\\tostring',
        1 => 'phpunit\\framework\\constraint\\matches',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Constraint/Math/IsInfinite.php' => 
    array (
      0 => 'e3fc5026cc76d76657cb119780e561b0e85442c5a8967823a1f7298486ae826e',
      1 => 
      array (
        0 => 'phpunit\\framework\\constraint\\isinfinite',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\constraint\\tostring',
        1 => 'phpunit\\framework\\constraint\\matches',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Constraint/Math/IsNan.php' => 
    array (
      0 => '2f1b7cbc0fd6e7e0978da3509eb6f81dcbb64467df1db77057a82cc5e9f16a8c',
      1 => 
      array (
        0 => 'phpunit\\framework\\constraint\\isnan',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\constraint\\tostring',
        1 => 'phpunit\\framework\\constraint\\matches',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Constraint/Object/ObjectEquals.php' => 
    array (
      0 => 'f72937b37c735399d8fce06ff19f236b80a512c711cda47f76b627af079387b1',
      1 => 
      array (
        0 => 'phpunit\\framework\\constraint\\objectequals',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\constraint\\__construct',
        1 => 'phpunit\\framework\\constraint\\tostring',
        2 => 'phpunit\\framework\\constraint\\matches',
        3 => 'phpunit\\framework\\constraint\\failuredescription',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Constraint/Object/ObjectHasProperty.php' => 
    array (
      0 => '25a3565f7e34c292188d80a9f90f5eb26cd058e29c42f36471ee69038651a313',
      1 => 
      array (
        0 => 'phpunit\\framework\\constraint\\objecthasproperty',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\constraint\\__construct',
        1 => 'phpunit\\framework\\constraint\\tostring',
        2 => 'phpunit\\framework\\constraint\\matches',
        3 => 'phpunit\\framework\\constraint\\failuredescription',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Constraint/Operator/BinaryOperator.php' => 
    array (
      0 => 'bd86e4b83af1ac019ea21bcf3fd6b15e344868d9008e58ae21282a35524b002c',
      1 => 
      array (
        0 => 'phpunit\\framework\\constraint\\binaryoperator',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\constraint\\__construct',
        1 => 'phpunit\\framework\\constraint\\arity',
        2 => 'phpunit\\framework\\constraint\\tostring',
        3 => 'phpunit\\framework\\constraint\\count',
        4 => 'phpunit\\framework\\constraint\\constraints',
        5 => 'phpunit\\framework\\constraint\\constraintneedsparentheses',
        6 => 'phpunit\\framework\\constraint\\reduce',
        7 => 'phpunit\\framework\\constraint\\constrainttostring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Constraint/Operator/LogicalAnd.php' => 
    array (
      0 => '793b261b22421a9577d4e42b7230bc9a2f5a82454488422553de051c1c0adab9',
      1 => 
      array (
        0 => 'phpunit\\framework\\constraint\\logicaland',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\constraint\\fromconstraints',
        1 => 'phpunit\\framework\\constraint\\operator',
        2 => 'phpunit\\framework\\constraint\\precedence',
        3 => 'phpunit\\framework\\constraint\\matches',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Constraint/Operator/LogicalNot.php' => 
    array (
      0 => '7585078d0a311abbfb12e1ee91a3bf97dbf580674de545da33255e00432f33e4',
      1 => 
      array (
        0 => 'phpunit\\framework\\constraint\\logicalnot',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\constraint\\negate',
        1 => 'phpunit\\framework\\constraint\\operator',
        2 => 'phpunit\\framework\\constraint\\precedence',
        3 => 'phpunit\\framework\\constraint\\matches',
        4 => 'phpunit\\framework\\constraint\\transformstring',
        5 => 'phpunit\\framework\\constraint\\reduce',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Constraint/Operator/LogicalOr.php' => 
    array (
      0 => '1768daa180c34a9d80d791a445c444ced6066e940740eab59c962f5ade9d6398',
      1 => 
      array (
        0 => 'phpunit\\framework\\constraint\\logicalor',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\constraint\\fromconstraints',
        1 => 'phpunit\\framework\\constraint\\operator',
        2 => 'phpunit\\framework\\constraint\\precedence',
        3 => 'phpunit\\framework\\constraint\\matches',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Constraint/Operator/LogicalXor.php' => 
    array (
      0 => '9ccdbc2e340d03e732e8a9d9529cbf4b064fbbb89d0ef1b81b74ec3416fb7bf8',
      1 => 
      array (
        0 => 'phpunit\\framework\\constraint\\logicalxor',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\constraint\\fromconstraints',
        1 => 'phpunit\\framework\\constraint\\operator',
        2 => 'phpunit\\framework\\constraint\\precedence',
        3 => 'phpunit\\framework\\constraint\\matches',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Constraint/Operator/Operator.php' => 
    array (
      0 => 'a23468e3168db41d19c3b169090c3c83243c2a28bd322ee0b507947f75406f84',
      1 => 
      array (
        0 => 'phpunit\\framework\\constraint\\operator',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\constraint\\operator',
        1 => 'phpunit\\framework\\constraint\\precedence',
        2 => 'phpunit\\framework\\constraint\\arity',
        3 => 'phpunit\\framework\\constraint\\checkconstraint',
        4 => 'phpunit\\framework\\constraint\\constraintneedsparentheses',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Constraint/Operator/UnaryOperator.php' => 
    array (
      0 => '8603d8f1907f6cf35443fc36c7bf0e8e2660fafcc4b2028a96a6e97f4e4b0692',
      1 => 
      array (
        0 => 'phpunit\\framework\\constraint\\unaryoperator',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\constraint\\__construct',
        1 => 'phpunit\\framework\\constraint\\arity',
        2 => 'phpunit\\framework\\constraint\\tostring',
        3 => 'phpunit\\framework\\constraint\\count',
        4 => 'phpunit\\framework\\constraint\\failuredescription',
        5 => 'phpunit\\framework\\constraint\\transformstring',
        6 => 'phpunit\\framework\\constraint\\constraint',
        7 => 'phpunit\\framework\\constraint\\constraintneedsparentheses',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Constraint/String/IsJson.php' => 
    array (
      0 => 'b6f39ad256d0826da3a2cb5aa8e7d3990cf9beef8dd8c7fca741af4f4b509e47',
      1 => 
      array (
        0 => 'phpunit\\framework\\constraint\\isjson',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\constraint\\tostring',
        1 => 'phpunit\\framework\\constraint\\matches',
        2 => 'phpunit\\framework\\constraint\\failuredescription',
        3 => 'phpunit\\framework\\constraint\\determinejsonerror',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Constraint/String/RegularExpression.php' => 
    array (
      0 => '36994e7bf3eecdd2c35c7a095b898595d0998884007716ec150b20a558e25bb6',
      1 => 
      array (
        0 => 'phpunit\\framework\\constraint\\regularexpression',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\constraint\\__construct',
        1 => 'phpunit\\framework\\constraint\\tostring',
        2 => 'phpunit\\framework\\constraint\\matches',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Constraint/String/StringContains.php' => 
    array (
      0 => '488feca4baf740e8e056332d228499e49a45f122a04a583def51b42ecf2b3f82',
      1 => 
      array (
        0 => 'phpunit\\framework\\constraint\\stringcontains',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\constraint\\__construct',
        1 => 'phpunit\\framework\\constraint\\tostring',
        2 => 'phpunit\\framework\\constraint\\failuredescription',
        3 => 'phpunit\\framework\\constraint\\matches',
        4 => 'phpunit\\framework\\constraint\\detectedencoding',
        5 => 'phpunit\\framework\\constraint\\haystacklength',
        6 => 'phpunit\\framework\\constraint\\normalizelineendings',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Constraint/String/StringEndsWith.php' => 
    array (
      0 => '1c39edd8bce69aaef8a476c575211fd34dbebcb818a73e569aa02ff42d29e268',
      1 => 
      array (
        0 => 'phpunit\\framework\\constraint\\stringendswith',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\constraint\\__construct',
        1 => 'phpunit\\framework\\constraint\\tostring',
        2 => 'phpunit\\framework\\constraint\\matches',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Constraint/String/StringEqualsStringIgnoringLineEndings.php' => 
    array (
      0 => '0f4fedcabdd05694591f5fe54a82e8e38d3e19a38dffeda7a733665425e21dc2',
      1 => 
      array (
        0 => 'phpunit\\framework\\constraint\\stringequalsstringignoringlineendings',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\constraint\\__construct',
        1 => 'phpunit\\framework\\constraint\\tostring',
        2 => 'phpunit\\framework\\constraint\\matches',
        3 => 'phpunit\\framework\\constraint\\normalizelineendings',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Constraint/String/StringMatchesFormatDescription.php' => 
    array (
      0 => '65c063dc5b16b00b59366831a346b8be2f5ca8e58d6a3751d2b1635d9b965c92',
      1 => 
      array (
        0 => 'phpunit\\framework\\constraint\\stringmatchesformatdescription',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\constraint\\__construct',
        1 => 'phpunit\\framework\\constraint\\tostring',
        2 => 'phpunit\\framework\\constraint\\matches',
        3 => 'phpunit\\framework\\constraint\\failuredescription',
        4 => 'phpunit\\framework\\constraint\\additionalfailuredescription',
        5 => 'phpunit\\framework\\constraint\\regularexpressionforformatdescription',
        6 => 'phpunit\\framework\\constraint\\convertnewlines',
        7 => 'phpunit\\framework\\constraint\\differ',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Constraint/String/StringStartsWith.php' => 
    array (
      0 => '9210e206051601a2a85e2721eefcd734ed029a81026c6aa2fa66c58de22bb12c',
      1 => 
      array (
        0 => 'phpunit\\framework\\constraint\\stringstartswith',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\constraint\\__construct',
        1 => 'phpunit\\framework\\constraint\\tostring',
        2 => 'phpunit\\framework\\constraint\\matches',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Constraint/Traversable/ArrayHasKey.php' => 
    array (
      0 => '1cfa3f067c6ed616d4425ed8389a1c0d5f465441970d430ac55ec538c55fe1e1',
      1 => 
      array (
        0 => 'phpunit\\framework\\constraint\\arrayhaskey',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\constraint\\__construct',
        1 => 'phpunit\\framework\\constraint\\tostring',
        2 => 'phpunit\\framework\\constraint\\matches',
        3 => 'phpunit\\framework\\constraint\\failuredescription',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Constraint/Traversable/IsList.php' => 
    array (
      0 => '60d73556a359ac28bef8c4b969d408568bc149ee1d33316b2b582855982274f5',
      1 => 
      array (
        0 => 'phpunit\\framework\\constraint\\islist',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\constraint\\tostring',
        1 => 'phpunit\\framework\\constraint\\matches',
        2 => 'phpunit\\framework\\constraint\\failuredescription',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Constraint/Traversable/TraversableContains.php' => 
    array (
      0 => '96af801f13097418181516e21bfaa277acc4cf79502dd1451fee51fcde09da02',
      1 => 
      array (
        0 => 'phpunit\\framework\\constraint\\traversablecontains',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\constraint\\__construct',
        1 => 'phpunit\\framework\\constraint\\tostring',
        2 => 'phpunit\\framework\\constraint\\failuredescription',
        3 => 'phpunit\\framework\\constraint\\value',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Constraint/Traversable/TraversableContainsEqual.php' => 
    array (
      0 => '2dcc0efcbec05b3ef14807e20413044d6637f08cca3339c7e64c825c65a13d5e',
      1 => 
      array (
        0 => 'phpunit\\framework\\constraint\\traversablecontainsequal',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\constraint\\matches',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Constraint/Traversable/TraversableContainsIdentical.php' => 
    array (
      0 => '7270941cb9a99c1b3ae11ff6a3303e3b6b42a0e7967fbe40bdfa41cf47d0518b',
      1 => 
      array (
        0 => 'phpunit\\framework\\constraint\\traversablecontainsidentical',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\constraint\\matches',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Constraint/Traversable/TraversableContainsOnly.php' => 
    array (
      0 => '1c76d4a1d49d4528dea628fff8ed906e625c505ab0650d4a43444a5408f18e30',
      1 => 
      array (
        0 => 'phpunit\\framework\\constraint\\traversablecontainsonly',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\constraint\\__construct',
        1 => 'phpunit\\framework\\constraint\\evaluate',
        2 => 'phpunit\\framework\\constraint\\tostring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Constraint/Type/IsInstanceOf.php' => 
    array (
      0 => 'e3d568612b99548b6dc6a199641fcf667c0c06c755a660c912c629c2dc50f6f7',
      1 => 
      array (
        0 => 'phpunit\\framework\\constraint\\isinstanceof',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\constraint\\__construct',
        1 => 'phpunit\\framework\\constraint\\tostring',
        2 => 'phpunit\\framework\\constraint\\matches',
        3 => 'phpunit\\framework\\constraint\\failuredescription',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Constraint/Type/IsNull.php' => 
    array (
      0 => '1acc9c8c80b5532ab682f617c8eaf9a63376b1860cbbbfc5d5b05f0388807433',
      1 => 
      array (
        0 => 'phpunit\\framework\\constraint\\isnull',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\constraint\\tostring',
        1 => 'phpunit\\framework\\constraint\\matches',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Constraint/Type/IsType.php' => 
    array (
      0 => 'c4eb9faa6ec6373d93bb35d37798100f0fa59d9a32dbd901ade7e5a072a677ba',
      1 => 
      array (
        0 => 'phpunit\\framework\\constraint\\istype',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\constraint\\__construct',
        1 => 'phpunit\\framework\\constraint\\tostring',
        2 => 'phpunit\\framework\\constraint\\matches',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/DataProviderTestSuite.php' => 
    array (
      0 => 'b1ff948cd6f227d6079621e2ef36a4fbf0d742a84bb4b7d42c523b28b178d5b4',
      1 => 
      array (
        0 => 'phpunit\\framework\\dataprovidertestsuite',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\setdependencies',
        1 => 'phpunit\\framework\\provides',
        2 => 'phpunit\\framework\\requires',
        3 => 'phpunit\\framework\\size',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Exception/AssertionFailedError.php' => 
    array (
      0 => '500585bbc42dd18f4599b6d760420b11771be42f685a2a1232a89b23186e5c10',
      1 => 
      array (
        0 => 'phpunit\\framework\\assertionfailederror',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\tostring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Exception/CodeCoverageException.php' => 
    array (
      0 => 'df6c8a7e72daf0f571225ea300893bbe1011db58845ee455e45d708306b20e66',
      1 => 
      array (
        0 => 'phpunit\\framework\\codecoverageexception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Exception/EmptyStringException.php' => 
    array (
      0 => '0a7e535a19ef1baa895c481f40875422515bd3e93266d9225b2a307458b7bfac',
      1 => 
      array (
        0 => 'phpunit\\framework\\emptystringexception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Exception/Exception.php' => 
    array (
      0 => '121091867018fc0ac1dcacd1ebc8ad5f0e5bb4f307f1ebef4f9b093249a5794b',
      1 => 
      array (
        0 => 'phpunit\\framework\\exception',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\__construct',
        1 => 'phpunit\\framework\\__serialize',
        2 => 'phpunit\\framework\\getserializabletrace',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Exception/ExpectationFailedException.php' => 
    array (
      0 => 'b294cec8909f3f976246ca047847f2340291481e3b6ee6789708a31dbdaf5471',
      1 => 
      array (
        0 => 'phpunit\\framework\\expectationfailedexception',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\__construct',
        1 => 'phpunit\\framework\\getcomparisonfailure',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Exception/GeneratorNotSupportedException.php' => 
    array (
      0 => '2d2b9bc5da71b951d1347437feb189ffe05f2f75816312e349967bc6e3d8ee75',
      1 => 
      array (
        0 => 'phpunit\\framework\\generatornotsupportedexception',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\fromparametername',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Exception/Incomplete/IncompleteTest.php' => 
    array (
      0 => '9a4a62fb6550e6fe2d07931788c41ecf4830503f9db50e6c075823d349f99dc0',
      1 => 
      array (
        0 => 'phpunit\\framework\\incompletetest',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Exception/Incomplete/IncompleteTestError.php' => 
    array (
      0 => '9c1c47ce26009192499793fe1275267bd54dbdac540f238ad3c792ae71855379',
      1 => 
      array (
        0 => 'phpunit\\framework\\incompletetesterror',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Exception/InvalidArgumentException.php' => 
    array (
      0 => '873604f0f81fb8c6ecb132a12a70df25a18139a2b5e3128afff8156509d54024',
      1 => 
      array (
        0 => 'phpunit\\framework\\invalidargumentexception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Exception/InvalidCoversTargetException.php' => 
    array (
      0 => '4db764ca7c925daafbdef850c3ecba2a5c940f3426120e39ebe4898362ea8aa6',
      1 => 
      array (
        0 => 'phpunit\\framework\\invalidcoverstargetexception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Exception/InvalidDataProviderException.php' => 
    array (
      0 => 'eb0e6bc2902f9dfa773c603e7123d8089cab557481dc3f30b0d75f0b0d6d1bdc',
      1 => 
      array (
        0 => 'phpunit\\framework\\invaliddataproviderexception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Exception/InvalidDependencyException.php' => 
    array (
      0 => 'cfe9b6f21bbc504952d7a4c027641fcb4559f5a51b63905325f40b41904b4a6f',
      1 => 
      array (
        0 => 'phpunit\\framework\\invaliddependencyexception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Exception/NoChildTestSuiteException.php' => 
    array (
      0 => 'db3efb25154fc7a3dc552eae2769d00cd04595dc20415c0ecb0142c4d1b303c9',
      1 => 
      array (
        0 => 'phpunit\\framework\\nochildtestsuiteexception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Exception/ObjectEquals/ActualValueIsNotAnObjectException.php' => 
    array (
      0 => '575cf97ec6f3adf77f592d2523a0a2bbac894d2d19cdf63d1a28ce2893841800',
      1 => 
      array (
        0 => 'phpunit\\framework\\actualvalueisnotanobjectexception',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\__construct',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Exception/ObjectEquals/ComparisonMethodDoesNotAcceptParameterTypeException.php' => 
    array (
      0 => '181782a85da2831acfc1d854e31fcbb7781adf3bc674ccf379b520fd383f2b98',
      1 => 
      array (
        0 => 'phpunit\\framework\\comparisonmethoddoesnotacceptparametertypeexception',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\__construct',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Exception/ObjectEquals/ComparisonMethodDoesNotDeclareBoolReturnTypeException.php' => 
    array (
      0 => '7211dae0d337710d2126ddee14e3e5a6ebeee76fba3411f2a4307958b8db714f',
      1 => 
      array (
        0 => 'phpunit\\framework\\comparisonmethoddoesnotdeclareboolreturntypeexception',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\__construct',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Exception/ObjectEquals/ComparisonMethodDoesNotDeclareExactlyOneParameterException.php' => 
    array (
      0 => 'b2e447081c1bb965a63783df77d34c185d4345327227892c6489719b4ecc1d14',
      1 => 
      array (
        0 => 'phpunit\\framework\\comparisonmethoddoesnotdeclareexactlyoneparameterexception',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\__construct',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Exception/ObjectEquals/ComparisonMethodDoesNotDeclareParameterTypeException.php' => 
    array (
      0 => '81e8eca8e085b01e261f6af71d676bf2b97687e2e315764aff3d4c7e2848294b',
      1 => 
      array (
        0 => 'phpunit\\framework\\comparisonmethoddoesnotdeclareparametertypeexception',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\__construct',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Exception/ObjectEquals/ComparisonMethodDoesNotExistException.php' => 
    array (
      0 => '83f8bd6ba6d6b3e4b64e1556d7d128a21cd94b274b75c53133004dd2630d0d23',
      1 => 
      array (
        0 => 'phpunit\\framework\\comparisonmethoddoesnotexistexception',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\__construct',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Exception/PhptAssertionFailedError.php' => 
    array (
      0 => 'b5880a18fcf40551509eecb751428a7058c174e8393c42019dac5730259757eb',
      1 => 
      array (
        0 => 'phpunit\\framework\\phptassertionfailederror',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\__construct',
        1 => 'phpunit\\framework\\syntheticfile',
        2 => 'phpunit\\framework\\syntheticline',
        3 => 'phpunit\\framework\\synthetictrace',
        4 => 'phpunit\\framework\\diff',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Exception/ProcessIsolationException.php' => 
    array (
      0 => 'c90e4d046a9f8f9bd8b5d3cffe1290bedc8323ecf9617d550cb917f9bbcbf53f',
      1 => 
      array (
        0 => 'phpunit\\framework\\processisolationexception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Exception/Skipped/SkippedTest.php' => 
    array (
      0 => 'fd087c9a43b6a575024505d1eb53db663d8ff702755193c9b9931e1152d56cc5',
      1 => 
      array (
        0 => 'phpunit\\framework\\skippedtest',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Exception/Skipped/SkippedTestSuiteError.php' => 
    array (
      0 => '334614f3eef2614fb24db7304b99e260d6f9d9bf0d2de857c9f3e7ce4743fc63',
      1 => 
      array (
        0 => 'phpunit\\framework\\skippedtestsuiteerror',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Exception/Skipped/SkippedWithMessageException.php' => 
    array (
      0 => '333f632260ddd85ae6d9c14b5d6bbd1080ba0edb2930838eb9a35f309178f836',
      1 => 
      array (
        0 => 'phpunit\\framework\\skippedwithmessageexception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Exception/UnknownClassOrInterfaceException.php' => 
    array (
      0 => 'a1ae446907f9b2f70f7577368d73b33146eabf97c6fadcbafa795278645e2b3c',
      1 => 
      array (
        0 => 'phpunit\\framework\\unknownclassorinterfaceexception',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\__construct',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Exception/UnknownTypeException.php' => 
    array (
      0 => 'fedf9b42aa418deda3073b6e3b7c8a9a859633af77be099a7bd1894a16c3d198',
      1 => 
      array (
        0 => 'phpunit\\framework\\unknowntypeexception',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\__construct',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/ExecutionOrderDependency.php' => 
    array (
      0 => '55d8137d97f2d4b4ec4cb76b066b14888d562a868e3a427dc99b28a859d344b8',
      1 => 
      array (
        0 => 'phpunit\\framework\\executionorderdependency',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\invalid',
        1 => 'phpunit\\framework\\forclass',
        2 => 'phpunit\\framework\\formethod',
        3 => 'phpunit\\framework\\filterinvalid',
        4 => 'phpunit\\framework\\mergeunique',
        5 => 'phpunit\\framework\\diff',
        6 => 'phpunit\\framework\\__construct',
        7 => 'phpunit\\framework\\__tostring',
        8 => 'phpunit\\framework\\isvalid',
        9 => 'phpunit\\framework\\shallowclone',
        10 => 'phpunit\\framework\\deepclone',
        11 => 'phpunit\\framework\\targetisclass',
        12 => 'phpunit\\framework\\gettarget',
        13 => 'phpunit\\framework\\gettargetclassname',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/ConfigurableMethod.php' => 
    array (
      0 => '96a53008a8cf69a2c3d52f7264632163d4acb0009dcbce9124364424744625de',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\configurablemethod',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\__construct',
        1 => 'phpunit\\framework\\mockobject\\name',
        2 => 'phpunit\\framework\\mockobject\\defaultparametervalues',
        3 => 'phpunit\\framework\\mockobject\\numberofparameters',
        4 => 'phpunit\\framework\\mockobject\\mayreturn',
        5 => 'phpunit\\framework\\mockobject\\returntypedeclaration',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Exception/BadMethodCallException.php' => 
    array (
      0 => '6a4e839f45f95af43d5f8e106001f0d67bb9d2551af74e1798287bd599ddd772',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\badmethodcallexception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Exception/CannotCloneTestDoubleForReadonlyClassException.php' => 
    array (
      0 => '9dbfe0c5769764d8914a601b180842f43a6279ff32e30a7dc358024cce805805',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\cannotclonetestdoubleforreadonlyclassexception',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\__construct',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Exception/CannotUseOnlyMethodsException.php' => 
    array (
      0 => '61207a926f74467b037a79d11530697ea8d16e8206e2c008bde48f275d16a99d',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\cannotuseonlymethodsexception',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\__construct',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Exception/Exception.php' => 
    array (
      0 => '22dd6286a484a6e9d755b1a39de0613d076d1680da010ef212727dcfacedc4e4',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\exception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Exception/IncompatibleReturnValueException.php' => 
    array (
      0 => 'a6e8da9e046abf777257d55d70994f980dd3c2c7a3fcac38514c2834ad48725c',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\incompatiblereturnvalueexception',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\__construct',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Exception/MatchBuilderNotFoundException.php' => 
    array (
      0 => '13922b06373112d449b4ad4a6009d81de619d16bdf34fbd50a86a4f237bc57c2',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\matchbuildernotfoundexception',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\__construct',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Exception/MatcherAlreadyRegisteredException.php' => 
    array (
      0 => '2c6ecb6d9f073c360ec3ef4fa64d84123674e824844cbd0cf92fef398da5824e',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\matcheralreadyregisteredexception',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\__construct',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Exception/MethodCannotBeConfiguredException.php' => 
    array (
      0 => 'e3046ae80e042b08d93d3f6facacfb614e3854adff924fc469736421dd4d6c99',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\methodcannotbeconfiguredexception',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\__construct',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Exception/MethodNameAlreadyConfiguredException.php' => 
    array (
      0 => 'bb1192d6bb9645e0e7b71dff862b1c82f7a00b88a1ed86ae4f2606d4bf7cc640',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\methodnamealreadyconfiguredexception',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\__construct',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Exception/MethodNameNotConfiguredException.php' => 
    array (
      0 => 'c34874cdcacf82636d7e570b7f394502fc13fda20a392dcd31bc5a8b32aaab36',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\methodnamenotconfiguredexception',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\__construct',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Exception/MethodParametersAlreadyConfiguredException.php' => 
    array (
      0 => 'd3883477fcea1d8c54f3b31053ca2b6290c5a2dc2f73136e9da344797eff62de',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\methodparametersalreadyconfiguredexception',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\__construct',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Exception/NeverReturningMethodException.php' => 
    array (
      0 => '015526fec310f377c75dff58c61ffd3309dde21fba205a840c46ed2657cd6427',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\neverreturningmethodexception',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\__construct',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Exception/NoMoreReturnValuesConfiguredException.php' => 
    array (
      0 => '5ed15ed4059b92db8abbd8e4f8683295457e938fc46efabaf8a416f6da0ae1d2',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\nomorereturnvaluesconfiguredexception',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\__construct',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Exception/ReturnValueNotConfiguredException.php' => 
    array (
      0 => '772ba0b1aebe96e2630cd94edffb53d532630d7709d686690cf3b6ad79aadfbc',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\returnvaluenotconfiguredexception',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\__construct',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Exception/RuntimeException.php' => 
    array (
      0 => 'f2436a78a46a7b879b4cd587341cfa5f0d103b42a8d637c6ee9e55c4c273f9bb',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\runtimeexception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Generator/Exception/CannotUseAddMethodsException.php' => 
    array (
      0 => '8757a17ad6d78bc407021b7cda69db7b66742c3776a94ef902966fd1ae79aaa2',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\generator\\cannotuseaddmethodsexception',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\generator\\__construct',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Generator/Exception/ClassIsEnumerationException.php' => 
    array (
      0 => 'c6d90d16d5cd68274e645fda3e9db9602d8c5de928b794ac58784cf063ce02e6',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\generator\\classisenumerationexception',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\generator\\__construct',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Generator/Exception/ClassIsFinalException.php' => 
    array (
      0 => '9920de20422bfbc0dbc5d893be1e185dcd2c3091662f6b48efffeba095bbd107',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\generator\\classisfinalexception',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\generator\\__construct',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Generator/Exception/DuplicateMethodException.php' => 
    array (
      0 => 'dc5e246aa60ae4a734f9acfa929c113c041b8499c0cd44d83fa068d8c3a74410',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\generator\\duplicatemethodexception',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\generator\\__construct',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Generator/Exception/Exception.php' => 
    array (
      0 => '3eedee3b216c6c5b62d2244a15a8b25bb2e5b66edf4c96170de537639a7c4d04',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\generator\\exception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Generator/Exception/InvalidMethodNameException.php' => 
    array (
      0 => '4de78639ff0579f76731cb91ca6e690e9ee1263da7c45b18411154da040f6df7',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\generator\\invalidmethodnameexception',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\generator\\__construct',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Generator/Exception/NameAlreadyInUseException.php' => 
    array (
      0 => '0f41717d0b0b09f01207313b2e01d7fe24d0144774af793454af99f93ce5e224',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\generator\\namealreadyinuseexception',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\generator\\__construct',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Generator/Exception/OriginalConstructorInvocationRequiredException.php' => 
    array (
      0 => 'de29e4ca0616e72283831513e13b54f74215f48928a7d07f93f3caa0c34760d5',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\generator\\originalconstructorinvocationrequiredexception',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\generator\\__construct',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Generator/Exception/ReflectionException.php' => 
    array (
      0 => '3a3bad8a385b9ed917d745a5f25360750f3ec4492f8d3ac7fcdf84c6eab76e95',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\generator\\reflectionexception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Generator/Exception/RuntimeException.php' => 
    array (
      0 => 'a561cdbb4bfbf74d400cb4510e06fe34aaab730bd29789b57f3c49cbe1535564',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\generator\\runtimeexception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Generator/Exception/SoapExtensionNotAvailableException.php' => 
    array (
      0 => 'add6892c208952edc9382e1f14813672e544080d3253c6477c47439b63a12932',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\generator\\soapextensionnotavailableexception',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\generator\\__construct',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Generator/Exception/UnknownClassException.php' => 
    array (
      0 => 'd8814bb3b1157ecfb36f86445da6424457f1e4c454d24074896367d63068ed72',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\generator\\unknownclassexception',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\generator\\__construct',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Generator/Exception/UnknownInterfaceException.php' => 
    array (
      0 => 'a19294c30fd7ba4cfb9d2944e202704cc653e32a170b19e1fa5f8d9ad5038299',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\generator\\unknowninterfaceexception',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\generator\\__construct',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Generator/Exception/UnknownTraitException.php' => 
    array (
      0 => '6e7e1745788ec6fa33fe9de2a724c09b4157a654ae85c77a9a22a3661b3feca2',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\generator\\unknowntraitexception',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\generator\\__construct',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Generator/Exception/UnknownTypeException.php' => 
    array (
      0 => '3d97cf5d6ee4c756660e340294fccc183f9b0667efb6601f8b30df6b0dcd5a6a',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\generator\\unknowntypeexception',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\generator\\__construct',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Generator/Generator.php' => 
    array (
      0 => 'a10687210a6c28a9aedd9e364a67a3fd2d030293d0e32f3018c1b0fa4bd12c4d',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\generator\\generator',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\generator\\testdouble',
        1 => 'phpunit\\framework\\mockobject\\generator\\testdoubleforinterfaceintersection',
        2 => 'phpunit\\framework\\mockobject\\generator\\mockobjectforabstractclass',
        3 => 'phpunit\\framework\\mockobject\\generator\\mockobjectfortrait',
        4 => 'phpunit\\framework\\mockobject\\generator\\objectfortrait',
        5 => 'phpunit\\framework\\mockobject\\generator\\generate',
        6 => 'phpunit\\framework\\mockobject\\generator\\generateclassfromwsdl',
        7 => 'phpunit\\framework\\mockobject\\generator\\mockclassmethods',
        8 => 'phpunit\\framework\\mockobject\\generator\\userdefinedinterfacemethods',
        9 => 'phpunit\\framework\\mockobject\\generator\\instantiate',
        10 => 'phpunit\\framework\\mockobject\\generator\\generatecodefortestdoubleclass',
        11 => 'phpunit\\framework\\mockobject\\generator\\generateclassname',
        12 => 'phpunit\\framework\\mockobject\\generator\\generatetestdoubleclassdeclaration',
        13 => 'phpunit\\framework\\mockobject\\generator\\canmethodbedoubled',
        14 => 'phpunit\\framework\\mockobject\\generator\\ismethodnameexcluded',
        15 => 'phpunit\\framework\\mockobject\\generator\\ensureknowntype',
        16 => 'phpunit\\framework\\mockobject\\generator\\ensurevalidmethods',
        17 => 'phpunit\\framework\\mockobject\\generator\\ensurenamefortestdoubleclassisavailable',
        18 => 'phpunit\\framework\\mockobject\\generator\\instantiateproxytarget',
        19 => 'phpunit\\framework\\mockobject\\generator\\reflectclass',
        20 => 'phpunit\\framework\\mockobject\\generator\\namesofmethodsin',
        21 => 'phpunit\\framework\\mockobject\\generator\\interfacemethods',
        22 => 'phpunit\\framework\\mockobject\\generator\\configurablemethods',
        23 => 'phpunit\\framework\\mockobject\\generator\\properties',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Generator/HookedProperty.php' => 
    array (
      0 => 'fc752f24d65ca1ce58ed988881a9a9d5a851a7a1fa1cfb13a92b6354282c2223',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\generator\\hookedproperty',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\generator\\__construct',
        1 => 'phpunit\\framework\\mockobject\\generator\\name',
        2 => 'phpunit\\framework\\mockobject\\generator\\type',
        3 => 'phpunit\\framework\\mockobject\\generator\\hasgethook',
        4 => 'phpunit\\framework\\mockobject\\generator\\hassethook',
        5 => 'phpunit\\framework\\mockobject\\generator\\settertype',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Generator/HookedPropertyGenerator.php' => 
    array (
      0 => '872ff63eb3d6f8c298428e691edc6da98ad97ff24f8e204f4c748b355d1cc84a',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\generator\\hookedpropertygenerator',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\generator\\generate',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Generator/MockClass.php' => 
    array (
      0 => 'f00ffd76280977862f618b83a765a07cff9a0786ba9e7982c6588509b22e411b',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\generator\\mockclass',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\generator\\__construct',
        1 => 'phpunit\\framework\\mockobject\\generator\\generate',
        2 => 'phpunit\\framework\\mockobject\\generator\\classcode',
        3 => 'phpunit\\framework\\mockobject\\generator\\configurablemethods',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Generator/MockMethod.php' => 
    array (
      0 => 'bfe6018b4a7f035203c997bf4c48b14fec34d072ce44c5e558b053620b4b2942',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\generator\\mockmethod',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\generator\\fromreflection',
        1 => 'phpunit\\framework\\mockobject\\generator\\fromname',
        2 => 'phpunit\\framework\\mockobject\\generator\\__construct',
        3 => 'phpunit\\framework\\mockobject\\generator\\methodname',
        4 => 'phpunit\\framework\\mockobject\\generator\\generatecode',
        5 => 'phpunit\\framework\\mockobject\\generator\\returntype',
        6 => 'phpunit\\framework\\mockobject\\generator\\defaultparametervalues',
        7 => 'phpunit\\framework\\mockobject\\generator\\numberofparameters',
        8 => 'phpunit\\framework\\mockobject\\generator\\methodparametersfordeclaration',
        9 => 'phpunit\\framework\\mockobject\\generator\\methodparametersforcall',
        10 => 'phpunit\\framework\\mockobject\\generator\\exportdefaultvalue',
        11 => 'phpunit\\framework\\mockobject\\generator\\methodparametersdefaultvalues',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Generator/MockMethodSet.php' => 
    array (
      0 => '14771a511598e7f13b82754731428d41cf05aa349fb22587702293c6614f659e',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\generator\\mockmethodset',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\generator\\addmethods',
        1 => 'phpunit\\framework\\mockobject\\generator\\asarray',
        2 => 'phpunit\\framework\\mockobject\\generator\\hasmethod',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Generator/MockTrait.php' => 
    array (
      0 => '1d1b7b0efcb10c5f11eb79e435d2d88308c33b83f0ca75bd1df220b90eed6d60',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\generator\\mocktrait',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\generator\\__construct',
        1 => 'phpunit\\framework\\mockobject\\generator\\generate',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Generator/MockType.php' => 
    array (
      0 => 'a3cdecc3f37a007785875dbbb09a69c6cbbdc61fd638f81c38964699a3ddfe7d',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\generator\\mocktype',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\generator\\generate',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Generator/TemplateLoader.php' => 
    array (
      0 => '3c0379630511f771d16b7323257a4365a55590a64a86c5240a0abc369c9a1bfc',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\generator\\templateloader',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\generator\\loadtemplate',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/MockBuilder.php' => 
    array (
      0 => '56afd928668e742ef3b103408c4c085a95d21d05f1e434d80b64048c9cfa2342',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\mockbuilder',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\__construct',
        1 => 'phpunit\\framework\\mockobject\\getmock',
        2 => 'phpunit\\framework\\mockobject\\getmockforabstractclass',
        3 => 'phpunit\\framework\\mockobject\\getmockfortrait',
        4 => 'phpunit\\framework\\mockobject\\onlymethods',
        5 => 'phpunit\\framework\\mockobject\\addmethods',
        6 => 'phpunit\\framework\\mockobject\\setconstructorargs',
        7 => 'phpunit\\framework\\mockobject\\setmockclassname',
        8 => 'phpunit\\framework\\mockobject\\disableoriginalconstructor',
        9 => 'phpunit\\framework\\mockobject\\enableoriginalconstructor',
        10 => 'phpunit\\framework\\mockobject\\disableoriginalclone',
        11 => 'phpunit\\framework\\mockobject\\enableoriginalclone',
        12 => 'phpunit\\framework\\mockobject\\disableautoload',
        13 => 'phpunit\\framework\\mockobject\\enableautoload',
        14 => 'phpunit\\framework\\mockobject\\disableargumentcloning',
        15 => 'phpunit\\framework\\mockobject\\enableargumentcloning',
        16 => 'phpunit\\framework\\mockobject\\enableproxyingtooriginalmethods',
        17 => 'phpunit\\framework\\mockobject\\disableproxyingtooriginalmethods',
        18 => 'phpunit\\framework\\mockobject\\setproxytarget',
        19 => 'phpunit\\framework\\mockobject\\allowmockingunknowntypes',
        20 => 'phpunit\\framework\\mockobject\\disallowmockingunknowntypes',
        21 => 'phpunit\\framework\\mockobject\\enableautoreturnvaluegeneration',
        22 => 'phpunit\\framework\\mockobject\\disableautoreturnvaluegeneration',
        23 => 'phpunit\\framework\\mockobject\\calledfromtestcase',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Runtime/Api/DoubledCloneMethod.php' => 
    array (
      0 => 'acb6f699cd95760f22b5f6abd338c4e80e641fdec4a3921516c1c70804a2e861',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\doubledclonemethod',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\__clone',
        1 => 'phpunit\\framework\\mockobject\\__phpunit_state',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Runtime/Api/ErrorCloneMethod.php' => 
    array (
      0 => '589cb16de66a7d19d6ebff70fb50d1374c2e1661c0748473c2b98da068e2e9bc',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\errorclonemethod',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\__clone',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Runtime/Api/GeneratedAsMockObject.php' => 
    array (
      0 => '3dd61777db170ae45549e279384ab1a965c1dabf70dac3d5fdbfbe23f65a1364',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\generatedasmockobject',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\__phpunit_wasgeneratedasmockobject',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Runtime/Api/GeneratedAsTestStub.php' => 
    array (
      0 => 'ac575210f42dd2c9fcd867c8d2e4030574e9c2ea9a423b04a580466f670da489',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\generatedasteststub',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\__phpunit_wasgeneratedasmockobject',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Runtime/Api/Method.php' => 
    array (
      0 => 'a427d31a5176661dd8273d069813778a0aad8880dd9521b69fdd7ffe66f95906',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\method',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\__phpunit_getinvocationhandler',
        1 => 'phpunit\\framework\\mockobject\\method',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Runtime/Api/MockObjectApi.php' => 
    array (
      0 => '22bd0a3587bb6fb902f73134881a8bc80f4cb06c5cdba0c530259892873db2ba',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\mockobjectapi',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\__phpunit_hasmatchers',
        1 => 'phpunit\\framework\\mockobject\\__phpunit_verify',
        2 => 'phpunit\\framework\\mockobject\\__phpunit_state',
        3 => 'phpunit\\framework\\mockobject\\__phpunit_getinvocationhandler',
        4 => 'phpunit\\framework\\mockobject\\__phpunit_unsetinvocationmocker',
        5 => 'phpunit\\framework\\mockobject\\expects',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Runtime/Api/MutableStubApi.php' => 
    array (
      0 => '38ef749b80a14b5700ad6c00d737ce55c9d659a77306d8ac665a92030ff8a632',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\mutablestubapi',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\__phpunit_state',
        1 => 'phpunit\\framework\\mockobject\\__phpunit_getinvocationhandler',
        2 => 'phpunit\\framework\\mockobject\\__phpunit_unsetinvocationmocker',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Runtime/Api/ProxiedCloneMethod.php' => 
    array (
      0 => '6f242c790b423fafdfca4e86715bef20c904a5d3f68dab79675bcd48494b64e5',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\proxiedclonemethod',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\__clone',
        1 => 'phpunit\\framework\\mockobject\\__phpunit_state',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Runtime/Api/StubApi.php' => 
    array (
      0 => 'f85d5e77d7fe372cecc283de38585cb4dff30cebbee18ebe9c15c8fef0acb8d4',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\stubapi',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\__phpunit_state',
        1 => 'phpunit\\framework\\mockobject\\__phpunit_getinvocationhandler',
        2 => 'phpunit\\framework\\mockobject\\__phpunit_unsetinvocationmocker',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Runtime/Api/TestDoubleState.php' => 
    array (
      0 => '503c8c39afb66cee69fa4dc54be4a6aacbffc5858410bb59669e3e1d70c050e4',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\testdoublestate',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\__construct',
        1 => 'phpunit\\framework\\mockobject\\invocationhandler',
        2 => 'phpunit\\framework\\mockobject\\cloneinvocationhandler',
        3 => 'phpunit\\framework\\mockobject\\unsetinvocationhandler',
        4 => 'phpunit\\framework\\mockobject\\setproxytarget',
        5 => 'phpunit\\framework\\mockobject\\proxytarget',
        6 => 'phpunit\\framework\\mockobject\\deprecationwasemittedfor',
        7 => 'phpunit\\framework\\mockobject\\wasdeprecationalreadyemittedfor',
        8 => 'phpunit\\framework\\mockobject\\configurablemethods',
        9 => 'phpunit\\framework\\mockobject\\generatereturnvalues',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Runtime/Builder/Identity.php' => 
    array (
      0 => '9585a5679a66690e8a24230a2011652322600867ebd01f68626f0c1a27b3f065',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\builder\\identity',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\builder\\id',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Runtime/Builder/InvocationMocker.php' => 
    array (
      0 => '3eabb61e8638bc6e5f74c56a9d34fc09acec45bdfc6fbde9620bbb7e624ae544',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\builder\\invocationmocker',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\builder\\__construct',
        1 => 'phpunit\\framework\\mockobject\\builder\\id',
        2 => 'phpunit\\framework\\mockobject\\builder\\will',
        3 => 'phpunit\\framework\\mockobject\\builder\\willreturn',
        4 => 'phpunit\\framework\\mockobject\\builder\\willreturnreference',
        5 => 'phpunit\\framework\\mockobject\\builder\\willreturnmap',
        6 => 'phpunit\\framework\\mockobject\\builder\\willreturnargument',
        7 => 'phpunit\\framework\\mockobject\\builder\\willreturncallback',
        8 => 'phpunit\\framework\\mockobject\\builder\\willreturnself',
        9 => 'phpunit\\framework\\mockobject\\builder\\willreturnonconsecutivecalls',
        10 => 'phpunit\\framework\\mockobject\\builder\\willthrowexception',
        11 => 'phpunit\\framework\\mockobject\\builder\\after',
        12 => 'phpunit\\framework\\mockobject\\builder\\with',
        13 => 'phpunit\\framework\\mockobject\\builder\\withanyparameters',
        14 => 'phpunit\\framework\\mockobject\\builder\\method',
        15 => 'phpunit\\framework\\mockobject\\builder\\ensureparameterscanbeconfigured',
        16 => 'phpunit\\framework\\mockobject\\builder\\configuredmethod',
        17 => 'phpunit\\framework\\mockobject\\builder\\ensuretypeofreturnvalues',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Runtime/Builder/InvocationStubber.php' => 
    array (
      0 => '426711c435e976b1818aac9e9d48929471ef0c0fa03b8c236c8ed48719436a4d',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\builder\\invocationstubber',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\builder\\will',
        1 => 'phpunit\\framework\\mockobject\\builder\\willreturn',
        2 => 'phpunit\\framework\\mockobject\\builder\\willreturnreference',
        3 => 'phpunit\\framework\\mockobject\\builder\\willreturnmap',
        4 => 'phpunit\\framework\\mockobject\\builder\\willreturnargument',
        5 => 'phpunit\\framework\\mockobject\\builder\\willreturncallback',
        6 => 'phpunit\\framework\\mockobject\\builder\\willreturnself',
        7 => 'phpunit\\framework\\mockobject\\builder\\willreturnonconsecutivecalls',
        8 => 'phpunit\\framework\\mockobject\\builder\\willthrowexception',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Runtime/Builder/MethodNameMatch.php' => 
    array (
      0 => 'b4f837338a86292f79615667e15892e9d330e784abdceb28c375c5ec3dde4908',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\builder\\methodnamematch',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\builder\\method',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Runtime/Builder/ParametersMatch.php' => 
    array (
      0 => 'a38732ba636f5dcaf358f1c01b02ff7557e900822aaf14978ec99b3062791b4a',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\builder\\parametersmatch',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\builder\\after',
        1 => 'phpunit\\framework\\mockobject\\builder\\with',
        2 => 'phpunit\\framework\\mockobject\\builder\\withanyparameters',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Runtime/Builder/Stub.php' => 
    array (
      0 => '4731fafa80251a02b89e12cf5a3aee78fb24c9ecbb6d52b04f3b984c8298349e',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\builder\\stub',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\builder\\will',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Runtime/Interface/MockObject.php' => 
    array (
      0 => '435a85c07d087814bec6d3c124335a9f9b67777065ba5f58c0796108f3832166',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\mockobject',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\expects',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Runtime/Interface/MockObjectInternal.php' => 
    array (
      0 => '9eea1bab4a9064355323fa63dbefa6c18beb70a5cad9d353d2fd775eed0f5e41',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\mockobjectinternal',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\__phpunit_hasmatchers',
        1 => 'phpunit\\framework\\mockobject\\__phpunit_verify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Runtime/Interface/Stub.php' => 
    array (
      0 => '33dad69469d99b64cf86563bc39fa8323fadf7a7f44870f15dd0e1bb1134a5e1',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\stub',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Runtime/Interface/StubInternal.php' => 
    array (
      0 => '77c0631cb3ec7fdc427d9f1045b76683744d10215bcba10ab8482761be955335',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\stubinternal',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\__phpunit_state',
        1 => 'phpunit\\framework\\mockobject\\__phpunit_getinvocationhandler',
        2 => 'phpunit\\framework\\mockobject\\__phpunit_unsetinvocationmocker',
        3 => 'phpunit\\framework\\mockobject\\__phpunit_wasgeneratedasmockobject',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Runtime/Invocation.php' => 
    array (
      0 => 'ef9ee31d1c161944a52d0c3fcccac102e3b4457773a2ea71cab39699075763c9',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\invocation',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\__construct',
        1 => 'phpunit\\framework\\mockobject\\classname',
        2 => 'phpunit\\framework\\mockobject\\methodname',
        3 => 'phpunit\\framework\\mockobject\\parameters',
        4 => 'phpunit\\framework\\mockobject\\generatereturnvalue',
        5 => 'phpunit\\framework\\mockobject\\tostring',
        6 => 'phpunit\\framework\\mockobject\\object',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Runtime/InvocationHandler.php' => 
    array (
      0 => 'a8286572f6dce75618d8101d97f242ef390bb1abda0d8bd1a0ee0544902d04de',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\invocationhandler',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\__construct',
        1 => 'phpunit\\framework\\mockobject\\hasmatchers',
        2 => 'phpunit\\framework\\mockobject\\lookupmatcher',
        3 => 'phpunit\\framework\\mockobject\\registermatcher',
        4 => 'phpunit\\framework\\mockobject\\expects',
        5 => 'phpunit\\framework\\mockobject\\invoke',
        6 => 'phpunit\\framework\\mockobject\\verify',
        7 => 'phpunit\\framework\\mockobject\\addmatcher',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Runtime/Matcher.php' => 
    array (
      0 => '9bf505dc61ca8f9ce5a7aa78404fa302120b4a63bab258d5703f8e46966c4546',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\matcher',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\__construct',
        1 => 'phpunit\\framework\\mockobject\\hasmatchers',
        2 => 'phpunit\\framework\\mockobject\\hasmethodnamerule',
        3 => 'phpunit\\framework\\mockobject\\methodnamerule',
        4 => 'phpunit\\framework\\mockobject\\setmethodnamerule',
        5 => 'phpunit\\framework\\mockobject\\hasparametersrule',
        6 => 'phpunit\\framework\\mockobject\\setparametersrule',
        7 => 'phpunit\\framework\\mockobject\\setstub',
        8 => 'phpunit\\framework\\mockobject\\setaftermatchbuilderid',
        9 => 'phpunit\\framework\\mockobject\\invoked',
        10 => 'phpunit\\framework\\mockobject\\matches',
        11 => 'phpunit\\framework\\mockobject\\verify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Runtime/MethodNameConstraint.php' => 
    array (
      0 => '22a5d142e2ffbb2d6f2e5b65471576ac2a0d3462687fa6c6df62bffe2c90527d',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\methodnameconstraint',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\__construct',
        1 => 'phpunit\\framework\\mockobject\\tostring',
        2 => 'phpunit\\framework\\mockobject\\matches',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Runtime/PropertyHook/PropertyGetHook.php' => 
    array (
      0 => 'ba5b1fef5a6b9b28f8d2c7389c1486b6bb26a5096ac8751082ceb3611c65e08a',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\runtime\\propertygethook',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\runtime\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Runtime/PropertyHook/PropertyHook.php' => 
    array (
      0 => '8d4b1f3b8fe3453ff5c53817fe941af0ee38837e9613fe9f42d0543178a81cea',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\runtime\\propertyhook',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\runtime\\get',
        1 => 'phpunit\\framework\\mockobject\\runtime\\set',
        2 => 'phpunit\\framework\\mockobject\\runtime\\__construct',
        3 => 'phpunit\\framework\\mockobject\\runtime\\propertyname',
        4 => 'phpunit\\framework\\mockobject\\runtime\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Runtime/PropertyHook/PropertySetHook.php' => 
    array (
      0 => 'd57b3792a6ea940206bb29cf758af27305966a99eed31a5c212e45ef9cd2a4e5',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\runtime\\propertysethook',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\runtime\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Runtime/ReturnValueGenerator.php' => 
    array (
      0 => '279b0dff181bdc8f9cb146628ea4803581966a8698cb1619d131a0f8e8bc02eb',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\returnvaluegenerator',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\generate',
        1 => 'phpunit\\framework\\mockobject\\onlyinterfaces',
        2 => 'phpunit\\framework\\mockobject\\newinstanceof',
        3 => 'phpunit\\framework\\mockobject\\testdoublefor',
        4 => 'phpunit\\framework\\mockobject\\testdoubleforintersectionofinterfaces',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Runtime/Rule/AnyInvokedCount.php' => 
    array (
      0 => '6387b00176b8154ad0281e61490490e42c99d507b5cdc576de94c07b044e4cde',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\rule\\anyinvokedcount',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\rule\\tostring',
        1 => 'phpunit\\framework\\mockobject\\rule\\verify',
        2 => 'phpunit\\framework\\mockobject\\rule\\matches',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Runtime/Rule/AnyParameters.php' => 
    array (
      0 => 'c0f8fabc60cf568d19f837710edcec431b87fab6491d6bb169ce990d187dea89',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\rule\\anyparameters',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\rule\\apply',
        1 => 'phpunit\\framework\\mockobject\\rule\\verify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Runtime/Rule/InvocationOrder.php' => 
    array (
      0 => '98fed03967fa117cb30e5402be3fe679745ec46f330ef1f3baa1ed0e109602a9',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\rule\\invocationorder',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\rule\\numberofinvocations',
        1 => 'phpunit\\framework\\mockobject\\rule\\hasbeeninvoked',
        2 => 'phpunit\\framework\\mockobject\\rule\\invoked',
        3 => 'phpunit\\framework\\mockobject\\rule\\matches',
        4 => 'phpunit\\framework\\mockobject\\rule\\verify',
        5 => 'phpunit\\framework\\mockobject\\rule\\invokeddo',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Runtime/Rule/InvokedAtLeastCount.php' => 
    array (
      0 => '01d8fb6859365053f7fd02d32b035a1e4633791807f1e6e3921e412d2656d1a4',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\rule\\invokedatleastcount',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\rule\\__construct',
        1 => 'phpunit\\framework\\mockobject\\rule\\tostring',
        2 => 'phpunit\\framework\\mockobject\\rule\\verify',
        3 => 'phpunit\\framework\\mockobject\\rule\\matches',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Runtime/Rule/InvokedAtLeastOnce.php' => 
    array (
      0 => 'c40e7b62ff50f8ee49289957b67f8dc9eaf667a69dc2a3ac2fe3219cf7b6a8b9',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\rule\\invokedatleastonce',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\rule\\tostring',
        1 => 'phpunit\\framework\\mockobject\\rule\\verify',
        2 => 'phpunit\\framework\\mockobject\\rule\\matches',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Runtime/Rule/InvokedAtMostCount.php' => 
    array (
      0 => '55ee74f1e86a4d5b585eedcb289bc79fe008fbef1dab2414f88e24c64002b665',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\rule\\invokedatmostcount',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\rule\\__construct',
        1 => 'phpunit\\framework\\mockobject\\rule\\tostring',
        2 => 'phpunit\\framework\\mockobject\\rule\\verify',
        3 => 'phpunit\\framework\\mockobject\\rule\\matches',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Runtime/Rule/InvokedCount.php' => 
    array (
      0 => '1b59f15a510d35991303454a04d8ea6fcb2fd85536fb02a9843324e8896e33d6',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\rule\\invokedcount',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\rule\\__construct',
        1 => 'phpunit\\framework\\mockobject\\rule\\isnever',
        2 => 'phpunit\\framework\\mockobject\\rule\\tostring',
        3 => 'phpunit\\framework\\mockobject\\rule\\matches',
        4 => 'phpunit\\framework\\mockobject\\rule\\verify',
        5 => 'phpunit\\framework\\mockobject\\rule\\invokeddo',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Runtime/Rule/MethodName.php' => 
    array (
      0 => '3b9d7ecee6031597f1dc8cf29d09978ab9aad6656b7943ee97d35ed619c5298a',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\rule\\methodname',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\rule\\__construct',
        1 => 'phpunit\\framework\\mockobject\\rule\\tostring',
        2 => 'phpunit\\framework\\mockobject\\rule\\matches',
        3 => 'phpunit\\framework\\mockobject\\rule\\matchesname',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Runtime/Rule/Parameters.php' => 
    array (
      0 => 'ea5e09d67f525cd79735e786bb47ae8fa0d7294e9eae707a90f2cf432ead3ef8',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\rule\\parameters',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\rule\\__construct',
        1 => 'phpunit\\framework\\mockobject\\rule\\apply',
        2 => 'phpunit\\framework\\mockobject\\rule\\verify',
        3 => 'phpunit\\framework\\mockobject\\rule\\doverify',
        4 => 'phpunit\\framework\\mockobject\\rule\\guardagainstduplicateevaluationofparameterconstraints',
        5 => 'phpunit\\framework\\mockobject\\rule\\incrementassertioncount',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Runtime/Rule/ParametersRule.php' => 
    array (
      0 => '97fe829daadb6cf58807d9914e5647b1918c42012d03e8b898c8718f37383fcc',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\rule\\parametersrule',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\rule\\apply',
        1 => 'phpunit\\framework\\mockobject\\rule\\verify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Runtime/Stub/ConsecutiveCalls.php' => 
    array (
      0 => 'af423f8b3ba9797cf817e39348aad570cbf9b35e9c86ddbc1d431578a1902652',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\stub\\consecutivecalls',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\stub\\__construct',
        1 => 'phpunit\\framework\\mockobject\\stub\\invoke',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Runtime/Stub/Exception.php' => 
    array (
      0 => '6a02a00d7169b1511615cb9193566a1bb820c58095459d65a02f2cfaddf82c7d',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\stub\\exception',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\stub\\__construct',
        1 => 'phpunit\\framework\\mockobject\\stub\\invoke',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Runtime/Stub/ReturnArgument.php' => 
    array (
      0 => '697bb7203d81071ac8b647857ca2407cdb0a9500017653be727e77d2960dbd24',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\stub\\returnargument',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\stub\\__construct',
        1 => 'phpunit\\framework\\mockobject\\stub\\invoke',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Runtime/Stub/ReturnCallback.php' => 
    array (
      0 => 'f32af886e8d37d7f3ab7a0a5d30cf94b1dd6f361be1f096de8bd5d0759c76ae8',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\stub\\returncallback',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\stub\\__construct',
        1 => 'phpunit\\framework\\mockobject\\stub\\invoke',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Runtime/Stub/ReturnReference.php' => 
    array (
      0 => '83ed35b5819a931e743ed666962273d5663e63fc7543a89c1e1123c6ea3d9858',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\stub\\returnreference',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\stub\\__construct',
        1 => 'phpunit\\framework\\mockobject\\stub\\invoke',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Runtime/Stub/ReturnSelf.php' => 
    array (
      0 => 'e667a6cf4b864e8046c51b366c24109b1a9e0529636c74f2cd953cd4052e4608',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\stub\\returnself',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\stub\\invoke',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Runtime/Stub/ReturnStub.php' => 
    array (
      0 => '10e2f707fb9a900cec3331fff614c8f6ea2e1bb350dcacf39208d5d0f7d878db',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\stub\\returnstub',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\stub\\__construct',
        1 => 'phpunit\\framework\\mockobject\\stub\\invoke',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Runtime/Stub/ReturnValueMap.php' => 
    array (
      0 => '09869e779362cce155aafc3f0d782be72f85f147066f3f98e191dfd546a19c2f',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\stub\\returnvaluemap',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\stub\\__construct',
        1 => 'phpunit\\framework\\mockobject\\stub\\invoke',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/MockObject/Runtime/Stub/Stub.php' => 
    array (
      0 => '8fa48f50da167449e2363f2226afe2d70506b45e59952128eb3e58a3e81a5f08',
      1 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\stub\\stub',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\mockobject\\stub\\invoke',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/NativeType.php' => 
    array (
      0 => '97baf86e200e2bcb98e0e98de045f19504dd2c97bfde35004b48c4ef6e961460',
      1 => 
      array (
        0 => 'phpunit\\framework\\nativetype',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Reorderable.php' => 
    array (
      0 => '2907aace3572d057592849e08ae6df7b0de7c87a7e78d1744d380e5c011f96ec',
      1 => 
      array (
        0 => 'phpunit\\framework\\reorderable',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\sortid',
        1 => 'phpunit\\framework\\provides',
        2 => 'phpunit\\framework\\requires',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/SelfDescribing.php' => 
    array (
      0 => '82fc851de6256fdfd21db6bf12a2878403b70c9864af5bb26bae74d4e90e3b74',
      1 => 
      array (
        0 => 'phpunit\\framework\\selfdescribing',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\tostring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Test.php' => 
    array (
      0 => '433ae6e0691452b183a3dcdc6961d0d59a9130af018231621544d72d7148d400',
      1 => 
      array (
        0 => 'phpunit\\framework\\test',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\run',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/TestBuilder.php' => 
    array (
      0 => 'b0e651cd13819c2b7dc006ca852f12788c374fcbc6224a508aef929151c54650',
      1 => 
      array (
        0 => 'phpunit\\framework\\testbuilder',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\build',
        1 => 'phpunit\\framework\\builddataprovidertestsuite',
        2 => 'phpunit\\framework\\configuretestcase',
        3 => 'phpunit\\framework\\backupsettings',
        4 => 'phpunit\\framework\\shouldglobalstatebepreserved',
        5 => 'phpunit\\framework\\shouldtestmethodberuninseparateprocess',
        6 => 'phpunit\\framework\\shouldalltestmethodsoftestclassberuninsingleseparateprocess',
        7 => 'phpunit\\framework\\requirementssatisfied',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/TestCase.php' => 
    array (
      0 => '20c5de1a77fc34c0418a8649fa06b3b06d223329f1bf8edd32f6a8fbbeddee64',
      1 => 
      array (
        0 => 'phpunit\\framework\\testcase',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\__construct',
        1 => 'phpunit\\framework\\setupbeforeclass',
        2 => 'phpunit\\framework\\teardownafterclass',
        3 => 'phpunit\\framework\\setup',
        4 => 'phpunit\\framework\\assertpreconditions',
        5 => 'phpunit\\framework\\assertpostconditions',
        6 => 'phpunit\\framework\\teardown',
        7 => 'phpunit\\framework\\tostring',
        8 => 'phpunit\\framework\\count',
        9 => 'phpunit\\framework\\status',
        10 => 'phpunit\\framework\\run',
        11 => 'phpunit\\framework\\groups',
        12 => 'phpunit\\framework\\setgroups',
        13 => 'phpunit\\framework\\namewithdataset',
        14 => 'phpunit\\framework\\name',
        15 => 'phpunit\\framework\\size',
        16 => 'phpunit\\framework\\hasunexpectedoutput',
        17 => 'phpunit\\framework\\output',
        18 => 'phpunit\\framework\\doesnotperformassertions',
        19 => 'phpunit\\framework\\expectsoutput',
        20 => 'phpunit\\framework\\runbare',
        21 => 'phpunit\\framework\\setdependencies',
        22 => 'phpunit\\framework\\setdependencyinput',
        23 => 'phpunit\\framework\\dependencyinput',
        24 => 'phpunit\\framework\\hasdependencyinput',
        25 => 'phpunit\\framework\\setbackupglobals',
        26 => 'phpunit\\framework\\setbackupglobalsexcludelist',
        27 => 'phpunit\\framework\\setbackupstaticproperties',
        28 => 'phpunit\\framework\\setbackupstaticpropertiesexcludelist',
        29 => 'phpunit\\framework\\setruntestinseparateprocess',
        30 => 'phpunit\\framework\\setrunclassinseparateprocess',
        31 => 'phpunit\\framework\\setpreserveglobalstate',
        32 => 'phpunit\\framework\\setinisolation',
        33 => 'phpunit\\framework\\result',
        34 => 'phpunit\\framework\\setresult',
        35 => 'phpunit\\framework\\registermockobject',
        36 => 'phpunit\\framework\\addtoassertioncount',
        37 => 'phpunit\\framework\\numberofassertionsperformed',
        38 => 'phpunit\\framework\\usesdataprovider',
        39 => 'phpunit\\framework\\dataname',
        40 => 'phpunit\\framework\\datasetasstring',
        41 => 'phpunit\\framework\\datasetasstringwithdata',
        42 => 'phpunit\\framework\\provideddata',
        43 => 'phpunit\\framework\\sortid',
        44 => 'phpunit\\framework\\provides',
        45 => 'phpunit\\framework\\requires',
        46 => 'phpunit\\framework\\setdata',
        47 => 'phpunit\\framework\\valueobjectforevents',
        48 => 'phpunit\\framework\\wasprepared',
        49 => 'phpunit\\framework\\any',
        50 => 'phpunit\\framework\\never',
        51 => 'phpunit\\framework\\atleast',
        52 => 'phpunit\\framework\\atleastonce',
        53 => 'phpunit\\framework\\once',
        54 => 'phpunit\\framework\\exactly',
        55 => 'phpunit\\framework\\atmost',
        56 => 'phpunit\\framework\\returnvalue',
        57 => 'phpunit\\framework\\returnvaluemap',
        58 => 'phpunit\\framework\\returnargument',
        59 => 'phpunit\\framework\\returncallback',
        60 => 'phpunit\\framework\\returnself',
        61 => 'phpunit\\framework\\throwexception',
        62 => 'phpunit\\framework\\onconsecutivecalls',
        63 => 'phpunit\\framework\\getactualoutputforassertion',
        64 => 'phpunit\\framework\\expectoutputregex',
        65 => 'phpunit\\framework\\expectoutputstring',
        66 => 'phpunit\\framework\\expectexception',
        67 => 'phpunit\\framework\\expectexceptioncode',
        68 => 'phpunit\\framework\\expectexceptionmessage',
        69 => 'phpunit\\framework\\expectexceptionmessagematches',
        70 => 'phpunit\\framework\\expectexceptionobject',
        71 => 'phpunit\\framework\\expectnottoperformassertions',
        72 => 'phpunit\\framework\\expectuserdeprecationmessage',
        73 => 'phpunit\\framework\\expectuserdeprecationmessagematches',
        74 => 'phpunit\\framework\\getmockbuilder',
        75 => 'phpunit\\framework\\registercomparator',
        76 => 'phpunit\\framework\\registerfailuretype',
        77 => 'phpunit\\framework\\iniset',
        78 => 'phpunit\\framework\\setlocale',
        79 => 'phpunit\\framework\\createmock',
        80 => 'phpunit\\framework\\createmockforintersectionofinterfaces',
        81 => 'phpunit\\framework\\createconfiguredmock',
        82 => 'phpunit\\framework\\createpartialmock',
        83 => 'phpunit\\framework\\createtestproxy',
        84 => 'phpunit\\framework\\getmockforabstractclass',
        85 => 'phpunit\\framework\\getmockfromwsdl',
        86 => 'phpunit\\framework\\getmockfortrait',
        87 => 'phpunit\\framework\\getobjectfortrait',
        88 => 'phpunit\\framework\\transformexception',
        89 => 'phpunit\\framework\\onnotsuccessfultest',
        90 => 'phpunit\\framework\\runtest',
        91 => 'phpunit\\framework\\verifydeprecationexpectations',
        92 => 'phpunit\\framework\\verifymockobjects',
        93 => 'phpunit\\framework\\checkrequirements',
        94 => 'phpunit\\framework\\handledependencies',
        95 => 'phpunit\\framework\\markerrorforinvaliddependency',
        96 => 'phpunit\\framework\\markskippedformissingdependency',
        97 => 'phpunit\\framework\\startoutputbuffering',
        98 => 'phpunit\\framework\\stopoutputbuffering',
        99 => 'phpunit\\framework\\snapshotglobalerrorexceptionhandlers',
        100 => 'phpunit\\framework\\restoreglobalerrorexceptionhandlers',
        101 => 'phpunit\\framework\\activeerrorhandlers',
        102 => 'phpunit\\framework\\activeexceptionhandlers',
        103 => 'phpunit\\framework\\snapshotglobalstate',
        104 => 'phpunit\\framework\\restoreglobalstate',
        105 => 'phpunit\\framework\\createglobalstatesnapshot',
        106 => 'phpunit\\framework\\compareglobalstatesnapshots',
        107 => 'phpunit\\framework\\compareglobalstatesnapshotpart',
        108 => 'phpunit\\framework\\shouldinvocationmockerbereset',
        109 => 'phpunit\\framework\\unregistercustomcomparators',
        110 => 'phpunit\\framework\\cleanupinisettings',
        111 => 'phpunit\\framework\\cleanuplocalesettings',
        112 => 'phpunit\\framework\\shouldexceptionexpectationsbeverified',
        113 => 'phpunit\\framework\\shouldruninseparateprocess',
        114 => 'phpunit\\framework\\iscallabletestmethod',
        115 => 'phpunit\\framework\\performassertionsonoutput',
        116 => 'phpunit\\framework\\invokebeforeclasshookmethods',
        117 => 'phpunit\\framework\\invokebeforetesthookmethods',
        118 => 'phpunit\\framework\\invokepreconditionhookmethods',
        119 => 'phpunit\\framework\\invokepostconditionhookmethods',
        120 => 'phpunit\\framework\\invokeaftertesthookmethods',
        121 => 'phpunit\\framework\\invokeafterclasshookmethods',
        122 => 'phpunit\\framework\\invokehookmethods',
        123 => 'phpunit\\framework\\methoddoesnotexistorisdeclaredintestcase',
        124 => 'phpunit\\framework\\verifyexceptionexpectations',
        125 => 'phpunit\\framework\\expectedexceptionwasnotraised',
        126 => 'phpunit\\framework\\isregisteredfailure',
        127 => 'phpunit\\framework\\hasexpectationonoutput',
        128 => 'phpunit\\framework\\requirementsnotsatisfied',
        129 => 'phpunit\\framework\\requiresxdebug',
        130 => 'phpunit\\framework\\handleexceptionfrominvokedcountmockobjectrule',
        131 => 'phpunit\\framework\\createstub',
        132 => 'phpunit\\framework\\createstubforintersectionofinterfaces',
        133 => 'phpunit\\framework\\createconfiguredstub',
        134 => 'phpunit\\framework\\generatereturnvaluesfortestdoubles',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/TestRunner/ChildProcessResultProcessor.php' => 
    array (
      0 => 'e4da28d482c3a714e47354490f517bca1b5f7c7f04803d1c08459a1f2ddb7721',
      1 => 
      array (
        0 => 'phpunit\\framework\\childprocessresultprocessor',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\__construct',
        1 => 'phpunit\\framework\\process',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/TestRunner/IsolatedTestRunner.php' => 
    array (
      0 => '8e02aee5bbf0b16a07265209aeb56530050c177fb8e362cf257473292e616a3f',
      1 => 
      array (
        0 => 'phpunit\\framework\\isolatedtestrunner',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\run',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/TestRunner/IsolatedTestRunnerRegistry.php' => 
    array (
      0 => '2838967a1dd29273f8c7c8d4ad9011d64da3ec41534bb597a29eb472567543f3',
      1 => 
      array (
        0 => 'phpunit\\framework\\isolatedtestrunnerregistry',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\run',
        1 => 'phpunit\\framework\\set',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/TestRunner/SeparateProcessTestRunner.php' => 
    array (
      0 => 'afc2e9afcbea306fe248a3caaede2c94bd83b57b01a7d7a975ed0da0e1ca135f',
      1 => 
      array (
        0 => 'phpunit\\framework\\separateprocesstestrunner',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\run',
        1 => 'phpunit\\framework\\sourcemapfileforchildprocess',
        2 => 'phpunit\\framework\\saveconfigurationforchildprocess',
        3 => 'phpunit\\framework\\pathforcachedsourcemap',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/TestRunner/TestRunner.php' => 
    array (
      0 => 'bc1d97902212ff261ace6b72993f4766c17dfcb072f6535b35993f275df75f0c',
      1 => 
      array (
        0 => 'phpunit\\framework\\testrunner',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\__construct',
        1 => 'phpunit\\framework\\run',
        2 => 'phpunit\\framework\\hascoveragemetadata',
        3 => 'phpunit\\framework\\cantimelimitbeenforced',
        4 => 'phpunit\\framework\\shouldtimelimitbeenforced',
        5 => 'phpunit\\framework\\runtestwithtimeout',
        6 => 'phpunit\\framework\\shoulderrorhandlerbeused',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/TestSize/Known.php' => 
    array (
      0 => 'dbaaef54c708617cd6be2fd949967d002989bc90c66961ae9da1eee1b467cbd5',
      1 => 
      array (
        0 => 'phpunit\\framework\\testsize\\known',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\testsize\\isknown',
        1 => 'phpunit\\framework\\testsize\\isgreaterthan',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/TestSize/Large.php' => 
    array (
      0 => 'b0cefa23b0db2da895afa1aeaefb98e7d888c611df8e3f1f45336e1830570de7',
      1 => 
      array (
        0 => 'phpunit\\framework\\testsize\\large',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\testsize\\islarge',
        1 => 'phpunit\\framework\\testsize\\isgreaterthan',
        2 => 'phpunit\\framework\\testsize\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/TestSize/Medium.php' => 
    array (
      0 => '3371dae2997336f9dd6dc03c92c18c51e0a38c59c094458e0381ff42e8815bfd',
      1 => 
      array (
        0 => 'phpunit\\framework\\testsize\\medium',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\testsize\\ismedium',
        1 => 'phpunit\\framework\\testsize\\isgreaterthan',
        2 => 'phpunit\\framework\\testsize\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/TestSize/Small.php' => 
    array (
      0 => '0dfa7286b7af217b058b731e27425def1d6c83f7bc8f3f2dbd613c71ff846e99',
      1 => 
      array (
        0 => 'phpunit\\framework\\testsize\\small',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\testsize\\issmall',
        1 => 'phpunit\\framework\\testsize\\isgreaterthan',
        2 => 'phpunit\\framework\\testsize\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/TestSize/TestSize.php' => 
    array (
      0 => '823ba65e80216e5167c1c6ca6109b641346b13a88c2b6d12f7539e6620e8782b',
      1 => 
      array (
        0 => 'phpunit\\framework\\testsize\\testsize',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\testsize\\unknown',
        1 => 'phpunit\\framework\\testsize\\small',
        2 => 'phpunit\\framework\\testsize\\medium',
        3 => 'phpunit\\framework\\testsize\\large',
        4 => 'phpunit\\framework\\testsize\\isknown',
        5 => 'phpunit\\framework\\testsize\\isunknown',
        6 => 'phpunit\\framework\\testsize\\issmall',
        7 => 'phpunit\\framework\\testsize\\ismedium',
        8 => 'phpunit\\framework\\testsize\\islarge',
        9 => 'phpunit\\framework\\testsize\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/TestSize/Unknown.php' => 
    array (
      0 => 'a3751a2fcc0d0a63e5a7acef2225aabf7a15d92766fcf7e7b87d98f4740b46ba',
      1 => 
      array (
        0 => 'phpunit\\framework\\testsize\\unknown',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\testsize\\isunknown',
        1 => 'phpunit\\framework\\testsize\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/TestStatus/Deprecation.php' => 
    array (
      0 => '4eb7b42ae3031b9a9ad408c91bcbdff1ec38e1c5455dab268a6b3fbc2b768b75',
      1 => 
      array (
        0 => 'phpunit\\framework\\teststatus\\deprecation',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\teststatus\\isdeprecation',
        1 => 'phpunit\\framework\\teststatus\\asint',
        2 => 'phpunit\\framework\\teststatus\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/TestStatus/Error.php' => 
    array (
      0 => '19a2ec8541e122724fc30699401dc20793cf67ae269131bf1d1c752670e7891d',
      1 => 
      array (
        0 => 'phpunit\\framework\\teststatus\\error',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\teststatus\\iserror',
        1 => 'phpunit\\framework\\teststatus\\asint',
        2 => 'phpunit\\framework\\teststatus\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/TestStatus/Failure.php' => 
    array (
      0 => '2d56a4c43dda559a736ad5a3e3c3c99d8454f4e7716d64859aff0289684c2cea',
      1 => 
      array (
        0 => 'phpunit\\framework\\teststatus\\failure',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\teststatus\\isfailure',
        1 => 'phpunit\\framework\\teststatus\\asint',
        2 => 'phpunit\\framework\\teststatus\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/TestStatus/Incomplete.php' => 
    array (
      0 => 'e36f107dc8a586393e566fa5af5da961d846eb78dfbb82fcaefbe764c15b8a1e',
      1 => 
      array (
        0 => 'phpunit\\framework\\teststatus\\incomplete',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\teststatus\\isincomplete',
        1 => 'phpunit\\framework\\teststatus\\asint',
        2 => 'phpunit\\framework\\teststatus\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/TestStatus/Known.php' => 
    array (
      0 => '8d68ae10464b4631038b68826c7226eda35e85d077a6789cf6d8ea2f7c0b99f7',
      1 => 
      array (
        0 => 'phpunit\\framework\\teststatus\\known',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\teststatus\\isknown',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/TestStatus/Notice.php' => 
    array (
      0 => '9c3f8011a1e342c12700f163da794db45131cfa36b3f0033b095a9793e597b9e',
      1 => 
      array (
        0 => 'phpunit\\framework\\teststatus\\notice',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\teststatus\\isnotice',
        1 => 'phpunit\\framework\\teststatus\\asint',
        2 => 'phpunit\\framework\\teststatus\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/TestStatus/Risky.php' => 
    array (
      0 => '775eea4d81f685900e8136d17f6027e5141b0b016205f4b54bc54939040b15f8',
      1 => 
      array (
        0 => 'phpunit\\framework\\teststatus\\risky',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\teststatus\\isrisky',
        1 => 'phpunit\\framework\\teststatus\\asint',
        2 => 'phpunit\\framework\\teststatus\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/TestStatus/Skipped.php' => 
    array (
      0 => 'd7ef3176f67d896df5250cd2f75cc422b26806002af93d364c6b209665dad765',
      1 => 
      array (
        0 => 'phpunit\\framework\\teststatus\\skipped',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\teststatus\\isskipped',
        1 => 'phpunit\\framework\\teststatus\\asint',
        2 => 'phpunit\\framework\\teststatus\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/TestStatus/Success.php' => 
    array (
      0 => 'dfc96d14917011fd735fed406bad64ea87fdec04d876f8bf023fd86158125f3c',
      1 => 
      array (
        0 => 'phpunit\\framework\\teststatus\\success',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\teststatus\\issuccess',
        1 => 'phpunit\\framework\\teststatus\\asint',
        2 => 'phpunit\\framework\\teststatus\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/TestStatus/TestStatus.php' => 
    array (
      0 => '797b729b9000a6c6d48aa64fa73cdfd41381bf3a9218fb1d0a4b20f039759293',
      1 => 
      array (
        0 => 'phpunit\\framework\\teststatus\\teststatus',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\teststatus\\from',
        1 => 'phpunit\\framework\\teststatus\\unknown',
        2 => 'phpunit\\framework\\teststatus\\success',
        3 => 'phpunit\\framework\\teststatus\\skipped',
        4 => 'phpunit\\framework\\teststatus\\incomplete',
        5 => 'phpunit\\framework\\teststatus\\notice',
        6 => 'phpunit\\framework\\teststatus\\deprecation',
        7 => 'phpunit\\framework\\teststatus\\failure',
        8 => 'phpunit\\framework\\teststatus\\error',
        9 => 'phpunit\\framework\\teststatus\\warning',
        10 => 'phpunit\\framework\\teststatus\\risky',
        11 => 'phpunit\\framework\\teststatus\\__construct',
        12 => 'phpunit\\framework\\teststatus\\isknown',
        13 => 'phpunit\\framework\\teststatus\\isunknown',
        14 => 'phpunit\\framework\\teststatus\\issuccess',
        15 => 'phpunit\\framework\\teststatus\\isskipped',
        16 => 'phpunit\\framework\\teststatus\\isincomplete',
        17 => 'phpunit\\framework\\teststatus\\isnotice',
        18 => 'phpunit\\framework\\teststatus\\isdeprecation',
        19 => 'phpunit\\framework\\teststatus\\isfailure',
        20 => 'phpunit\\framework\\teststatus\\iserror',
        21 => 'phpunit\\framework\\teststatus\\iswarning',
        22 => 'phpunit\\framework\\teststatus\\isrisky',
        23 => 'phpunit\\framework\\teststatus\\message',
        24 => 'phpunit\\framework\\teststatus\\ismoreimportantthan',
        25 => 'phpunit\\framework\\teststatus\\asint',
        26 => 'phpunit\\framework\\teststatus\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/TestStatus/Unknown.php' => 
    array (
      0 => '7903b190712d5f5f3a05615541ac005082763f32fa0ff8871f26d4fc91d1a49b',
      1 => 
      array (
        0 => 'phpunit\\framework\\teststatus\\unknown',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\teststatus\\isunknown',
        1 => 'phpunit\\framework\\teststatus\\asint',
        2 => 'phpunit\\framework\\teststatus\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/TestStatus/Warning.php' => 
    array (
      0 => 'c5bca13a1891392126c4940988d4a344378112cce38e119da3c4df6c73bac692',
      1 => 
      array (
        0 => 'phpunit\\framework\\teststatus\\warning',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\teststatus\\iswarning',
        1 => 'phpunit\\framework\\teststatus\\asint',
        2 => 'phpunit\\framework\\teststatus\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/TestSuite.php' => 
    array (
      0 => '42605dcb8c765c4efd9652672d7cc9c3af8fb01f364c5055fd7f6ce8df61cb78',
      1 => 
      array (
        0 => 'phpunit\\framework\\testsuite',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\empty',
        1 => 'phpunit\\framework\\fromclassreflector',
        2 => 'phpunit\\framework\\__construct',
        3 => 'phpunit\\framework\\addtest',
        4 => 'phpunit\\framework\\addtestsuite',
        5 => 'phpunit\\framework\\addtestfile',
        6 => 'phpunit\\framework\\addtestfiles',
        7 => 'phpunit\\framework\\count',
        8 => 'phpunit\\framework\\isempty',
        9 => 'phpunit\\framework\\name',
        10 => 'phpunit\\framework\\groups',
        11 => 'phpunit\\framework\\collect',
        12 => 'phpunit\\framework\\run',
        13 => 'phpunit\\framework\\tests',
        14 => 'phpunit\\framework\\settests',
        15 => 'phpunit\\framework\\marktestsuiteskipped',
        16 => 'phpunit\\framework\\getiterator',
        17 => 'phpunit\\framework\\injectfilter',
        18 => 'phpunit\\framework\\provides',
        19 => 'phpunit\\framework\\requires',
        20 => 'phpunit\\framework\\sortid',
        21 => 'phpunit\\framework\\isfortestclass',
        22 => 'phpunit\\framework\\addtestmethod',
        23 => 'phpunit\\framework\\clearcaches',
        24 => 'phpunit\\framework\\containsonlyvirtualgroups',
        25 => 'phpunit\\framework\\methoddoesnotexistorisdeclaredintestcase',
        26 => 'phpunit\\framework\\throwabletostring',
        27 => 'phpunit\\framework\\invokemethodsbeforefirsttest',
        28 => 'phpunit\\framework\\invokemethodsafterlasttest',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/TestSuiteIterator.php' => 
    array (
      0 => '0ec3f48a6c45242d4a4ee3d6222645442f5fb6dc8eed51b113e213d8a8da4bae',
      1 => 
      array (
        0 => 'phpunit\\framework\\testsuiteiterator',
      ),
      2 => 
      array (
        0 => 'phpunit\\framework\\__construct',
        1 => 'phpunit\\framework\\rewind',
        2 => 'phpunit\\framework\\valid',
        3 => 'phpunit\\framework\\key',
        4 => 'phpunit\\framework\\current',
        5 => 'phpunit\\framework\\next',
        6 => 'phpunit\\framework\\getchildren',
        7 => 'phpunit\\framework\\haschildren',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Logging/EventLogger.php' => 
    array (
      0 => 'c731136ce99c9047dcfeeae600ce4d080ab71dd56d904ef9662de10a3aff4bca',
      1 => 
      array (
        0 => 'phpunit\\logging\\eventlogger',
      ),
      2 => 
      array (
        0 => 'phpunit\\logging\\__construct',
        1 => 'phpunit\\logging\\trace',
        2 => 'phpunit\\logging\\telemetryinfo',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Logging/JUnit/JunitXmlLogger.php' => 
    array (
      0 => 'f62a215c2432c738502b65e18c2e2bd52963201a9604b5b7f2627fd72870bf30',
      1 => 
      array (
        0 => 'phpunit\\logging\\junit\\junitxmllogger',
      ),
      2 => 
      array (
        0 => 'phpunit\\logging\\junit\\__construct',
        1 => 'phpunit\\logging\\junit\\flush',
        2 => 'phpunit\\logging\\junit\\testsuitestarted',
        3 => 'phpunit\\logging\\junit\\testsuitefinished',
        4 => 'phpunit\\logging\\junit\\testpreparationstarted',
        5 => 'phpunit\\logging\\junit\\testpreparationfailed',
        6 => 'phpunit\\logging\\junit\\testprepared',
        7 => 'phpunit\\logging\\junit\\testprintedunexpectedoutput',
        8 => 'phpunit\\logging\\junit\\testfinished',
        9 => 'phpunit\\logging\\junit\\testmarkedincomplete',
        10 => 'phpunit\\logging\\junit\\testskipped',
        11 => 'phpunit\\logging\\junit\\testerrored',
        12 => 'phpunit\\logging\\junit\\testfailed',
        13 => 'phpunit\\logging\\junit\\handlefinish',
        14 => 'phpunit\\logging\\junit\\registersubscribers',
        15 => 'phpunit\\logging\\junit\\createdocument',
        16 => 'phpunit\\logging\\junit\\handlefault',
        17 => 'phpunit\\logging\\junit\\handleincompleteorskipped',
        18 => 'phpunit\\logging\\junit\\testasstring',
        19 => 'phpunit\\logging\\junit\\name',
        20 => 'phpunit\\logging\\junit\\createtestcase',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Logging/JUnit/Subscriber/Subscriber.php' => 
    array (
      0 => 'd5f9684e66d6522cbb2300f0e6415ccfe4e1ee0cfbcf28b1c58ff5379d91acd8',
      1 => 
      array (
        0 => 'phpunit\\logging\\junit\\subscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\logging\\junit\\__construct',
        1 => 'phpunit\\logging\\junit\\logger',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Logging/JUnit/Subscriber/TestErroredSubscriber.php' => 
    array (
      0 => 'a3ff150be1d38896269bb31c1b25d90770cc074235372de9023e119236da3fbb',
      1 => 
      array (
        0 => 'phpunit\\logging\\junit\\testerroredsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\logging\\junit\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Logging/JUnit/Subscriber/TestFailedSubscriber.php' => 
    array (
      0 => '2733aeb3637705cbab19a43e768e6ee83983782b2ce1dd26987af5a7c11aceb9',
      1 => 
      array (
        0 => 'phpunit\\logging\\junit\\testfailedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\logging\\junit\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Logging/JUnit/Subscriber/TestFinishedSubscriber.php' => 
    array (
      0 => 'a33e2adc43d25a214656ff86f51b400c3a13057fa8e1700feeae20d3abcac7b5',
      1 => 
      array (
        0 => 'phpunit\\logging\\junit\\testfinishedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\logging\\junit\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Logging/JUnit/Subscriber/TestMarkedIncompleteSubscriber.php' => 
    array (
      0 => '7c5af882516475fd0b9a94deece0aae49fecb16c7e42bbf6c38667c84c68cd07',
      1 => 
      array (
        0 => 'phpunit\\logging\\junit\\testmarkedincompletesubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\logging\\junit\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Logging/JUnit/Subscriber/TestPreparationFailedSubscriber.php' => 
    array (
      0 => '06e32c91d4f21404df4b623e4dde773e892b6e8a7a5dce7743ed1cc01e85c5af',
      1 => 
      array (
        0 => 'phpunit\\logging\\junit\\testpreparationfailedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\logging\\junit\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Logging/JUnit/Subscriber/TestPreparationStartedSubscriber.php' => 
    array (
      0 => '8ed26f230d06a02e87f5915a1845ee631c46b0f698a453882b42877758127192',
      1 => 
      array (
        0 => 'phpunit\\logging\\junit\\testpreparationstartedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\logging\\junit\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Logging/JUnit/Subscriber/TestPreparedSubscriber.php' => 
    array (
      0 => 'fd28b398569574b03db42afb90070f7264880e95bf9db2f81bf30deb6f3f9a57',
      1 => 
      array (
        0 => 'phpunit\\logging\\junit\\testpreparedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\logging\\junit\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Logging/JUnit/Subscriber/TestPrintedUnexpectedOutputSubscriber.php' => 
    array (
      0 => '783a33a0641a926774585a18f2163df296b9610ef71b6e008caddfde2cb9c8bc',
      1 => 
      array (
        0 => 'phpunit\\logging\\junit\\testprintedunexpectedoutputsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\logging\\junit\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Logging/JUnit/Subscriber/TestRunnerExecutionFinishedSubscriber.php' => 
    array (
      0 => 'd36f3f40e686dc81b16830b6fa0cc8c3d643641245bf561ffdcacb82bd00452e',
      1 => 
      array (
        0 => 'phpunit\\logging\\junit\\testrunnerexecutionfinishedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\logging\\junit\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Logging/JUnit/Subscriber/TestSkippedSubscriber.php' => 
    array (
      0 => '02d653809f417c894616c5adba4ddd7ea16022c6a0401551056b31c772ec1a3e',
      1 => 
      array (
        0 => 'phpunit\\logging\\junit\\testskippedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\logging\\junit\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Logging/JUnit/Subscriber/TestSuiteFinishedSubscriber.php' => 
    array (
      0 => '95d500aceffe778469dc3c1520801d23ba6da1fa05e5da3ca88c7c43fd529d46',
      1 => 
      array (
        0 => 'phpunit\\logging\\junit\\testsuitefinishedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\logging\\junit\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Logging/JUnit/Subscriber/TestSuiteStartedSubscriber.php' => 
    array (
      0 => 'ab324ebbba9065669b0986f05bddaf046106e9846ca45252c3428ec5b3564e5b',
      1 => 
      array (
        0 => 'phpunit\\logging\\junit\\testsuitestartedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\logging\\junit\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Logging/TeamCity/Subscriber/Subscriber.php' => 
    array (
      0 => '73d596993c3c1175bc79e25214bb19dcae4ffe69255ebf748b2dfaced5857fc0',
      1 => 
      array (
        0 => 'phpunit\\logging\\teamcity\\subscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\logging\\teamcity\\__construct',
        1 => 'phpunit\\logging\\teamcity\\logger',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Logging/TeamCity/Subscriber/TestConsideredRiskySubscriber.php' => 
    array (
      0 => '3fb76af86fb0cc9055b5938e60bde81c9037504f0041be120a2e879ef8f6bab9',
      1 => 
      array (
        0 => 'phpunit\\logging\\teamcity\\testconsideredriskysubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\logging\\teamcity\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Logging/TeamCity/Subscriber/TestErroredSubscriber.php' => 
    array (
      0 => '16da35fc385f7b45d6509b8c7e54099fba48525e2be30d0e7ad4308846b5e69b',
      1 => 
      array (
        0 => 'phpunit\\logging\\teamcity\\testerroredsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\logging\\teamcity\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Logging/TeamCity/Subscriber/TestFailedSubscriber.php' => 
    array (
      0 => '69421e5b1d09dd83e15b11e520137119d23a9c285adb0fd9a26fac046fd23dd7',
      1 => 
      array (
        0 => 'phpunit\\logging\\teamcity\\testfailedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\logging\\teamcity\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Logging/TeamCity/Subscriber/TestFinishedSubscriber.php' => 
    array (
      0 => 'bcf13555e26f30c55c12ad6cb55ed3ecff589a1b65f4a4ae3151e1e5e11ab542',
      1 => 
      array (
        0 => 'phpunit\\logging\\teamcity\\testfinishedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\logging\\teamcity\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Logging/TeamCity/Subscriber/TestMarkedIncompleteSubscriber.php' => 
    array (
      0 => '1a89934f7fe40a66e751e3b7d090d601b2069d37b9c93c6f7dd66796fda725bc',
      1 => 
      array (
        0 => 'phpunit\\logging\\teamcity\\testmarkedincompletesubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\logging\\teamcity\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Logging/TeamCity/Subscriber/TestPreparedSubscriber.php' => 
    array (
      0 => 'a6bc835cd1e39e7eafd541703919b7ea822df72e828dd4e76ad86b3924be85f1',
      1 => 
      array (
        0 => 'phpunit\\logging\\teamcity\\testpreparedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\logging\\teamcity\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Logging/TeamCity/Subscriber/TestRunnerExecutionFinishedSubscriber.php' => 
    array (
      0 => 'f30fd7272bd0bdd98237333285aefc4736e2213aa7ae2a2081c3ee2f90cb3f5f',
      1 => 
      array (
        0 => 'phpunit\\logging\\teamcity\\testrunnerexecutionfinishedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\logging\\teamcity\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Logging/TeamCity/Subscriber/TestSkippedSubscriber.php' => 
    array (
      0 => 'fbfa501de0cc123d926da389b3c381ad0503e20baf56eb95f11a0f7a9c6b8a1a',
      1 => 
      array (
        0 => 'phpunit\\logging\\teamcity\\testskippedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\logging\\teamcity\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Logging/TeamCity/Subscriber/TestSuiteBeforeFirstTestMethodErroredSubscriber.php' => 
    array (
      0 => '8829d5d7b82144eb00fc475f10d89e9e580f5b1e19041349902972053179fa97',
      1 => 
      array (
        0 => 'phpunit\\logging\\teamcity\\testsuitebeforefirsttestmethoderroredsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\logging\\teamcity\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Logging/TeamCity/Subscriber/TestSuiteFinishedSubscriber.php' => 
    array (
      0 => 'c80f4a68f33a81febb6798f00196a833c60fbe337f244a3fc78b9926ddc45ab0',
      1 => 
      array (
        0 => 'phpunit\\logging\\teamcity\\testsuitefinishedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\logging\\teamcity\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Logging/TeamCity/Subscriber/TestSuiteSkippedSubscriber.php' => 
    array (
      0 => '2ba5109f89a2ece8ab60936e69ad997d253b47ef1d3fbb138fecdac6e87a72c4',
      1 => 
      array (
        0 => 'phpunit\\logging\\teamcity\\testsuiteskippedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\logging\\teamcity\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Logging/TeamCity/Subscriber/TestSuiteStartedSubscriber.php' => 
    array (
      0 => '87102e82a006348ee90a3f595f57241972f3d74671e334f43363d4a47b6eb9a1',
      1 => 
      array (
        0 => 'phpunit\\logging\\teamcity\\testsuitestartedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\logging\\teamcity\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Logging/TeamCity/TeamCityLogger.php' => 
    array (
      0 => '39a674f555f40fa9f2e99f9996d200510f14f53ef576e6f03db439aa0731f95d',
      1 => 
      array (
        0 => 'phpunit\\logging\\teamcity\\teamcitylogger',
      ),
      2 => 
      array (
        0 => 'phpunit\\logging\\teamcity\\__construct',
        1 => 'phpunit\\logging\\teamcity\\testsuitestarted',
        2 => 'phpunit\\logging\\teamcity\\testsuitefinished',
        3 => 'phpunit\\logging\\teamcity\\testprepared',
        4 => 'phpunit\\logging\\teamcity\\testmarkedincomplete',
        5 => 'phpunit\\logging\\teamcity\\testskipped',
        6 => 'phpunit\\logging\\teamcity\\testsuiteskipped',
        7 => 'phpunit\\logging\\teamcity\\beforefirsttestmethoderrored',
        8 => 'phpunit\\logging\\teamcity\\testerrored',
        9 => 'phpunit\\logging\\teamcity\\testfailed',
        10 => 'phpunit\\logging\\teamcity\\testconsideredrisky',
        11 => 'phpunit\\logging\\teamcity\\testfinished',
        12 => 'phpunit\\logging\\teamcity\\flush',
        13 => 'phpunit\\logging\\teamcity\\registersubscribers',
        14 => 'phpunit\\logging\\teamcity\\setflowid',
        15 => 'phpunit\\logging\\teamcity\\writemessage',
        16 => 'phpunit\\logging\\teamcity\\duration',
        17 => 'phpunit\\logging\\teamcity\\escape',
        18 => 'phpunit\\logging\\teamcity\\message',
        19 => 'phpunit\\logging\\teamcity\\details',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Logging/TestDox/HtmlRenderer.php' => 
    array (
      0 => '267bdb802310fd4ebf93ddb4a770be6d5aa0ffde646185f474d797d1ac43c518',
      1 => 
      array (
        0 => 'phpunit\\logging\\testdox\\htmlrenderer',
      ),
      2 => 
      array (
        0 => 'phpunit\\logging\\testdox\\render',
        1 => 'phpunit\\logging\\testdox\\reduce',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Logging/TestDox/NamePrettifier.php' => 
    array (
      0 => '56d5cea44e9ef32d7027029a2d933710f03f3484381d597a5e2782bbf7ae42f8',
      1 => 
      array (
        0 => 'phpunit\\logging\\testdox\\nameprettifier',
      ),
      2 => 
      array (
        0 => 'phpunit\\logging\\testdox\\prettifytestclassname',
        1 => 'phpunit\\logging\\testdox\\prettifytestmethodname',
        2 => 'phpunit\\logging\\testdox\\prettifytestcase',
        3 => 'phpunit\\logging\\testdox\\prettifydataset',
        4 => 'phpunit\\logging\\testdox\\maptestmethodparameternamestoprovideddatavalues',
        5 => 'phpunit\\logging\\testdox\\objecttostring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Logging/TestDox/PlainTextRenderer.php' => 
    array (
      0 => '748038792de4a0ab274d1f2ac4d10bd32fede873964305e2da7c5de27954e529',
      1 => 
      array (
        0 => 'phpunit\\logging\\testdox\\plaintextrenderer',
      ),
      2 => 
      array (
        0 => 'phpunit\\logging\\testdox\\render',
        1 => 'phpunit\\logging\\testdox\\reduce',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Logging/TestDox/TestResult/Subscriber/Subscriber.php' => 
    array (
      0 => '70acf65fe0d03927217b2fea427705c9dc516caa225cf24d49d382b5f66fcfaf',
      1 => 
      array (
        0 => 'phpunit\\logging\\testdox\\subscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\logging\\testdox\\__construct',
        1 => 'phpunit\\logging\\testdox\\collector',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Logging/TestDox/TestResult/Subscriber/TestConsideredRiskySubscriber.php' => 
    array (
      0 => 'c04a5933dfc36388b0015c3253c4619333dbb02182e1287dc15305e72b8d61d5',
      1 => 
      array (
        0 => 'phpunit\\logging\\testdox\\testconsideredriskysubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\logging\\testdox\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Logging/TestDox/TestResult/Subscriber/TestErroredSubscriber.php' => 
    array (
      0 => '0ef8e59c01c1c5091f51e5dfa1ee93907b8c08831bdcf8dcf2b54e79066f7973',
      1 => 
      array (
        0 => 'phpunit\\logging\\testdox\\testerroredsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\logging\\testdox\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Logging/TestDox/TestResult/Subscriber/TestFailedSubscriber.php' => 
    array (
      0 => '10c36df4b19937537fa725c8c972eb31f737e7ae07695339f6c8606c1e6bec37',
      1 => 
      array (
        0 => 'phpunit\\logging\\testdox\\testfailedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\logging\\testdox\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Logging/TestDox/TestResult/Subscriber/TestFinishedSubscriber.php' => 
    array (
      0 => '479d5366fe2ead718299aedfe3caa5a7314eda28dd6deb34b0a3e7d5b97f0d90',
      1 => 
      array (
        0 => 'phpunit\\logging\\testdox\\testfinishedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\logging\\testdox\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Logging/TestDox/TestResult/Subscriber/TestMarkedIncompleteSubscriber.php' => 
    array (
      0 => '65e91b82089e2fd9ba361c0c8ce4ce936d0d99cd2bf010c9c4dcc715289101e5',
      1 => 
      array (
        0 => 'phpunit\\logging\\testdox\\testmarkedincompletesubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\logging\\testdox\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Logging/TestDox/TestResult/Subscriber/TestPassedSubscriber.php' => 
    array (
      0 => '04c5a8de3c5ec49ce8228030b90f1b0564bf9c4cc43a284208ba418a00f5b8bb',
      1 => 
      array (
        0 => 'phpunit\\logging\\testdox\\testpassedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\logging\\testdox\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Logging/TestDox/TestResult/Subscriber/TestPreparedSubscriber.php' => 
    array (
      0 => 'd58f579e2c3b9d1d4f6436cccf8d215ec4fbedc3193f951f1491edd661e3c55f',
      1 => 
      array (
        0 => 'phpunit\\logging\\testdox\\testpreparedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\logging\\testdox\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Logging/TestDox/TestResult/Subscriber/TestSkippedSubscriber.php' => 
    array (
      0 => '794f1c060cbd33561bffc60e73905403269b63a3a97113f5e724db9839b4746c',
      1 => 
      array (
        0 => 'phpunit\\logging\\testdox\\testskippedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\logging\\testdox\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Logging/TestDox/TestResult/Subscriber/TestTriggeredDeprecationSubscriber.php' => 
    array (
      0 => 'e789b3ae21e8bdcd01e9ee2124cf6b37c2e10859fc7df4d430997b6e63b1ef53',
      1 => 
      array (
        0 => 'phpunit\\logging\\testdox\\testtriggereddeprecationsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\logging\\testdox\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Logging/TestDox/TestResult/Subscriber/TestTriggeredNoticeSubscriber.php' => 
    array (
      0 => '2ac28dd5a7ffce74ff166f3687560268dbe4364e88bf299cecd02e0d49b044a5',
      1 => 
      array (
        0 => 'phpunit\\logging\\testdox\\testtriggerednoticesubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\logging\\testdox\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Logging/TestDox/TestResult/Subscriber/TestTriggeredPhpDeprecationSubscriber.php' => 
    array (
      0 => '2a31968ec10c7845695ec4a4aa7400dc3542f99713679f0374512baf5378cd87',
      1 => 
      array (
        0 => 'phpunit\\logging\\testdox\\testtriggeredphpdeprecationsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\logging\\testdox\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Logging/TestDox/TestResult/Subscriber/TestTriggeredPhpNoticeSubscriber.php' => 
    array (
      0 => 'ba582bf2ad9c0e1d28715417a51887c5c35af5b6af89351bc82facd0df3a7465',
      1 => 
      array (
        0 => 'phpunit\\logging\\testdox\\testtriggeredphpnoticesubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\logging\\testdox\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Logging/TestDox/TestResult/Subscriber/TestTriggeredPhpWarningSubscriber.php' => 
    array (
      0 => 'b46e70063ed231d6749d89ea9edc4c307aba6fea6c6ab5bbe5ae6d35f54385c4',
      1 => 
      array (
        0 => 'phpunit\\logging\\testdox\\testtriggeredphpwarningsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\logging\\testdox\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Logging/TestDox/TestResult/Subscriber/TestTriggeredPhpunitDeprecationSubscriber.php' => 
    array (
      0 => '8eba1a61373f96a1064f17a12df12bbedaf7b0192f67aa593805b4e491889884',
      1 => 
      array (
        0 => 'phpunit\\logging\\testdox\\testtriggeredphpunitdeprecationsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\logging\\testdox\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Logging/TestDox/TestResult/Subscriber/TestTriggeredPhpunitErrorSubscriber.php' => 
    array (
      0 => '1173a6063271a2d9979b1e868a69b95b8e8bd3a6da8f1214c7649fa31f0d1afe',
      1 => 
      array (
        0 => 'phpunit\\logging\\testdox\\testtriggeredphpuniterrorsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\logging\\testdox\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Logging/TestDox/TestResult/Subscriber/TestTriggeredPhpunitWarningSubscriber.php' => 
    array (
      0 => 'bc89e580ee54e1d0ab6beb0d2311287002574a78d16e12a300645034faeaa373',
      1 => 
      array (
        0 => 'phpunit\\logging\\testdox\\testtriggeredphpunitwarningsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\logging\\testdox\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Logging/TestDox/TestResult/Subscriber/TestTriggeredWarningSubscriber.php' => 
    array (
      0 => 'abdae9d7b1cd2cafe8d7e4c7d80a9e6a9ec2a2b360108ecdc8b584df41050fc3',
      1 => 
      array (
        0 => 'phpunit\\logging\\testdox\\testtriggeredwarningsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\logging\\testdox\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Logging/TestDox/TestResult/TestResult.php' => 
    array (
      0 => '4bad4b5b07fe34bc56bb18166745d5b8fbf8d55004dc8a62ffb8bc6f82432f36',
      1 => 
      array (
        0 => 'phpunit\\logging\\testdox\\testresult',
      ),
      2 => 
      array (
        0 => 'phpunit\\logging\\testdox\\__construct',
        1 => 'phpunit\\logging\\testdox\\test',
        2 => 'phpunit\\logging\\testdox\\status',
        3 => 'phpunit\\logging\\testdox\\hasthrowable',
        4 => 'phpunit\\logging\\testdox\\throwable',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Logging/TestDox/TestResult/TestResultCollection.php' => 
    array (
      0 => '09bc2cd9b5f8677f2e421c2a48e81886e08a3380c39bd83ec9744ed5be1f654c',
      1 => 
      array (
        0 => 'phpunit\\logging\\testdox\\testresultcollection',
      ),
      2 => 
      array (
        0 => 'phpunit\\logging\\testdox\\fromarray',
        1 => 'phpunit\\logging\\testdox\\__construct',
        2 => 'phpunit\\logging\\testdox\\asarray',
        3 => 'phpunit\\logging\\testdox\\getiterator',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Logging/TestDox/TestResult/TestResultCollectionIterator.php' => 
    array (
      0 => 'c368e4268247df33879bdecb9730f03cd34fdb0c0b5da851b45e4c55564cbab4',
      1 => 
      array (
        0 => 'phpunit\\logging\\testdox\\testresultcollectioniterator',
      ),
      2 => 
      array (
        0 => 'phpunit\\logging\\testdox\\__construct',
        1 => 'phpunit\\logging\\testdox\\rewind',
        2 => 'phpunit\\logging\\testdox\\valid',
        3 => 'phpunit\\logging\\testdox\\key',
        4 => 'phpunit\\logging\\testdox\\current',
        5 => 'phpunit\\logging\\testdox\\next',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Logging/TestDox/TestResult/TestResultCollector.php' => 
    array (
      0 => '3c5f7052f6447a81d34b2d209d0e847e6db5bb6986a500f6502dbc0c6a947b16',
      1 => 
      array (
        0 => 'phpunit\\logging\\testdox\\testresultcollector',
      ),
      2 => 
      array (
        0 => 'phpunit\\logging\\testdox\\__construct',
        1 => 'phpunit\\logging\\testdox\\testmethodsgroupedbyclass',
        2 => 'phpunit\\logging\\testdox\\testprepared',
        3 => 'phpunit\\logging\\testdox\\testerrored',
        4 => 'phpunit\\logging\\testdox\\testfailed',
        5 => 'phpunit\\logging\\testdox\\testpassed',
        6 => 'phpunit\\logging\\testdox\\testskipped',
        7 => 'phpunit\\logging\\testdox\\testmarkedincomplete',
        8 => 'phpunit\\logging\\testdox\\testconsideredrisky',
        9 => 'phpunit\\logging\\testdox\\testtriggereddeprecation',
        10 => 'phpunit\\logging\\testdox\\testtriggerednotice',
        11 => 'phpunit\\logging\\testdox\\testtriggeredwarning',
        12 => 'phpunit\\logging\\testdox\\testtriggeredphpdeprecation',
        13 => 'phpunit\\logging\\testdox\\testtriggeredphpnotice',
        14 => 'phpunit\\logging\\testdox\\testtriggeredphpwarning',
        15 => 'phpunit\\logging\\testdox\\testtriggeredphpunitdeprecation',
        16 => 'phpunit\\logging\\testdox\\testtriggeredphpuniterror',
        17 => 'phpunit\\logging\\testdox\\testtriggeredphpunitwarning',
        18 => 'phpunit\\logging\\testdox\\testfinished',
        19 => 'phpunit\\logging\\testdox\\registersubscribers',
        20 => 'phpunit\\logging\\testdox\\updateteststatus',
        21 => 'phpunit\\logging\\testdox\\process',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/After.php' => 
    array (
      0 => 'ed5452c0ed04f0318e0d6771d9e7e48abe25851da734ffaaf86bb90e28c1efb6',
      1 => 
      array (
        0 => 'phpunit\\metadata\\after',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\__construct',
        1 => 'phpunit\\metadata\\isafter',
        2 => 'phpunit\\metadata\\priority',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/AfterClass.php' => 
    array (
      0 => '2efced2c1bc9d22ba08f1654e49a333a3769e17a2e0f8d8024e9827aef1ef7af',
      1 => 
      array (
        0 => 'phpunit\\metadata\\afterclass',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\__construct',
        1 => 'phpunit\\metadata\\isafterclass',
        2 => 'phpunit\\metadata\\priority',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/Api/CodeCoverage.php' => 
    array (
      0 => 'b24d30682d095b0ff61de6dba36dede8c0646631f9fa5c996a1389495a91dc43',
      1 => 
      array (
        0 => 'phpunit\\metadata\\api\\codecoverage',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\api\\linestobecovered',
        1 => 'phpunit\\metadata\\api\\linestobeused',
        2 => 'phpunit\\metadata\\api\\shouldcodecoveragebecollectedfor',
        3 => 'phpunit\\metadata\\api\\maptocodeunits',
        4 => 'phpunit\\metadata\\api\\names',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/Api/DataProvider.php' => 
    array (
      0 => 'f5e9708cceac122fdc9473899cb9dd4966a976cca0081cf70453f8e30dad7c3d',
      1 => 
      array (
        0 => 'phpunit\\metadata\\api\\dataprovider',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\api\\provideddata',
        1 => 'phpunit\\metadata\\api\\dataprovidedbymethods',
        2 => 'phpunit\\metadata\\api\\dataprovidedbymetadata',
        3 => 'phpunit\\metadata\\api\\dataprovidedbytestwithannotation',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/Api/Dependencies.php' => 
    array (
      0 => 'd232b30ba2ffefc0414c3ac6cb6a9d16470c1ee4572419b6e5f6d5ef23f2c583',
      1 => 
      array (
        0 => 'phpunit\\metadata\\api\\dependencies',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\api\\dependencies',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/Api/Groups.php' => 
    array (
      0 => '02849840754e09ed31bb82e4c5159e4121de32a0c156fb614b9cbec88753f687',
      1 => 
      array (
        0 => 'phpunit\\metadata\\api\\groups',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\api\\groups',
        1 => 'phpunit\\metadata\\api\\size',
        2 => 'phpunit\\metadata\\api\\canonicalizename',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/Api/HookMethods.php' => 
    array (
      0 => '5ae028ef254df58b3e26d22ed123c4ad2e5c61483d0d0cbcff489ef50372d698',
      1 => 
      array (
        0 => 'phpunit\\metadata\\api\\hookmethods',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\api\\hookmethods',
        1 => 'phpunit\\metadata\\api\\emptyhookmethodsarray',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/Api/Requirements.php' => 
    array (
      0 => '8070a97b3b63c57fa95c1791f51ca6c4bccace253dc12962b85d5a6f1bf03076',
      1 => 
      array (
        0 => 'phpunit\\metadata\\api\\requirements',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\api\\requirementsnotsatisfiedfor',
        1 => 'phpunit\\metadata\\api\\requiresxdebug',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/BackupGlobals.php' => 
    array (
      0 => '233bab8e1ec065d222614ba518f99c5c2ad50ddc9e21b6123ad1cc16b67c6a6d',
      1 => 
      array (
        0 => 'phpunit\\metadata\\backupglobals',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\__construct',
        1 => 'phpunit\\metadata\\isbackupglobals',
        2 => 'phpunit\\metadata\\enabled',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/BackupStaticProperties.php' => 
    array (
      0 => '652d9477e10244085010fac606e01abee006f454a0024cc1c34d88cfe1e036f5',
      1 => 
      array (
        0 => 'phpunit\\metadata\\backupstaticproperties',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\__construct',
        1 => 'phpunit\\metadata\\isbackupstaticproperties',
        2 => 'phpunit\\metadata\\enabled',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/Before.php' => 
    array (
      0 => '366e40866ef9d64388e33c38058edd2d36d4c3d5ff8db3234f6a1865a9dd01c1',
      1 => 
      array (
        0 => 'phpunit\\metadata\\before',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\__construct',
        1 => 'phpunit\\metadata\\isbefore',
        2 => 'phpunit\\metadata\\priority',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/BeforeClass.php' => 
    array (
      0 => '0e4162f29deb57c7721f60f8fe200eccc2e812bdde47ab6881e0c30a73b890eb',
      1 => 
      array (
        0 => 'phpunit\\metadata\\beforeclass',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\__construct',
        1 => 'phpunit\\metadata\\isbeforeclass',
        2 => 'phpunit\\metadata\\priority',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/Covers.php' => 
    array (
      0 => '37ea792bc513a607d7285e9a8383b1ae83d96c9958f2afc01c97c8d00c97072f',
      1 => 
      array (
        0 => 'phpunit\\metadata\\covers',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\__construct',
        1 => 'phpunit\\metadata\\iscovers',
        2 => 'phpunit\\metadata\\target',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/CoversClass.php' => 
    array (
      0 => '2dd7809e3e38d0e409cc05bceb85d0a58b68e1e7ee11a987ab8ffd99d3cea248',
      1 => 
      array (
        0 => 'phpunit\\metadata\\coversclass',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\__construct',
        1 => 'phpunit\\metadata\\iscoversclass',
        2 => 'phpunit\\metadata\\classname',
        3 => 'phpunit\\metadata\\asstringforcodeunitmapper',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/CoversDefaultClass.php' => 
    array (
      0 => 'd08ed97d004bd6042db33ee0820dc923e9c49b282bab6917e1a21419b8228718',
      1 => 
      array (
        0 => 'phpunit\\metadata\\coversdefaultclass',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\__construct',
        1 => 'phpunit\\metadata\\iscoversdefaultclass',
        2 => 'phpunit\\metadata\\classname',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/CoversFunction.php' => 
    array (
      0 => '8316126f04cd5d37210c5b5a82b34b2a58a0154af698efc36fab891db400964b',
      1 => 
      array (
        0 => 'phpunit\\metadata\\coversfunction',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\__construct',
        1 => 'phpunit\\metadata\\iscoversfunction',
        2 => 'phpunit\\metadata\\functionname',
        3 => 'phpunit\\metadata\\asstringforcodeunitmapper',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/CoversMethod.php' => 
    array (
      0 => '9d9e546ae0450bc72362007e7c34b0fc685f8ffb68cd2136b749d789511c9fbc',
      1 => 
      array (
        0 => 'phpunit\\metadata\\coversmethod',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\__construct',
        1 => 'phpunit\\metadata\\iscoversmethod',
        2 => 'phpunit\\metadata\\classname',
        3 => 'phpunit\\metadata\\methodname',
        4 => 'phpunit\\metadata\\asstringforcodeunitmapper',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/CoversNothing.php' => 
    array (
      0 => '0e89d982baa8f9f6b7477e92af8ba322f4a71878d796707cfc60f591b918713b',
      1 => 
      array (
        0 => 'phpunit\\metadata\\coversnothing',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\iscoversnothing',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/CoversTrait.php' => 
    array (
      0 => 'a13e8f4d9e1fadf357e561d11fe79f1d5c72f158cbe067872d991c6f1ddaee48',
      1 => 
      array (
        0 => 'phpunit\\metadata\\coverstrait',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\__construct',
        1 => 'phpunit\\metadata\\iscoverstrait',
        2 => 'phpunit\\metadata\\traitname',
        3 => 'phpunit\\metadata\\asstringforcodeunitmapper',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/DataProvider.php' => 
    array (
      0 => '8872436e120e4faab936b638ad989fb7e9e75d4f11c6a0f8d30393752dd5dd46',
      1 => 
      array (
        0 => 'phpunit\\metadata\\dataprovider',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\__construct',
        1 => 'phpunit\\metadata\\isdataprovider',
        2 => 'phpunit\\metadata\\classname',
        3 => 'phpunit\\metadata\\methodname',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/DependsOnClass.php' => 
    array (
      0 => '41d534fbcf0116972ae6839b1368b2ebb6a896bf76ba73af80ff3aadf665c9d5',
      1 => 
      array (
        0 => 'phpunit\\metadata\\dependsonclass',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\__construct',
        1 => 'phpunit\\metadata\\isdependsonclass',
        2 => 'phpunit\\metadata\\classname',
        3 => 'phpunit\\metadata\\deepclone',
        4 => 'phpunit\\metadata\\shallowclone',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/DependsOnMethod.php' => 
    array (
      0 => 'cd8bbcf848c812979116ba3c81b18a5f61625240dd97ad1db3ad972a4a3ff2b0',
      1 => 
      array (
        0 => 'phpunit\\metadata\\dependsonmethod',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\__construct',
        1 => 'phpunit\\metadata\\isdependsonmethod',
        2 => 'phpunit\\metadata\\classname',
        3 => 'phpunit\\metadata\\methodname',
        4 => 'phpunit\\metadata\\deepclone',
        5 => 'phpunit\\metadata\\shallowclone',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/DisableReturnValueGenerationForTestDoubles.php' => 
    array (
      0 => '176a8aab5d9f766a8a9534e6f6f03ce2946ea3001322186e25010eaccd4d8566',
      1 => 
      array (
        0 => 'phpunit\\metadata\\disablereturnvaluegenerationfortestdoubles',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\isdisablereturnvaluegenerationfortestdoubles',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/DoesNotPerformAssertions.php' => 
    array (
      0 => '9cb11fdd9ab2b7e322d23b73040edf9ad55723bc99ffc48ca1f0d65d17b684d3',
      1 => 
      array (
        0 => 'phpunit\\metadata\\doesnotperformassertions',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\isdoesnotperformassertions',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/Exception/AnnotationsAreNotSupportedForInternalClassesException.php' => 
    array (
      0 => 'e48ee344c50cb95ddde2023e3b6163ec9ae00b947730249e5729171bdfed0b4d',
      1 => 
      array (
        0 => 'phpunit\\metadata\\annotationsarenotsupportedforinternalclassesexception',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\__construct',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/Exception/Exception.php' => 
    array (
      0 => '6d48d93c317de61b5acb54a8a2f040111c969a338c9a6d55ad57291fd531b1ee',
      1 => 
      array (
        0 => 'phpunit\\metadata\\exception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/Exception/InvalidAttributeException.php' => 
    array (
      0 => '42ff6d0d64f656265b1f08c0cb85e64637ce73624fb132641cb9964fe58824e1',
      1 => 
      array (
        0 => 'phpunit\\metadata\\invalidattributeexception',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\__construct',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/Exception/InvalidVersionRequirementException.php' => 
    array (
      0 => '10f5128cdef283edb5345c01f582644809cc83cb4df605208d2abdf86b46cfd5',
      1 => 
      array (
        0 => 'phpunit\\metadata\\invalidversionrequirementexception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/Exception/NoVersionRequirementException.php' => 
    array (
      0 => '3b5199ad81e11a7d6431fe868d2239bd06871e9f5d9b5d8319045630f362aabe',
      1 => 
      array (
        0 => 'phpunit\\metadata\\noversionrequirementexception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/Exception/ReflectionException.php' => 
    array (
      0 => '678acaa2d995139068084e0dc9a82969c4b11f5aff2852d4ae5385d403f832e1',
      1 => 
      array (
        0 => 'phpunit\\metadata\\reflectionexception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/ExcludeGlobalVariableFromBackup.php' => 
    array (
      0 => '6574365cd99626efc5c02146e66e6b3945ab538e61b16d2f0ae67b03ec55670c',
      1 => 
      array (
        0 => 'phpunit\\metadata\\excludeglobalvariablefrombackup',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\__construct',
        1 => 'phpunit\\metadata\\isexcludeglobalvariablefrombackup',
        2 => 'phpunit\\metadata\\globalvariablename',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/ExcludeStaticPropertyFromBackup.php' => 
    array (
      0 => '1747e9c91e0be5e19e8d9c4bd298871bdbcb3945d60c3d2061b37f6dc031ddc0',
      1 => 
      array (
        0 => 'phpunit\\metadata\\excludestaticpropertyfrombackup',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\__construct',
        1 => 'phpunit\\metadata\\isexcludestaticpropertyfrombackup',
        2 => 'phpunit\\metadata\\classname',
        3 => 'phpunit\\metadata\\propertyname',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/Group.php' => 
    array (
      0 => 'cb94afccda195d205bc3ca557b8171f1006d3b272a64f850361da2732202b108',
      1 => 
      array (
        0 => 'phpunit\\metadata\\group',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\__construct',
        1 => 'phpunit\\metadata\\isgroup',
        2 => 'phpunit\\metadata\\groupname',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/IgnoreDeprecations.php' => 
    array (
      0 => 'ca35941f22846d1139bc79e3498fcaddc4b3430dac7c71c3fb8f6919c268472e',
      1 => 
      array (
        0 => 'phpunit\\metadata\\ignoredeprecations',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\isignoredeprecations',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/IgnorePhpunitDeprecations.php' => 
    array (
      0 => '8f9230f9d70ef40fb47384dd4b1bbe25daf7e7ad4677d326d90a0d65899622f3',
      1 => 
      array (
        0 => 'phpunit\\metadata\\ignorephpunitdeprecations',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\isignorephpunitdeprecations',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/Metadata.php' => 
    array (
      0 => 'dffa4bc617c76fe6bcbded82cb39f9cbf08270b5691ccf2a48d0502fd2d0ccc7',
      1 => 
      array (
        0 => 'phpunit\\metadata\\metadata',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\after',
        1 => 'phpunit\\metadata\\afterclass',
        2 => 'phpunit\\metadata\\backupglobalsonclass',
        3 => 'phpunit\\metadata\\backupglobalsonmethod',
        4 => 'phpunit\\metadata\\backupstaticpropertiesonclass',
        5 => 'phpunit\\metadata\\backupstaticpropertiesonmethod',
        6 => 'phpunit\\metadata\\before',
        7 => 'phpunit\\metadata\\beforeclass',
        8 => 'phpunit\\metadata\\coversclass',
        9 => 'phpunit\\metadata\\coverstrait',
        10 => 'phpunit\\metadata\\coversmethod',
        11 => 'phpunit\\metadata\\coversfunction',
        12 => 'phpunit\\metadata\\coversonclass',
        13 => 'phpunit\\metadata\\coversonmethod',
        14 => 'phpunit\\metadata\\coversdefaultclass',
        15 => 'phpunit\\metadata\\coversnothingonclass',
        16 => 'phpunit\\metadata\\coversnothingonmethod',
        17 => 'phpunit\\metadata\\dataprovider',
        18 => 'phpunit\\metadata\\dependsonclass',
        19 => 'phpunit\\metadata\\dependsonmethod',
        20 => 'phpunit\\metadata\\disablereturnvaluegenerationfortestdoubles',
        21 => 'phpunit\\metadata\\doesnotperformassertionsonclass',
        22 => 'phpunit\\metadata\\doesnotperformassertionsonmethod',
        23 => 'phpunit\\metadata\\excludeglobalvariablefrombackuponclass',
        24 => 'phpunit\\metadata\\excludeglobalvariablefrombackuponmethod',
        25 => 'phpunit\\metadata\\excludestaticpropertyfrombackuponclass',
        26 => 'phpunit\\metadata\\excludestaticpropertyfrombackuponmethod',
        27 => 'phpunit\\metadata\\grouponclass',
        28 => 'phpunit\\metadata\\grouponmethod',
        29 => 'phpunit\\metadata\\ignoredeprecationsonclass',
        30 => 'phpunit\\metadata\\ignoredeprecationsonmethod',
        31 => 'phpunit\\metadata\\ignorephpunitdeprecationsonclass',
        32 => 'phpunit\\metadata\\ignorephpunitdeprecationsonmethod',
        33 => 'phpunit\\metadata\\postcondition',
        34 => 'phpunit\\metadata\\precondition',
        35 => 'phpunit\\metadata\\preserveglobalstateonclass',
        36 => 'phpunit\\metadata\\preserveglobalstateonmethod',
        37 => 'phpunit\\metadata\\requiresfunctiononclass',
        38 => 'phpunit\\metadata\\requiresfunctiononmethod',
        39 => 'phpunit\\metadata\\requiresmethodonclass',
        40 => 'phpunit\\metadata\\requiresmethodonmethod',
        41 => 'phpunit\\metadata\\requiresoperatingsystemonclass',
        42 => 'phpunit\\metadata\\requiresoperatingsystemonmethod',
        43 => 'phpunit\\metadata\\requiresoperatingsystemfamilyonclass',
        44 => 'phpunit\\metadata\\requiresoperatingsystemfamilyonmethod',
        45 => 'phpunit\\metadata\\requiresphponclass',
        46 => 'phpunit\\metadata\\requiresphponmethod',
        47 => 'phpunit\\metadata\\requiresphpextensiononclass',
        48 => 'phpunit\\metadata\\requiresphpextensiononmethod',
        49 => 'phpunit\\metadata\\requiresphpunitonclass',
        50 => 'phpunit\\metadata\\requiresphpunitonmethod',
        51 => 'phpunit\\metadata\\requiresphpunitextensiononclass',
        52 => 'phpunit\\metadata\\requiresphpunitextensiononmethod',
        53 => 'phpunit\\metadata\\requiressettingonclass',
        54 => 'phpunit\\metadata\\requiressettingonmethod',
        55 => 'phpunit\\metadata\\runclassinseparateprocess',
        56 => 'phpunit\\metadata\\runtestsinseparateprocesses',
        57 => 'phpunit\\metadata\\runinseparateprocess',
        58 => 'phpunit\\metadata\\test',
        59 => 'phpunit\\metadata\\testdoxonclass',
        60 => 'phpunit\\metadata\\testdoxonmethod',
        61 => 'phpunit\\metadata\\testwith',
        62 => 'phpunit\\metadata\\usesclass',
        63 => 'phpunit\\metadata\\usestrait',
        64 => 'phpunit\\metadata\\usesfunction',
        65 => 'phpunit\\metadata\\usesmethod',
        66 => 'phpunit\\metadata\\usesonclass',
        67 => 'phpunit\\metadata\\usesonmethod',
        68 => 'phpunit\\metadata\\usesdefaultclass',
        69 => 'phpunit\\metadata\\withouterrorhandler',
        70 => 'phpunit\\metadata\\__construct',
        71 => 'phpunit\\metadata\\isclasslevel',
        72 => 'phpunit\\metadata\\ismethodlevel',
        73 => 'phpunit\\metadata\\isafter',
        74 => 'phpunit\\metadata\\isafterclass',
        75 => 'phpunit\\metadata\\isbackupglobals',
        76 => 'phpunit\\metadata\\isbackupstaticproperties',
        77 => 'phpunit\\metadata\\isbeforeclass',
        78 => 'phpunit\\metadata\\isbefore',
        79 => 'phpunit\\metadata\\iscovers',
        80 => 'phpunit\\metadata\\iscoversclass',
        81 => 'phpunit\\metadata\\iscoversdefaultclass',
        82 => 'phpunit\\metadata\\iscoverstrait',
        83 => 'phpunit\\metadata\\iscoversfunction',
        84 => 'phpunit\\metadata\\iscoversmethod',
        85 => 'phpunit\\metadata\\iscoversnothing',
        86 => 'phpunit\\metadata\\isdataprovider',
        87 => 'phpunit\\metadata\\isdependsonclass',
        88 => 'phpunit\\metadata\\isdependsonmethod',
        89 => 'phpunit\\metadata\\isdisablereturnvaluegenerationfortestdoubles',
        90 => 'phpunit\\metadata\\isdoesnotperformassertions',
        91 => 'phpunit\\metadata\\isexcludeglobalvariablefrombackup',
        92 => 'phpunit\\metadata\\isexcludestaticpropertyfrombackup',
        93 => 'phpunit\\metadata\\isgroup',
        94 => 'phpunit\\metadata\\isignoredeprecations',
        95 => 'phpunit\\metadata\\isignorephpunitdeprecations',
        96 => 'phpunit\\metadata\\isrunclassinseparateprocess',
        97 => 'phpunit\\metadata\\isruninseparateprocess',
        98 => 'phpunit\\metadata\\isruntestsinseparateprocesses',
        99 => 'phpunit\\metadata\\istest',
        100 => 'phpunit\\metadata\\isprecondition',
        101 => 'phpunit\\metadata\\ispostcondition',
        102 => 'phpunit\\metadata\\ispreserveglobalstate',
        103 => 'phpunit\\metadata\\isrequiresmethod',
        104 => 'phpunit\\metadata\\isrequiresfunction',
        105 => 'phpunit\\metadata\\isrequiresoperatingsystem',
        106 => 'phpunit\\metadata\\isrequiresoperatingsystemfamily',
        107 => 'phpunit\\metadata\\isrequiresphp',
        108 => 'phpunit\\metadata\\isrequiresphpextension',
        109 => 'phpunit\\metadata\\isrequiresphpunit',
        110 => 'phpunit\\metadata\\isrequiresphpunitextension',
        111 => 'phpunit\\metadata\\isrequiressetting',
        112 => 'phpunit\\metadata\\istestdox',
        113 => 'phpunit\\metadata\\istestwith',
        114 => 'phpunit\\metadata\\isuses',
        115 => 'phpunit\\metadata\\isusesclass',
        116 => 'phpunit\\metadata\\isusesdefaultclass',
        117 => 'phpunit\\metadata\\isusestrait',
        118 => 'phpunit\\metadata\\isusesfunction',
        119 => 'phpunit\\metadata\\isusesmethod',
        120 => 'phpunit\\metadata\\iswithouterrorhandler',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/MetadataCollection.php' => 
    array (
      0 => 'c58d15b487dd156a76e7ee8c9b87c5b1da6a6af8d1327ff4ef973889d30449dc',
      1 => 
      array (
        0 => 'phpunit\\metadata\\metadatacollection',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\fromarray',
        1 => 'phpunit\\metadata\\__construct',
        2 => 'phpunit\\metadata\\asarray',
        3 => 'phpunit\\metadata\\count',
        4 => 'phpunit\\metadata\\isempty',
        5 => 'phpunit\\metadata\\isnotempty',
        6 => 'phpunit\\metadata\\getiterator',
        7 => 'phpunit\\metadata\\mergewith',
        8 => 'phpunit\\metadata\\isclasslevel',
        9 => 'phpunit\\metadata\\ismethodlevel',
        10 => 'phpunit\\metadata\\isafter',
        11 => 'phpunit\\metadata\\isafterclass',
        12 => 'phpunit\\metadata\\isbackupglobals',
        13 => 'phpunit\\metadata\\isbackupstaticproperties',
        14 => 'phpunit\\metadata\\isbeforeclass',
        15 => 'phpunit\\metadata\\isbefore',
        16 => 'phpunit\\metadata\\iscovers',
        17 => 'phpunit\\metadata\\iscoversclass',
        18 => 'phpunit\\metadata\\iscoversdefaultclass',
        19 => 'phpunit\\metadata\\iscoverstrait',
        20 => 'phpunit\\metadata\\iscoversfunction',
        21 => 'phpunit\\metadata\\iscoversmethod',
        22 => 'phpunit\\metadata\\isexcludeglobalvariablefrombackup',
        23 => 'phpunit\\metadata\\isexcludestaticpropertyfrombackup',
        24 => 'phpunit\\metadata\\iscoversnothing',
        25 => 'phpunit\\metadata\\isdataprovider',
        26 => 'phpunit\\metadata\\isdepends',
        27 => 'phpunit\\metadata\\isdependsonclass',
        28 => 'phpunit\\metadata\\isdependsonmethod',
        29 => 'phpunit\\metadata\\isdisablereturnvaluegenerationfortestdoubles',
        30 => 'phpunit\\metadata\\isdoesnotperformassertions',
        31 => 'phpunit\\metadata\\isgroup',
        32 => 'phpunit\\metadata\\isignoredeprecations',
        33 => 'phpunit\\metadata\\isignorephpunitdeprecations',
        34 => 'phpunit\\metadata\\isrunclassinseparateprocess',
        35 => 'phpunit\\metadata\\isruninseparateprocess',
        36 => 'phpunit\\metadata\\isruntestsinseparateprocesses',
        37 => 'phpunit\\metadata\\istest',
        38 => 'phpunit\\metadata\\isprecondition',
        39 => 'phpunit\\metadata\\ispostcondition',
        40 => 'phpunit\\metadata\\ispreserveglobalstate',
        41 => 'phpunit\\metadata\\isrequiresmethod',
        42 => 'phpunit\\metadata\\isrequiresfunction',
        43 => 'phpunit\\metadata\\isrequiresoperatingsystem',
        44 => 'phpunit\\metadata\\isrequiresoperatingsystemfamily',
        45 => 'phpunit\\metadata\\isrequiresphp',
        46 => 'phpunit\\metadata\\isrequiresphpextension',
        47 => 'phpunit\\metadata\\isrequiresphpunit',
        48 => 'phpunit\\metadata\\isrequiresphpunitextension',
        49 => 'phpunit\\metadata\\isrequiressetting',
        50 => 'phpunit\\metadata\\istestdox',
        51 => 'phpunit\\metadata\\istestwith',
        52 => 'phpunit\\metadata\\isuses',
        53 => 'phpunit\\metadata\\isusesclass',
        54 => 'phpunit\\metadata\\isusesdefaultclass',
        55 => 'phpunit\\metadata\\isusestrait',
        56 => 'phpunit\\metadata\\isusesfunction',
        57 => 'phpunit\\metadata\\isusesmethod',
        58 => 'phpunit\\metadata\\iswithouterrorhandler',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/MetadataCollectionIterator.php' => 
    array (
      0 => '494ec53270222b65080af5b9ac1e83325957b169e3b32299729a73dce8a7acae',
      1 => 
      array (
        0 => 'phpunit\\metadata\\metadatacollectioniterator',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\__construct',
        1 => 'phpunit\\metadata\\rewind',
        2 => 'phpunit\\metadata\\valid',
        3 => 'phpunit\\metadata\\key',
        4 => 'phpunit\\metadata\\current',
        5 => 'phpunit\\metadata\\next',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/Parser/Annotation/DocBlock.php' => 
    array (
      0 => '5c5f2985ba9011241538dbf6ae5897a9f2c0f554811cd2125d64704e9d86e67b',
      1 => 
      array (
        0 => 'phpunit\\metadata\\annotation\\parser\\docblock',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\annotation\\parser\\ofclass',
        1 => 'phpunit\\metadata\\annotation\\parser\\ofmethod',
        2 => 'phpunit\\metadata\\annotation\\parser\\__construct',
        3 => 'phpunit\\metadata\\annotation\\parser\\requirements',
        4 => 'phpunit\\metadata\\annotation\\parser\\symbolannotations',
        5 => 'phpunit\\metadata\\annotation\\parser\\parsedocblock',
        6 => 'phpunit\\metadata\\annotation\\parser\\extractannotationsfromreflector',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/Parser/Annotation/Registry.php' => 
    array (
      0 => '1c6eb76efb12480c2798ac7d1b6a3de429664c260003265e2ab24bfde093818f',
      1 => 
      array (
        0 => 'phpunit\\metadata\\annotation\\parser\\registry',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\annotation\\parser\\getinstance',
        1 => 'phpunit\\metadata\\annotation\\parser\\forclassname',
        2 => 'phpunit\\metadata\\annotation\\parser\\formethod',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/Parser/AnnotationParser.php' => 
    array (
      0 => '64275132007dee2f455a62029346964c098a1fd885e69fdd66cf4457aaff0862',
      1 => 
      array (
        0 => 'phpunit\\metadata\\parser\\annotationparser',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\parser\\forclass',
        1 => 'phpunit\\metadata\\parser\\formethod',
        2 => 'phpunit\\metadata\\parser\\forclassandmethod',
        3 => 'phpunit\\metadata\\parser\\stringtobool',
        4 => 'phpunit\\metadata\\parser\\cleanupcoversorusestarget',
        5 => 'phpunit\\metadata\\parser\\parserequirements',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/Parser/AttributeParser.php' => 
    array (
      0 => '6c000bb748b104f58bbcc1a584d38d1226423c62207df3cec02ec447f59b0e08',
      1 => 
      array (
        0 => 'phpunit\\metadata\\parser\\attributeparser',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\parser\\forclass',
        1 => 'phpunit\\metadata\\parser\\formethod',
        2 => 'phpunit\\metadata\\parser\\forclassandmethod',
        3 => 'phpunit\\metadata\\parser\\issizegroup',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/Parser/CachingParser.php' => 
    array (
      0 => '1cf70352fa63780d6a02cad3df0708fb1d0d406d9348c21d65ff5f4b9912ccfc',
      1 => 
      array (
        0 => 'phpunit\\metadata\\parser\\cachingparser',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\parser\\__construct',
        1 => 'phpunit\\metadata\\parser\\forclass',
        2 => 'phpunit\\metadata\\parser\\formethod',
        3 => 'phpunit\\metadata\\parser\\forclassandmethod',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/Parser/Parser.php' => 
    array (
      0 => '63a52b67b4e530395699b5f9ad52c10e388303e1bb94e33228bb8b78dacc5f89',
      1 => 
      array (
        0 => 'phpunit\\metadata\\parser\\parser',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\parser\\forclass',
        1 => 'phpunit\\metadata\\parser\\formethod',
        2 => 'phpunit\\metadata\\parser\\forclassandmethod',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/Parser/ParserChain.php' => 
    array (
      0 => '5de7e8f96832d817556b544334623fa1690e2de0e19e096f994809125585b15d',
      1 => 
      array (
        0 => 'phpunit\\metadata\\parser\\parserchain',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\parser\\__construct',
        1 => 'phpunit\\metadata\\parser\\forclass',
        2 => 'phpunit\\metadata\\parser\\formethod',
        3 => 'phpunit\\metadata\\parser\\forclassandmethod',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/Parser/Registry.php' => 
    array (
      0 => 'a7d3ba8b661083a9e7ab2afed1accb828fa18331424086b7776675b82d1740ac',
      1 => 
      array (
        0 => 'phpunit\\metadata\\parser\\registry',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\parser\\parser',
        1 => 'phpunit\\metadata\\parser\\build',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/PostCondition.php' => 
    array (
      0 => 'e01b4f6d8914903497339b467769a1d6d4d84c93c1977bcc195ad9570fb59a0f',
      1 => 
      array (
        0 => 'phpunit\\metadata\\postcondition',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\__construct',
        1 => 'phpunit\\metadata\\ispostcondition',
        2 => 'phpunit\\metadata\\priority',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/PreCondition.php' => 
    array (
      0 => '53f1741c539e53311939cec30153f205a4c30746a60d1927c1708eb20274a55f',
      1 => 
      array (
        0 => 'phpunit\\metadata\\precondition',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\__construct',
        1 => 'phpunit\\metadata\\isprecondition',
        2 => 'phpunit\\metadata\\priority',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/PreserveGlobalState.php' => 
    array (
      0 => '7bb401a5e69b588233f1b1915ca7bb639768af636301bfe8979e434f1975386d',
      1 => 
      array (
        0 => 'phpunit\\metadata\\preserveglobalstate',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\__construct',
        1 => 'phpunit\\metadata\\ispreserveglobalstate',
        2 => 'phpunit\\metadata\\enabled',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/RequiresFunction.php' => 
    array (
      0 => '952c547cdd401f42cae83dfd86b5adc1f60cabc47e88afb78b658a3d0e60f732',
      1 => 
      array (
        0 => 'phpunit\\metadata\\requiresfunction',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\__construct',
        1 => 'phpunit\\metadata\\isrequiresfunction',
        2 => 'phpunit\\metadata\\functionname',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/RequiresMethod.php' => 
    array (
      0 => '197d9b3dbd2caf40c60f9e6790916953c1a6c60b0c1eaefef4402d6f748a18f4',
      1 => 
      array (
        0 => 'phpunit\\metadata\\requiresmethod',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\__construct',
        1 => 'phpunit\\metadata\\isrequiresmethod',
        2 => 'phpunit\\metadata\\classname',
        3 => 'phpunit\\metadata\\methodname',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/RequiresOperatingSystem.php' => 
    array (
      0 => '711143115b2567fc18e388ad5e1630ad6c25e85fec313903ce77f60f9efb68e4',
      1 => 
      array (
        0 => 'phpunit\\metadata\\requiresoperatingsystem',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\__construct',
        1 => 'phpunit\\metadata\\isrequiresoperatingsystem',
        2 => 'phpunit\\metadata\\operatingsystem',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/RequiresOperatingSystemFamily.php' => 
    array (
      0 => '0f83d1b4c1b5386c68b8069eb024126ea6d670615305d2a905f836f90f004fd7',
      1 => 
      array (
        0 => 'phpunit\\metadata\\requiresoperatingsystemfamily',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\__construct',
        1 => 'phpunit\\metadata\\isrequiresoperatingsystemfamily',
        2 => 'phpunit\\metadata\\operatingsystemfamily',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/RequiresPhp.php' => 
    array (
      0 => 'fb6a9d845ec3e317aa202c229c4d7199a8f988fdce94725fcb4b03a4b5642d8b',
      1 => 
      array (
        0 => 'phpunit\\metadata\\requiresphp',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\__construct',
        1 => 'phpunit\\metadata\\isrequiresphp',
        2 => 'phpunit\\metadata\\versionrequirement',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/RequiresPhpExtension.php' => 
    array (
      0 => '30000d8e188598aeb929ea0079d327c3bcb49887e171d7cf0d212d83b597fa7d',
      1 => 
      array (
        0 => 'phpunit\\metadata\\requiresphpextension',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\__construct',
        1 => 'phpunit\\metadata\\isrequiresphpextension',
        2 => 'phpunit\\metadata\\extension',
        3 => 'phpunit\\metadata\\hasversionrequirement',
        4 => 'phpunit\\metadata\\versionrequirement',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/RequiresPhpunit.php' => 
    array (
      0 => '6abd993119d7585396549cad78929c4cdfde9c26ee0836e340d32360c983f0e5',
      1 => 
      array (
        0 => 'phpunit\\metadata\\requiresphpunit',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\__construct',
        1 => 'phpunit\\metadata\\isrequiresphpunit',
        2 => 'phpunit\\metadata\\versionrequirement',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/RequiresPhpunitExtension.php' => 
    array (
      0 => 'ea709e4b9e2cf34801707d5f6f5a0a348ec1021a2fd870a554183e236bd465f5',
      1 => 
      array (
        0 => 'phpunit\\metadata\\requiresphpunitextension',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\__construct',
        1 => 'phpunit\\metadata\\isrequiresphpunitextension',
        2 => 'phpunit\\metadata\\extensionclass',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/RequiresSetting.php' => 
    array (
      0 => 'ef264069968cffc40f9bd2e7c798761fac8a669dc6556331688246dbfe887fb7',
      1 => 
      array (
        0 => 'phpunit\\metadata\\requiressetting',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\__construct',
        1 => 'phpunit\\metadata\\isrequiressetting',
        2 => 'phpunit\\metadata\\setting',
        3 => 'phpunit\\metadata\\value',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/RunClassInSeparateProcess.php' => 
    array (
      0 => 'e6577287dde51c8aac43c0f81ad354fc3cd0b77064d29c43daa2077cff43f802',
      1 => 
      array (
        0 => 'phpunit\\metadata\\runclassinseparateprocess',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\isrunclassinseparateprocess',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/RunInSeparateProcess.php' => 
    array (
      0 => '88dd3a93775540135a1b6962eb9e5fa6c8b8fd004ba15338030b15a164248cd1',
      1 => 
      array (
        0 => 'phpunit\\metadata\\runinseparateprocess',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\isruninseparateprocess',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/RunTestsInSeparateProcesses.php' => 
    array (
      0 => '7a1b6c95bf7c7d7f884e25ddc8dd6cbdf4e4c7a02f8d25c64f43c92cfe03e9cc',
      1 => 
      array (
        0 => 'phpunit\\metadata\\runtestsinseparateprocesses',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\isruntestsinseparateprocesses',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/Test.php' => 
    array (
      0 => '2dab284da06f7cc318f64a645138c87a622b754a8153d93dbc0a1d9fd07e1b1b',
      1 => 
      array (
        0 => 'phpunit\\metadata\\test',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\istest',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/TestDox.php' => 
    array (
      0 => 'd67ba50ae0070e5e1ff452b2316b1cda35023274cf2ae0d0e0d6126073a20643',
      1 => 
      array (
        0 => 'phpunit\\metadata\\testdox',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\__construct',
        1 => 'phpunit\\metadata\\istestdox',
        2 => 'phpunit\\metadata\\text',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/TestWith.php' => 
    array (
      0 => 'c2d40204f254f1f3b559736812d1c96ecdd214cc0b21e8dfb1dd5d372c94cc36',
      1 => 
      array (
        0 => 'phpunit\\metadata\\testwith',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\__construct',
        1 => 'phpunit\\metadata\\istestwith',
        2 => 'phpunit\\metadata\\data',
        3 => 'phpunit\\metadata\\hasname',
        4 => 'phpunit\\metadata\\name',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/Uses.php' => 
    array (
      0 => 'ffb262cd6539668e2f27e8bb1bc87ff9414ee61113c8733fba96a2dd4edfdadf',
      1 => 
      array (
        0 => 'phpunit\\metadata\\uses',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\__construct',
        1 => 'phpunit\\metadata\\isuses',
        2 => 'phpunit\\metadata\\target',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/UsesClass.php' => 
    array (
      0 => 'e064685ba9ad8b14307072caf71735bc569d9a708f3ccd02946287fa7614e5bd',
      1 => 
      array (
        0 => 'phpunit\\metadata\\usesclass',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\__construct',
        1 => 'phpunit\\metadata\\isusesclass',
        2 => 'phpunit\\metadata\\classname',
        3 => 'phpunit\\metadata\\asstringforcodeunitmapper',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/UsesDefaultClass.php' => 
    array (
      0 => '382ab20a03f3d519a3fb8d5c60b9b218872e8cb99eebbdb0d753247df054bd8f',
      1 => 
      array (
        0 => 'phpunit\\metadata\\usesdefaultclass',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\__construct',
        1 => 'phpunit\\metadata\\isusesdefaultclass',
        2 => 'phpunit\\metadata\\classname',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/UsesFunction.php' => 
    array (
      0 => 'bde95c46668cf8fa5f2db8dad774d1220b33211150d364b17d2bc13c609f2c65',
      1 => 
      array (
        0 => 'phpunit\\metadata\\usesfunction',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\__construct',
        1 => 'phpunit\\metadata\\isusesfunction',
        2 => 'phpunit\\metadata\\functionname',
        3 => 'phpunit\\metadata\\asstringforcodeunitmapper',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/UsesMethod.php' => 
    array (
      0 => '4b1e1a38d748ec55e6ed41b595d3db84391e46ad0133052e579a0a2130d20c46',
      1 => 
      array (
        0 => 'phpunit\\metadata\\usesmethod',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\__construct',
        1 => 'phpunit\\metadata\\isusesmethod',
        2 => 'phpunit\\metadata\\classname',
        3 => 'phpunit\\metadata\\methodname',
        4 => 'phpunit\\metadata\\asstringforcodeunitmapper',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/UsesTrait.php' => 
    array (
      0 => '39076afb14d50035fc958819074754fa8cde484755377bc00ce96bc7dd7b55aa',
      1 => 
      array (
        0 => 'phpunit\\metadata\\usestrait',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\__construct',
        1 => 'phpunit\\metadata\\isusestrait',
        2 => 'phpunit\\metadata\\traitname',
        3 => 'phpunit\\metadata\\asstringforcodeunitmapper',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/Version/ComparisonRequirement.php' => 
    array (
      0 => '245eb5b9b1a3a041cb93e9c7ffb1059c4c180d4b93a4de971fea4d791a53ef61',
      1 => 
      array (
        0 => 'phpunit\\metadata\\version\\comparisonrequirement',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\version\\__construct',
        1 => 'phpunit\\metadata\\version\\issatisfiedby',
        2 => 'phpunit\\metadata\\version\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/Version/ConstraintRequirement.php' => 
    array (
      0 => 'bb197a2f78d73106d9c20c201c951c775cdc685f3c29abbdc6b8cdc00a557cfd',
      1 => 
      array (
        0 => 'phpunit\\metadata\\version\\constraintrequirement',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\version\\__construct',
        1 => 'phpunit\\metadata\\version\\issatisfiedby',
        2 => 'phpunit\\metadata\\version\\asstring',
        3 => 'phpunit\\metadata\\version\\sanitize',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/Version/Requirement.php' => 
    array (
      0 => 'eb9610e04c6eb3ccb6380fde7015c44d0533c63824b5235da391fadc0b86a8cf',
      1 => 
      array (
        0 => 'phpunit\\metadata\\version\\requirement',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\version\\from',
        1 => 'phpunit\\metadata\\version\\issatisfiedby',
        2 => 'phpunit\\metadata\\version\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Metadata/WithoutErrorHandler.php' => 
    array (
      0 => '01410ebb02a555507e9b05a9ae4ba7500e52ebaedc57c92f72f6f2f11409d4ce',
      1 => 
      array (
        0 => 'phpunit\\metadata\\withouterrorhandler',
      ),
      2 => 
      array (
        0 => 'phpunit\\metadata\\iswithouterrorhandler',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/Baseline/Baseline.php' => 
    array (
      0 => '6f90825a70a32e8f448211eb3131bd8abd34e1285aded15c1124fa617c5a8e93',
      1 => 
      array (
        0 => 'phpunit\\runner\\baseline\\baseline',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\baseline\\add',
        1 => 'phpunit\\runner\\baseline\\has',
        2 => 'phpunit\\runner\\baseline\\groupedbyfileandline',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/Baseline/Exception/CannotLoadBaselineException.php' => 
    array (
      0 => '893aa2fd857fbceb89cf7a605898c7d6b069e0e119cbb6f6bb50f1a3622cdfdd',
      1 => 
      array (
        0 => 'phpunit\\runner\\baseline\\cannotloadbaselineexception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/Baseline/Exception/CannotWriteBaselineException.php' => 
    array (
      0 => '604b8d2c8570ecf5f444ad87d80e6f66f482c563823bef05dda3194413e2d595',
      1 => 
      array (
        0 => 'phpunit\\runner\\baseline\\cannotwritebaselineexception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/Baseline/Exception/FileDoesNotHaveLineException.php' => 
    array (
      0 => '13cc975b87e123782d20a61a98426493cc161bafaec8a9c5210d9032c91975fc',
      1 => 
      array (
        0 => 'phpunit\\runner\\baseline\\filedoesnothavelineexception',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\baseline\\__construct',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/Baseline/Generator.php' => 
    array (
      0 => '03777471b344dc901f4047de95515d7f10360ac15ce879a7c7b7ddafb2005cda',
      1 => 
      array (
        0 => 'phpunit\\runner\\baseline\\generator',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\baseline\\__construct',
        1 => 'phpunit\\runner\\baseline\\baseline',
        2 => 'phpunit\\runner\\baseline\\testtriggeredissue',
        3 => 'phpunit\\runner\\baseline\\restrict',
        4 => 'phpunit\\runner\\baseline\\issuppressionignored',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/Baseline/Issue.php' => 
    array (
      0 => 'bd99271bf895e401bae1a5caf957b3923fb568f0d48b0a5a030555ce316d7f26',
      1 => 
      array (
        0 => 'phpunit\\runner\\baseline\\issue',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\baseline\\from',
        1 => 'phpunit\\runner\\baseline\\__construct',
        2 => 'phpunit\\runner\\baseline\\file',
        3 => 'phpunit\\runner\\baseline\\line',
        4 => 'phpunit\\runner\\baseline\\hash',
        5 => 'phpunit\\runner\\baseline\\description',
        6 => 'phpunit\\runner\\baseline\\equals',
        7 => 'phpunit\\runner\\baseline\\calculatehash',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/Baseline/Reader.php' => 
    array (
      0 => 'e64fd052b13ef9f4ab8f84e5988b2f51452a0441db18e4081718a16c5959e711',
      1 => 
      array (
        0 => 'phpunit\\runner\\baseline\\reader',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\baseline\\read',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/Baseline/RelativePathCalculator.php' => 
    array (
      0 => 'f8c99ac87f74341770152059472a89b06815bc60d3e2309947f3ead8dbdcecc8',
      1 => 
      array (
        0 => 'phpunit\\runner\\baseline\\relativepathcalculator',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\baseline\\__construct',
        1 => 'phpunit\\runner\\baseline\\calculate',
        2 => 'phpunit\\runner\\baseline\\parts',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/Baseline/Subscriber/Subscriber.php' => 
    array (
      0 => 'aec5b7a94b9b772593a704a34930ac274da08ed4776312dd7650f87276231b7c',
      1 => 
      array (
        0 => 'phpunit\\runner\\baseline\\subscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\baseline\\__construct',
        1 => 'phpunit\\runner\\baseline\\generator',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/Baseline/Subscriber/TestTriggeredDeprecationSubscriber.php' => 
    array (
      0 => 'c2b4f1b1578c8138a933cd18ca3c67709c85a00559bd5e19df2f935972207ae6',
      1 => 
      array (
        0 => 'phpunit\\runner\\baseline\\testtriggereddeprecationsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\baseline\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/Baseline/Subscriber/TestTriggeredNoticeSubscriber.php' => 
    array (
      0 => '6a97cd00650bd378825051602964232481e8f2728b4ffb9132e84e3096be184a',
      1 => 
      array (
        0 => 'phpunit\\runner\\baseline\\testtriggerednoticesubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\baseline\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/Baseline/Subscriber/TestTriggeredPhpDeprecationSubscriber.php' => 
    array (
      0 => '31e35c5821b4d9c736ea512a48eea297268d111ae093b51a65f7aae90ce9bc24',
      1 => 
      array (
        0 => 'phpunit\\runner\\baseline\\testtriggeredphpdeprecationsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\baseline\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/Baseline/Subscriber/TestTriggeredPhpNoticeSubscriber.php' => 
    array (
      0 => '6c0a22175c174fb0f209ab10f74a4049a8b4c03c2c5a4af76dbfac135bfa7387',
      1 => 
      array (
        0 => 'phpunit\\runner\\baseline\\testtriggeredphpnoticesubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\baseline\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/Baseline/Subscriber/TestTriggeredPhpWarningSubscriber.php' => 
    array (
      0 => '820b0240e04c07ffca4ffd3b70e83c81d1abebb135a25a26ea9ba5ac7dec719c',
      1 => 
      array (
        0 => 'phpunit\\runner\\baseline\\testtriggeredphpwarningsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\baseline\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/Baseline/Subscriber/TestTriggeredWarningSubscriber.php' => 
    array (
      0 => '29000316f79a5aca9f7bd21a7786b3a1fbb86769164d4542efe74169e1e8d202',
      1 => 
      array (
        0 => 'phpunit\\runner\\baseline\\testtriggeredwarningsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\baseline\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/Baseline/Writer.php' => 
    array (
      0 => '76db711592fb758406ae1d376c50e4510ac90ff464463f69cef115d4a8a9c28e',
      1 => 
      array (
        0 => 'phpunit\\runner\\baseline\\writer',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\baseline\\write',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/CodeCoverage.php' => 
    array (
      0 => '32396d21430ef70929e5baf328db17bff34bf2f3fff21f7f09f1fe8b4858e752',
      1 => 
      array (
        0 => 'phpunit\\runner\\codecoverage',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\instance',
        1 => 'phpunit\\runner\\init',
        2 => 'phpunit\\runner\\isactive',
        3 => 'phpunit\\runner\\codecoverage',
        4 => 'phpunit\\runner\\drivernameandversion',
        5 => 'phpunit\\runner\\start',
        6 => 'phpunit\\runner\\stop',
        7 => 'phpunit\\runner\\deactivate',
        8 => 'phpunit\\runner\\generatereports',
        9 => 'phpunit\\runner\\activate',
        10 => 'phpunit\\runner\\codecoveragegenerationstart',
        11 => 'phpunit\\runner\\codecoveragegenerationsucceeded',
        12 => 'phpunit\\runner\\codecoveragegenerationfailed',
        13 => 'phpunit\\runner\\timer',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/DeprecationCollector/Collector.php' => 
    array (
      0 => '3bf04141232f3395fcf174bddb838874e66cc35cc832fb3607d6a247b63a048d',
      1 => 
      array (
        0 => 'phpunit\\runner\\deprecationcollector\\collector',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\deprecationcollector\\__construct',
        1 => 'phpunit\\runner\\deprecationcollector\\deprecations',
        2 => 'phpunit\\runner\\deprecationcollector\\filtereddeprecations',
        3 => 'phpunit\\runner\\deprecationcollector\\testprepared',
        4 => 'phpunit\\runner\\deprecationcollector\\testtriggereddeprecation',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/DeprecationCollector/Facade.php' => 
    array (
      0 => '2e78775b4285aa1515cfe7d9e276d28640c4f2adc20531bc3c0790744a2a8a48',
      1 => 
      array (
        0 => 'phpunit\\runner\\deprecationcollector\\facade',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\deprecationcollector\\init',
        1 => 'phpunit\\runner\\deprecationcollector\\initforisolation',
        2 => 'phpunit\\runner\\deprecationcollector\\deprecations',
        3 => 'phpunit\\runner\\deprecationcollector\\filtereddeprecations',
        4 => 'phpunit\\runner\\deprecationcollector\\collector',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/DeprecationCollector/InIsolationCollector.php' => 
    array (
      0 => 'b657c0bff803e1056fdf884789facc1d5fadc8be44e707051a142fe53a0d004d',
      1 => 
      array (
        0 => 'phpunit\\runner\\deprecationcollector\\inisolationcollector',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\deprecationcollector\\__construct',
        1 => 'phpunit\\runner\\deprecationcollector\\deprecations',
        2 => 'phpunit\\runner\\deprecationcollector\\filtereddeprecations',
        3 => 'phpunit\\runner\\deprecationcollector\\testtriggereddeprecation',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/DeprecationCollector/Subscriber/Subscriber.php' => 
    array (
      0 => 'c1cdd6ae409a11cc65027020a2594031c286fa154826499784eb018397ed65b4',
      1 => 
      array (
        0 => 'phpunit\\runner\\deprecationcollector\\subscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\deprecationcollector\\__construct',
        1 => 'phpunit\\runner\\deprecationcollector\\collector',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/DeprecationCollector/Subscriber/TestPreparedSubscriber.php' => 
    array (
      0 => '0cf07aa862bd3025bcfc69d5d5b10d691f42cd61d41fffec0dc576e02b51b2b1',
      1 => 
      array (
        0 => 'phpunit\\runner\\deprecationcollector\\testpreparedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\deprecationcollector\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/DeprecationCollector/Subscriber/TestTriggeredDeprecationSubscriber.php' => 
    array (
      0 => '930e9ee334561f00b3a753ba2bd326b82bb105fa0f5970b632b1dc7a29b4c7f3',
      1 => 
      array (
        0 => 'phpunit\\runner\\deprecationcollector\\testtriggereddeprecationsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\deprecationcollector\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/ErrorHandler.php' => 
    array (
      0 => 'c5a8b69f05c8d3cc684dfd7ca59d176505caeeaca58c1e4ae4138ea594b92532',
      1 => 
      array (
        0 => 'phpunit\\runner\\errorhandler',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\instance',
        1 => 'phpunit\\runner\\__construct',
        2 => 'phpunit\\runner\\__invoke',
        3 => 'phpunit\\runner\\enable',
        4 => 'phpunit\\runner\\disable',
        5 => 'phpunit\\runner\\usebaseline',
        6 => 'phpunit\\runner\\usedeprecationtriggers',
        7 => 'phpunit\\runner\\ignoredbybaseline',
        8 => 'phpunit\\runner\\trigger',
        9 => 'phpunit\\runner\\filteredstacktrace',
        10 => 'phpunit\\runner\\guessdeprecationframe',
        11 => 'phpunit\\runner\\errorstacktrace',
        12 => 'phpunit\\runner\\frameisfunction',
        13 => 'phpunit\\runner\\frameismethod',
        14 => 'phpunit\\runner\\stacktrace',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/Exception/ClassCannotBeFoundException.php' => 
    array (
      0 => '5451a110b0881b81f74fc9290c72826737d3704933f5bbec1776df1340f9f2cf',
      1 => 
      array (
        0 => 'phpunit\\runner\\classcannotbefoundexception',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\__construct',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/Exception/ClassDoesNotExtendTestCaseException.php' => 
    array (
      0 => '232fc367825a06dd897c7cdbccd588f3809b3ba6906c5d5a8055bc772da7ada2',
      1 => 
      array (
        0 => 'phpunit\\runner\\classdoesnotextendtestcaseexception',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\__construct',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/Exception/ClassIsAbstractException.php' => 
    array (
      0 => 'f0b6850e762de9cc5a030c839d43f35fd71cbc7aa4e8b7e50d44bd509fd70248',
      1 => 
      array (
        0 => 'phpunit\\runner\\classisabstractexception',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\__construct',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/Exception/CodeCoverageFileExistsException.php' => 
    array (
      0 => 'd5dda35523584b570b5469f13f2f4634cdef42769277fdf1b973a2c57a33017e',
      1 => 
      array (
        0 => 'phpunit\\runner\\codecoveragefileexistsexception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/Exception/DirectoryDoesNotExistException.php' => 
    array (
      0 => '78494a46525d6aa073a37a378950cc74450b5a98d0954b292221737bab76e70b',
      1 => 
      array (
        0 => 'phpunit\\runner\\directorydoesnotexistexception',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\__construct',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/Exception/ErrorException.php' => 
    array (
      0 => 'f6a016b5350e67dbe8ac08d7bab42efbf7f2a7c9e1ef448767d931f3cb718acf',
      1 => 
      array (
        0 => 'phpunit\\runner\\errorexception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/Exception/Exception.php' => 
    array (
      0 => '5df90948f512530f902627631358b02dd497db57308ad03782dfa0c276fe510f',
      1 => 
      array (
        0 => 'phpunit\\runner\\exception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/Exception/FileDoesNotExistException.php' => 
    array (
      0 => 'ad5aebdbd23bfa9dcb06e5c2ba8f7f1119c0f5497fa69e912e41fd859517e6cf',
      1 => 
      array (
        0 => 'phpunit\\runner\\filedoesnotexistexception',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\__construct',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/Exception/InvalidOrderException.php' => 
    array (
      0 => '90f0c09f677fe28e0fffe5597b88d5c6ecfdd4e733cc66d7cdba5d8c79b705ec',
      1 => 
      array (
        0 => 'phpunit\\runner\\invalidorderexception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/Exception/InvalidPhptFileException.php' => 
    array (
      0 => 'd87948b088ac70843522ebc7936ae11b78f7b0e36c40d5b95275d43ad8b692d4',
      1 => 
      array (
        0 => 'phpunit\\runner\\invalidphptfileexception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/Exception/ParameterDoesNotExistException.php' => 
    array (
      0 => '14cc0ca1eaed041b821f1684fb56680278eddbf5d3e9f2248ea46455b7064225',
      1 => 
      array (
        0 => 'phpunit\\runner\\parameterdoesnotexistexception',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\__construct',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/Exception/PhptExternalFileCannotBeLoadedException.php' => 
    array (
      0 => 'fca9a7a56821dbc69fcafa3fea408e16e098a3c728bff8115b0c6302b4cd13e1',
      1 => 
      array (
        0 => 'phpunit\\runner\\phptexternalfilecannotbeloadedexception',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\__construct',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/Exception/UnsupportedPhptSectionException.php' => 
    array (
      0 => '1cf94434f56d269b1c82c0782f549036f489175f89c52a67a6dbaba86fd1db4b',
      1 => 
      array (
        0 => 'phpunit\\runner\\unsupportedphptsectionexception',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\__construct',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/Extension/Extension.php' => 
    array (
      0 => 'f0c35eabe7c4992e57ae645491ed393a431983b339817ae869bcea31feac8d05',
      1 => 
      array (
        0 => 'phpunit\\runner\\extension\\extension',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\extension\\bootstrap',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/Extension/ExtensionBootstrapper.php' => 
    array (
      0 => '87708043877c21123c073ef8e0e8d87e0d0c3baaa087fb7e714f0d2c320ebc1f',
      1 => 
      array (
        0 => 'phpunit\\runner\\extension\\extensionbootstrapper',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\extension\\__construct',
        1 => 'phpunit\\runner\\extension\\bootstrap',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/Extension/Facade.php' => 
    array (
      0 => '553dfb0b4e7781e45e9677f5b0319d88651680c6b86fab6b328f5572bca1331f',
      1 => 
      array (
        0 => 'phpunit\\runner\\extension\\facade',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\extension\\registersubscribers',
        1 => 'phpunit\\runner\\extension\\registersubscriber',
        2 => 'phpunit\\runner\\extension\\registertracer',
        3 => 'phpunit\\runner\\extension\\replaceoutput',
        4 => 'phpunit\\runner\\extension\\replacesoutput',
        5 => 'phpunit\\runner\\extension\\replaceprogressoutput',
        6 => 'phpunit\\runner\\extension\\replacesprogressoutput',
        7 => 'phpunit\\runner\\extension\\replaceresultoutput',
        8 => 'phpunit\\runner\\extension\\replacesresultoutput',
        9 => 'phpunit\\runner\\extension\\requirecodecoveragecollection',
        10 => 'phpunit\\runner\\extension\\requirescodecoveragecollection',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/Extension/ParameterCollection.php' => 
    array (
      0 => '998412e1f44d72edce3a13386ee233462f0a27c5724669a14206458e6656c2f1',
      1 => 
      array (
        0 => 'phpunit\\runner\\extension\\parametercollection',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\extension\\fromarray',
        1 => 'phpunit\\runner\\extension\\__construct',
        2 => 'phpunit\\runner\\extension\\has',
        3 => 'phpunit\\runner\\extension\\get',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/Extension/PharLoader.php' => 
    array (
      0 => '676c29d71e0b49a4286c546fe91e62cf84aef013f46f734d7e0c6ba55940a6ae',
      1 => 
      array (
        0 => 'phpunit\\runner\\extension\\pharloader',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\extension\\loadpharextensionsindirectory',
        1 => 'phpunit\\runner\\extension\\phpunitversion',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/Filter/ExcludeGroupFilterIterator.php' => 
    array (
      0 => 'd1d7a0ced770e44a1a3647d472c3afd85ced3c64186db35e715dc263ef7812db',
      1 => 
      array (
        0 => 'phpunit\\runner\\filter\\excludegroupfilteriterator',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\filter\\doaccept',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/Filter/ExcludeNameFilterIterator.php' => 
    array (
      0 => '4ec29beaa1789d54630a2681663e37ee409b12cc33521513b94a3260bfffad05',
      1 => 
      array (
        0 => 'phpunit\\runner\\filter\\excludenamefilteriterator',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\filter\\doaccept',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/Filter/Factory.php' => 
    array (
      0 => 'bdb7d8c6e7967f5af9429d8378b5dbb16b689e9d90757f3feb86b98591ec4f59',
      1 => 
      array (
        0 => 'phpunit\\runner\\filter\\factory',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\filter\\addtestidfilter',
        1 => 'phpunit\\runner\\filter\\addincludegroupfilter',
        2 => 'phpunit\\runner\\filter\\addexcludegroupfilter',
        3 => 'phpunit\\runner\\filter\\addincludenamefilter',
        4 => 'phpunit\\runner\\filter\\addexcludenamefilter',
        5 => 'phpunit\\runner\\filter\\factory',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/Filter/GroupFilterIterator.php' => 
    array (
      0 => '04879217582f518b1972849ff923c4942916a3eb224fb3ba0c40ab518f52fabe',
      1 => 
      array (
        0 => 'phpunit\\runner\\filter\\groupfilteriterator',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\filter\\__construct',
        1 => 'phpunit\\runner\\filter\\accept',
        2 => 'phpunit\\runner\\filter\\doaccept',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/Filter/IncludeGroupFilterIterator.php' => 
    array (
      0 => '580d01828d7368355bbc56175236a1a97c8361795e24322bbaea4b1ed277dd41',
      1 => 
      array (
        0 => 'phpunit\\runner\\filter\\includegroupfilteriterator',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\filter\\doaccept',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/Filter/IncludeNameFilterIterator.php' => 
    array (
      0 => '398d1b0aa2aed0f4cca0c7516b846cd41e4870065a84885e3a8d4ee91f841dc3',
      1 => 
      array (
        0 => 'phpunit\\runner\\filter\\includenamefilteriterator',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\filter\\doaccept',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/Filter/NameFilterIterator.php' => 
    array (
      0 => '53c246e5f416a39817ac81124cdd64ea8403038d01d7a202e1ffa486fbdf3fa7',
      1 => 
      array (
        0 => 'phpunit\\runner\\filter\\namefilteriterator',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\filter\\__construct',
        1 => 'phpunit\\runner\\filter\\accept',
        2 => 'phpunit\\runner\\filter\\doaccept',
        3 => 'phpunit\\runner\\filter\\preparefilter',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/Filter/TestIdFilterIterator.php' => 
    array (
      0 => '99764c90d72f557b6f71af8c5aa154506e103af2fb7e6861a85e1fb19c639b8a',
      1 => 
      array (
        0 => 'phpunit\\runner\\filter\\testidfilteriterator',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\filter\\__construct',
        1 => 'phpunit\\runner\\filter\\accept',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/GarbageCollection/GarbageCollectionHandler.php' => 
    array (
      0 => '45ea3a11b583a577cc2a9ce45c3b09cb587d1cf817e3c56b7fb02e006f17b8b4',
      1 => 
      array (
        0 => 'phpunit\\runner\\garbagecollection\\garbagecollectionhandler',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\garbagecollection\\__construct',
        1 => 'phpunit\\runner\\garbagecollection\\executionstarted',
        2 => 'phpunit\\runner\\garbagecollection\\executionfinished',
        3 => 'phpunit\\runner\\garbagecollection\\testfinished',
        4 => 'phpunit\\runner\\garbagecollection\\registersubscribers',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/GarbageCollection/Subscriber/ExecutionFinishedSubscriber.php' => 
    array (
      0 => 'b2fcd357035487ba83a12cacdd0741d5d2f8799e8c4c3e4d85c97c8d0a9b6608',
      1 => 
      array (
        0 => 'phpunit\\runner\\garbagecollection\\executionfinishedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\garbagecollection\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/GarbageCollection/Subscriber/ExecutionStartedSubscriber.php' => 
    array (
      0 => '29ae73c5de2435766ccbdd94fd57d9534050d8def94f9141f69dab0f3a9b775d',
      1 => 
      array (
        0 => 'phpunit\\runner\\garbagecollection\\executionstartedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\garbagecollection\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/GarbageCollection/Subscriber/Subscriber.php' => 
    array (
      0 => '67f91feef53152ffa28254d7dae349f57e94bd2882afe04677a1768ea18934b6',
      1 => 
      array (
        0 => 'phpunit\\runner\\garbagecollection\\subscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\garbagecollection\\__construct',
        1 => 'phpunit\\runner\\garbagecollection\\handler',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/GarbageCollection/Subscriber/TestFinishedSubscriber.php' => 
    array (
      0 => 'eb3ae62f09218aa50c2497002bec3f4a5f62021d918ba2c65934e2c62afd9993',
      1 => 
      array (
        0 => 'phpunit\\runner\\garbagecollection\\testfinishedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\garbagecollection\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/HookMethod/HookMethod.php' => 
    array (
      0 => 'd9c261f7a3339759e3ea415cad95dc720bda8fc3a5d0c3f0a5c6f6938b75be7d',
      1 => 
      array (
        0 => 'phpunit\\runner\\hookmethod',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\__construct',
        1 => 'phpunit\\runner\\methodname',
        2 => 'phpunit\\runner\\priority',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/HookMethod/HookMethodCollection.php' => 
    array (
      0 => 'c91beb252727cbf836998350000d35e7bd44e8f9d4ba0fd79b20ad9d32a18587',
      1 => 
      array (
        0 => 'phpunit\\runner\\hookmethodcollection',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\defaultbeforeclass',
        1 => 'phpunit\\runner\\defaultbefore',
        2 => 'phpunit\\runner\\defaultprecondition',
        3 => 'phpunit\\runner\\defaultpostcondition',
        4 => 'phpunit\\runner\\defaultafter',
        5 => 'phpunit\\runner\\defaultafterclass',
        6 => 'phpunit\\runner\\__construct',
        7 => 'phpunit\\runner\\add',
        8 => 'phpunit\\runner\\methodnamessortedbypriority',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/IssueFilter.php' => 
    array (
      0 => 'a949f29a26d94610b5664c466521c6c6ea8fe972128f53fc7ae6e356ebee8c64',
      1 => 
      array (
        0 => 'phpunit\\testrunner\\issuefilter',
      ),
      2 => 
      array (
        0 => 'phpunit\\testrunner\\__construct',
        1 => 'phpunit\\testrunner\\shouldbeprocessed',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/PHPT/PhptTestCase.php' => 
    array (
      0 => 'cc2e31ba78255fd7edfc69c27962bf7d12695e0711f98afecd1ec748377ccf30',
      1 => 
      array (
        0 => 'phpunit\\runner\\phpttestcase',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\__construct',
        1 => 'phpunit\\runner\\count',
        2 => 'phpunit\\runner\\run',
        3 => 'phpunit\\runner\\getname',
        4 => 'phpunit\\runner\\tostring',
        5 => 'phpunit\\runner\\usesdataprovider',
        6 => 'phpunit\\runner\\numberofassertionsperformed',
        7 => 'phpunit\\runner\\output',
        8 => 'phpunit\\runner\\hasoutput',
        9 => 'phpunit\\runner\\sortid',
        10 => 'phpunit\\runner\\provides',
        11 => 'phpunit\\runner\\requires',
        12 => 'phpunit\\runner\\valueobjectforevents',
        13 => 'phpunit\\runner\\parseinisection',
        14 => 'phpunit\\runner\\parseenvsection',
        15 => 'phpunit\\runner\\assertphptexpectation',
        16 => 'phpunit\\runner\\shouldtestbeskipped',
        17 => 'phpunit\\runner\\shouldruninsubprocess',
        18 => 'phpunit\\runner\\runcodeinlocalsandbox',
        19 => 'phpunit\\runner\\runclean',
        20 => 'phpunit\\runner\\parse',
        21 => 'phpunit\\runner\\parseexternal',
        22 => 'phpunit\\runner\\validate',
        23 => 'phpunit\\runner\\render',
        24 => 'phpunit\\runner\\coveragefiles',
        25 => 'phpunit\\runner\\renderforcoverage',
        26 => 'phpunit\\runner\\cleanupforcoverage',
        27 => 'phpunit\\runner\\stringifyini',
        28 => 'phpunit\\runner\\locationhintfromdiff',
        29 => 'phpunit\\runner\\cleandiffline',
        30 => 'phpunit\\runner\\locationhint',
        31 => 'phpunit\\runner\\settings',
        32 => 'phpunit\\runner\\ensurecoveragefiledoesnotexist',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/ResultCache/DefaultResultCache.php' => 
    array (
      0 => '2c6308299990a81f71b745cebf82c7555ae179241ef8ab07dab32d761f435ec1',
      1 => 
      array (
        0 => 'phpunit\\runner\\resultcache\\defaultresultcache',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\resultcache\\__construct',
        1 => 'phpunit\\runner\\resultcache\\setstatus',
        2 => 'phpunit\\runner\\resultcache\\status',
        3 => 'phpunit\\runner\\resultcache\\settime',
        4 => 'phpunit\\runner\\resultcache\\time',
        5 => 'phpunit\\runner\\resultcache\\mergewith',
        6 => 'phpunit\\runner\\resultcache\\load',
        7 => 'phpunit\\runner\\resultcache\\persist',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/ResultCache/NullResultCache.php' => 
    array (
      0 => '3091d1a34db4fee70ab5a7bea2dbd85739963ec2c89c433949af5616dec369b7',
      1 => 
      array (
        0 => 'phpunit\\runner\\resultcache\\nullresultcache',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\resultcache\\setstatus',
        1 => 'phpunit\\runner\\resultcache\\status',
        2 => 'phpunit\\runner\\resultcache\\settime',
        3 => 'phpunit\\runner\\resultcache\\time',
        4 => 'phpunit\\runner\\resultcache\\load',
        5 => 'phpunit\\runner\\resultcache\\persist',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/ResultCache/ResultCache.php' => 
    array (
      0 => 'c0d592a6a5b02b6963c1611b92ea7ca6374ef7f63204f4f433cd79dcaeeae8c8',
      1 => 
      array (
        0 => 'phpunit\\runner\\resultcache\\resultcache',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\resultcache\\setstatus',
        1 => 'phpunit\\runner\\resultcache\\status',
        2 => 'phpunit\\runner\\resultcache\\settime',
        3 => 'phpunit\\runner\\resultcache\\time',
        4 => 'phpunit\\runner\\resultcache\\load',
        5 => 'phpunit\\runner\\resultcache\\persist',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/ResultCache/ResultCacheHandler.php' => 
    array (
      0 => '1ebdd078082e73fac19e25b36410039e74c677e77acb13ccf674c26e810e8518',
      1 => 
      array (
        0 => 'phpunit\\runner\\resultcache\\resultcachehandler',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\resultcache\\__construct',
        1 => 'phpunit\\runner\\resultcache\\testsuitestarted',
        2 => 'phpunit\\runner\\resultcache\\testsuitefinished',
        3 => 'phpunit\\runner\\resultcache\\testprepared',
        4 => 'phpunit\\runner\\resultcache\\testmarkedincomplete',
        5 => 'phpunit\\runner\\resultcache\\testconsideredrisky',
        6 => 'phpunit\\runner\\resultcache\\testerrored',
        7 => 'phpunit\\runner\\resultcache\\testfailed',
        8 => 'phpunit\\runner\\resultcache\\testskipped',
        9 => 'phpunit\\runner\\resultcache\\testfinished',
        10 => 'phpunit\\runner\\resultcache\\duration',
        11 => 'phpunit\\runner\\resultcache\\registersubscribers',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/ResultCache/ResultCacheId.php' => 
    array (
      0 => '65beda1f3fdae6d6a3c7323ef42e51cf63aefd0fad32e8bd8345b6a199f32d23',
      1 => 
      array (
        0 => 'phpunit\\runner\\resultcache\\resultcacheid',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\resultcache\\fromtest',
        1 => 'phpunit\\runner\\resultcache\\fromreorderable',
        2 => 'phpunit\\runner\\resultcache\\fromtestclassandmethodname',
        3 => 'phpunit\\runner\\resultcache\\__construct',
        4 => 'phpunit\\runner\\resultcache\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/ResultCache/Subscriber/Subscriber.php' => 
    array (
      0 => '5214f7801e3647ccac80edb3fee8f9f419db85b0883ebf60161f265b0fa1aab0',
      1 => 
      array (
        0 => 'phpunit\\runner\\resultcache\\subscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\resultcache\\__construct',
        1 => 'phpunit\\runner\\resultcache\\handler',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/ResultCache/Subscriber/TestConsideredRiskySubscriber.php' => 
    array (
      0 => '51ad5d158dc9b37aeff336a1fa4b45000f503d313d910ac4e2ff6a24d68ca56d',
      1 => 
      array (
        0 => 'phpunit\\runner\\resultcache\\testconsideredriskysubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\resultcache\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/ResultCache/Subscriber/TestErroredSubscriber.php' => 
    array (
      0 => '83020c2a025ddb6c297457c732f2848ec5a20b5fda2d4b288690584202f0d3f4',
      1 => 
      array (
        0 => 'phpunit\\runner\\resultcache\\testerroredsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\resultcache\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/ResultCache/Subscriber/TestFailedSubscriber.php' => 
    array (
      0 => '762251ab7ee82b79d8cc6e6c16bd7b2fe4cd69d7feb1ce315ffdf731e73e65c1',
      1 => 
      array (
        0 => 'phpunit\\runner\\resultcache\\testfailedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\resultcache\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/ResultCache/Subscriber/TestFinishedSubscriber.php' => 
    array (
      0 => '8082026d3b8ae06301378777430760d9ffec0efc3a6c634bf5f4efee3f0caed8',
      1 => 
      array (
        0 => 'phpunit\\runner\\resultcache\\testfinishedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\resultcache\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/ResultCache/Subscriber/TestMarkedIncompleteSubscriber.php' => 
    array (
      0 => '11c0c841fe2b1e76ef1a96c1c9a3b056c0f93e05f1fbc28153c39fdbcd9f2318',
      1 => 
      array (
        0 => 'phpunit\\runner\\resultcache\\testmarkedincompletesubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\resultcache\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/ResultCache/Subscriber/TestPreparedSubscriber.php' => 
    array (
      0 => '8adcb22362142282f57a9866225e3263d8c41fd36300d679f939082592ff3d8a',
      1 => 
      array (
        0 => 'phpunit\\runner\\resultcache\\testpreparedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\resultcache\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/ResultCache/Subscriber/TestSkippedSubscriber.php' => 
    array (
      0 => 'ff20c5b0b504177b406e24da399c26f963e564a4833f95002c3f3f96ebcdca18',
      1 => 
      array (
        0 => 'phpunit\\runner\\resultcache\\testskippedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\resultcache\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/ResultCache/Subscriber/TestSuiteFinishedSubscriber.php' => 
    array (
      0 => 'e29c719a5c3f79d8aa9c37608245de65b9a05a6f6c283ef0e275a2496eba2645',
      1 => 
      array (
        0 => 'phpunit\\runner\\resultcache\\testsuitefinishedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\resultcache\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/ResultCache/Subscriber/TestSuiteStartedSubscriber.php' => 
    array (
      0 => 'd33eddb9552de13a27f8c82810865a242aae6a4906a003a3344c59b2da7dd0df',
      1 => 
      array (
        0 => 'phpunit\\runner\\resultcache\\testsuitestartedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\resultcache\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/TestResult/Collector.php' => 
    array (
      0 => '963e01c6fc32585830f27400a5880a118623e74e331a24943e5f5d618d7a2fa6',
      1 => 
      array (
        0 => 'phpunit\\testrunner\\testresult\\collector',
      ),
      2 => 
      array (
        0 => 'phpunit\\testrunner\\testresult\\__construct',
        1 => 'phpunit\\testrunner\\testresult\\result',
        2 => 'phpunit\\testrunner\\testresult\\executionstarted',
        3 => 'phpunit\\testrunner\\testresult\\testsuiteskipped',
        4 => 'phpunit\\testrunner\\testresult\\testsuitestarted',
        5 => 'phpunit\\testrunner\\testresult\\testsuitefinished',
        6 => 'phpunit\\testrunner\\testresult\\testprepared',
        7 => 'phpunit\\testrunner\\testresult\\testfinished',
        8 => 'phpunit\\testrunner\\testresult\\beforetestclassmethoderrored',
        9 => 'phpunit\\testrunner\\testresult\\aftertestclassmethoderrored',
        10 => 'phpunit\\testrunner\\testresult\\testerrored',
        11 => 'phpunit\\testrunner\\testresult\\testfailed',
        12 => 'phpunit\\testrunner\\testresult\\testmarkedincomplete',
        13 => 'phpunit\\testrunner\\testresult\\testskipped',
        14 => 'phpunit\\testrunner\\testresult\\testconsideredrisky',
        15 => 'phpunit\\testrunner\\testresult\\testtriggereddeprecation',
        16 => 'phpunit\\testrunner\\testresult\\testtriggeredphpdeprecation',
        17 => 'phpunit\\testrunner\\testresult\\testtriggeredphpunitdeprecation',
        18 => 'phpunit\\testrunner\\testresult\\testtriggerederror',
        19 => 'phpunit\\testrunner\\testresult\\testtriggerednotice',
        20 => 'phpunit\\testrunner\\testresult\\testtriggeredphpnotice',
        21 => 'phpunit\\testrunner\\testresult\\testtriggeredwarning',
        22 => 'phpunit\\testrunner\\testresult\\testtriggeredphpwarning',
        23 => 'phpunit\\testrunner\\testresult\\testtriggeredphpuniterror',
        24 => 'phpunit\\testrunner\\testresult\\testtriggeredphpunitwarning',
        25 => 'phpunit\\testrunner\\testresult\\testrunnertriggereddeprecation',
        26 => 'phpunit\\testrunner\\testresult\\testrunnertriggeredwarning',
        27 => 'phpunit\\testrunner\\testresult\\haserroredtests',
        28 => 'phpunit\\testrunner\\testresult\\hasfailedtests',
        29 => 'phpunit\\testrunner\\testresult\\hasriskytests',
        30 => 'phpunit\\testrunner\\testresult\\hasskippedtests',
        31 => 'phpunit\\testrunner\\testresult\\hasincompletetests',
        32 => 'phpunit\\testrunner\\testresult\\hasdeprecations',
        33 => 'phpunit\\testrunner\\testresult\\hasnotices',
        34 => 'phpunit\\testrunner\\testresult\\haswarnings',
        35 => 'phpunit\\testrunner\\testresult\\issueid',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/TestResult/Facade.php' => 
    array (
      0 => 'c58edd84be66988e795c82ce33b2b9d50f99f876e0b2ab2fb40ede755cea7f24',
      1 => 
      array (
        0 => 'phpunit\\testrunner\\testresult\\facade',
      ),
      2 => 
      array (
        0 => 'phpunit\\testrunner\\testresult\\init',
        1 => 'phpunit\\testrunner\\testresult\\result',
        2 => 'phpunit\\testrunner\\testresult\\shouldstop',
        3 => 'phpunit\\testrunner\\testresult\\collector',
        4 => 'phpunit\\testrunner\\testresult\\stopondeprecation',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/TestResult/Issue.php' => 
    array (
      0 => '0033c452c9e0daee44eb5f8848791dbf69418fbd44b5f4992552d629d2db194c',
      1 => 
      array (
        0 => 'phpunit\\testrunner\\testresult\\issues\\issue',
      ),
      2 => 
      array (
        0 => 'phpunit\\testrunner\\testresult\\issues\\from',
        1 => 'phpunit\\testrunner\\testresult\\issues\\__construct',
        2 => 'phpunit\\testrunner\\testresult\\issues\\triggeredby',
        3 => 'phpunit\\testrunner\\testresult\\issues\\file',
        4 => 'phpunit\\testrunner\\testresult\\issues\\line',
        5 => 'phpunit\\testrunner\\testresult\\issues\\description',
        6 => 'phpunit\\testrunner\\testresult\\issues\\triggeringtests',
        7 => 'phpunit\\testrunner\\testresult\\issues\\hasstacktrace',
        8 => 'phpunit\\testrunner\\testresult\\issues\\stacktrace',
        9 => 'phpunit\\testrunner\\testresult\\issues\\triggeredintest',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/TestResult/PassedTests.php' => 
    array (
      0 => '619732a2826fb13aa44c7b209fedc017e5e428ac1f1130d54e78efde957b8060',
      1 => 
      array (
        0 => 'phpunit\\testrunner\\testresult\\passedtests',
      ),
      2 => 
      array (
        0 => 'phpunit\\testrunner\\testresult\\instance',
        1 => 'phpunit\\testrunner\\testresult\\testclasspassed',
        2 => 'phpunit\\testrunner\\testresult\\testmethodpassed',
        3 => 'phpunit\\testrunner\\testresult\\import',
        4 => 'phpunit\\testrunner\\testresult\\hastestclasspassed',
        5 => 'phpunit\\testrunner\\testresult\\hastestmethodpassed',
        6 => 'phpunit\\testrunner\\testresult\\isgreaterthan',
        7 => 'phpunit\\testrunner\\testresult\\hasreturnvalue',
        8 => 'phpunit\\testrunner\\testresult\\returnvalue',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/TestResult/Subscriber/AfterTestClassMethodErroredSubscriber.php' => 
    array (
      0 => '2d3331f85bdbbe786495281e1a8befcc042c3b42b94fa22dfb40bf46e7d58982',
      1 => 
      array (
        0 => 'phpunit\\testrunner\\testresult\\aftertestclassmethoderroredsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\testrunner\\testresult\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/TestResult/Subscriber/BeforeTestClassMethodErroredSubscriber.php' => 
    array (
      0 => '1f7b0c068671995d196fe5cd4a6324f4c431b671ecf63af70f4f44104f80a969',
      1 => 
      array (
        0 => 'phpunit\\testrunner\\testresult\\beforetestclassmethoderroredsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\testrunner\\testresult\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/TestResult/Subscriber/ExecutionStartedSubscriber.php' => 
    array (
      0 => 'f35d7c441080acfc26838ab4f9c743133a5c98cc2e24556e96c4a33efd19df17',
      1 => 
      array (
        0 => 'phpunit\\testrunner\\testresult\\executionstartedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\testrunner\\testresult\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/TestResult/Subscriber/Subscriber.php' => 
    array (
      0 => '3ca67ddf1007b299fa5d834cfad15a29ed9f4d48074b5c5ac7ac527fc36ebb7a',
      1 => 
      array (
        0 => 'phpunit\\testrunner\\testresult\\subscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\testrunner\\testresult\\__construct',
        1 => 'phpunit\\testrunner\\testresult\\collector',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/TestResult/Subscriber/TestConsideredRiskySubscriber.php' => 
    array (
      0 => '6fa2d6e4a77165903e140a17ae18613e92261efc26d6bb316c57c3ffe96df7f4',
      1 => 
      array (
        0 => 'phpunit\\testrunner\\testresult\\testconsideredriskysubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\testrunner\\testresult\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/TestResult/Subscriber/TestErroredSubscriber.php' => 
    array (
      0 => '4c8323f7a9dd4a212fed760abe529f97a375c9f2d2a9f6e7c23a078e91557751',
      1 => 
      array (
        0 => 'phpunit\\testrunner\\testresult\\testerroredsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\testrunner\\testresult\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/TestResult/Subscriber/TestFailedSubscriber.php' => 
    array (
      0 => 'b7b3ea1b151a9f8d8c462863c3de2f9e2c95193a242cc00b14a334f6a62a35ac',
      1 => 
      array (
        0 => 'phpunit\\testrunner\\testresult\\testfailedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\testrunner\\testresult\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/TestResult/Subscriber/TestFinishedSubscriber.php' => 
    array (
      0 => 'f8a261b36faa45d24e06ccba4430b9f18e2823a5283d3cac8b9cd1c46fab267c',
      1 => 
      array (
        0 => 'phpunit\\testrunner\\testresult\\testfinishedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\testrunner\\testresult\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/TestResult/Subscriber/TestMarkedIncompleteSubscriber.php' => 
    array (
      0 => '28d1b59bd4b8442de579fed00bdd55db94c39625b887c1e81c99ad1104e6eba9',
      1 => 
      array (
        0 => 'phpunit\\testrunner\\testresult\\testmarkedincompletesubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\testrunner\\testresult\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/TestResult/Subscriber/TestPreparedSubscriber.php' => 
    array (
      0 => 'df8ec62842210e8eba0629eb1d124adb3bb2413402f291ce772360317e16c609',
      1 => 
      array (
        0 => 'phpunit\\testrunner\\testresult\\testpreparedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\testrunner\\testresult\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/TestResult/Subscriber/TestRunnerTriggeredDeprecationSubscriber.php' => 
    array (
      0 => 'cc8c207929f9e49ddc224cfe117f717b67c9223d6a158a99d604580514a6fb95',
      1 => 
      array (
        0 => 'phpunit\\testrunner\\testresult\\testrunnertriggereddeprecationsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\testrunner\\testresult\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/TestResult/Subscriber/TestRunnerTriggeredWarningSubscriber.php' => 
    array (
      0 => 'd439428cde1b9349b2673d83065d1f79d10d38cfbc7fcbac65d10563ba21cffb',
      1 => 
      array (
        0 => 'phpunit\\testrunner\\testresult\\testrunnertriggeredwarningsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\testrunner\\testresult\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/TestResult/Subscriber/TestSkippedSubscriber.php' => 
    array (
      0 => 'e88903aaf01e2bc167428d8c03e5b4a2162fb2f099bfb7aa8c8069418f008775',
      1 => 
      array (
        0 => 'phpunit\\testrunner\\testresult\\testskippedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\testrunner\\testresult\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/TestResult/Subscriber/TestSuiteFinishedSubscriber.php' => 
    array (
      0 => 'acc28f57215976a6131cfcc3c4b4415aaeae439491b5a5855510301d406e7112',
      1 => 
      array (
        0 => 'phpunit\\testrunner\\testresult\\testsuitefinishedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\testrunner\\testresult\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/TestResult/Subscriber/TestSuiteSkippedSubscriber.php' => 
    array (
      0 => '86d749e785216e1ff398adce1b3aea0b5e663a627c77dcb98bf8898a18e090c5',
      1 => 
      array (
        0 => 'phpunit\\testrunner\\testresult\\testsuiteskippedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\testrunner\\testresult\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/TestResult/Subscriber/TestSuiteStartedSubscriber.php' => 
    array (
      0 => 'f5568c9450a57b0415a8d11b5e1809a55a29cbf5cf08b7e2630fdf43c8772a60',
      1 => 
      array (
        0 => 'phpunit\\testrunner\\testresult\\testsuitestartedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\testrunner\\testresult\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/TestResult/Subscriber/TestTriggeredDeprecationSubscriber.php' => 
    array (
      0 => 'e2d72cc13d99d8aaccb680541740264718d76557010826b74c32582f5e32a176',
      1 => 
      array (
        0 => 'phpunit\\testrunner\\testresult\\testtriggereddeprecationsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\testrunner\\testresult\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/TestResult/Subscriber/TestTriggeredErrorSubscriber.php' => 
    array (
      0 => '59ed5a4836476a5f4d39173a12a33bc07aa8f9a786770cbb41f06d1505cdb56b',
      1 => 
      array (
        0 => 'phpunit\\testrunner\\testresult\\testtriggerederrorsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\testrunner\\testresult\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/TestResult/Subscriber/TestTriggeredNoticeSubscriber.php' => 
    array (
      0 => '5867b82a9c92d10591155584c0cfe23510a9fe55c64d0310de57d5aed1459d62',
      1 => 
      array (
        0 => 'phpunit\\testrunner\\testresult\\testtriggerednoticesubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\testrunner\\testresult\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/TestResult/Subscriber/TestTriggeredPhpDeprecationSubscriber.php' => 
    array (
      0 => '5dda486c3fce8f91f7fbaf80c09eb79d138fbf026d720e4c1be7dedb85d4973c',
      1 => 
      array (
        0 => 'phpunit\\testrunner\\testresult\\testtriggeredphpdeprecationsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\testrunner\\testresult\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/TestResult/Subscriber/TestTriggeredPhpNoticeSubscriber.php' => 
    array (
      0 => '6f590ddf520dae357cd7623f309769591c807436241b961a628d443e5e914276',
      1 => 
      array (
        0 => 'phpunit\\testrunner\\testresult\\testtriggeredphpnoticesubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\testrunner\\testresult\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/TestResult/Subscriber/TestTriggeredPhpWarningSubscriber.php' => 
    array (
      0 => 'c2b67e251f05fbfbcbc6ddc8fddf810937437e136f092a5252fda75da955fdd9',
      1 => 
      array (
        0 => 'phpunit\\testrunner\\testresult\\testtriggeredphpwarningsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\testrunner\\testresult\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/TestResult/Subscriber/TestTriggeredPhpunitDeprecationSubscriber.php' => 
    array (
      0 => 'bbcd34ae13a3df875e5d9a126ae995baec5d5dee5a85a2d241a4533bf2e612a5',
      1 => 
      array (
        0 => 'phpunit\\testrunner\\testresult\\testtriggeredphpunitdeprecationsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\testrunner\\testresult\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/TestResult/Subscriber/TestTriggeredPhpunitErrorSubscriber.php' => 
    array (
      0 => 'a23e4f903f0ff63d92e746a843a283a58fb9c55db1a972b3c5c5b46be5650434',
      1 => 
      array (
        0 => 'phpunit\\testrunner\\testresult\\testtriggeredphpuniterrorsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\testrunner\\testresult\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/TestResult/Subscriber/TestTriggeredPhpunitWarningSubscriber.php' => 
    array (
      0 => '0dfc84efd3791bafee8b4937b08477b492ce8c58697d3c71eff1085f262f083d',
      1 => 
      array (
        0 => 'phpunit\\testrunner\\testresult\\testtriggeredphpunitwarningsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\testrunner\\testresult\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/TestResult/Subscriber/TestTriggeredWarningSubscriber.php' => 
    array (
      0 => 'c61526f5be1e637ae4e84b1f1b923e9fdd7667e2a888e73b54936175b03d70ee',
      1 => 
      array (
        0 => 'phpunit\\testrunner\\testresult\\testtriggeredwarningsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\testrunner\\testresult\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/TestResult/TestResult.php' => 
    array (
      0 => 'd123a16bb9d9673bb8472bd27252537baefe47fe6fd999ec511e4b3d66f6027e',
      1 => 
      array (
        0 => 'phpunit\\testrunner\\testresult\\testresult',
      ),
      2 => 
      array (
        0 => 'phpunit\\testrunner\\testresult\\__construct',
        1 => 'phpunit\\testrunner\\testresult\\numberoftestsrun',
        2 => 'phpunit\\testrunner\\testresult\\numberofassertions',
        3 => 'phpunit\\testrunner\\testresult\\testerroredevents',
        4 => 'phpunit\\testrunner\\testresult\\numberoftesterroredevents',
        5 => 'phpunit\\testrunner\\testresult\\hastesterroredevents',
        6 => 'phpunit\\testrunner\\testresult\\testfailedevents',
        7 => 'phpunit\\testrunner\\testresult\\numberoftestfailedevents',
        8 => 'phpunit\\testrunner\\testresult\\hastestfailedevents',
        9 => 'phpunit\\testrunner\\testresult\\testconsideredriskyevents',
        10 => 'phpunit\\testrunner\\testresult\\numberoftestswithtestconsideredriskyevents',
        11 => 'phpunit\\testrunner\\testresult\\hastestconsideredriskyevents',
        12 => 'phpunit\\testrunner\\testresult\\testsuiteskippedevents',
        13 => 'phpunit\\testrunner\\testresult\\numberoftestskippedbytestsuiteskippedevents',
        14 => 'phpunit\\testrunner\\testresult\\hastestsuiteskippedevents',
        15 => 'phpunit\\testrunner\\testresult\\testskippedevents',
        16 => 'phpunit\\testrunner\\testresult\\numberoftestskippedevents',
        17 => 'phpunit\\testrunner\\testresult\\hastestskippedevents',
        18 => 'phpunit\\testrunner\\testresult\\testmarkedincompleteevents',
        19 => 'phpunit\\testrunner\\testresult\\numberoftestmarkedincompleteevents',
        20 => 'phpunit\\testrunner\\testresult\\hastestmarkedincompleteevents',
        21 => 'phpunit\\testrunner\\testresult\\testtriggeredphpunitdeprecationevents',
        22 => 'phpunit\\testrunner\\testresult\\numberoftestswithtesttriggeredphpunitdeprecationevents',
        23 => 'phpunit\\testrunner\\testresult\\hastesttriggeredphpunitdeprecationevents',
        24 => 'phpunit\\testrunner\\testresult\\testtriggeredphpuniterrorevents',
        25 => 'phpunit\\testrunner\\testresult\\numberoftestswithtesttriggeredphpuniterrorevents',
        26 => 'phpunit\\testrunner\\testresult\\hastesttriggeredphpuniterrorevents',
        27 => 'phpunit\\testrunner\\testresult\\testtriggeredphpunitwarningevents',
        28 => 'phpunit\\testrunner\\testresult\\numberoftestswithtesttriggeredphpunitwarningevents',
        29 => 'phpunit\\testrunner\\testresult\\hastesttriggeredphpunitwarningevents',
        30 => 'phpunit\\testrunner\\testresult\\testrunnertriggereddeprecationevents',
        31 => 'phpunit\\testrunner\\testresult\\numberoftestrunnertriggereddeprecationevents',
        32 => 'phpunit\\testrunner\\testresult\\hastestrunnertriggereddeprecationevents',
        33 => 'phpunit\\testrunner\\testresult\\testrunnertriggeredwarningevents',
        34 => 'phpunit\\testrunner\\testresult\\numberoftestrunnertriggeredwarningevents',
        35 => 'phpunit\\testrunner\\testresult\\hastestrunnertriggeredwarningevents',
        36 => 'phpunit\\testrunner\\testresult\\wassuccessful',
        37 => 'phpunit\\testrunner\\testresult\\hasissues',
        38 => 'phpunit\\testrunner\\testresult\\hastestswithissues',
        39 => 'phpunit\\testrunner\\testresult\\errors',
        40 => 'phpunit\\testrunner\\testresult\\deprecations',
        41 => 'phpunit\\testrunner\\testresult\\notices',
        42 => 'phpunit\\testrunner\\testresult\\warnings',
        43 => 'phpunit\\testrunner\\testresult\\phpdeprecations',
        44 => 'phpunit\\testrunner\\testresult\\phpnotices',
        45 => 'phpunit\\testrunner\\testresult\\phpwarnings',
        46 => 'phpunit\\testrunner\\testresult\\hastests',
        47 => 'phpunit\\testrunner\\testresult\\haserrors',
        48 => 'phpunit\\testrunner\\testresult\\numberoferrors',
        49 => 'phpunit\\testrunner\\testresult\\hasdeprecations',
        50 => 'phpunit\\testrunner\\testresult\\hasphporuserdeprecations',
        51 => 'phpunit\\testrunner\\testresult\\numberofphporuserdeprecations',
        52 => 'phpunit\\testrunner\\testresult\\hasphpunitdeprecations',
        53 => 'phpunit\\testrunner\\testresult\\numberofphpunitdeprecations',
        54 => 'phpunit\\testrunner\\testresult\\hasphpunitwarnings',
        55 => 'phpunit\\testrunner\\testresult\\numberofphpunitwarnings',
        56 => 'phpunit\\testrunner\\testresult\\numberofdeprecations',
        57 => 'phpunit\\testrunner\\testresult\\hasnotices',
        58 => 'phpunit\\testrunner\\testresult\\numberofnotices',
        59 => 'phpunit\\testrunner\\testresult\\haswarnings',
        60 => 'phpunit\\testrunner\\testresult\\numberofwarnings',
        61 => 'phpunit\\testrunner\\testresult\\hasincompletetests',
        62 => 'phpunit\\testrunner\\testresult\\hasriskytests',
        63 => 'phpunit\\testrunner\\testresult\\hasskippedtests',
        64 => 'phpunit\\testrunner\\testresult\\hasissuesignoredbybaseline',
        65 => 'phpunit\\testrunner\\testresult\\numberofissuesignoredbybaseline',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/TestSuiteLoader.php' => 
    array (
      0 => 'd0e81317889ad88c707db4b08a94cadee4c9010d05ff0a759f04e71af5efed89',
      1 => 
      array (
        0 => 'phpunit\\runner\\testsuiteloader',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\load',
        1 => 'phpunit\\runner\\classnamefromfilename',
        2 => 'phpunit\\runner\\loadsuiteclassfile',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/TestSuiteSorter.php' => 
    array (
      0 => 'edb085b5525291d0b2e7d2701be14b1768f2db64b894c67d4ca7b14546c9764a',
      1 => 
      array (
        0 => 'phpunit\\runner\\testsuitesorter',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\__construct',
        1 => 'phpunit\\runner\\reordertestsinsuite',
        2 => 'phpunit\\runner\\sort',
        3 => 'phpunit\\runner\\addsuitetodefectsortorder',
        4 => 'phpunit\\runner\\reverse',
        5 => 'phpunit\\runner\\randomize',
        6 => 'phpunit\\runner\\sortdefectsfirst',
        7 => 'phpunit\\runner\\sortbyduration',
        8 => 'phpunit\\runner\\sortbysize',
        9 => 'phpunit\\runner\\cmpdefectpriorityandtime',
        10 => 'phpunit\\runner\\cmpduration',
        11 => 'phpunit\\runner\\cmpsize',
        12 => 'phpunit\\runner\\resolvedependencies',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Runner/Version.php' => 
    array (
      0 => '70085caddc8766e8698da0af8bb5307be888b151bde14f3e3387740153f52550',
      1 => 
      array (
        0 => 'phpunit\\runner\\version',
      ),
      2 => 
      array (
        0 => 'phpunit\\runner\\id',
        1 => 'phpunit\\runner\\series',
        2 => 'phpunit\\runner\\majorversionnumber',
        3 => 'phpunit\\runner\\getversionstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Application.php' => 
    array (
      0 => '586415b0993879128d3673130a65f2ca9677cd5cb7435b4e9248c8ff4a2890e1',
      1 => 
      array (
        0 => 'phpunit\\textui\\application',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\run',
        1 => 'phpunit\\textui\\execute',
        2 => 'phpunit\\textui\\loadbootstrapscript',
        3 => 'phpunit\\textui\\buildcliconfiguration',
        4 => 'phpunit\\textui\\loadxmlconfiguration',
        5 => 'phpunit\\textui\\buildtestsuite',
        6 => 'phpunit\\textui\\bootstrapextensions',
        7 => 'phpunit\\textui\\executecommandsthatonlyrequirecliconfiguration',
        8 => 'phpunit\\textui\\executecommandsthatdonotrequirethetestsuite',
        9 => 'phpunit\\textui\\executecommandsthatrequirethetestsuite',
        10 => 'phpunit\\textui\\writeruntimeinformation',
        11 => 'phpunit\\textui\\writepharextensioninformation',
        12 => 'phpunit\\textui\\writemessage',
        13 => 'phpunit\\textui\\writerandomseedinformation',
        14 => 'phpunit\\textui\\registerlogfilewriters',
        15 => 'phpunit\\textui\\testdoxresultcollector',
        16 => 'phpunit\\textui\\initializetestresultcache',
        17 => 'phpunit\\textui\\configurebaseline',
        18 => 'phpunit\\textui\\exitwithcrashmessage',
        19 => 'phpunit\\textui\\exitwitherrormessage',
        20 => 'phpunit\\textui\\filteredtests',
        21 => 'phpunit\\textui\\configuredeprecationtriggers',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Command/Command.php' => 
    array (
      0 => 'edb83e573fcd6d0202e850a3c6f72658a2f7ebfcaca5f6d4266a16e6bdfdabc3',
      1 => 
      array (
        0 => 'phpunit\\textui\\command\\command',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\command\\execute',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Command/Commands/AtLeastVersionCommand.php' => 
    array (
      0 => '45939777971173595fdfabb69297ae01d39756f27001b81c33115a250060c446',
      1 => 
      array (
        0 => 'phpunit\\textui\\command\\atleastversioncommand',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\command\\__construct',
        1 => 'phpunit\\textui\\command\\execute',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Command/Commands/CheckPhpConfigurationCommand.php' => 
    array (
      0 => '95851e6042a0554f39b74166b05108da52856fad923861902482ea6028594079',
      1 => 
      array (
        0 => 'phpunit\\textui\\command\\checkphpconfigurationcommand',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\command\\__construct',
        1 => 'phpunit\\textui\\command\\execute',
        2 => 'phpunit\\textui\\command\\ok',
        3 => 'phpunit\\textui\\command\\notok',
        4 => 'phpunit\\textui\\command\\settings',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Command/Commands/GenerateConfigurationCommand.php' => 
    array (
      0 => '8fb71d38bf46271bd4f70dc7cdda3cd73e5535878937b8dec37636d177e55af4',
      1 => 
      array (
        0 => 'phpunit\\textui\\command\\generateconfigurationcommand',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\command\\execute',
        1 => 'phpunit\\textui\\command\\read',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Command/Commands/ListGroupsCommand.php' => 
    array (
      0 => 'b2ff4494bedd0a26858e9afae44cbb46cffca5f290b34db30476f8ae5b5fa631',
      1 => 
      array (
        0 => 'phpunit\\textui\\command\\listgroupscommand',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\command\\__construct',
        1 => 'phpunit\\textui\\command\\execute',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Command/Commands/ListTestFilesCommand.php' => 
    array (
      0 => '7d2a94e5ecb94b8c3fc1bf85e29ae9220aed0a780396b97ed4a561b4a0877eb0',
      1 => 
      array (
        0 => 'phpunit\\textui\\command\\listtestfilescommand',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\command\\__construct',
        1 => 'phpunit\\textui\\command\\execute',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Command/Commands/ListTestSuitesCommand.php' => 
    array (
      0 => '4ddebb45580443543982eb6a21ca241282203f60e19cf432cadebc5e5d2bfb90',
      1 => 
      array (
        0 => 'phpunit\\textui\\command\\listtestsuitescommand',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\command\\__construct',
        1 => 'phpunit\\textui\\command\\execute',
        2 => 'phpunit\\textui\\command\\warnaboutconflictingoptions',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Command/Commands/ListTestsAsTextCommand.php' => 
    array (
      0 => 'b0369f5636e78dc42391631015d846850c4fe30b86fafec738f223cb620320b7',
      1 => 
      array (
        0 => 'phpunit\\textui\\command\\listtestsastextcommand',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\command\\__construct',
        1 => 'phpunit\\textui\\command\\execute',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Command/Commands/ListTestsAsXmlCommand.php' => 
    array (
      0 => 'b4ce823b01706ca60434180b4bf92b95a1b2deb31e61ee2472cd7fe59d55893e',
      1 => 
      array (
        0 => 'phpunit\\textui\\command\\listtestsasxmlcommand',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\command\\__construct',
        1 => 'phpunit\\textui\\command\\execute',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Command/Commands/MigrateConfigurationCommand.php' => 
    array (
      0 => '106d86bbb3947a0c8da73fae4b3ed045ccb37c614b04234a680587a90829b3cd',
      1 => 
      array (
        0 => 'phpunit\\textui\\command\\migrateconfigurationcommand',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\command\\__construct',
        1 => 'phpunit\\textui\\command\\execute',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Command/Commands/ShowHelpCommand.php' => 
    array (
      0 => '7d6d078c0a7ba70a83d63614efd6a1c6b401213d981b44b5ee8b7abd1618fec3',
      1 => 
      array (
        0 => 'phpunit\\textui\\command\\showhelpcommand',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\command\\__construct',
        1 => 'phpunit\\textui\\command\\execute',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Command/Commands/ShowVersionCommand.php' => 
    array (
      0 => '983869b6ef89b70221d18b517312366eadf3efbad387db0d10f542662b00e723',
      1 => 
      array (
        0 => 'phpunit\\textui\\command\\showversioncommand',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\command\\execute',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Command/Commands/VersionCheckCommand.php' => 
    array (
      0 => '049986c4f9d2f4e55d49fe2c8d251aedf9faf98f74b5c6196ad65c843c024517',
      1 => 
      array (
        0 => 'phpunit\\textui\\command\\versioncheckcommand',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\command\\__construct',
        1 => 'phpunit\\textui\\command\\execute',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Command/Commands/WarmCodeCoverageCacheCommand.php' => 
    array (
      0 => '3bb609b0d3bf6dee8df8d6cd62a3c8ece823c4bb941eaaae39e3cb267171b9d2',
      1 => 
      array (
        0 => 'phpunit\\textui\\command\\warmcodecoveragecachecommand',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\command\\__construct',
        1 => 'phpunit\\textui\\command\\execute',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Command/Result.php' => 
    array (
      0 => '3abbfc893ae31f8a002918c78a39edad180effefaf9ef7f29c687b26a6705e0a',
      1 => 
      array (
        0 => 'phpunit\\textui\\command\\result',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\command\\from',
        1 => 'phpunit\\textui\\command\\__construct',
        2 => 'phpunit\\textui\\command\\output',
        3 => 'phpunit\\textui\\command\\shellexitcode',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Builder.php' => 
    array (
      0 => '0f9fe542ff746e003d968799269bd3a73b944376164ec051a12ce20ff2c72f48',
      1 => 
      array (
        0 => 'phpunit\\textui\\configuration\\builder',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\configuration\\build',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Cli/Builder.php' => 
    array (
      0 => '128185e4751be14726663099bd506251c06cfffcf85754b19631d190fea59663',
      1 => 
      array (
        0 => 'phpunit\\textui\\cliarguments\\builder',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\cliarguments\\fromparameters',
        1 => 'phpunit\\textui\\cliarguments\\markprocessed',
        2 => 'phpunit\\textui\\cliarguments\\warnwhenoptionsconflict',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Cli/Configuration.php' => 
    array (
      0 => 'bae9c02d60e8e1ef8cf61abc754ea637067558e0361530e364ffd38d8ebef942',
      1 => 
      array (
        0 => 'phpunit\\textui\\cliarguments\\configuration',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\cliarguments\\__construct',
        1 => 'phpunit\\textui\\cliarguments\\arguments',
        2 => 'phpunit\\textui\\cliarguments\\hasatleastversion',
        3 => 'phpunit\\textui\\cliarguments\\atleastversion',
        4 => 'phpunit\\textui\\cliarguments\\hasbackupglobals',
        5 => 'phpunit\\textui\\cliarguments\\backupglobals',
        6 => 'phpunit\\textui\\cliarguments\\hasbackupstaticproperties',
        7 => 'phpunit\\textui\\cliarguments\\backupstaticproperties',
        8 => 'phpunit\\textui\\cliarguments\\hasbestrictaboutchangestoglobalstate',
        9 => 'phpunit\\textui\\cliarguments\\bestrictaboutchangestoglobalstate',
        10 => 'phpunit\\textui\\cliarguments\\hasbootstrap',
        11 => 'phpunit\\textui\\cliarguments\\bootstrap',
        12 => 'phpunit\\textui\\cliarguments\\hascachedirectory',
        13 => 'phpunit\\textui\\cliarguments\\cachedirectory',
        14 => 'phpunit\\textui\\cliarguments\\hascacheresult',
        15 => 'phpunit\\textui\\cliarguments\\cacheresult',
        16 => 'phpunit\\textui\\cliarguments\\checkphpconfiguration',
        17 => 'phpunit\\textui\\cliarguments\\checkversion',
        18 => 'phpunit\\textui\\cliarguments\\hascolors',
        19 => 'phpunit\\textui\\cliarguments\\colors',
        20 => 'phpunit\\textui\\cliarguments\\hascolumns',
        21 => 'phpunit\\textui\\cliarguments\\columns',
        22 => 'phpunit\\textui\\cliarguments\\hasconfigurationfile',
        23 => 'phpunit\\textui\\cliarguments\\configurationfile',
        24 => 'phpunit\\textui\\cliarguments\\hascoveragefilter',
        25 => 'phpunit\\textui\\cliarguments\\coveragefilter',
        26 => 'phpunit\\textui\\cliarguments\\hascoverageclover',
        27 => 'phpunit\\textui\\cliarguments\\coverageclover',
        28 => 'phpunit\\textui\\cliarguments\\hascoveragecobertura',
        29 => 'phpunit\\textui\\cliarguments\\coveragecobertura',
        30 => 'phpunit\\textui\\cliarguments\\hascoveragecrap4j',
        31 => 'phpunit\\textui\\cliarguments\\coveragecrap4j',
        32 => 'phpunit\\textui\\cliarguments\\hascoveragehtml',
        33 => 'phpunit\\textui\\cliarguments\\coveragehtml',
        34 => 'phpunit\\textui\\cliarguments\\hascoveragephp',
        35 => 'phpunit\\textui\\cliarguments\\coveragephp',
        36 => 'phpunit\\textui\\cliarguments\\hascoveragetext',
        37 => 'phpunit\\textui\\cliarguments\\coveragetext',
        38 => 'phpunit\\textui\\cliarguments\\hascoveragetextshowuncoveredfiles',
        39 => 'phpunit\\textui\\cliarguments\\coveragetextshowuncoveredfiles',
        40 => 'phpunit\\textui\\cliarguments\\hascoveragetextshowonlysummary',
        41 => 'phpunit\\textui\\cliarguments\\coveragetextshowonlysummary',
        42 => 'phpunit\\textui\\cliarguments\\hascoveragexml',
        43 => 'phpunit\\textui\\cliarguments\\coveragexml',
        44 => 'phpunit\\textui\\cliarguments\\haspathcoverage',
        45 => 'phpunit\\textui\\cliarguments\\pathcoverage',
        46 => 'phpunit\\textui\\cliarguments\\warmcoveragecache',
        47 => 'phpunit\\textui\\cliarguments\\hasdefaulttimelimit',
        48 => 'phpunit\\textui\\cliarguments\\defaulttimelimit',
        49 => 'phpunit\\textui\\cliarguments\\hasdisablecodecoverageignore',
        50 => 'phpunit\\textui\\cliarguments\\disablecodecoverageignore',
        51 => 'phpunit\\textui\\cliarguments\\hasdisallowtestoutput',
        52 => 'phpunit\\textui\\cliarguments\\disallowtestoutput',
        53 => 'phpunit\\textui\\cliarguments\\hasenforcetimelimit',
        54 => 'phpunit\\textui\\cliarguments\\enforcetimelimit',
        55 => 'phpunit\\textui\\cliarguments\\hasexcludegroups',
        56 => 'phpunit\\textui\\cliarguments\\excludegroups',
        57 => 'phpunit\\textui\\cliarguments\\hasexecutionorder',
        58 => 'phpunit\\textui\\cliarguments\\executionorder',
        59 => 'phpunit\\textui\\cliarguments\\hasexecutionorderdefects',
        60 => 'phpunit\\textui\\cliarguments\\executionorderdefects',
        61 => 'phpunit\\textui\\cliarguments\\hasfailonallissues',
        62 => 'phpunit\\textui\\cliarguments\\failonallissues',
        63 => 'phpunit\\textui\\cliarguments\\hasfailondeprecation',
        64 => 'phpunit\\textui\\cliarguments\\failondeprecation',
        65 => 'phpunit\\textui\\cliarguments\\hasfailonphpunitdeprecation',
        66 => 'phpunit\\textui\\cliarguments\\failonphpunitdeprecation',
        67 => 'phpunit\\textui\\cliarguments\\hasfailonphpunitwarning',
        68 => 'phpunit\\textui\\cliarguments\\failonphpunitwarning',
        69 => 'phpunit\\textui\\cliarguments\\hasfailonemptytestsuite',
        70 => 'phpunit\\textui\\cliarguments\\failonemptytestsuite',
        71 => 'phpunit\\textui\\cliarguments\\hasfailonincomplete',
        72 => 'phpunit\\textui\\cliarguments\\failonincomplete',
        73 => 'phpunit\\textui\\cliarguments\\hasfailonnotice',
        74 => 'phpunit\\textui\\cliarguments\\failonnotice',
        75 => 'phpunit\\textui\\cliarguments\\hasfailonrisky',
        76 => 'phpunit\\textui\\cliarguments\\failonrisky',
        77 => 'phpunit\\textui\\cliarguments\\hasfailonskipped',
        78 => 'phpunit\\textui\\cliarguments\\failonskipped',
        79 => 'phpunit\\textui\\cliarguments\\hasfailonwarning',
        80 => 'phpunit\\textui\\cliarguments\\failonwarning',
        81 => 'phpunit\\textui\\cliarguments\\hasdonotfailondeprecation',
        82 => 'phpunit\\textui\\cliarguments\\donotfailondeprecation',
        83 => 'phpunit\\textui\\cliarguments\\hasdonotfailonphpunitdeprecation',
        84 => 'phpunit\\textui\\cliarguments\\donotfailonphpunitdeprecation',
        85 => 'phpunit\\textui\\cliarguments\\hasdonotfailonphpunitwarning',
        86 => 'phpunit\\textui\\cliarguments\\donotfailonphpunitwarning',
        87 => 'phpunit\\textui\\cliarguments\\hasdonotfailonemptytestsuite',
        88 => 'phpunit\\textui\\cliarguments\\donotfailonemptytestsuite',
        89 => 'phpunit\\textui\\cliarguments\\hasdonotfailonincomplete',
        90 => 'phpunit\\textui\\cliarguments\\donotfailonincomplete',
        91 => 'phpunit\\textui\\cliarguments\\hasdonotfailonnotice',
        92 => 'phpunit\\textui\\cliarguments\\donotfailonnotice',
        93 => 'phpunit\\textui\\cliarguments\\hasdonotfailonrisky',
        94 => 'phpunit\\textui\\cliarguments\\donotfailonrisky',
        95 => 'phpunit\\textui\\cliarguments\\hasdonotfailonskipped',
        96 => 'phpunit\\textui\\cliarguments\\donotfailonskipped',
        97 => 'phpunit\\textui\\cliarguments\\hasdonotfailonwarning',
        98 => 'phpunit\\textui\\cliarguments\\donotfailonwarning',
        99 => 'phpunit\\textui\\cliarguments\\hasstopondefect',
        100 => 'phpunit\\textui\\cliarguments\\stopondefect',
        101 => 'phpunit\\textui\\cliarguments\\hasstopondeprecation',
        102 => 'phpunit\\textui\\cliarguments\\stopondeprecation',
        103 => 'phpunit\\textui\\cliarguments\\hasspecificdeprecationtostopon',
        104 => 'phpunit\\textui\\cliarguments\\specificdeprecationtostopon',
        105 => 'phpunit\\textui\\cliarguments\\hasstoponerror',
        106 => 'phpunit\\textui\\cliarguments\\stoponerror',
        107 => 'phpunit\\textui\\cliarguments\\hasstoponfailure',
        108 => 'phpunit\\textui\\cliarguments\\stoponfailure',
        109 => 'phpunit\\textui\\cliarguments\\hasstoponincomplete',
        110 => 'phpunit\\textui\\cliarguments\\stoponincomplete',
        111 => 'phpunit\\textui\\cliarguments\\hasstoponnotice',
        112 => 'phpunit\\textui\\cliarguments\\stoponnotice',
        113 => 'phpunit\\textui\\cliarguments\\hasstoponrisky',
        114 => 'phpunit\\textui\\cliarguments\\stoponrisky',
        115 => 'phpunit\\textui\\cliarguments\\hasstoponskipped',
        116 => 'phpunit\\textui\\cliarguments\\stoponskipped',
        117 => 'phpunit\\textui\\cliarguments\\hasstoponwarning',
        118 => 'phpunit\\textui\\cliarguments\\stoponwarning',
        119 => 'phpunit\\textui\\cliarguments\\hasexcludefilter',
        120 => 'phpunit\\textui\\cliarguments\\excludefilter',
        121 => 'phpunit\\textui\\cliarguments\\hasfilter',
        122 => 'phpunit\\textui\\cliarguments\\filter',
        123 => 'phpunit\\textui\\cliarguments\\hasgeneratebaseline',
        124 => 'phpunit\\textui\\cliarguments\\generatebaseline',
        125 => 'phpunit\\textui\\cliarguments\\hasusebaseline',
        126 => 'phpunit\\textui\\cliarguments\\usebaseline',
        127 => 'phpunit\\textui\\cliarguments\\ignorebaseline',
        128 => 'phpunit\\textui\\cliarguments\\generateconfiguration',
        129 => 'phpunit\\textui\\cliarguments\\migrateconfiguration',
        130 => 'phpunit\\textui\\cliarguments\\hasgroups',
        131 => 'phpunit\\textui\\cliarguments\\groups',
        132 => 'phpunit\\textui\\cliarguments\\hastestscovering',
        133 => 'phpunit\\textui\\cliarguments\\testscovering',
        134 => 'phpunit\\textui\\cliarguments\\hastestsusing',
        135 => 'phpunit\\textui\\cliarguments\\testsusing',
        136 => 'phpunit\\textui\\cliarguments\\hastestsrequiringphpextension',
        137 => 'phpunit\\textui\\cliarguments\\testsrequiringphpextension',
        138 => 'phpunit\\textui\\cliarguments\\help',
        139 => 'phpunit\\textui\\cliarguments\\hasincludepath',
        140 => 'phpunit\\textui\\cliarguments\\includepath',
        141 => 'phpunit\\textui\\cliarguments\\hasinisettings',
        142 => 'phpunit\\textui\\cliarguments\\inisettings',
        143 => 'phpunit\\textui\\cliarguments\\hasjunitlogfile',
        144 => 'phpunit\\textui\\cliarguments\\junitlogfile',
        145 => 'phpunit\\textui\\cliarguments\\listgroups',
        146 => 'phpunit\\textui\\cliarguments\\listsuites',
        147 => 'phpunit\\textui\\cliarguments\\listtestfiles',
        148 => 'phpunit\\textui\\cliarguments\\listtests',
        149 => 'phpunit\\textui\\cliarguments\\haslisttestsxml',
        150 => 'phpunit\\textui\\cliarguments\\listtestsxml',
        151 => 'phpunit\\textui\\cliarguments\\hasnocoverage',
        152 => 'phpunit\\textui\\cliarguments\\nocoverage',
        153 => 'phpunit\\textui\\cliarguments\\hasnoextensions',
        154 => 'phpunit\\textui\\cliarguments\\noextensions',
        155 => 'phpunit\\textui\\cliarguments\\hasnooutput',
        156 => 'phpunit\\textui\\cliarguments\\nooutput',
        157 => 'phpunit\\textui\\cliarguments\\hasnoprogress',
        158 => 'phpunit\\textui\\cliarguments\\noprogress',
        159 => 'phpunit\\textui\\cliarguments\\hasnoresults',
        160 => 'phpunit\\textui\\cliarguments\\noresults',
        161 => 'phpunit\\textui\\cliarguments\\hasnologging',
        162 => 'phpunit\\textui\\cliarguments\\nologging',
        163 => 'phpunit\\textui\\cliarguments\\hasprocessisolation',
        164 => 'phpunit\\textui\\cliarguments\\processisolation',
        165 => 'phpunit\\textui\\cliarguments\\hasrandomorderseed',
        166 => 'phpunit\\textui\\cliarguments\\randomorderseed',
        167 => 'phpunit\\textui\\cliarguments\\hasreportuselesstests',
        168 => 'phpunit\\textui\\cliarguments\\reportuselesstests',
        169 => 'phpunit\\textui\\cliarguments\\hasresolvedependencies',
        170 => 'phpunit\\textui\\cliarguments\\resolvedependencies',
        171 => 'phpunit\\textui\\cliarguments\\hasreverselist',
        172 => 'phpunit\\textui\\cliarguments\\reverselist',
        173 => 'phpunit\\textui\\cliarguments\\hasstderr',
        174 => 'phpunit\\textui\\cliarguments\\stderr',
        175 => 'phpunit\\textui\\cliarguments\\hasstrictcoverage',
        176 => 'phpunit\\textui\\cliarguments\\strictcoverage',
        177 => 'phpunit\\textui\\cliarguments\\hasteamcitylogfile',
        178 => 'phpunit\\textui\\cliarguments\\teamcitylogfile',
        179 => 'phpunit\\textui\\cliarguments\\hasteamcityprinter',
        180 => 'phpunit\\textui\\cliarguments\\teamcityprinter',
        181 => 'phpunit\\textui\\cliarguments\\hastestdoxhtmlfile',
        182 => 'phpunit\\textui\\cliarguments\\testdoxhtmlfile',
        183 => 'phpunit\\textui\\cliarguments\\hastestdoxtextfile',
        184 => 'phpunit\\textui\\cliarguments\\testdoxtextfile',
        185 => 'phpunit\\textui\\cliarguments\\hastestdoxprinter',
        186 => 'phpunit\\textui\\cliarguments\\testdoxprinter',
        187 => 'phpunit\\textui\\cliarguments\\hastestdoxprintersummary',
        188 => 'phpunit\\textui\\cliarguments\\testdoxprintersummary',
        189 => 'phpunit\\textui\\cliarguments\\hastestsuffixes',
        190 => 'phpunit\\textui\\cliarguments\\testsuffixes',
        191 => 'phpunit\\textui\\cliarguments\\hastestsuite',
        192 => 'phpunit\\textui\\cliarguments\\testsuite',
        193 => 'phpunit\\textui\\cliarguments\\hasexcludedtestsuite',
        194 => 'phpunit\\textui\\cliarguments\\excludedtestsuite',
        195 => 'phpunit\\textui\\cliarguments\\usedefaultconfiguration',
        196 => 'phpunit\\textui\\cliarguments\\hasdisplaydetailsonallissues',
        197 => 'phpunit\\textui\\cliarguments\\displaydetailsonallissues',
        198 => 'phpunit\\textui\\cliarguments\\hasdisplaydetailsonincompletetests',
        199 => 'phpunit\\textui\\cliarguments\\displaydetailsonincompletetests',
        200 => 'phpunit\\textui\\cliarguments\\hasdisplaydetailsonskippedtests',
        201 => 'phpunit\\textui\\cliarguments\\displaydetailsonskippedtests',
        202 => 'phpunit\\textui\\cliarguments\\hasdisplaydetailsonteststhattriggerdeprecations',
        203 => 'phpunit\\textui\\cliarguments\\displaydetailsonteststhattriggerdeprecations',
        204 => 'phpunit\\textui\\cliarguments\\hasdisplaydetailsonphpunitdeprecations',
        205 => 'phpunit\\textui\\cliarguments\\displaydetailsonphpunitdeprecations',
        206 => 'phpunit\\textui\\cliarguments\\hasdisplaydetailsonteststhattriggererrors',
        207 => 'phpunit\\textui\\cliarguments\\displaydetailsonteststhattriggererrors',
        208 => 'phpunit\\textui\\cliarguments\\hasdisplaydetailsonteststhattriggernotices',
        209 => 'phpunit\\textui\\cliarguments\\displaydetailsonteststhattriggernotices',
        210 => 'phpunit\\textui\\cliarguments\\hasdisplaydetailsonteststhattriggerwarnings',
        211 => 'phpunit\\textui\\cliarguments\\displaydetailsonteststhattriggerwarnings',
        212 => 'phpunit\\textui\\cliarguments\\version',
        213 => 'phpunit\\textui\\cliarguments\\haslogeventstext',
        214 => 'phpunit\\textui\\cliarguments\\logeventstext',
        215 => 'phpunit\\textui\\cliarguments\\haslogeventsverbosetext',
        216 => 'phpunit\\textui\\cliarguments\\logeventsverbosetext',
        217 => 'phpunit\\textui\\cliarguments\\debug',
        218 => 'phpunit\\textui\\cliarguments\\hasextensions',
        219 => 'phpunit\\textui\\cliarguments\\extensions',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Cli/Exception.php' => 
    array (
      0 => '949e51af2d37a2cc4180dddbbff84882ce55f540cf2e4cc6ea036d42285ab80d',
      1 => 
      array (
        0 => 'phpunit\\textui\\cliarguments\\exception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Cli/XmlConfigurationFileFinder.php' => 
    array (
      0 => '99425f9f85dd5214e56ecd86e336bb1de3a8c712b41d55d9e38c2e9c2ec502c1',
      1 => 
      array (
        0 => 'phpunit\\textui\\cliarguments\\xmlconfigurationfilefinder',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\cliarguments\\find',
        1 => 'phpunit\\textui\\cliarguments\\configurationfileindirectory',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/CodeCoverageFilterRegistry.php' => 
    array (
      0 => '0963b61fe4c76f98d7c748851ea88358191e6f3ce74971afca35d473b3bd56f3',
      1 => 
      array (
        0 => 'phpunit\\textui\\configuration\\codecoveragefilterregistry',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\configuration\\instance',
        1 => 'phpunit\\textui\\configuration\\get',
        2 => 'phpunit\\textui\\configuration\\init',
        3 => 'phpunit\\textui\\configuration\\configured',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Configuration.php' => 
    array (
      0 => '1893053c5dc713564190efc9acbcd1c59a731631bfc6e762c542bd4ed43040c2',
      1 => 
      array (
        0 => 'phpunit\\textui\\configuration\\configuration',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\configuration\\__construct',
        1 => 'phpunit\\textui\\configuration\\hascliarguments',
        2 => 'phpunit\\textui\\configuration\\cliarguments',
        3 => 'phpunit\\textui\\configuration\\hasconfigurationfile',
        4 => 'phpunit\\textui\\configuration\\configurationfile',
        5 => 'phpunit\\textui\\configuration\\hasbootstrap',
        6 => 'phpunit\\textui\\configuration\\bootstrap',
        7 => 'phpunit\\textui\\configuration\\cacheresult',
        8 => 'phpunit\\textui\\configuration\\hascachedirectory',
        9 => 'phpunit\\textui\\configuration\\cachedirectory',
        10 => 'phpunit\\textui\\configuration\\hascoveragecachedirectory',
        11 => 'phpunit\\textui\\configuration\\coveragecachedirectory',
        12 => 'phpunit\\textui\\configuration\\source',
        13 => 'phpunit\\textui\\configuration\\testresultcachefile',
        14 => 'phpunit\\textui\\configuration\\ignoredeprecatedcodeunitsfromcodecoverage',
        15 => 'phpunit\\textui\\configuration\\disablecodecoverageignore',
        16 => 'phpunit\\textui\\configuration\\pathcoverage',
        17 => 'phpunit\\textui\\configuration\\hascoveragereport',
        18 => 'phpunit\\textui\\configuration\\hascoverageclover',
        19 => 'phpunit\\textui\\configuration\\coverageclover',
        20 => 'phpunit\\textui\\configuration\\hascoveragecobertura',
        21 => 'phpunit\\textui\\configuration\\coveragecobertura',
        22 => 'phpunit\\textui\\configuration\\hascoveragecrap4j',
        23 => 'phpunit\\textui\\configuration\\coveragecrap4j',
        24 => 'phpunit\\textui\\configuration\\coveragecrap4jthreshold',
        25 => 'phpunit\\textui\\configuration\\hascoveragehtml',
        26 => 'phpunit\\textui\\configuration\\coveragehtml',
        27 => 'phpunit\\textui\\configuration\\coveragehtmllowupperbound',
        28 => 'phpunit\\textui\\configuration\\coveragehtmlhighlowerbound',
        29 => 'phpunit\\textui\\configuration\\coveragehtmlcolorsuccesslow',
        30 => 'phpunit\\textui\\configuration\\coveragehtmlcolorsuccessmedium',
        31 => 'phpunit\\textui\\configuration\\coveragehtmlcolorsuccesshigh',
        32 => 'phpunit\\textui\\configuration\\coveragehtmlcolorwarning',
        33 => 'phpunit\\textui\\configuration\\coveragehtmlcolordanger',
        34 => 'phpunit\\textui\\configuration\\hascoveragehtmlcustomcssfile',
        35 => 'phpunit\\textui\\configuration\\coveragehtmlcustomcssfile',
        36 => 'phpunit\\textui\\configuration\\hascoveragephp',
        37 => 'phpunit\\textui\\configuration\\coveragephp',
        38 => 'phpunit\\textui\\configuration\\hascoveragetext',
        39 => 'phpunit\\textui\\configuration\\coveragetext',
        40 => 'phpunit\\textui\\configuration\\coveragetextshowuncoveredfiles',
        41 => 'phpunit\\textui\\configuration\\coveragetextshowonlysummary',
        42 => 'phpunit\\textui\\configuration\\hascoveragexml',
        43 => 'phpunit\\textui\\configuration\\coveragexml',
        44 => 'phpunit\\textui\\configuration\\failonallissues',
        45 => 'phpunit\\textui\\configuration\\failondeprecation',
        46 => 'phpunit\\textui\\configuration\\failonphpunitdeprecation',
        47 => 'phpunit\\textui\\configuration\\failonphpunitwarning',
        48 => 'phpunit\\textui\\configuration\\failonemptytestsuite',
        49 => 'phpunit\\textui\\configuration\\failonincomplete',
        50 => 'phpunit\\textui\\configuration\\failonnotice',
        51 => 'phpunit\\textui\\configuration\\failonrisky',
        52 => 'phpunit\\textui\\configuration\\failonskipped',
        53 => 'phpunit\\textui\\configuration\\failonwarning',
        54 => 'phpunit\\textui\\configuration\\donotfailondeprecation',
        55 => 'phpunit\\textui\\configuration\\donotfailonphpunitdeprecation',
        56 => 'phpunit\\textui\\configuration\\donotfailonphpunitwarning',
        57 => 'phpunit\\textui\\configuration\\donotfailonemptytestsuite',
        58 => 'phpunit\\textui\\configuration\\donotfailonincomplete',
        59 => 'phpunit\\textui\\configuration\\donotfailonnotice',
        60 => 'phpunit\\textui\\configuration\\donotfailonrisky',
        61 => 'phpunit\\textui\\configuration\\donotfailonskipped',
        62 => 'phpunit\\textui\\configuration\\donotfailonwarning',
        63 => 'phpunit\\textui\\configuration\\stopondefect',
        64 => 'phpunit\\textui\\configuration\\stopondeprecation',
        65 => 'phpunit\\textui\\configuration\\hasspecificdeprecationtostopon',
        66 => 'phpunit\\textui\\configuration\\specificdeprecationtostopon',
        67 => 'phpunit\\textui\\configuration\\stoponerror',
        68 => 'phpunit\\textui\\configuration\\stoponfailure',
        69 => 'phpunit\\textui\\configuration\\stoponincomplete',
        70 => 'phpunit\\textui\\configuration\\stoponnotice',
        71 => 'phpunit\\textui\\configuration\\stoponrisky',
        72 => 'phpunit\\textui\\configuration\\stoponskipped',
        73 => 'phpunit\\textui\\configuration\\stoponwarning',
        74 => 'phpunit\\textui\\configuration\\outputtostandarderrorstream',
        75 => 'phpunit\\textui\\configuration\\columns',
        76 => 'phpunit\\textui\\configuration\\noextensions',
        77 => 'phpunit\\textui\\configuration\\haspharextensiondirectory',
        78 => 'phpunit\\textui\\configuration\\pharextensiondirectory',
        79 => 'phpunit\\textui\\configuration\\extensionbootstrappers',
        80 => 'phpunit\\textui\\configuration\\backupglobals',
        81 => 'phpunit\\textui\\configuration\\backupstaticproperties',
        82 => 'phpunit\\textui\\configuration\\bestrictaboutchangestoglobalstate',
        83 => 'phpunit\\textui\\configuration\\colors',
        84 => 'phpunit\\textui\\configuration\\processisolation',
        85 => 'phpunit\\textui\\configuration\\enforcetimelimit',
        86 => 'phpunit\\textui\\configuration\\defaulttimelimit',
        87 => 'phpunit\\textui\\configuration\\timeoutforsmalltests',
        88 => 'phpunit\\textui\\configuration\\timeoutformediumtests',
        89 => 'phpunit\\textui\\configuration\\timeoutforlargetests',
        90 => 'phpunit\\textui\\configuration\\reportuselesstests',
        91 => 'phpunit\\textui\\configuration\\strictcoverage',
        92 => 'phpunit\\textui\\configuration\\disallowtestoutput',
        93 => 'phpunit\\textui\\configuration\\displaydetailsonallissues',
        94 => 'phpunit\\textui\\configuration\\displaydetailsonincompletetests',
        95 => 'phpunit\\textui\\configuration\\displaydetailsonskippedtests',
        96 => 'phpunit\\textui\\configuration\\displaydetailsonteststhattriggerdeprecations',
        97 => 'phpunit\\textui\\configuration\\displaydetailsonphpunitdeprecations',
        98 => 'phpunit\\textui\\configuration\\displaydetailsonteststhattriggererrors',
        99 => 'phpunit\\textui\\configuration\\displaydetailsonteststhattriggernotices',
        100 => 'phpunit\\textui\\configuration\\displaydetailsonteststhattriggerwarnings',
        101 => 'phpunit\\textui\\configuration\\reversedefectlist',
        102 => 'phpunit\\textui\\configuration\\requirecoveragemetadata',
        103 => 'phpunit\\textui\\configuration\\noprogress',
        104 => 'phpunit\\textui\\configuration\\noresults',
        105 => 'phpunit\\textui\\configuration\\nooutput',
        106 => 'phpunit\\textui\\configuration\\executionorder',
        107 => 'phpunit\\textui\\configuration\\executionorderdefects',
        108 => 'phpunit\\textui\\configuration\\resolvedependencies',
        109 => 'phpunit\\textui\\configuration\\haslogfileteamcity',
        110 => 'phpunit\\textui\\configuration\\logfileteamcity',
        111 => 'phpunit\\textui\\configuration\\haslogfilejunit',
        112 => 'phpunit\\textui\\configuration\\logfilejunit',
        113 => 'phpunit\\textui\\configuration\\haslogfiletestdoxhtml',
        114 => 'phpunit\\textui\\configuration\\logfiletestdoxhtml',
        115 => 'phpunit\\textui\\configuration\\haslogfiletestdoxtext',
        116 => 'phpunit\\textui\\configuration\\logfiletestdoxtext',
        117 => 'phpunit\\textui\\configuration\\haslogeventstext',
        118 => 'phpunit\\textui\\configuration\\logeventstext',
        119 => 'phpunit\\textui\\configuration\\haslogeventsverbosetext',
        120 => 'phpunit\\textui\\configuration\\logeventsverbosetext',
        121 => 'phpunit\\textui\\configuration\\outputisteamcity',
        122 => 'phpunit\\textui\\configuration\\outputistestdox',
        123 => 'phpunit\\textui\\configuration\\testdoxoutputwithsummary',
        124 => 'phpunit\\textui\\configuration\\hastestscovering',
        125 => 'phpunit\\textui\\configuration\\testscovering',
        126 => 'phpunit\\textui\\configuration\\hastestsusing',
        127 => 'phpunit\\textui\\configuration\\testsusing',
        128 => 'phpunit\\textui\\configuration\\hastestsrequiringphpextension',
        129 => 'phpunit\\textui\\configuration\\testsrequiringphpextension',
        130 => 'phpunit\\textui\\configuration\\hasfilter',
        131 => 'phpunit\\textui\\configuration\\filter',
        132 => 'phpunit\\textui\\configuration\\hasexcludefilter',
        133 => 'phpunit\\textui\\configuration\\excludefilter',
        134 => 'phpunit\\textui\\configuration\\hasgroups',
        135 => 'phpunit\\textui\\configuration\\groups',
        136 => 'phpunit\\textui\\configuration\\hasexcludegroups',
        137 => 'phpunit\\textui\\configuration\\excludegroups',
        138 => 'phpunit\\textui\\configuration\\randomorderseed',
        139 => 'phpunit\\textui\\configuration\\includeuncoveredfiles',
        140 => 'phpunit\\textui\\configuration\\testsuite',
        141 => 'phpunit\\textui\\configuration\\includetestsuite',
        142 => 'phpunit\\textui\\configuration\\excludetestsuite',
        143 => 'phpunit\\textui\\configuration\\hasdefaulttestsuite',
        144 => 'phpunit\\textui\\configuration\\defaulttestsuite',
        145 => 'phpunit\\textui\\configuration\\testsuffixes',
        146 => 'phpunit\\textui\\configuration\\php',
        147 => 'phpunit\\textui\\configuration\\controlgarbagecollector',
        148 => 'phpunit\\textui\\configuration\\numberoftestsbeforegarbagecollection',
        149 => 'phpunit\\textui\\configuration\\hasgeneratebaseline',
        150 => 'phpunit\\textui\\configuration\\generatebaseline',
        151 => 'phpunit\\textui\\configuration\\debug',
        152 => 'phpunit\\textui\\configuration\\shortenarraysforexportthreshold',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Exception/CannotFindSchemaException.php' => 
    array (
      0 => 'c4a498bc24749136e622f9c1a14552d97cfd46513ca7b67a11d8160e37d13dd7',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\cannotfindschemaexception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Exception/CodeCoverageReportNotConfiguredException.php' => 
    array (
      0 => 'bc08b56e47e85787639313e9063bf2cc998d10142ceade161349bc95a6ed0f1d',
      1 => 
      array (
        0 => 'phpunit\\textui\\configuration\\codecoveragereportnotconfiguredexception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Exception/ConfigurationCannotBeBuiltException.php' => 
    array (
      0 => 'a10618c45b793a63d285d393e50c85efeb98033809a574e121c72073a0f51a7a',
      1 => 
      array (
        0 => 'phpunit\\textui\\configuration\\configurationcannotbebuiltexception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Exception/Exception.php' => 
    array (
      0 => '80423bd88ca93cd314e7d3aaaae9cb199f8a52f9db2521938f5e32ba39f22643',
      1 => 
      array (
        0 => 'phpunit\\textui\\configuration\\exception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Exception/FilterNotConfiguredException.php' => 
    array (
      0 => '1ff0f1532614424c50c0ad6655114cee24803c2091ba0441588565e4e310600e',
      1 => 
      array (
        0 => 'phpunit\\textui\\configuration\\filternotconfiguredexception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Exception/LoggingNotConfiguredException.php' => 
    array (
      0 => '201c01829509a1d4c37c38b87261762131ac84ae6038bb8b49f17142e462f8fc',
      1 => 
      array (
        0 => 'phpunit\\textui\\configuration\\loggingnotconfiguredexception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Exception/NoBaselineException.php' => 
    array (
      0 => '6703df3a8f00f26f1a958aa7fac2cc472ebeb39bc9d059075f2840f1d995e1a8',
      1 => 
      array (
        0 => 'phpunit\\textui\\configuration\\nobaselineexception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Exception/NoBootstrapException.php' => 
    array (
      0 => '50bb4f0ceaa8898d3d9e9c8371e62de7d25a8521f2456e0d036ca70c8ff55253',
      1 => 
      array (
        0 => 'phpunit\\textui\\configuration\\nobootstrapexception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Exception/NoCacheDirectoryException.php' => 
    array (
      0 => '13e6bcaac0b01a8bfee2dabce47092bece817edd33f5486b6ed06834b33781ab',
      1 => 
      array (
        0 => 'phpunit\\textui\\configuration\\nocachedirectoryexception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Exception/NoConfigurationFileException.php' => 
    array (
      0 => 'e910f155103d8a6a8dc9b71393c607dbb9881b202363d834d0812affc1945511',
      1 => 
      array (
        0 => 'phpunit\\textui\\configuration\\noconfigurationfileexception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Exception/NoCoverageCacheDirectoryException.php' => 
    array (
      0 => '2f8d156fe9e7dbbf647d7f6c1a5ee1f9ad470cc437a9ac2788be9204f26a3c80',
      1 => 
      array (
        0 => 'phpunit\\textui\\configuration\\nocoveragecachedirectoryexception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Exception/NoCustomCssFileException.php' => 
    array (
      0 => 'ca486689a615cddbff5f262277c4f369dcd41b34d6891ad757526f736572e3d2',
      1 => 
      array (
        0 => 'phpunit\\textui\\configuration\\nocustomcssfileexception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Exception/NoDefaultTestSuiteException.php' => 
    array (
      0 => 'd7a2ec4176d667adb24028a259ec1abe85541bce382b9f8a9e9ea469454146ce',
      1 => 
      array (
        0 => 'phpunit\\textui\\configuration\\nodefaulttestsuiteexception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Exception/NoPharExtensionDirectoryException.php' => 
    array (
      0 => 'e869ad92dc39f5d2a16c53da6cd8a4aa889586f27f714d81341a862084ed687d',
      1 => 
      array (
        0 => 'phpunit\\textui\\configuration\\nopharextensiondirectoryexception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Exception/SpecificDeprecationToStopOnNotConfiguredException.php' => 
    array (
      0 => 'f00e2925bb819dd7d77f94157be35aeef6f4f2aae7fbdd897a45cd4b6c82ce17',
      1 => 
      array (
        0 => 'phpunit\\textui\\configuration\\specificdeprecationtostoponnotconfiguredexception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Merger.php' => 
    array (
      0 => '872c621b6b07bf36ee5dc5b6aa50b306892fa71ebd6b698aa8ae4cce9828095b',
      1 => 
      array (
        0 => 'phpunit\\textui\\configuration\\merger',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\configuration\\merge',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/PhpHandler.php' => 
    array (
      0 => '65acfc365e7968ff47af2f739d4a2765a86008c557eca88684edb9b8cba1c66d',
      1 => 
      array (
        0 => 'phpunit\\textui\\configuration\\phphandler',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\configuration\\handle',
        1 => 'phpunit\\textui\\configuration\\handleincludepaths',
        2 => 'phpunit\\textui\\configuration\\handleinisettings',
        3 => 'phpunit\\textui\\configuration\\handleconstants',
        4 => 'phpunit\\textui\\configuration\\handleglobalvariables',
        5 => 'phpunit\\textui\\configuration\\handleservervariables',
        6 => 'phpunit\\textui\\configuration\\handlevariables',
        7 => 'phpunit\\textui\\configuration\\handleenvvariables',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Registry.php' => 
    array (
      0 => '27aa272f732e49440a202b612f6d86882fd4c9988a4155c87ce50396ed226315',
      1 => 
      array (
        0 => 'phpunit\\textui\\configuration\\registry',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\configuration\\saveto',
        1 => 'phpunit\\textui\\configuration\\loadfrom',
        2 => 'phpunit\\textui\\configuration\\get',
        3 => 'phpunit\\textui\\configuration\\init',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/SourceFilter.php' => 
    array (
      0 => 'fb996f6b58c4b7c61552242be2567b159cc948133f0425ec6217c382c16d6f7b',
      1 => 
      array (
        0 => 'phpunit\\textui\\configuration\\sourcefilter',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\configuration\\instance',
        1 => 'phpunit\\textui\\configuration\\__construct',
        2 => 'phpunit\\textui\\configuration\\includes',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/SourceMapper.php' => 
    array (
      0 => '304c97e3f3a1719beb4e8ef11ab438a4fd16fd44c790be0c20e6f5d3340033d4',
      1 => 
      array (
        0 => 'phpunit\\textui\\configuration\\sourcemapper',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\configuration\\saveto',
        1 => 'phpunit\\textui\\configuration\\loadfrom',
        2 => 'phpunit\\textui\\configuration\\map',
        3 => 'phpunit\\textui\\configuration\\aggregatedirectories',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/TestSuiteBuilder.php' => 
    array (
      0 => 'deaed72f15c2120211c6ba0b7efbafae6ed8fa4e14b3035ddb18bbaac3919f58',
      1 => 
      array (
        0 => 'phpunit\\textui\\configuration\\testsuitebuilder',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\configuration\\build',
        1 => 'phpunit\\textui\\configuration\\testsuitefrompath',
        2 => 'phpunit\\textui\\configuration\\testsuitefrompathlist',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Value/Constant.php' => 
    array (
      0 => '846dcb9e01b2411e1cbb6dfae5cc4fb1063f1f12553eb3ce4ba4fcf39df5189e',
      1 => 
      array (
        0 => 'phpunit\\textui\\configuration\\constant',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\configuration\\__construct',
        1 => 'phpunit\\textui\\configuration\\name',
        2 => 'phpunit\\textui\\configuration\\value',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Value/ConstantCollection.php' => 
    array (
      0 => '1afbdb49eeb97a965e7fe8654dd5b9a5e6c1f769ea38ec2ffa03bccdd2b0b5ab',
      1 => 
      array (
        0 => 'phpunit\\textui\\configuration\\constantcollection',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\configuration\\fromarray',
        1 => 'phpunit\\textui\\configuration\\__construct',
        2 => 'phpunit\\textui\\configuration\\asarray',
        3 => 'phpunit\\textui\\configuration\\count',
        4 => 'phpunit\\textui\\configuration\\getiterator',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Value/ConstantCollectionIterator.php' => 
    array (
      0 => 'e1863501cb6f6d6aafb74df0795b094cffe76a8f0a9173e6a1562a39c5a239fa',
      1 => 
      array (
        0 => 'phpunit\\textui\\configuration\\constantcollectioniterator',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\configuration\\__construct',
        1 => 'phpunit\\textui\\configuration\\rewind',
        2 => 'phpunit\\textui\\configuration\\valid',
        3 => 'phpunit\\textui\\configuration\\key',
        4 => 'phpunit\\textui\\configuration\\current',
        5 => 'phpunit\\textui\\configuration\\next',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Value/Directory.php' => 
    array (
      0 => 'fbdfa2b7b7026400e391adb0ade707108acaaa13733826e4bffa9a14c1ca6ba5',
      1 => 
      array (
        0 => 'phpunit\\textui\\configuration\\directory',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\configuration\\__construct',
        1 => 'phpunit\\textui\\configuration\\path',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Value/DirectoryCollection.php' => 
    array (
      0 => '860e158abcc117530ee0ea7bde899946f17a56dd51898e02bdb4a15c922e590d',
      1 => 
      array (
        0 => 'phpunit\\textui\\configuration\\directorycollection',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\configuration\\fromarray',
        1 => 'phpunit\\textui\\configuration\\__construct',
        2 => 'phpunit\\textui\\configuration\\asarray',
        3 => 'phpunit\\textui\\configuration\\count',
        4 => 'phpunit\\textui\\configuration\\getiterator',
        5 => 'phpunit\\textui\\configuration\\isempty',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Value/DirectoryCollectionIterator.php' => 
    array (
      0 => '7244522c3b8340e9e60b01f57cc57f94ac6cf3503b939a5884ca54c1c631a9b5',
      1 => 
      array (
        0 => 'phpunit\\textui\\configuration\\directorycollectioniterator',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\configuration\\__construct',
        1 => 'phpunit\\textui\\configuration\\rewind',
        2 => 'phpunit\\textui\\configuration\\valid',
        3 => 'phpunit\\textui\\configuration\\key',
        4 => 'phpunit\\textui\\configuration\\current',
        5 => 'phpunit\\textui\\configuration\\next',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Value/ExtensionBootstrap.php' => 
    array (
      0 => 'a343d149be194c754675b2cb0df2eaa45497ed506015a729ead73b246128f968',
      1 => 
      array (
        0 => 'phpunit\\textui\\configuration\\extensionbootstrap',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\configuration\\__construct',
        1 => 'phpunit\\textui\\configuration\\classname',
        2 => 'phpunit\\textui\\configuration\\parameters',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Value/ExtensionBootstrapCollection.php' => 
    array (
      0 => 'db1f9fac1afc79bd6376fefed48c55fb36c4ae87c32238828f30dd9e6ce42f74',
      1 => 
      array (
        0 => 'phpunit\\textui\\configuration\\extensionbootstrapcollection',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\configuration\\fromarray',
        1 => 'phpunit\\textui\\configuration\\__construct',
        2 => 'phpunit\\textui\\configuration\\asarray',
        3 => 'phpunit\\textui\\configuration\\getiterator',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Value/ExtensionBootstrapCollectionIterator.php' => 
    array (
      0 => '06e320cda885709f1ab2e09d345712f21f463c1cce283f4d06d2d00b80920635',
      1 => 
      array (
        0 => 'phpunit\\textui\\configuration\\extensionbootstrapcollectioniterator',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\configuration\\__construct',
        1 => 'phpunit\\textui\\configuration\\rewind',
        2 => 'phpunit\\textui\\configuration\\valid',
        3 => 'phpunit\\textui\\configuration\\key',
        4 => 'phpunit\\textui\\configuration\\current',
        5 => 'phpunit\\textui\\configuration\\next',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Value/File.php' => 
    array (
      0 => '0130b0d08e9f23fce2277018ff9a74358cd3d11b518bb655f3e8e3e0870f724e',
      1 => 
      array (
        0 => 'phpunit\\textui\\configuration\\file',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\configuration\\__construct',
        1 => 'phpunit\\textui\\configuration\\path',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Value/FileCollection.php' => 
    array (
      0 => '2b901474b95cd9fa39b0fbe80d73733862aede11fe64807e86cbd46f8c793877',
      1 => 
      array (
        0 => 'phpunit\\textui\\configuration\\filecollection',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\configuration\\fromarray',
        1 => 'phpunit\\textui\\configuration\\__construct',
        2 => 'phpunit\\textui\\configuration\\asarray',
        3 => 'phpunit\\textui\\configuration\\count',
        4 => 'phpunit\\textui\\configuration\\notempty',
        5 => 'phpunit\\textui\\configuration\\getiterator',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Value/FileCollectionIterator.php' => 
    array (
      0 => '65c0fbaa1652f475952eedc4186e9d4ca3cdd9858e8a64f9709efd0b59964519',
      1 => 
      array (
        0 => 'phpunit\\textui\\configuration\\filecollectioniterator',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\configuration\\__construct',
        1 => 'phpunit\\textui\\configuration\\rewind',
        2 => 'phpunit\\textui\\configuration\\valid',
        3 => 'phpunit\\textui\\configuration\\key',
        4 => 'phpunit\\textui\\configuration\\current',
        5 => 'phpunit\\textui\\configuration\\next',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Value/FilterDirectory.php' => 
    array (
      0 => 'cb52319d6f8dc0ef2ccef888a00ae1745bdb46c7df98586130f10fbf9156ffe9',
      1 => 
      array (
        0 => 'phpunit\\textui\\configuration\\filterdirectory',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\configuration\\__construct',
        1 => 'phpunit\\textui\\configuration\\path',
        2 => 'phpunit\\textui\\configuration\\prefix',
        3 => 'phpunit\\textui\\configuration\\suffix',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Value/FilterDirectoryCollection.php' => 
    array (
      0 => '8e4aaad50c1ffb70ec0ba3cf12bc1c70bb8fe7c2d0c392edc43219d98b034186',
      1 => 
      array (
        0 => 'phpunit\\textui\\configuration\\filterdirectorycollection',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\configuration\\fromarray',
        1 => 'phpunit\\textui\\configuration\\__construct',
        2 => 'phpunit\\textui\\configuration\\asarray',
        3 => 'phpunit\\textui\\configuration\\count',
        4 => 'phpunit\\textui\\configuration\\notempty',
        5 => 'phpunit\\textui\\configuration\\getiterator',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Value/FilterDirectoryCollectionIterator.php' => 
    array (
      0 => 'fb433541c9a06c989e868eb2d5086cb99e6d805e330d568a4e00c0d9685c2f1a',
      1 => 
      array (
        0 => 'phpunit\\textui\\configuration\\filterdirectorycollectioniterator',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\configuration\\__construct',
        1 => 'phpunit\\textui\\configuration\\rewind',
        2 => 'phpunit\\textui\\configuration\\valid',
        3 => 'phpunit\\textui\\configuration\\key',
        4 => 'phpunit\\textui\\configuration\\current',
        5 => 'phpunit\\textui\\configuration\\next',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Value/Group.php' => 
    array (
      0 => 'e0c6d46351b9336dc56720cfeca7824aeef0409f215e34c13d34c98b4ad0a67e',
      1 => 
      array (
        0 => 'phpunit\\textui\\configuration\\group',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\configuration\\__construct',
        1 => 'phpunit\\textui\\configuration\\name',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Value/GroupCollection.php' => 
    array (
      0 => 'd194cf081f3f43a706f221297b08c4429c2869466cff7894d91d448c94c3441a',
      1 => 
      array (
        0 => 'phpunit\\textui\\configuration\\groupcollection',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\configuration\\fromarray',
        1 => 'phpunit\\textui\\configuration\\__construct',
        2 => 'phpunit\\textui\\configuration\\asarray',
        3 => 'phpunit\\textui\\configuration\\asarrayofstrings',
        4 => 'phpunit\\textui\\configuration\\isempty',
        5 => 'phpunit\\textui\\configuration\\getiterator',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Value/GroupCollectionIterator.php' => 
    array (
      0 => '3a34a765716b8d547874a0fd4d745d147179c3fb766684a121ac38994df0bba1',
      1 => 
      array (
        0 => 'phpunit\\textui\\configuration\\groupcollectioniterator',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\configuration\\__construct',
        1 => 'phpunit\\textui\\configuration\\rewind',
        2 => 'phpunit\\textui\\configuration\\valid',
        3 => 'phpunit\\textui\\configuration\\key',
        4 => 'phpunit\\textui\\configuration\\current',
        5 => 'phpunit\\textui\\configuration\\next',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Value/IniSetting.php' => 
    array (
      0 => '1527dde4cbd627c7ca936b6629eeaa2ada0d9c22214460169d5b5adb29daf749',
      1 => 
      array (
        0 => 'phpunit\\textui\\configuration\\inisetting',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\configuration\\__construct',
        1 => 'phpunit\\textui\\configuration\\name',
        2 => 'phpunit\\textui\\configuration\\value',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Value/IniSettingCollection.php' => 
    array (
      0 => '1149f689d6c2160c9cb84c83c5ada7d9a9b7bd673f8ff14bfdddcee297a27fad',
      1 => 
      array (
        0 => 'phpunit\\textui\\configuration\\inisettingcollection',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\configuration\\fromarray',
        1 => 'phpunit\\textui\\configuration\\__construct',
        2 => 'phpunit\\textui\\configuration\\asarray',
        3 => 'phpunit\\textui\\configuration\\count',
        4 => 'phpunit\\textui\\configuration\\getiterator',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Value/IniSettingCollectionIterator.php' => 
    array (
      0 => 'b3ffefde7497a7b2d8be00d6e8db8d401e63270309f039710152c1e89bc0980b',
      1 => 
      array (
        0 => 'phpunit\\textui\\configuration\\inisettingcollectioniterator',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\configuration\\__construct',
        1 => 'phpunit\\textui\\configuration\\rewind',
        2 => 'phpunit\\textui\\configuration\\valid',
        3 => 'phpunit\\textui\\configuration\\key',
        4 => 'phpunit\\textui\\configuration\\current',
        5 => 'phpunit\\textui\\configuration\\next',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Value/Php.php' => 
    array (
      0 => '8a79b0713f6418992e23e3a63d3f08ca2e08b88904030652a1742b2a744e97bd',
      1 => 
      array (
        0 => 'phpunit\\textui\\configuration\\php',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\configuration\\__construct',
        1 => 'phpunit\\textui\\configuration\\includepaths',
        2 => 'phpunit\\textui\\configuration\\inisettings',
        3 => 'phpunit\\textui\\configuration\\constants',
        4 => 'phpunit\\textui\\configuration\\globalvariables',
        5 => 'phpunit\\textui\\configuration\\envvariables',
        6 => 'phpunit\\textui\\configuration\\postvariables',
        7 => 'phpunit\\textui\\configuration\\getvariables',
        8 => 'phpunit\\textui\\configuration\\cookievariables',
        9 => 'phpunit\\textui\\configuration\\servervariables',
        10 => 'phpunit\\textui\\configuration\\filesvariables',
        11 => 'phpunit\\textui\\configuration\\requestvariables',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Value/Source.php' => 
    array (
      0 => 'fa0d56217fa6cc6d919e22306d04cf4aec611cdf7d57418882cf93c1a5e0e043',
      1 => 
      array (
        0 => 'phpunit\\textui\\configuration\\source',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\configuration\\__construct',
        1 => 'phpunit\\textui\\configuration\\usebaseline',
        2 => 'phpunit\\textui\\configuration\\hasbaseline',
        3 => 'phpunit\\textui\\configuration\\baseline',
        4 => 'phpunit\\textui\\configuration\\includedirectories',
        5 => 'phpunit\\textui\\configuration\\includefiles',
        6 => 'phpunit\\textui\\configuration\\excludedirectories',
        7 => 'phpunit\\textui\\configuration\\excludefiles',
        8 => 'phpunit\\textui\\configuration\\notempty',
        9 => 'phpunit\\textui\\configuration\\restrictdeprecations',
        10 => 'phpunit\\textui\\configuration\\restrictnotices',
        11 => 'phpunit\\textui\\configuration\\restrictwarnings',
        12 => 'phpunit\\textui\\configuration\\ignoresuppressionofdeprecations',
        13 => 'phpunit\\textui\\configuration\\ignoresuppressionofphpdeprecations',
        14 => 'phpunit\\textui\\configuration\\ignoresuppressionoferrors',
        15 => 'phpunit\\textui\\configuration\\ignoresuppressionofnotices',
        16 => 'phpunit\\textui\\configuration\\ignoresuppressionofphpnotices',
        17 => 'phpunit\\textui\\configuration\\ignoresuppressionofwarnings',
        18 => 'phpunit\\textui\\configuration\\ignoresuppressionofphpwarnings',
        19 => 'phpunit\\textui\\configuration\\deprecationtriggers',
        20 => 'phpunit\\textui\\configuration\\ignoreselfdeprecations',
        21 => 'phpunit\\textui\\configuration\\ignoredirectdeprecations',
        22 => 'phpunit\\textui\\configuration\\ignoreindirectdeprecations',
        23 => 'phpunit\\textui\\configuration\\identifyissuetrigger',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Value/TestDirectory.php' => 
    array (
      0 => 'afca46edb3a91732c1b78949f045b16a0261d4660ddfd73e4d3918f52f293767',
      1 => 
      array (
        0 => 'phpunit\\textui\\configuration\\testdirectory',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\configuration\\__construct',
        1 => 'phpunit\\textui\\configuration\\path',
        2 => 'phpunit\\textui\\configuration\\prefix',
        3 => 'phpunit\\textui\\configuration\\suffix',
        4 => 'phpunit\\textui\\configuration\\phpversion',
        5 => 'phpunit\\textui\\configuration\\phpversionoperator',
        6 => 'phpunit\\textui\\configuration\\groups',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Value/TestDirectoryCollection.php' => 
    array (
      0 => '2876ca70fa64fbe8fbc802c1a2b6c6a16e5306082f389947db9559ab6ac54f32',
      1 => 
      array (
        0 => 'phpunit\\textui\\configuration\\testdirectorycollection',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\configuration\\fromarray',
        1 => 'phpunit\\textui\\configuration\\__construct',
        2 => 'phpunit\\textui\\configuration\\asarray',
        3 => 'phpunit\\textui\\configuration\\count',
        4 => 'phpunit\\textui\\configuration\\getiterator',
        5 => 'phpunit\\textui\\configuration\\isempty',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Value/TestDirectoryCollectionIterator.php' => 
    array (
      0 => '55a7a17e5d9717d9f65c1a8ea41692439ef454bbce2e2a865066be8e109baac7',
      1 => 
      array (
        0 => 'phpunit\\textui\\configuration\\testdirectorycollectioniterator',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\configuration\\__construct',
        1 => 'phpunit\\textui\\configuration\\rewind',
        2 => 'phpunit\\textui\\configuration\\valid',
        3 => 'phpunit\\textui\\configuration\\key',
        4 => 'phpunit\\textui\\configuration\\current',
        5 => 'phpunit\\textui\\configuration\\next',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Value/TestFile.php' => 
    array (
      0 => '466cfbe8adfdd8d0abb5df76d72db46364064626c388d411106286008bb15063',
      1 => 
      array (
        0 => 'phpunit\\textui\\configuration\\testfile',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\configuration\\__construct',
        1 => 'phpunit\\textui\\configuration\\path',
        2 => 'phpunit\\textui\\configuration\\phpversion',
        3 => 'phpunit\\textui\\configuration\\phpversionoperator',
        4 => 'phpunit\\textui\\configuration\\groups',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Value/TestFileCollection.php' => 
    array (
      0 => '60724ecccde3c1c7e2e8901c1ef30aa5a82aac3427334885fb0f130309ebb0ce',
      1 => 
      array (
        0 => 'phpunit\\textui\\configuration\\testfilecollection',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\configuration\\fromarray',
        1 => 'phpunit\\textui\\configuration\\__construct',
        2 => 'phpunit\\textui\\configuration\\asarray',
        3 => 'phpunit\\textui\\configuration\\count',
        4 => 'phpunit\\textui\\configuration\\getiterator',
        5 => 'phpunit\\textui\\configuration\\isempty',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Value/TestFileCollectionIterator.php' => 
    array (
      0 => '4cb2462af39de416322c49b28b44de31d60550b2f16ee7c723eaef76193731bc',
      1 => 
      array (
        0 => 'phpunit\\textui\\configuration\\testfilecollectioniterator',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\configuration\\__construct',
        1 => 'phpunit\\textui\\configuration\\rewind',
        2 => 'phpunit\\textui\\configuration\\valid',
        3 => 'phpunit\\textui\\configuration\\key',
        4 => 'phpunit\\textui\\configuration\\current',
        5 => 'phpunit\\textui\\configuration\\next',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Value/TestSuite.php' => 
    array (
      0 => 'a66040835c00e2898073def2757388838f6e16140abe1a7010617cbaea9bb775',
      1 => 
      array (
        0 => 'phpunit\\textui\\configuration\\testsuite',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\configuration\\__construct',
        1 => 'phpunit\\textui\\configuration\\name',
        2 => 'phpunit\\textui\\configuration\\directories',
        3 => 'phpunit\\textui\\configuration\\files',
        4 => 'phpunit\\textui\\configuration\\exclude',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Value/TestSuiteCollection.php' => 
    array (
      0 => 'a805cbe1a7e581ebbfeaa9f72dc3f1ac77c13936c8816a49a1788725008d9c9e',
      1 => 
      array (
        0 => 'phpunit\\textui\\configuration\\testsuitecollection',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\configuration\\fromarray',
        1 => 'phpunit\\textui\\configuration\\__construct',
        2 => 'phpunit\\textui\\configuration\\asarray',
        3 => 'phpunit\\textui\\configuration\\count',
        4 => 'phpunit\\textui\\configuration\\getiterator',
        5 => 'phpunit\\textui\\configuration\\isempty',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Value/TestSuiteCollectionIterator.php' => 
    array (
      0 => '6f71efd2064e1e8e89425557fb3f8f7ea56560730002d0e5e2d5447a618ed741',
      1 => 
      array (
        0 => 'phpunit\\textui\\configuration\\testsuitecollectioniterator',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\configuration\\__construct',
        1 => 'phpunit\\textui\\configuration\\rewind',
        2 => 'phpunit\\textui\\configuration\\valid',
        3 => 'phpunit\\textui\\configuration\\key',
        4 => 'phpunit\\textui\\configuration\\current',
        5 => 'phpunit\\textui\\configuration\\next',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Value/Variable.php' => 
    array (
      0 => 'f2851241bace9d87fd2ead90dd8c2788095d1d5360211238f90d80a11d153927',
      1 => 
      array (
        0 => 'phpunit\\textui\\configuration\\variable',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\configuration\\__construct',
        1 => 'phpunit\\textui\\configuration\\name',
        2 => 'phpunit\\textui\\configuration\\value',
        3 => 'phpunit\\textui\\configuration\\force',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Value/VariableCollection.php' => 
    array (
      0 => '4ac2cbee8423fe3008ab1b5afa995c1f5095d0fc66215046c58303890923b8ea',
      1 => 
      array (
        0 => 'phpunit\\textui\\configuration\\variablecollection',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\configuration\\fromarray',
        1 => 'phpunit\\textui\\configuration\\__construct',
        2 => 'phpunit\\textui\\configuration\\asarray',
        3 => 'phpunit\\textui\\configuration\\count',
        4 => 'phpunit\\textui\\configuration\\getiterator',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Value/VariableCollectionIterator.php' => 
    array (
      0 => '05bdfea71c54933f0862a20f39bc5892b63da7c593ba2d7f1cdd43a5afd3d451',
      1 => 
      array (
        0 => 'phpunit\\textui\\configuration\\variablecollectioniterator',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\configuration\\__construct',
        1 => 'phpunit\\textui\\configuration\\rewind',
        2 => 'phpunit\\textui\\configuration\\valid',
        3 => 'phpunit\\textui\\configuration\\key',
        4 => 'phpunit\\textui\\configuration\\current',
        5 => 'phpunit\\textui\\configuration\\next',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/CodeCoverage/CodeCoverage.php' => 
    array (
      0 => 'e08a585520fb921b58d0b0eb407a47f6a7d412250523f79afe299a99ed06a19f',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\codecoverage\\codecoverage',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\codecoverage\\__construct',
        1 => 'phpunit\\textui\\xmlconfiguration\\codecoverage\\pathcoverage',
        2 => 'phpunit\\textui\\xmlconfiguration\\codecoverage\\includeuncoveredfiles',
        3 => 'phpunit\\textui\\xmlconfiguration\\codecoverage\\ignoredeprecatedcodeunits',
        4 => 'phpunit\\textui\\xmlconfiguration\\codecoverage\\disablecodecoverageignore',
        5 => 'phpunit\\textui\\xmlconfiguration\\codecoverage\\hasclover',
        6 => 'phpunit\\textui\\xmlconfiguration\\codecoverage\\clover',
        7 => 'phpunit\\textui\\xmlconfiguration\\codecoverage\\hascobertura',
        8 => 'phpunit\\textui\\xmlconfiguration\\codecoverage\\cobertura',
        9 => 'phpunit\\textui\\xmlconfiguration\\codecoverage\\hascrap4j',
        10 => 'phpunit\\textui\\xmlconfiguration\\codecoverage\\crap4j',
        11 => 'phpunit\\textui\\xmlconfiguration\\codecoverage\\hashtml',
        12 => 'phpunit\\textui\\xmlconfiguration\\codecoverage\\html',
        13 => 'phpunit\\textui\\xmlconfiguration\\codecoverage\\hasphp',
        14 => 'phpunit\\textui\\xmlconfiguration\\codecoverage\\php',
        15 => 'phpunit\\textui\\xmlconfiguration\\codecoverage\\hastext',
        16 => 'phpunit\\textui\\xmlconfiguration\\codecoverage\\text',
        17 => 'phpunit\\textui\\xmlconfiguration\\codecoverage\\hasxml',
        18 => 'phpunit\\textui\\xmlconfiguration\\codecoverage\\xml',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/CodeCoverage/Report/Clover.php' => 
    array (
      0 => '9f7196d5540fe59c3f81fc3e84ee761ed446d71d8ace276550b2425c599c09e9',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\codecoverage\\report\\clover',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\codecoverage\\report\\__construct',
        1 => 'phpunit\\textui\\xmlconfiguration\\codecoverage\\report\\target',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/CodeCoverage/Report/Cobertura.php' => 
    array (
      0 => 'e9c1f43464ec2e2be7e62aa72b78bd90053b0b11eb046a7ebbaabe7a247c24b8',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\codecoverage\\report\\cobertura',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\codecoverage\\report\\__construct',
        1 => 'phpunit\\textui\\xmlconfiguration\\codecoverage\\report\\target',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/CodeCoverage/Report/Crap4j.php' => 
    array (
      0 => 'f11b3e6d564dae7cbe0a65b3f54336d344155cf168017aeb3fddd718a469c8ae',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\codecoverage\\report\\crap4j',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\codecoverage\\report\\__construct',
        1 => 'phpunit\\textui\\xmlconfiguration\\codecoverage\\report\\target',
        2 => 'phpunit\\textui\\xmlconfiguration\\codecoverage\\report\\threshold',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/CodeCoverage/Report/Html.php' => 
    array (
      0 => '96b728ba530ab9edbc27e6644b7ee305ea785c6021fc922c760ee4a9b5d6d450',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\codecoverage\\report\\html',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\codecoverage\\report\\__construct',
        1 => 'phpunit\\textui\\xmlconfiguration\\codecoverage\\report\\target',
        2 => 'phpunit\\textui\\xmlconfiguration\\codecoverage\\report\\lowupperbound',
        3 => 'phpunit\\textui\\xmlconfiguration\\codecoverage\\report\\highlowerbound',
        4 => 'phpunit\\textui\\xmlconfiguration\\codecoverage\\report\\colorsuccesslow',
        5 => 'phpunit\\textui\\xmlconfiguration\\codecoverage\\report\\colorsuccessmedium',
        6 => 'phpunit\\textui\\xmlconfiguration\\codecoverage\\report\\colorsuccesshigh',
        7 => 'phpunit\\textui\\xmlconfiguration\\codecoverage\\report\\colorwarning',
        8 => 'phpunit\\textui\\xmlconfiguration\\codecoverage\\report\\colordanger',
        9 => 'phpunit\\textui\\xmlconfiguration\\codecoverage\\report\\hascustomcssfile',
        10 => 'phpunit\\textui\\xmlconfiguration\\codecoverage\\report\\customcssfile',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/CodeCoverage/Report/Php.php' => 
    array (
      0 => '41f9d41b7e7e07559c15897770ff74d24074160831fa2478674cb9c7077d4f1e',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\codecoverage\\report\\php',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\codecoverage\\report\\__construct',
        1 => 'phpunit\\textui\\xmlconfiguration\\codecoverage\\report\\target',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/CodeCoverage/Report/Text.php' => 
    array (
      0 => '1d0da6da414085561e96c0218c8e85c6229eb8f5dc97eaad5c7ee8c9b3e65ff3',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\codecoverage\\report\\text',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\codecoverage\\report\\__construct',
        1 => 'phpunit\\textui\\xmlconfiguration\\codecoverage\\report\\target',
        2 => 'phpunit\\textui\\xmlconfiguration\\codecoverage\\report\\showuncoveredfiles',
        3 => 'phpunit\\textui\\xmlconfiguration\\codecoverage\\report\\showonlysummary',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/CodeCoverage/Report/Xml.php' => 
    array (
      0 => '9398189ecd4e33c2a8cfede78922f31ddd8f0dcb553e74f99a0c3309df6ea299',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\codecoverage\\report\\xml',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\codecoverage\\report\\__construct',
        1 => 'phpunit\\textui\\xmlconfiguration\\codecoverage\\report\\target',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/Configuration.php' => 
    array (
      0 => '164eb7385f742d21c6645493b865a3bd4c4d34562da48be4cceb21363e4728e8',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\configuration',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\__construct',
        1 => 'phpunit\\textui\\xmlconfiguration\\extensions',
        2 => 'phpunit\\textui\\xmlconfiguration\\source',
        3 => 'phpunit\\textui\\xmlconfiguration\\codecoverage',
        4 => 'phpunit\\textui\\xmlconfiguration\\groups',
        5 => 'phpunit\\textui\\xmlconfiguration\\logging',
        6 => 'phpunit\\textui\\xmlconfiguration\\php',
        7 => 'phpunit\\textui\\xmlconfiguration\\phpunit',
        8 => 'phpunit\\textui\\xmlconfiguration\\testsuite',
        9 => 'phpunit\\textui\\xmlconfiguration\\isdefault',
        10 => 'phpunit\\textui\\xmlconfiguration\\wasloadedfromfile',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/DefaultConfiguration.php' => 
    array (
      0 => '1eaeb2318462bb0fb9be4b81fb578ecaadf593ae1d6eaf5eab308901492572d9',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\defaultconfiguration',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\create',
        1 => 'phpunit\\textui\\xmlconfiguration\\isdefault',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/Exception.php' => 
    array (
      0 => '97954264e491e45960ef1042b8efea31907c893d48fa2999d24c91f2d8472a80',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\exception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/Generator.php' => 
    array (
      0 => '9ef845c2a9b6e27be1ef2206c255ea6bdef8155546105314a3185e40ca62e402',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\generator',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\generatedefaultconfiguration',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/Groups.php' => 
    array (
      0 => '357e709f9dfcc3ee2eeffd07490ce516cd2486182b2103cfc8cf0a25c6ca320f',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\groups',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\__construct',
        1 => 'phpunit\\textui\\xmlconfiguration\\hasinclude',
        2 => 'phpunit\\textui\\xmlconfiguration\\include',
        3 => 'phpunit\\textui\\xmlconfiguration\\hasexclude',
        4 => 'phpunit\\textui\\xmlconfiguration\\exclude',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/LoadedFromFileConfiguration.php' => 
    array (
      0 => '5cd4384af2bc16820419412407f2893b8ca888814bc239f2d40984d10a2847de',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\loadedfromfileconfiguration',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\__construct',
        1 => 'phpunit\\textui\\xmlconfiguration\\filename',
        2 => 'phpunit\\textui\\xmlconfiguration\\hasvalidationerrors',
        3 => 'phpunit\\textui\\xmlconfiguration\\validationerrors',
        4 => 'phpunit\\textui\\xmlconfiguration\\wasloadedfromfile',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/Loader.php' => 
    array (
      0 => 'ae6badf188dab561d29c8320895a0cc89c04f2f86b47e80b17b70a0a78b6ede4',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\loader',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\load',
        1 => 'phpunit\\textui\\xmlconfiguration\\logging',
        2 => 'phpunit\\textui\\xmlconfiguration\\extensions',
        3 => 'phpunit\\textui\\xmlconfiguration\\toabsolutepath',
        4 => 'phpunit\\textui\\xmlconfiguration\\source',
        5 => 'phpunit\\textui\\xmlconfiguration\\codecoverage',
        6 => 'phpunit\\textui\\xmlconfiguration\\booleanfromstring',
        7 => 'phpunit\\textui\\xmlconfiguration\\valuefromstring',
        8 => 'phpunit\\textui\\xmlconfiguration\\readfilterdirectories',
        9 => 'phpunit\\textui\\xmlconfiguration\\readfilterfiles',
        10 => 'phpunit\\textui\\xmlconfiguration\\groups',
        11 => 'phpunit\\textui\\xmlconfiguration\\parsebooleanattribute',
        12 => 'phpunit\\textui\\xmlconfiguration\\parseintegerattribute',
        13 => 'phpunit\\textui\\xmlconfiguration\\parsestringattribute',
        14 => 'phpunit\\textui\\xmlconfiguration\\parsestringattributewithdefault',
        15 => 'phpunit\\textui\\xmlconfiguration\\parseinteger',
        16 => 'phpunit\\textui\\xmlconfiguration\\php',
        17 => 'phpunit\\textui\\xmlconfiguration\\phpunit',
        18 => 'phpunit\\textui\\xmlconfiguration\\parsecolors',
        19 => 'phpunit\\textui\\xmlconfiguration\\parsecolumns',
        20 => 'phpunit\\textui\\xmlconfiguration\\testsuite',
        21 => 'phpunit\\textui\\xmlconfiguration\\parsetestsuiteelements',
        22 => 'phpunit\\textui\\xmlconfiguration\\element',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/Logging/Junit.php' => 
    array (
      0 => 'cc8f8c3b33ff677ad0ef88f484e9ed2d202714eca1e863b49147a87333d6f829',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\logging\\junit',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\logging\\__construct',
        1 => 'phpunit\\textui\\xmlconfiguration\\logging\\target',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/Logging/Logging.php' => 
    array (
      0 => '63dd547f716115bdb1371eb995b8efe49fa2cbc77b27b15ba54e553f1a087453',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\logging\\logging',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\logging\\__construct',
        1 => 'phpunit\\textui\\xmlconfiguration\\logging\\hasjunit',
        2 => 'phpunit\\textui\\xmlconfiguration\\logging\\junit',
        3 => 'phpunit\\textui\\xmlconfiguration\\logging\\hasteamcity',
        4 => 'phpunit\\textui\\xmlconfiguration\\logging\\teamcity',
        5 => 'phpunit\\textui\\xmlconfiguration\\logging\\hastestdoxhtml',
        6 => 'phpunit\\textui\\xmlconfiguration\\logging\\testdoxhtml',
        7 => 'phpunit\\textui\\xmlconfiguration\\logging\\hastestdoxtext',
        8 => 'phpunit\\textui\\xmlconfiguration\\logging\\testdoxtext',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/Logging/TeamCity.php' => 
    array (
      0 => 'ede6e5a604d57dfee0e49161022476507501edbbc2e0774f09395442c255d5ce',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\logging\\teamcity',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\logging\\__construct',
        1 => 'phpunit\\textui\\xmlconfiguration\\logging\\target',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/Logging/TestDox/Html.php' => 
    array (
      0 => '928bfdcb302572329eb2d2faa40d699859e9f0b077111b8fc365ae5e833ef9c3',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\logging\\testdox\\html',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\logging\\testdox\\__construct',
        1 => 'phpunit\\textui\\xmlconfiguration\\logging\\testdox\\target',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/Logging/TestDox/Text.php' => 
    array (
      0 => '2c14f7ba0684773380cd93cafb404902afcee2778fd691ae71f7fd72a3f7242b',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\logging\\testdox\\text',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\logging\\testdox\\__construct',
        1 => 'phpunit\\textui\\xmlconfiguration\\logging\\testdox\\target',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/Migration/MigrationBuilder.php' => 
    array (
      0 => 'e5155617f48d48acbf32560417e7fac912b0bd36c4250e85709c0785b7066ee7',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\migrationbuilder',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\build',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/Migration/MigrationException.php' => 
    array (
      0 => '692747516f2a6d0dafbb98c2b780d15ee772e58b3bc8f4798d08706d5a5971f3',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\migrationexception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/Migration/Migrations/ConvertLogTypes.php' => 
    array (
      0 => '4374f6ea409c07f48f5ab7832d1b224f40778d7e0580da54245ef812f552674c',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\convertlogtypes',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\migrate',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/Migration/Migrations/CoverageCloverToReport.php' => 
    array (
      0 => 'bf799f26e3d1f8dc8c858edebc75b62cf6553a1f7a51e75f01d3a2950534706f',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\coverageclovertoreport',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\fortype',
        1 => 'phpunit\\textui\\xmlconfiguration\\toreportformat',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/Migration/Migrations/CoverageCrap4jToReport.php' => 
    array (
      0 => 'ba35fab7d61c886910c85ec1d1ac5cc0e98e7f0fd4280cf1fd87397274a7bf60',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\coveragecrap4jtoreport',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\fortype',
        1 => 'phpunit\\textui\\xmlconfiguration\\toreportformat',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/Migration/Migrations/CoverageHtmlToReport.php' => 
    array (
      0 => '7a0031057a8b1d1a372348e425523a275804365d94ed6ff9e91df18bb5788066',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\coveragehtmltoreport',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\fortype',
        1 => 'phpunit\\textui\\xmlconfiguration\\toreportformat',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/Migration/Migrations/CoveragePhpToReport.php' => 
    array (
      0 => '163afff32e30ef65d3d68996ffac2092f0d29cc3a4e6292ec2ae81f2a6a5b662',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\coveragephptoreport',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\fortype',
        1 => 'phpunit\\textui\\xmlconfiguration\\toreportformat',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/Migration/Migrations/CoverageTextToReport.php' => 
    array (
      0 => 'b821ed86bbecc51a54febf9003e2df4c5a10305b95f36bb4b9df78e04cc5a78d',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\coveragetexttoreport',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\fortype',
        1 => 'phpunit\\textui\\xmlconfiguration\\toreportformat',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/Migration/Migrations/CoverageXmlToReport.php' => 
    array (
      0 => 'd97d0a281494e63cea4a9a5fc1a5a02e80db40a3d6091b17e73eb3a93d94db0e',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\coveragexmltoreport',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\fortype',
        1 => 'phpunit\\textui\\xmlconfiguration\\toreportformat',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/Migration/Migrations/IntroduceCacheDirectoryAttribute.php' => 
    array (
      0 => '801ea73a827c6b3ab6b7899ee5a2fb6829cbc7fc3a3f72dad2db9a60a958d084',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\introducecachedirectoryattribute',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\migrate',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/Migration/Migrations/IntroduceCoverageElement.php' => 
    array (
      0 => 'be7af0b37c3981cbf89638014d351eceb1b6d38e14ccdac7605a9e39d1115fee',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\introducecoverageelement',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\migrate',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/Migration/Migrations/LogToReportMigration.php' => 
    array (
      0 => '180f5afc342b0824e132cb62ff5ca17349c0ff5b40f58bf457a8137b5787816a',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\logtoreportmigration',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\migrate',
        1 => 'phpunit\\textui\\xmlconfiguration\\migrateattributes',
        2 => 'phpunit\\textui\\xmlconfiguration\\fortype',
        3 => 'phpunit\\textui\\xmlconfiguration\\toreportformat',
        4 => 'phpunit\\textui\\xmlconfiguration\\findlognode',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/Migration/Migrations/Migration.php' => 
    array (
      0 => 'ee388cf7f5f91244acc9148345d3e54fd1b1a83a3b8c8bb23adb97567bc83439',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\migration',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\migrate',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/Migration/Migrations/MoveAttributesFromFilterWhitelistToCoverage.php' => 
    array (
      0 => '50c56c474324b1e5a5e7191d5aa5ca025113aa8b6283e527f6ddcbd393359788',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\moveattributesfromfilterwhitelisttocoverage',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\migrate',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/Migration/Migrations/MoveAttributesFromRootToCoverage.php' => 
    array (
      0 => 'cdc38aa99ca74d506d5efbe7c4e6844718a60a139614749418b448894db700c4',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\moveattributesfromroottocoverage',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\migrate',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/Migration/Migrations/MoveCoverageDirectoriesToSource.php' => 
    array (
      0 => '8aa56e1aafd0ae5cc10e2b84fa7eb5b5607c42e74ef5c2b1bd300babb74066e5',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\movecoveragedirectoriestosource',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\migrate',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/Migration/Migrations/MoveWhitelistExcludesToCoverage.php' => 
    array (
      0 => 'bd4ad428c08c7a93addb903e39906c8342d822ee078d6b65a12ae9b765e89680',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\movewhitelistexcludestocoverage',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\migrate',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/Migration/Migrations/MoveWhitelistIncludesToCoverage.php' => 
    array (
      0 => 'ce233b76068049456ea1dd40e5c12f8d1156e7d2589c637678c0b105836d35f3',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\movewhitelistincludestocoverage',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\migrate',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/Migration/Migrations/RemoveBeStrictAboutResourceUsageDuringSmallTestsAttribute.php' => 
    array (
      0 => '9237d9a4a76eb2e76709e65c9de6a1342a070745ac3e53c2c87e057cafc56455',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\removebestrictaboutresourceusageduringsmalltestsattribute',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\migrate',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/Migration/Migrations/RemoveBeStrictAboutTodoAnnotatedTestsAttribute.php' => 
    array (
      0 => '2fe3b5fb7562213f303ba49437d11a64c486028f0e4d549c3c7551270b36096d',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\removebestrictabouttodoannotatedtestsattribute',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\migrate',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/Migration/Migrations/RemoveCacheResultFileAttribute.php' => 
    array (
      0 => '89fd32f59fde090223bc92bfb5bb2e932e5f49a2e98a1b54bee0d41a57a8a840',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\removecacheresultfileattribute',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\migrate',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/Migration/Migrations/RemoveCacheTokensAttribute.php' => 
    array (
      0 => '1d444918f1ef070a556ee6ee4afb53c65294a71afe18edd5487d71b9dac70a92',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\removecachetokensattribute',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\migrate',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/Migration/Migrations/RemoveConversionToExceptionsAttributes.php' => 
    array (
      0 => '5d4571e8813bc33e70380a7d300a293811f6a5d88fd984f64f26acb73ba0c4de',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\removeconversiontoexceptionsattributes',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\migrate',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/Migration/Migrations/RemoveCoverageElementCacheDirectoryAttribute.php' => 
    array (
      0 => 'c771e7741bfabc3723d21fbb14246e76a511855dd481e2c3236a0dd688c2f6bb',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\removecoverageelementcachedirectoryattribute',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\migrate',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/Migration/Migrations/RemoveCoverageElementProcessUncoveredFilesAttribute.php' => 
    array (
      0 => '3c4b27aa704cc32107f167408f707870548e36cd072178c1b75ac73af42a3826',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\removecoverageelementprocessuncoveredfilesattribute',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\migrate',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/Migration/Migrations/RemoveEmptyFilter.php' => 
    array (
      0 => '9516f5f7aacc2fae5e35ed36523ee974c70e2d7b1b8993f3c83993b54d8bb4d6',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\removeemptyfilter',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\migrate',
        1 => 'phpunit\\textui\\xmlconfiguration\\ensureempty',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/Migration/Migrations/RemoveListeners.php' => 
    array (
      0 => '597012a85c166d4a6de1232f4ba357be00ec6a9078e9142f4de24941c81cbeba',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\removelisteners',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\migrate',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/Migration/Migrations/RemoveLogTypes.php' => 
    array (
      0 => 'e50e25231e170dd68ba0a07c15b0f7c024055f60aea6f5ee1d67c48af440cd35',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\removelogtypes',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\migrate',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/Migration/Migrations/RemoveLoggingElements.php' => 
    array (
      0 => '91d690e61e7bd08bc822797e50318381cf3a33f3990f513fe7ef38c208a53967',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\removeloggingelements',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\migrate',
        1 => 'phpunit\\textui\\xmlconfiguration\\removetestdoxelement',
        2 => 'phpunit\\textui\\xmlconfiguration\\removetextelement',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/Migration/Migrations/RemoveNoInteractionAttribute.php' => 
    array (
      0 => 'af2f5db09a02a95ce5a40248cff4cbb8853b8ea64f72e53ddcbf11b177ee38ac',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\removenointeractionattribute',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\migrate',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/Migration/Migrations/RemovePrinterAttributes.php' => 
    array (
      0 => '1eefdcf8307ec7834708e53ad733668cad63d241d0dfdaad4c0d729080462c25',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\removeprinterattributes',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\migrate',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/Migration/Migrations/RemoveRegisterMockObjectsFromTestArgumentsRecursivelyAttribute.php' => 
    array (
      0 => 'a0dcea8a9840acbc681cda46866f9fe4b8196896fafb3ef6522c96d5f0a99167',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\removeregistermockobjectsfromtestargumentsrecursivelyattribute',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\migrate',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/Migration/Migrations/RemoveTestDoxGroupsElement.php' => 
    array (
      0 => '71cc6d35176a51d8c9fe27428675b7f2911fae2e0fb1ab516d662c6b0b234d61',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\removetestdoxgroupselement',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\migrate',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/Migration/Migrations/RemoveTestSuiteLoaderAttributes.php' => 
    array (
      0 => '779ad94e995cd6e6908b764fab0ebe0169c35d6d00d852d1acf473ea838fa6b3',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\removetestsuiteloaderattributes',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\migrate',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/Migration/Migrations/RemoveVerboseAttribute.php' => 
    array (
      0 => '8580012317a3cd9ffc6689a575913fe3562f71978184e1a2b84145f039534d6b',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\removeverboseattribute',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\migrate',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/Migration/Migrations/RenameBackupStaticAttributesAttribute.php' => 
    array (
      0 => '8987906a68ea768d6f3a6c6033d6abe6336dcd428cad8a5bda11f1b277ccedfd',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\renamebackupstaticattributesattribute',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\migrate',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/Migration/Migrations/RenameBeStrictAboutCoversAnnotationAttribute.php' => 
    array (
      0 => 'a28112c4f2bef54f4b9ca176ae7a348154df1f99794a9cbe94cdbaf775b1a38b',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\renamebestrictaboutcoversannotationattribute',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\migrate',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/Migration/Migrations/RenameForceCoversAnnotationAttribute.php' => 
    array (
      0 => 'e2aaabdcbabda709a4a824fe6eb256f388c5f868afd884d6e3fa9867534048dc',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\renameforcecoversannotationattribute',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\migrate',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/Migration/Migrations/ReplaceRestrictDeprecationsWithIgnoreDeprecations.php' => 
    array (
      0 => '4323cffb14790c43720409215112a54abbc65060bd410a2f36ace7e3eef22a19',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\replacerestrictdeprecationswithignoredeprecations',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\migrate',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/Migration/Migrations/UpdateSchemaLocation.php' => 
    array (
      0 => 'e9757be81330e35b167429c5f714502e2f3301d8b1639d8fd54ee0c9fe700d03',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\updateschemalocation',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\migrate',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/Migration/Migrator.php' => 
    array (
      0 => '2bfb6b7cd93464f3ffb6dd1bf536ad65c5f20a90b39323d762e8a775b769f479',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\migrator',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\migrate',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/Migration/SnapshotNodeList.php' => 
    array (
      0 => 'f612ecfdd3716936f64f34bbb16ffe3ef4074e643fbb56aaa3e36c734697ebbd',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\snapshotnodelist',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\fromnodelist',
        1 => 'phpunit\\textui\\xmlconfiguration\\count',
        2 => 'phpunit\\textui\\xmlconfiguration\\getiterator',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/PHPUnit.php' => 
    array (
      0 => 'd14097e827fe66845fc89006573766b941e91fff4b33a46df14616e9ccb7eab9',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\phpunit',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\__construct',
        1 => 'phpunit\\textui\\xmlconfiguration\\hascachedirectory',
        2 => 'phpunit\\textui\\xmlconfiguration\\cachedirectory',
        3 => 'phpunit\\textui\\xmlconfiguration\\cacheresult',
        4 => 'phpunit\\textui\\xmlconfiguration\\columns',
        5 => 'phpunit\\textui\\xmlconfiguration\\colors',
        6 => 'phpunit\\textui\\xmlconfiguration\\stderr',
        7 => 'phpunit\\textui\\xmlconfiguration\\displaydetailsonallissues',
        8 => 'phpunit\\textui\\xmlconfiguration\\displaydetailsonincompletetests',
        9 => 'phpunit\\textui\\xmlconfiguration\\displaydetailsonskippedtests',
        10 => 'phpunit\\textui\\xmlconfiguration\\displaydetailsonteststhattriggerdeprecations',
        11 => 'phpunit\\textui\\xmlconfiguration\\displaydetailsonphpunitdeprecations',
        12 => 'phpunit\\textui\\xmlconfiguration\\displaydetailsonteststhattriggererrors',
        13 => 'phpunit\\textui\\xmlconfiguration\\displaydetailsonteststhattriggernotices',
        14 => 'phpunit\\textui\\xmlconfiguration\\displaydetailsonteststhattriggerwarnings',
        15 => 'phpunit\\textui\\xmlconfiguration\\reversedefectlist',
        16 => 'phpunit\\textui\\xmlconfiguration\\requirecoveragemetadata',
        17 => 'phpunit\\textui\\xmlconfiguration\\hasbootstrap',
        18 => 'phpunit\\textui\\xmlconfiguration\\bootstrap',
        19 => 'phpunit\\textui\\xmlconfiguration\\processisolation',
        20 => 'phpunit\\textui\\xmlconfiguration\\failonallissues',
        21 => 'phpunit\\textui\\xmlconfiguration\\failondeprecation',
        22 => 'phpunit\\textui\\xmlconfiguration\\failonphpunitdeprecation',
        23 => 'phpunit\\textui\\xmlconfiguration\\failonphpunitwarning',
        24 => 'phpunit\\textui\\xmlconfiguration\\failonemptytestsuite',
        25 => 'phpunit\\textui\\xmlconfiguration\\failonincomplete',
        26 => 'phpunit\\textui\\xmlconfiguration\\failonnotice',
        27 => 'phpunit\\textui\\xmlconfiguration\\failonrisky',
        28 => 'phpunit\\textui\\xmlconfiguration\\failonskipped',
        29 => 'phpunit\\textui\\xmlconfiguration\\failonwarning',
        30 => 'phpunit\\textui\\xmlconfiguration\\stopondefect',
        31 => 'phpunit\\textui\\xmlconfiguration\\stopondeprecation',
        32 => 'phpunit\\textui\\xmlconfiguration\\stoponerror',
        33 => 'phpunit\\textui\\xmlconfiguration\\stoponfailure',
        34 => 'phpunit\\textui\\xmlconfiguration\\stoponincomplete',
        35 => 'phpunit\\textui\\xmlconfiguration\\stoponnotice',
        36 => 'phpunit\\textui\\xmlconfiguration\\stoponrisky',
        37 => 'phpunit\\textui\\xmlconfiguration\\stoponskipped',
        38 => 'phpunit\\textui\\xmlconfiguration\\stoponwarning',
        39 => 'phpunit\\textui\\xmlconfiguration\\hasextensionsdirectory',
        40 => 'phpunit\\textui\\xmlconfiguration\\extensionsdirectory',
        41 => 'phpunit\\textui\\xmlconfiguration\\bestrictaboutchangestoglobalstate',
        42 => 'phpunit\\textui\\xmlconfiguration\\bestrictaboutoutputduringtests',
        43 => 'phpunit\\textui\\xmlconfiguration\\bestrictaboutteststhatdonottestanything',
        44 => 'phpunit\\textui\\xmlconfiguration\\bestrictaboutcoveragemetadata',
        45 => 'phpunit\\textui\\xmlconfiguration\\enforcetimelimit',
        46 => 'phpunit\\textui\\xmlconfiguration\\defaulttimelimit',
        47 => 'phpunit\\textui\\xmlconfiguration\\timeoutforsmalltests',
        48 => 'phpunit\\textui\\xmlconfiguration\\timeoutformediumtests',
        49 => 'phpunit\\textui\\xmlconfiguration\\timeoutforlargetests',
        50 => 'phpunit\\textui\\xmlconfiguration\\hasdefaulttestsuite',
        51 => 'phpunit\\textui\\xmlconfiguration\\defaulttestsuite',
        52 => 'phpunit\\textui\\xmlconfiguration\\executionorder',
        53 => 'phpunit\\textui\\xmlconfiguration\\resolvedependencies',
        54 => 'phpunit\\textui\\xmlconfiguration\\defectsfirst',
        55 => 'phpunit\\textui\\xmlconfiguration\\backupglobals',
        56 => 'phpunit\\textui\\xmlconfiguration\\backupstaticproperties',
        57 => 'phpunit\\textui\\xmlconfiguration\\testdoxprinter',
        58 => 'phpunit\\textui\\xmlconfiguration\\testdoxprintersummary',
        59 => 'phpunit\\textui\\xmlconfiguration\\controlgarbagecollector',
        60 => 'phpunit\\textui\\xmlconfiguration\\numberoftestsbeforegarbagecollection',
        61 => 'phpunit\\textui\\xmlconfiguration\\shortenarraysforexportthreshold',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/SchemaDetector/FailedSchemaDetectionResult.php' => 
    array (
      0 => 'f98b63f98683773dc839309ad305a332f3375e8b65ec5ec050fae35924773cfc',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\failedschemadetectionresult',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/SchemaDetector/SchemaDetectionResult.php' => 
    array (
      0 => '5ecb5d49d5929ef0e3c294acc1cccb2041177c09c8fd44ebb6d65fb046fee10e',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\schemadetectionresult',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\detected',
        1 => 'phpunit\\textui\\xmlconfiguration\\version',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/SchemaDetector/SchemaDetector.php' => 
    array (
      0 => '7335a7bc9bcd79eedc74d82cf1d01493f93c66d6eaa19c6d97dcb0fbf5e36a83',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\schemadetector',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\detect',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/SchemaDetector/SuccessfulSchemaDetectionResult.php' => 
    array (
      0 => '7d1d670c07bf6812e248fe97bb2e0733d09cef72e9aca4f0a6b2c0a799167e95',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\successfulschemadetectionresult',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\__construct',
        1 => 'phpunit\\textui\\xmlconfiguration\\detected',
        2 => 'phpunit\\textui\\xmlconfiguration\\version',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/SchemaFinder.php' => 
    array (
      0 => 'f9bdf3e781875b578210ed50e45e60d3c590e3b4b1fafe283fb3b6b84ae6279b',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\schemafinder',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\available',
        1 => 'phpunit\\textui\\xmlconfiguration\\find',
        2 => 'phpunit\\textui\\xmlconfiguration\\path',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/TestSuiteMapper.php' => 
    array (
      0 => 'c9719dd22ae3a5e16c32de0d34f98a075343a5b9986cdedbd41140e998996be8',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\testsuitemapper',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\map',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/Validator/ValidationResult.php' => 
    array (
      0 => '3c901df4623ba52cb78765cb5b227bd26b441f85791240ab9f8b48eb906c040e',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\validationresult',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\fromarray',
        1 => 'phpunit\\textui\\xmlconfiguration\\__construct',
        2 => 'phpunit\\textui\\xmlconfiguration\\hasvalidationerrors',
        3 => 'phpunit\\textui\\xmlconfiguration\\asstring',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Configuration/Xml/Validator/Validator.php' => 
    array (
      0 => '41dd9b477af26816908e42123e70d27035299bc931fafcd437a45ebb962c41d1',
      1 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\validator',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\xmlconfiguration\\validate',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Exception/CannotOpenSocketException.php' => 
    array (
      0 => 'bc0bc9bbb481d8f774b6d57d4b8a04f459432e2b1f1dcc13a760bd74d776406d',
      1 => 
      array (
        0 => 'phpunit\\textui\\cannotopensocketexception',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\__construct',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Exception/Exception.php' => 
    array (
      0 => 'eb1d279e9aa5911d66ac3b18aef618a44bde6a3329e79391d98393e1fd82d8fc',
      1 => 
      array (
        0 => 'phpunit\\textui\\exception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Exception/InvalidSocketException.php' => 
    array (
      0 => 'f052b70a39f35bc7156a5955bd429ef0ff0c7fdef7ea9969b4c0c0b443ac3c5f',
      1 => 
      array (
        0 => 'phpunit\\textui\\invalidsocketexception',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\__construct',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Exception/RuntimeException.php' => 
    array (
      0 => '351cbbef8bfda22f09d5c2150f32a87d412bf794cbcc92d9742171daada98e0d',
      1 => 
      array (
        0 => 'phpunit\\textui\\runtimeexception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Exception/TestDirectoryNotFoundException.php' => 
    array (
      0 => '74d23a69559c01a9ad9126d56c061d17319677ad5c01737b9848271405cc3651',
      1 => 
      array (
        0 => 'phpunit\\textui\\testdirectorynotfoundexception',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\__construct',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Exception/TestFileNotFoundException.php' => 
    array (
      0 => 'c9cca9b07be4b5a284b04b8629e0c703321061dc3bb3a6292126cbcefceb3b68',
      1 => 
      array (
        0 => 'phpunit\\textui\\testfilenotfoundexception',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\__construct',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Help.php' => 
    array (
      0 => 'd3cbd1dc9edc3d5702487d7e2c58a6d13a89355e1ac2fdc5fdabb74c8c561fab',
      1 => 
      array (
        0 => 'phpunit\\textui\\help',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\__construct',
        1 => 'phpunit\\textui\\generate',
        2 => 'phpunit\\textui\\writewithoutcolor',
        3 => 'phpunit\\textui\\writewithcolor',
        4 => 'phpunit\\textui\\elements',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Output/Default/ProgressPrinter/ProgressPrinter.php' => 
    array (
      0 => 'b0df83c5653d6104a0c6c01eaf225d1da1c6f8bc22c76803f880f84cec31b0b5',
      1 => 
      array (
        0 => 'phpunit\\textui\\output\\default\\progressprinter\\progressprinter',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\output\\default\\progressprinter\\__construct',
        1 => 'phpunit\\textui\\output\\default\\progressprinter\\testrunnerexecutionstarted',
        2 => 'phpunit\\textui\\output\\default\\progressprinter\\beforetestclassmethoderrored',
        3 => 'phpunit\\textui\\output\\default\\progressprinter\\testprepared',
        4 => 'phpunit\\textui\\output\\default\\progressprinter\\testskipped',
        5 => 'phpunit\\textui\\output\\default\\progressprinter\\testsuiteskipped',
        6 => 'phpunit\\textui\\output\\default\\progressprinter\\testmarkedincomplete',
        7 => 'phpunit\\textui\\output\\default\\progressprinter\\testtriggerednotice',
        8 => 'phpunit\\textui\\output\\default\\progressprinter\\testtriggeredphpnotice',
        9 => 'phpunit\\textui\\output\\default\\progressprinter\\testtriggereddeprecation',
        10 => 'phpunit\\textui\\output\\default\\progressprinter\\testtriggeredphpdeprecation',
        11 => 'phpunit\\textui\\output\\default\\progressprinter\\testtriggeredphpunitdeprecation',
        12 => 'phpunit\\textui\\output\\default\\progressprinter\\testconsideredrisky',
        13 => 'phpunit\\textui\\output\\default\\progressprinter\\testtriggeredwarning',
        14 => 'phpunit\\textui\\output\\default\\progressprinter\\testtriggeredphpwarning',
        15 => 'phpunit\\textui\\output\\default\\progressprinter\\testtriggeredphpunitwarning',
        16 => 'phpunit\\textui\\output\\default\\progressprinter\\testtriggerederror',
        17 => 'phpunit\\textui\\output\\default\\progressprinter\\testfailed',
        18 => 'phpunit\\textui\\output\\default\\progressprinter\\testerrored',
        19 => 'phpunit\\textui\\output\\default\\progressprinter\\testfinished',
        20 => 'phpunit\\textui\\output\\default\\progressprinter\\registersubscribers',
        21 => 'phpunit\\textui\\output\\default\\progressprinter\\updateteststatus',
        22 => 'phpunit\\textui\\output\\default\\progressprinter\\printprogressforsuccess',
        23 => 'phpunit\\textui\\output\\default\\progressprinter\\printprogressforskipped',
        24 => 'phpunit\\textui\\output\\default\\progressprinter\\printprogressforincomplete',
        25 => 'phpunit\\textui\\output\\default\\progressprinter\\printprogressfornotice',
        26 => 'phpunit\\textui\\output\\default\\progressprinter\\printprogressfordeprecation',
        27 => 'phpunit\\textui\\output\\default\\progressprinter\\printprogressforrisky',
        28 => 'phpunit\\textui\\output\\default\\progressprinter\\printprogressforwarning',
        29 => 'phpunit\\textui\\output\\default\\progressprinter\\printprogressforfailure',
        30 => 'phpunit\\textui\\output\\default\\progressprinter\\printprogressforerror',
        31 => 'phpunit\\textui\\output\\default\\progressprinter\\printprogresswithcolor',
        32 => 'phpunit\\textui\\output\\default\\progressprinter\\printprogress',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Output/Default/ProgressPrinter/Subscriber/BeforeTestClassMethodErroredSubscriber.php' => 
    array (
      0 => 'a7603d22958594640f42c254c598574f6995a3cc24f90189e2e07de46fe41e63',
      1 => 
      array (
        0 => 'phpunit\\textui\\output\\default\\progressprinter\\beforetestclassmethoderroredsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\output\\default\\progressprinter\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Output/Default/ProgressPrinter/Subscriber/Subscriber.php' => 
    array (
      0 => 'e4af16b2d20396bac5f6f107592b6b35cf51e750f4a3b4055dbe75a8d60dc1b9',
      1 => 
      array (
        0 => 'phpunit\\textui\\output\\default\\progressprinter\\subscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\output\\default\\progressprinter\\__construct',
        1 => 'phpunit\\textui\\output\\default\\progressprinter\\printer',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Output/Default/ProgressPrinter/Subscriber/TestConsideredRiskySubscriber.php' => 
    array (
      0 => '06215d675f7e4bc815a4cfc435db06dd6fefdb911ce00ca37fe64a0296c3eb90',
      1 => 
      array (
        0 => 'phpunit\\textui\\output\\default\\progressprinter\\testconsideredriskysubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\output\\default\\progressprinter\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Output/Default/ProgressPrinter/Subscriber/TestErroredSubscriber.php' => 
    array (
      0 => '07949a807210f35bcce4768fa8f0481dbac34971d42acc47fbe25a2d2efd2292',
      1 => 
      array (
        0 => 'phpunit\\textui\\output\\default\\progressprinter\\testerroredsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\output\\default\\progressprinter\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Output/Default/ProgressPrinter/Subscriber/TestFailedSubscriber.php' => 
    array (
      0 => 'fd569a7b31ad08909fe746c978a53ed932cc105c0f68d3db2032175cf9398815',
      1 => 
      array (
        0 => 'phpunit\\textui\\output\\default\\progressprinter\\testfailedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\output\\default\\progressprinter\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Output/Default/ProgressPrinter/Subscriber/TestFinishedSubscriber.php' => 
    array (
      0 => '042dfc3a23136eb360aed01b505af22a181de4399d3c709e733ef7fe95c557c2',
      1 => 
      array (
        0 => 'phpunit\\textui\\output\\default\\progressprinter\\testfinishedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\output\\default\\progressprinter\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Output/Default/ProgressPrinter/Subscriber/TestMarkedIncompleteSubscriber.php' => 
    array (
      0 => '3634ad409057d5e27cdc605afce3f6b0b6c859b9c79c938ec825b4ef3a720e9b',
      1 => 
      array (
        0 => 'phpunit\\textui\\output\\default\\progressprinter\\testmarkedincompletesubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\output\\default\\progressprinter\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Output/Default/ProgressPrinter/Subscriber/TestPreparedSubscriber.php' => 
    array (
      0 => 'd030abd6c7b8c71da1f41c6b0b6dbc5377d6bbdd5f42d4c2958e9a762a24f229',
      1 => 
      array (
        0 => 'phpunit\\textui\\output\\default\\progressprinter\\testpreparedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\output\\default\\progressprinter\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Output/Default/ProgressPrinter/Subscriber/TestRunnerExecutionStartedSubscriber.php' => 
    array (
      0 => '687f06756c1de498899f58aa30df6e195fe307547b47cf939ddda6c4b95aefaa',
      1 => 
      array (
        0 => 'phpunit\\textui\\output\\default\\progressprinter\\testrunnerexecutionstartedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\output\\default\\progressprinter\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Output/Default/ProgressPrinter/Subscriber/TestSkippedSubscriber.php' => 
    array (
      0 => '8abdad6413329c6fe0d7d44a8b9926e390af32c0b3123f3720bb9c5bbc6fbb7e',
      1 => 
      array (
        0 => 'phpunit\\textui\\output\\default\\progressprinter\\testskippedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\output\\default\\progressprinter\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Output/Default/ProgressPrinter/Subscriber/TestSuiteSkippedSubscriber.php' => 
    array (
      0 => '07b4fe4f5f7a34529f602a8d9fd4118cca905f28495751977eb124bac43cda6e',
      1 => 
      array (
        0 => 'phpunit\\textui\\output\\default\\progressprinter\\testsuiteskippedsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\output\\default\\progressprinter\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Output/Default/ProgressPrinter/Subscriber/TestTriggeredDeprecationSubscriber.php' => 
    array (
      0 => '02e41860e8a811040c113e32771da395ec34cd1629e89716661ddbd14be857fc',
      1 => 
      array (
        0 => 'phpunit\\textui\\output\\default\\progressprinter\\testtriggereddeprecationsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\output\\default\\progressprinter\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Output/Default/ProgressPrinter/Subscriber/TestTriggeredErrorSubscriber.php' => 
    array (
      0 => '4be348c7b4693168afda944c160380252af90d2b6b57a47f9beaaaa22dc63fd1',
      1 => 
      array (
        0 => 'phpunit\\textui\\output\\default\\progressprinter\\testtriggerederrorsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\output\\default\\progressprinter\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Output/Default/ProgressPrinter/Subscriber/TestTriggeredNoticeSubscriber.php' => 
    array (
      0 => 'ceeefb8ff96c6b53f78bffc572eb13aa8475feef1b93b2c3493fb3c2a5e44df4',
      1 => 
      array (
        0 => 'phpunit\\textui\\output\\default\\progressprinter\\testtriggerednoticesubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\output\\default\\progressprinter\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Output/Default/ProgressPrinter/Subscriber/TestTriggeredPhpDeprecationSubscriber.php' => 
    array (
      0 => '9f5b997720497e0207c88575fec5f7f833935dd6132e12ab621a185eb7c00b0b',
      1 => 
      array (
        0 => 'phpunit\\textui\\output\\default\\progressprinter\\testtriggeredphpdeprecationsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\output\\default\\progressprinter\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Output/Default/ProgressPrinter/Subscriber/TestTriggeredPhpNoticeSubscriber.php' => 
    array (
      0 => '60772aa7f8aca019198bafdebf4fb9b0cc99ccead80ba56df8fe26cb9af18184',
      1 => 
      array (
        0 => 'phpunit\\textui\\output\\default\\progressprinter\\testtriggeredphpnoticesubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\output\\default\\progressprinter\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Output/Default/ProgressPrinter/Subscriber/TestTriggeredPhpWarningSubscriber.php' => 
    array (
      0 => '742bed17665fd5976a5dacc0218aaa4fcc7f16299aa4782b7c5e613b20bf85c6',
      1 => 
      array (
        0 => 'phpunit\\textui\\output\\default\\progressprinter\\testtriggeredphpwarningsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\output\\default\\progressprinter\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Output/Default/ProgressPrinter/Subscriber/TestTriggeredPhpunitDeprecationSubscriber.php' => 
    array (
      0 => 'ebe43f9cb203732d33248332c6e36651a5abe3435544f23def93f56cc1c156cc',
      1 => 
      array (
        0 => 'phpunit\\textui\\output\\default\\progressprinter\\testtriggeredphpunitdeprecationsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\output\\default\\progressprinter\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Output/Default/ProgressPrinter/Subscriber/TestTriggeredPhpunitWarningSubscriber.php' => 
    array (
      0 => '260e6414df74093c00402fe12134e0a98a99487f2f2634179162a7d42cd1af23',
      1 => 
      array (
        0 => 'phpunit\\textui\\output\\default\\progressprinter\\testtriggeredphpunitwarningsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\output\\default\\progressprinter\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Output/Default/ProgressPrinter/Subscriber/TestTriggeredWarningSubscriber.php' => 
    array (
      0 => 'aa01885e42e144dfa8b9eb0ae3f52c9560e6fed2a2e11b506c1cc575ed993c6a',
      1 => 
      array (
        0 => 'phpunit\\textui\\output\\default\\progressprinter\\testtriggeredwarningsubscriber',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\output\\default\\progressprinter\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Output/Default/ResultPrinter.php' => 
    array (
      0 => 'eb5ea8b3d7a5ec6e0cfe567336501f0f1a74825594da18805d85ed6ee2e015b1',
      1 => 
      array (
        0 => 'phpunit\\textui\\output\\default\\resultprinter',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\output\\default\\__construct',
        1 => 'phpunit\\textui\\output\\default\\print',
        2 => 'phpunit\\textui\\output\\default\\printphpuniterrors',
        3 => 'phpunit\\textui\\output\\default\\printdetailsonteststhattriggeredphpunitdeprecations',
        4 => 'phpunit\\textui\\output\\default\\printtestrunnerwarnings',
        5 => 'phpunit\\textui\\output\\default\\printtestrunnerdeprecations',
        6 => 'phpunit\\textui\\output\\default\\printdetailsonteststhattriggeredphpunitwarnings',
        7 => 'phpunit\\textui\\output\\default\\printtestswitherrors',
        8 => 'phpunit\\textui\\output\\default\\printtestswithfailedassertions',
        9 => 'phpunit\\textui\\output\\default\\printriskytests',
        10 => 'phpunit\\textui\\output\\default\\printincompletetests',
        11 => 'phpunit\\textui\\output\\default\\printskippedtestsuites',
        12 => 'phpunit\\textui\\output\\default\\printskippedtests',
        13 => 'phpunit\\textui\\output\\default\\printissuelist',
        14 => 'phpunit\\textui\\output\\default\\printlistheaderwithnumberoftestsandnumberofissues',
        15 => 'phpunit\\textui\\output\\default\\printlistheaderwithnumber',
        16 => 'phpunit\\textui\\output\\default\\printlistheader',
        17 => 'phpunit\\textui\\output\\default\\printlist',
        18 => 'phpunit\\textui\\output\\default\\printlistelement',
        19 => 'phpunit\\textui\\output\\default\\printissuelistelement',
        20 => 'phpunit\\textui\\output\\default\\name',
        21 => 'phpunit\\textui\\output\\default\\maptestswithissueseventstoelements',
        22 => 'phpunit\\textui\\output\\default\\testlocation',
        23 => 'phpunit\\textui\\output\\default\\reasonmessage',
        24 => 'phpunit\\textui\\output\\default\\reasonlocation',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Output/Default/UnexpectedOutputPrinter.php' => 
    array (
      0 => '0922b8ace68b9b8835041bc643864cda6c55830cdd76da2ac1351160f6181738',
      1 => 
      array (
        0 => 'phpunit\\textui\\output\\default\\unexpectedoutputprinter',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\output\\default\\__construct',
        1 => 'phpunit\\textui\\output\\default\\notify',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Output/Facade.php' => 
    array (
      0 => '2338e9a4333aa042217644f40ac799ff9aada0f5787f484213b1a5023b80c3bb',
      1 => 
      array (
        0 => 'phpunit\\textui\\output\\facade',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\output\\init',
        1 => 'phpunit\\textui\\output\\printresult',
        2 => 'phpunit\\textui\\output\\printerfor',
        3 => 'phpunit\\textui\\output\\createprinter',
        4 => 'phpunit\\textui\\output\\createprogressprinter',
        5 => 'phpunit\\textui\\output\\usedefaultprogressprinter',
        6 => 'phpunit\\textui\\output\\createresultprinter',
        7 => 'phpunit\\textui\\output\\createsummaryprinter',
        8 => 'phpunit\\textui\\output\\createunexpectedoutputprinter',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Output/Printer/DefaultPrinter.php' => 
    array (
      0 => 'de2f8f15210bee77c45028863d41c627b656a51071baac502c962bb666e54402',
      1 => 
      array (
        0 => 'phpunit\\textui\\output\\defaultprinter',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\output\\from',
        1 => 'phpunit\\textui\\output\\standardoutput',
        2 => 'phpunit\\textui\\output\\standarderror',
        3 => 'phpunit\\textui\\output\\__construct',
        4 => 'phpunit\\textui\\output\\print',
        5 => 'phpunit\\textui\\output\\flush',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Output/Printer/NullPrinter.php' => 
    array (
      0 => '8ad00f365f3258b0d3aca1fbb587c8ada08217ea6a15009223673317a74f90d8',
      1 => 
      array (
        0 => 'phpunit\\textui\\output\\nullprinter',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\output\\print',
        1 => 'phpunit\\textui\\output\\flush',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Output/Printer/Printer.php' => 
    array (
      0 => '04f52beccce187224baf7c60c4a065eb3ee1bda7e0c644caf467d9b8b1918e35',
      1 => 
      array (
        0 => 'phpunit\\textui\\output\\printer',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\output\\print',
        1 => 'phpunit\\textui\\output\\flush',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Output/SummaryPrinter.php' => 
    array (
      0 => 'ac635c7c976e8022df76769b4afec039c8d8f0fc61fa2d32274a403b1f956789',
      1 => 
      array (
        0 => 'phpunit\\textui\\output\\summaryprinter',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\output\\__construct',
        1 => 'phpunit\\textui\\output\\print',
        2 => 'phpunit\\textui\\output\\printcountstring',
        3 => 'phpunit\\textui\\output\\printwithcolor',
        4 => 'phpunit\\textui\\output\\printnumberofissuesignoredbybaseline',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/Output/TestDox/ResultPrinter.php' => 
    array (
      0 => '02c468122a45b0fb6885d9e3140039579db60ed45d4e7aaebf82c11ae6ccf8c9',
      1 => 
      array (
        0 => 'phpunit\\textui\\output\\testdox\\resultprinter',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\output\\testdox\\__construct',
        1 => 'phpunit\\textui\\output\\testdox\\print',
        2 => 'phpunit\\textui\\output\\testdox\\doprint',
        3 => 'phpunit\\textui\\output\\testdox\\printprettifiedclassname',
        4 => 'phpunit\\textui\\output\\testdox\\printtestresult',
        5 => 'phpunit\\textui\\output\\testdox\\printtestresultheader',
        6 => 'phpunit\\textui\\output\\testdox\\printtestresultbody',
        7 => 'phpunit\\textui\\output\\testdox\\printtestresultbodystart',
        8 => 'phpunit\\textui\\output\\testdox\\printtestresultbodyend',
        9 => 'phpunit\\textui\\output\\testdox\\printthrowable',
        10 => 'phpunit\\textui\\output\\testdox\\colorizemessageanddiff',
        11 => 'phpunit\\textui\\output\\testdox\\formatstacktrace',
        12 => 'phpunit\\textui\\output\\testdox\\prefixlines',
        13 => 'phpunit\\textui\\output\\testdox\\prefixfor',
        14 => 'phpunit\\textui\\output\\testdox\\colorfor',
        15 => 'phpunit\\textui\\output\\testdox\\messagecolorfor',
        16 => 'phpunit\\textui\\output\\testdox\\symbolfor',
        17 => 'phpunit\\textui\\output\\testdox\\printbeforeclassorafterclasserrors',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/ShellExitCodeCalculator.php' => 
    array (
      0 => 'b079fdfe1668922f9e2ee4c2923af8291682de1c08f30210e6bec74dc42cb381',
      1 => 
      array (
        0 => 'phpunit\\textui\\shellexitcodecalculator',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\calculate',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/TestRunner.php' => 
    array (
      0 => '91204a181e556057bdc4580d124bfaa3a991d8ae234b1c3cdcd16b59d7862884',
      1 => 
      array (
        0 => 'phpunit\\textui\\testrunner',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\run',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/TextUI/TestSuiteFilterProcessor.php' => 
    array (
      0 => 'b4250fc3ffad5954624cb5e682fd940b874e8d3422fa1ee298bd7225e1aa5fc2',
      1 => 
      array (
        0 => 'phpunit\\textui\\testsuitefilterprocessor',
      ),
      2 => 
      array (
        0 => 'phpunit\\textui\\process',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Util/Cloner.php' => 
    array (
      0 => '5dd832728bbfe15e30dfa79973a57ce0d1448715badab5558e019423deded81d',
      1 => 
      array (
        0 => 'phpunit\\util\\cloner',
      ),
      2 => 
      array (
        0 => 'phpunit\\util\\clone',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Util/Color.php' => 
    array (
      0 => 'bbe27c32a8bdf3965d1bcf51874d54a697cb31e5d0a5ac6e51012d949e5fadfc',
      1 => 
      array (
        0 => 'phpunit\\util\\color',
      ),
      2 => 
      array (
        0 => 'phpunit\\util\\colorize',
        1 => 'phpunit\\util\\colorizetextbox',
        2 => 'phpunit\\util\\colorizepath',
        3 => 'phpunit\\util\\dim',
        4 => 'phpunit\\util\\visualizewhitespace',
        5 => 'phpunit\\util\\optimizecolor',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Util/Exception/Exception.php' => 
    array (
      0 => 'c5d1c65054dd9b80fe73bcfc008ab85986bb2a3088a45a9f6d6fe5a03e944461',
      1 => 
      array (
        0 => 'phpunit\\util\\exception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Util/Exception/InvalidDirectoryException.php' => 
    array (
      0 => '9cee327e831d29541ba5d0ed6d7dac040bbd291c1f3cf99045fcdc62ae2bb039',
      1 => 
      array (
        0 => 'phpunit\\util\\invaliddirectoryexception',
      ),
      2 => 
      array (
        0 => 'phpunit\\util\\__construct',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Util/Exception/InvalidJsonException.php' => 
    array (
      0 => '64a8b7fbc965d4da08d5fc2cc0cbd3f0ad12398e36066cb5f220455b9026606d',
      1 => 
      array (
        0 => 'phpunit\\util\\invalidjsonexception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Util/Exception/InvalidVersionOperatorException.php' => 
    array (
      0 => '81f2299bf10f9d21502d87b905a37707bd1aa9d39c18dedf47476d3bc6b1c8ff',
      1 => 
      array (
        0 => 'phpunit\\util\\invalidversionoperatorexception',
      ),
      2 => 
      array (
        0 => 'phpunit\\util\\__construct',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Util/Exception/PhpProcessException.php' => 
    array (
      0 => '543c0b7962f00dd53a20707c0aac81c98f98c7ad6802d6066a8da814df1c2f5c',
      1 => 
      array (
        0 => 'phpunit\\util\\php\\phpprocessexception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Util/Exception/XmlException.php' => 
    array (
      0 => '3f18d9f88aa26d765baf217b64897c470bdf01c387c2573bb8f05822a17b0004',
      1 => 
      array (
        0 => 'phpunit\\util\\xml\\xmlexception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Util/ExcludeList.php' => 
    array (
      0 => '0f959be93f12091c7f60a6267ddc43be7ac51d425af8090748ebe2fea5ada52a',
      1 => 
      array (
        0 => 'phpunit\\util\\excludelist',
      ),
      2 => 
      array (
        0 => 'phpunit\\util\\adddirectory',
        1 => 'phpunit\\util\\__construct',
        2 => 'phpunit\\util\\getexcludeddirectories',
        3 => 'phpunit\\util\\isexcluded',
        4 => 'phpunit\\util\\initialize',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Util/Exporter.php' => 
    array (
      0 => '7dfcc743512a5d49eda26f387d376d08f6fc6efcbc165f3b3ff69f68a2d81784',
      1 => 
      array (
        0 => 'phpunit\\util\\exporter',
      ),
      2 => 
      array (
        0 => 'phpunit\\util\\export',
        1 => 'phpunit\\util\\shortenedrecursiveexport',
        2 => 'phpunit\\util\\shortenedexport',
        3 => 'phpunit\\util\\exporter',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Util/Filesystem.php' => 
    array (
      0 => '36c9d20b0212591b014cdeb53659fba6a59c32221a064f8f5f5c9013885a403d',
      1 => 
      array (
        0 => 'phpunit\\util\\filesystem',
      ),
      2 => 
      array (
        0 => 'phpunit\\util\\createdirectory',
        1 => 'phpunit\\util\\resolvestreamorfile',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Util/Filter.php' => 
    array (
      0 => 'b4e039aa7fa90ccc4e363e0b294987441f179b24b35c39533575f3616dbb24e4',
      1 => 
      array (
        0 => 'phpunit\\util\\filter',
      ),
      2 => 
      array (
        0 => 'phpunit\\util\\stacktracefromthrowableasstring',
        1 => 'phpunit\\util\\stacktraceasstring',
        2 => 'phpunit\\util\\shouldprintframe',
        3 => 'phpunit\\util\\fileisexcluded',
        4 => 'phpunit\\util\\frameexists',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Util/GlobalState.php' => 
    array (
      0 => '7788cabbc19f297595aded4f1192bfe8717f594940db99e8827370a0d6d548c4',
      1 => 
      array (
        0 => 'phpunit\\util\\globalstate',
      ),
      2 => 
      array (
        0 => 'phpunit\\util\\getincludedfilesasstring',
        1 => 'phpunit\\util\\processincludedfilesasstring',
        2 => 'phpunit\\util\\getinisettingsasstring',
        3 => 'phpunit\\util\\getconstantsasstring',
        4 => 'phpunit\\util\\getglobalsasstring',
        5 => 'phpunit\\util\\exportvariable',
        6 => 'phpunit\\util\\arrayonlycontainsscalars',
        7 => 'phpunit\\util\\isinisettingdeprecated',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Util/Http/Downloader.php' => 
    array (
      0 => '348716b83fd9abf58466cd0937c5de22c3b7e922bd3dec522ab7c889c95565ec',
      1 => 
      array (
        0 => 'phpunit\\util\\http\\downloader',
      ),
      2 => 
      array (
        0 => 'phpunit\\util\\http\\download',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Util/Http/PhpDownloader.php' => 
    array (
      0 => '452d7c5c1f7906c57225d13c22732a4f279a8bb5722c2079674bf52295d104df',
      1 => 
      array (
        0 => 'phpunit\\util\\http\\phpdownloader',
      ),
      2 => 
      array (
        0 => 'phpunit\\util\\http\\download',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Util/Json.php' => 
    array (
      0 => 'a02ed022513eeeeb2445f2e1c2570e2fab4cd16cdb2d13975d19fd8990d30c21',
      1 => 
      array (
        0 => 'phpunit\\util\\json',
      ),
      2 => 
      array (
        0 => 'phpunit\\util\\prettify',
        1 => 'phpunit\\util\\canonicalize',
        2 => 'phpunit\\util\\recursivesort',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Util/PHP/DefaultJobRunner.php' => 
    array (
      0 => 'c8c00f6d0dc16b2fc9fb5edcb3a107b184f5f25418ee0477ca3bb0d4a6b55711',
      1 => 
      array (
        0 => 'phpunit\\util\\php\\defaultjobrunner',
      ),
      2 => 
      array (
        0 => 'phpunit\\util\\php\\run',
        1 => 'phpunit\\util\\php\\runprocess',
        2 => 'phpunit\\util\\php\\buildcommand',
        3 => 'phpunit\\util\\php\\settingstoparameters',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Util/PHP/Job.php' => 
    array (
      0 => 'be1c33e082af79680b45862316c90cd2e1046d40f0635dbd2e5c790b6ba4f9bf',
      1 => 
      array (
        0 => 'phpunit\\util\\php\\job',
      ),
      2 => 
      array (
        0 => 'phpunit\\util\\php\\__construct',
        1 => 'phpunit\\util\\php\\code',
        2 => 'phpunit\\util\\php\\phpsettings',
        3 => 'phpunit\\util\\php\\hasenvironmentvariables',
        4 => 'phpunit\\util\\php\\environmentvariables',
        5 => 'phpunit\\util\\php\\hasarguments',
        6 => 'phpunit\\util\\php\\arguments',
        7 => 'phpunit\\util\\php\\hasinput',
        8 => 'phpunit\\util\\php\\input',
        9 => 'phpunit\\util\\php\\redirecterrors',
        10 => 'phpunit\\util\\php\\requiresxdebug',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Util/PHP/JobRunner.php' => 
    array (
      0 => '1c3b4550018071bc24e9be4927baac7500e2314a428e49c3f7df81cd20f77bcf',
      1 => 
      array (
        0 => 'phpunit\\util\\php\\jobrunner',
      ),
      2 => 
      array (
        0 => 'phpunit\\util\\php\\__construct',
        1 => 'phpunit\\util\\php\\runtestjob',
        2 => 'phpunit\\util\\php\\run',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Util/PHP/JobRunnerRegistry.php' => 
    array (
      0 => '598b3332beef8a35208d1122675606ce3df8d3a8cc4b331c985e7cd97c3f1882',
      1 => 
      array (
        0 => 'phpunit\\util\\php\\jobrunnerregistry',
      ),
      2 => 
      array (
        0 => 'phpunit\\util\\php\\run',
        1 => 'phpunit\\util\\php\\runtestjob',
        2 => 'phpunit\\util\\php\\set',
        3 => 'phpunit\\util\\php\\runner',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Util/PHP/Result.php' => 
    array (
      0 => 'c3146d26d90bec249f6afeeeb6eb74754f3fb68634c5d262a36989b30225e0de',
      1 => 
      array (
        0 => 'phpunit\\util\\php\\result',
      ),
      2 => 
      array (
        0 => 'phpunit\\util\\php\\__construct',
        1 => 'phpunit\\util\\php\\stdout',
        2 => 'phpunit\\util\\php\\stderr',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Util/Reflection.php' => 
    array (
      0 => 'fa060d000b826e9164ea3b0abdbc3588ff381f4b56d709dcb068d3c6b800be63',
      1 => 
      array (
        0 => 'phpunit\\util\\reflection',
      ),
      2 => 
      array (
        0 => 'phpunit\\util\\sourcelocationfor',
        1 => 'phpunit\\util\\publicmethodsdeclareddirectlyintestclass',
        2 => 'phpunit\\util\\methodsdeclareddirectlyintestclass',
        3 => 'phpunit\\util\\filterandsortmethods',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Util/Test.php' => 
    array (
      0 => '377243cd58b4e9d7650dbac1b8e4925221c31c8eb11e1883ffcb8e78a3b6d763',
      1 => 
      array (
        0 => 'phpunit\\util\\test',
      ),
      2 => 
      array (
        0 => 'phpunit\\util\\currenttestcase',
        1 => 'phpunit\\util\\istestmethod',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Util/ThrowableToStringMapper.php' => 
    array (
      0 => 'a3618a989ba57dedd5cc9b743a62fd2102127dcac7e7bb37bc9e4f83e20cf352',
      1 => 
      array (
        0 => 'phpunit\\util\\throwabletostringmapper',
      ),
      2 => 
      array (
        0 => 'phpunit\\util\\map',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Util/VersionComparisonOperator.php' => 
    array (
      0 => '48b2d81f4a6f13aba38fa0b16f2d7eaeaad93126439302d246a6b7d62b34c190',
      1 => 
      array (
        0 => 'phpunit\\util\\versioncomparisonoperator',
      ),
      2 => 
      array (
        0 => 'phpunit\\util\\__construct',
        1 => 'phpunit\\util\\asstring',
        2 => 'phpunit\\util\\ensureoperatorisvalid',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Util/Xml/Loader.php' => 
    array (
      0 => 'b2e27a3dc6c511b690a22d234cf5534ae728411716bf0990b664c53e063132f7',
      1 => 
      array (
        0 => 'phpunit\\util\\xml\\loader',
      ),
      2 => 
      array (
        0 => 'phpunit\\util\\xml\\loadfile',
        1 => 'phpunit\\util\\xml\\load',
      ),
      3 => 
      array (
      ),
    ),
    '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Util/Xml/Xml.php' => 
    array (
      0 => '8473b7abf702fcf8372ca256a0793e731f95295b4c2a3cdf584d8d99465bb131',
      1 => 
      array (
        0 => 'phpunit\\util\\xml',
      ),
      2 => 
      array (
        0 => 'phpunit\\util\\preparestring',
        1 => 'phpunit\\util\\converttoutf8',
        2 => 'phpunit\\util\\isutf8',
      ),
      3 => 
      array (
      ),
    ),
  ),
));