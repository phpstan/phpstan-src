<?php
// source: /home/runner/work/phpstan-src/phpstan-src/conf/config.neon
// source: /home/runner/work/phpstan-src/phpstan-src/conf/config.level8.neon
// source: /home/runner/work/phpstan-src/phpstan-src/phpstan.neon.dist
// source: /home/runner/work/phpstan-src/phpstan-src/src/PhpDoc/../../conf/config.stubValidator.neon
// source: array

/** @noinspection PhpParamsInspection,PhpMethodMayBeStaticInspection */

declare(strict_types=1);

class Container_c66912be09 extends Nette\DI\Container
{
	protected $tags = [
		'phpstan.diagnoseExtension' => ['06' => true, '08' => true, '01002' => true],
		'phpstan.rules.rule' => [
			'021' => true,
			'022' => true,
			'023' => true,
			'024' => true,
			'025' => true,
			'026' => true,
			'027' => true,
			'028' => true,
			'029' => true,
			'030' => true,
			'048' => true,
			'049' => true,
			'050' => true,
			'051' => true,
			'052' => true,
			'0462' => true,
			'0463' => true,
			'0464' => true,
			'0465' => true,
			'0466' => true,
			'0467' => true,
			'0468' => true,
			'0469' => true,
			'0470' => true,
			'0471' => true,
			'0472' => true,
			'0473' => true,
			'0474' => true,
			'0475' => true,
			'0476' => true,
			'0477' => true,
			'0478' => true,
			'0479' => true,
			'0480' => true,
			'0481' => true,
			'0482' => true,
			'0483' => true,
			'0484' => true,
			'0485' => true,
			'0486' => true,
			'0487' => true,
			'0488' => true,
			'0489' => true,
			'0490' => true,
			'0491' => true,
			'0492' => true,
			'0493' => true,
			'0494' => true,
			'0495' => true,
			'0496' => true,
			'0497' => true,
			'0498' => true,
			'0499' => true,
			'0500' => true,
			'0501' => true,
			'0502' => true,
			'0503' => true,
			'0504' => true,
			'0505' => true,
			'0506' => true,
			'0507' => true,
			'0508' => true,
			'0509' => true,
			'0510' => true,
			'0511' => true,
			'0512' => true,
			'0513' => true,
			'0514' => true,
			'0515' => true,
			'0516' => true,
			'0517' => true,
			'0518' => true,
			'0519' => true,
			'0520' => true,
			'0521' => true,
			'0522' => true,
			'0523' => true,
			'0524' => true,
			'0525' => true,
			'0526' => true,
			'0527' => true,
			'0528' => true,
			'0529' => true,
			'0530' => true,
			'0531' => true,
			'0532' => true,
			'0533' => true,
			'0534' => true,
			'0535' => true,
			'0536' => true,
			'0537' => true,
			'0538' => true,
			'0539' => true,
			'0540' => true,
			'0541' => true,
			'0542' => true,
			'0543' => true,
			'0544' => true,
			'0545' => true,
			'0546' => true,
			'0547' => true,
			'0548' => true,
			'0549' => true,
			'0550' => true,
			'0551' => true,
			'0552' => true,
			'0553' => true,
			'0554' => true,
			'0555' => true,
			'0556' => true,
			'0557' => true,
			'0558' => true,
			'0559' => true,
			'0560' => true,
			'0561' => true,
			'0562' => true,
			'0563' => true,
			'0564' => true,
			'0565' => true,
			'0566' => true,
			'0567' => true,
			'0568' => true,
			'0569' => true,
			'0570' => true,
			'0571' => true,
			'0572' => true,
			'0573' => true,
			'0574' => true,
			'0575' => true,
			'0576' => true,
			'0577' => true,
			'0578' => true,
			'0579' => true,
			'0580' => true,
			'0581' => true,
			'0582' => true,
			'0583' => true,
			'0584' => true,
			'0585' => true,
			'0586' => true,
			'0587' => true,
			'0588' => true,
			'0589' => true,
			'0590' => true,
			'0591' => true,
			'0592' => true,
			'0593' => true,
			'0594' => true,
			'0595' => true,
			'0596' => true,
			'0597' => true,
			'0598' => true,
			'0599' => true,
			'0600' => true,
			'0601' => true,
			'0602' => true,
			'0603' => true,
			'0604' => true,
			'0605' => true,
			'0606' => true,
			'0607' => true,
			'0608' => true,
			'0609' => true,
			'0610' => true,
			'0611' => true,
			'0612' => true,
			'0613' => true,
			'0614' => true,
			'0615' => true,
			'0616' => true,
			'0617' => true,
			'0618' => true,
			'0619' => true,
			'0620' => true,
			'0621' => true,
			'0622' => true,
			'0623' => true,
			'0624' => true,
			'0625' => true,
			'0626' => true,
			'0627' => true,
			'0628' => true,
			'0629' => true,
			'0630' => true,
			'0631' => true,
			'0632' => true,
			'0633' => true,
			'0634' => true,
			'0635' => true,
			'0636' => true,
			'0637' => true,
			'0638' => true,
			'0639' => true,
			'0640' => true,
			'0641' => true,
			'0642' => true,
			'0643' => true,
			'0644' => true,
			'0645' => true,
			'0646' => true,
			'0647' => true,
			'0648' => true,
			'0649' => true,
			'0650' => true,
			'0651' => true,
			'0652' => true,
			'0653' => true,
			'0654' => true,
			'0655' => true,
			'0656' => true,
			'0657' => true,
			'0658' => true,
			'0659' => true,
			'0660' => true,
			'0661' => true,
			'0662' => true,
			'0663' => true,
			'0664' => true,
			'0665' => true,
			'0666' => true,
			'0667' => true,
			'0668' => true,
			'0669' => true,
			'0670' => true,
			'0671' => true,
			'0672' => true,
			'0673' => true,
			'0674' => true,
			'0675' => true,
			'0676' => true,
			'0677' => true,
			'0678' => true,
			'0679' => true,
			'0680' => true,
			'0681' => true,
			'0682' => true,
			'0683' => true,
			'0684' => true,
			'0685' => true,
			'0686' => true,
			'0687' => true,
			'0688' => true,
			'0689' => true,
			'0690' => true,
			'0691' => true,
			'0692' => true,
			'0693' => true,
			'0694' => true,
			'0695' => true,
			'0696' => true,
			'0697' => true,
			'0698' => true,
			'0699' => true,
			'0700' => true,
			'0701' => true,
			'0702' => true,
			'0703' => true,
			'0704' => true,
			'0705' => true,
			'0706' => true,
			'0707' => true,
			'0708' => true,
			'0709' => true,
			'0710' => true,
			'0711' => true,
			'0712' => true,
			'0713' => true,
			'0714' => true,
			'0715' => true,
			'0716' => true,
			'0717' => true,
			'0718' => true,
			'0719' => true,
			'0720' => true,
			'0721' => true,
			'0722' => true,
			'0723' => true,
			'0724' => true,
			'0725' => true,
			'0726' => true,
			'0727' => true,
			'0728' => true,
			'0729' => true,
			'0730' => true,
			'0731' => true,
			'0732' => true,
			'0733' => true,
			'0734' => true,
			'0735' => true,
			'0736' => true,
			'0737' => true,
			'0738' => true,
			'0739' => true,
			'0740' => true,
			'0741' => true,
			'0742' => true,
			'0743' => true,
			'0744' => true,
			'0745' => true,
			'0746' => true,
			'0747' => true,
			'0748' => true,
			'0749' => true,
			'0750' => true,
			'0751' => true,
			'0752' => true,
			'0753' => true,
			'0754' => true,
			'0755' => true,
			'0756' => true,
			'0757' => true,
			'0758' => true,
			'0759' => true,
			'0760' => true,
			'0761' => true,
			'0762' => true,
			'0763' => true,
			'0764' => true,
			'0765' => true,
			'0766' => true,
			'0767' => true,
			'0768' => true,
			'0769' => true,
			'0876' => true,
			'0877' => true,
			'0878' => true,
			'0879' => true,
			'0880' => true,
			'0881' => true,
			'0885' => true,
			'0888' => true,
			'0889' => true,
			'0890' => true,
			'0891' => true,
			'0892' => true,
			'0893' => true,
			'0894' => true,
			'0895' => true,
			'0896' => true,
			'0900' => true,
			'0906' => true,
			'0922' => true,
			'0923' => true,
			'0924' => true,
			'0925' => true,
			'0928' => true,
			'0929' => true,
			'0930' => true,
			'0931' => true,
			'0932' => true,
			'0933' => true,
			'0934' => true,
			'0935' => true,
			'0936' => true,
			'0937' => true,
			'0938' => true,
			'0939' => true,
			'0940' => true,
			'0941' => true,
			'0942' => true,
			'0943' => true,
			'0944' => true,
			'0945' => true,
			'0946' => true,
			'0947' => true,
			'0948' => true,
			'0949' => true,
			'0950' => true,
			'0951' => true,
			'0952' => true,
			'0953' => true,
			'0954' => true,
			'0955' => true,
			'0956' => true,
			'0957' => true,
			'0958' => true,
			'0959' => true,
			'0960' => true,
			'0961' => true,
			'0962' => true,
			'0963' => true,
			'0964' => true,
			'0965' => true,
			'0966' => true,
			'0967' => true,
			'0968' => true,
			'0969' => true,
			'0970' => true,
			'0971' => true,
			'0972' => true,
			'01002' => true,
			'rules.0' => true,
			'rules.1' => true,
			'rules.10' => true,
			'rules.11' => true,
			'rules.12' => true,
			'rules.13' => true,
			'rules.14' => true,
			'rules.15' => true,
			'rules.16' => true,
			'rules.17' => true,
			'rules.18' => true,
			'rules.2' => true,
			'rules.3' => true,
			'rules.4' => true,
			'rules.5' => true,
			'rules.6' => true,
			'rules.7' => true,
			'rules.8' => true,
			'rules.9' => true,
		],
		'phpstan.exprHandler' => [
			'086' => true,
			'087' => true,
			'088' => true,
			'089' => true,
			'090' => true,
			'091' => true,
			'092' => true,
			'093' => true,
			'094' => true,
			'095' => true,
			'096' => true,
			'097' => true,
			'098' => true,
			'099' => true,
			'0100' => true,
			'0101' => true,
			'0102' => true,
			'0103' => true,
			'0104' => true,
			'0105' => true,
			'0106' => true,
			'0107' => true,
			'0108' => true,
			'0109' => true,
			'0110' => true,
			'0111' => true,
			'0112' => true,
			'0113' => true,
			'0114' => true,
			'0115' => true,
			'0116' => true,
			'0117' => true,
			'0118' => true,
			'0119' => true,
			'0120' => true,
			'0121' => true,
			'0122' => true,
			'0123' => true,
			'0124' => true,
			'0125' => true,
			'0126' => true,
			'0127' => true,
			'0128' => true,
			'0129' => true,
			'0130' => true,
			'0131' => true,
			'0132' => true,
			'0133' => true,
			'0134' => true,
			'0135' => true,
			'0136' => true,
			'0137' => true,
			'0138' => true,
			'0139' => true,
			'0145' => true,
			'0146' => true,
			'0147' => true,
			'0148' => true,
			'0149' => true,
			'0150' => true,
			'0151' => true,
			'0152' => true,
			'0153' => true,
			'0154' => true,
			'0155' => true,
			'0156' => true,
			'0157' => true,
		],
		'phpstan.broker.dynamicMethodReturnTypeExtension' => [
			'0202' => true,
			'0267' => true,
			'0273' => true,
			'0305' => true,
			'0338' => true,
			'0351' => true,
			'0391' => true,
			'0401' => true,
			'0405' => true,
			'0417' => true,
			'0435' => true,
			'0438' => true,
			'0444' => true,
			'0863' => true,
			'0864' => true,
			'0865' => true,
			'0866' => true,
			'0867' => true,
			'0868' => true,
			'0869' => true,
			'0870' => true,
			'0871' => true,
			'0872' => true,
			'0873' => true,
			'0911' => true,
			'0912' => true,
			'01005' => true,
			'01006' => true,
		],
		'phpstan.broker.allowedSubTypesClassReflectionExtension' => ['0212' => true, '0213' => true],
		'phpstan.stubFilesExtension' => ['0224' => true, '0225' => true, '0227' => true, '0229' => true, '0230' => true],
		'phpstan.parser.richParserNodeVisitor' => [
			'0236' => true,
			'0237' => true,
			'0238' => true,
			'0239' => true,
			'0240' => true,
			'0241' => true,
			'0242' => true,
			'0243' => true,
			'0244' => true,
			'0245' => true,
			'0246' => true,
			'0247' => true,
			'0248' => true,
			'0249' => true,
			'0250' => true,
			'0251' => true,
			'0252' => true,
			'0253' => true,
			'0255' => true,
			'0256' => true,
			'0257' => true,
			'0258' => true,
			'0259' => true,
			'01004' => true,
		],
		'phpstan.broker.dynamicFunctionReturnTypeExtension' => [
			'0268' => true,
			'0269' => true,
			'0271' => true,
			'0272' => true,
			'0276' => true,
			'0278' => true,
			'0281' => true,
			'0283' => true,
			'0284' => true,
			'0285' => true,
			'0288' => true,
			'0289' => true,
			'0290' => true,
			'0291' => true,
			'0292' => true,
			'0293' => true,
			'0294' => true,
			'0295' => true,
			'0297' => true,
			'0298' => true,
			'0299' => true,
			'0302' => true,
			'0303' => true,
			'0306' => true,
			'0308' => true,
			'0310' => true,
			'0311' => true,
			'0312' => true,
			'0313' => true,
			'0316' => true,
			'0319' => true,
			'0320' => true,
			'0322' => true,
			'0323' => true,
			'0324' => true,
			'0326' => true,
			'0329' => true,
			'0330' => true,
			'0332' => true,
			'0333' => true,
			'0334' => true,
			'0335' => true,
			'0342' => true,
			'0343' => true,
			'0344' => true,
			'0346' => true,
			'0347' => true,
			'0349' => true,
			'0352' => true,
			'0353' => true,
			'0355' => true,
			'0356' => true,
			'0357' => true,
			'0358' => true,
			'0360' => true,
			'0361' => true,
			'0363' => true,
			'0365' => true,
			'0366' => true,
			'0367' => true,
			'0369' => true,
			'0371' => true,
			'0372' => true,
			'0374' => true,
			'0375' => true,
			'0377' => true,
			'0380' => true,
			'0381' => true,
			'0384' => true,
			'0385' => true,
			'0386' => true,
			'0388' => true,
			'0390' => true,
			'0394' => true,
			'0395' => true,
			'0396' => true,
			'0397' => true,
			'0398' => true,
			'0399' => true,
			'0401' => true,
			'0402' => true,
			'0404' => true,
			'0406' => true,
			'0409' => true,
			'0411' => true,
			'0412' => true,
			'0413' => true,
			'0415' => true,
			'0416' => true,
			'0419' => true,
			'0420' => true,
			'0421' => true,
			'0422' => true,
			'0423' => true,
			'0424' => true,
			'0427' => true,
			'0430' => true,
			'0431' => true,
			'0432' => true,
			'0434' => true,
			'0437' => true,
			'0441' => true,
			'0443' => true,
			'0445' => true,
			'0446' => true,
		],
		'phpstan.broker.dynamicStaticMethodReturnTypeExtension' => [
			'0270' => true,
			'0286' => true,
			'0304' => true,
			'0315' => true,
			'0318' => true,
			'0327' => true,
			'0392' => true,
			'0444' => true,
			'0912' => true,
		],
		'phpstan.typeSpecifier.functionTypeSpecifyingExtension' => [
			'0275' => true,
			'0277' => true,
			'0280' => true,
			'0307' => true,
			'0331' => true,
			'0341' => true,
			'0364' => true,
			'0373' => true,
			'0378' => true,
			'0382' => true,
			'0389' => true,
			'0393' => true,
			'0400' => true,
			'0403' => true,
			'0410' => true,
			'0414' => true,
			'0436' => true,
			'0442' => true,
			'0447' => true,
			'0448' => true,
			'0908' => true,
		],
		'phpstan.dynamicStaticMethodThrowTypeExtension' => [
			'0279' => true,
			'0296' => true,
			'0301' => true,
			'0325' => true,
			'0328' => true,
			'0339' => true,
			'0354' => true,
			'0418' => true,
			'0439' => true,
		],
		'phpstan.functionParameterClosureTypeExtension' => ['0282' => true],
		'phpstan.dynamicFunctionThrowTypeExtension' => [
			'0287' => true,
			'0300' => true,
			'0359' => true,
			'0376' => true,
			'0408' => true,
			'0440' => true,
		],
		'phpstan.broker.propertiesClassReflectionExtension' => ['0317' => true],
		'phpstan.dynamicMethodThrowTypeExtension' => ['0321' => true, '0345' => true, '0368' => true, '0426' => true],
		'phpstan.broker.operatorTypeSpecifyingExtension' => ['0337' => true, '0379' => true],
		'phpstan.typeSpecifier.methodTypeSpecifyingExtension' => ['0362' => true, '0909' => true],
		'phpstan.functionParameterOutTypeExtension' => ['0383' => true, '0407' => true, '0428' => true],
		'phpstan.broker.unaryOperatorTypeSpecifyingExtension' => ['0429' => true],
		'phpstan.collector' => [
			'0770' => true,
			'0771' => true,
			'0772' => true,
			'0773' => true,
			'0774' => true,
			'0775' => true,
			'0776' => true,
			'0777' => true,
			'0778' => true,
			'0997' => true,
			'0998' => true,
			'0999' => true,
			'01000' => true,
			'01001' => true,
		],
		'phpstan.stubValidator.rule' => [
			'0779' => true,
			'0780' => true,
			'0781' => true,
			'0782' => true,
			'0783' => true,
			'0784' => true,
			'0785' => true,
			'0786' => true,
			'0787' => true,
			'0788' => true,
			'0789' => true,
			'0790' => true,
			'0791' => true,
			'0792' => true,
			'0793' => true,
			'0794' => true,
			'0795' => true,
			'0796' => true,
			'0797' => true,
			'0798' => true,
			'0799' => true,
			'0800' => true,
			'0801' => true,
			'0802' => true,
			'0803' => true,
			'0804' => true,
			'0805' => true,
			'0806' => true,
			'0807' => true,
			'0808' => true,
			'0809' => true,
			'0810' => true,
			'0811' => true,
			'0812' => true,
			'0813' => true,
			'0814' => true,
			'0815' => true,
			'0816' => true,
			'0817' => true,
			'0818' => true,
			'0819' => true,
			'0820' => true,
			'0821' => true,
			'0822' => true,
			'0823' => true,
			'0824' => true,
			'0825' => true,
			'0826' => true,
			'0827' => true,
			'0828' => true,
			'0829' => true,
			'0830' => true,
			'0831' => true,
			'0832' => true,
			'0833' => true,
			'0834' => true,
			'0835' => true,
			'0836' => true,
			'0837' => true,
			'0838' => true,
			'0839' => true,
			'0840' => true,
		],
		'phpstan.restrictedClassConstantUsageExtension' => ['0882' => true, '0901' => true],
		'phpstan.restrictedClassNameUsageExtension' => ['0883' => true, '0905' => true],
		'phpstan.restrictedFunctionUsageExtension' => ['0884' => true, '0902' => true],
		'phpstan.restrictedPropertyUsageExtension' => ['0886' => true, '0904' => true],
		'phpstan.restrictedMethodUsageExtension' => ['0887' => true, '0903' => true],
		'phpstan.deprecations.deprecatedScopeResolver' => ['0899' => true],
		'phpstan.phpDoc.typeNodeResolverExtension' => ['0907' => true],
		'phpstan.typeSpecifier.staticMethodTypeSpecifyingExtension' => ['0910' => true],
		'phpstan.ignoreErrorExtension' => ['0920' => true, '0921' => true],
		'shipmonk.deadCode.memberUsageProvider' => [
			'0978' => true,
			'0979' => true,
			'0980' => true,
			'0981' => true,
			'0982' => true,
			'0983' => true,
			'0984' => true,
			'0985' => true,
			'0986' => true,
			'0987' => true,
			'0988' => true,
			'0989' => true,
			'0990' => true,
			'0991' => true,
			'0992' => true,
			'0993' => true,
			'0994' => true,
		],
		'shipmonk.deadCode.memberUsageExcluder' => ['0995' => true, '0996' => true],
	];

	protected $types = ['container' => 'Nette\DI\Container'];
	protected $aliases = [];

	protected $wiring = [
		'Nette\DI\Container' => [['container']],
		'PHPStan\Rules\Rule' => [
			[
				'021',
				'022',
				'023',
				'024',
				'025',
				'026',
				'027',
				'028',
				'029',
				'030',
				'048',
				'049',
				'050',
				'051',
				'052',
				'0862',
				'0876',
				'0877',
				'0878',
				'0879',
				'0880',
				'0881',
				'0885',
				'0888',
				'0889',
				'0890',
				'0891',
				'0892',
				'0893',
				'0894',
				'0895',
				'0896',
				'0900',
				'0906',
				'0922',
				'0923',
				'0924',
				'0925',
				'0928',
				'0929',
				'0930',
				'0931',
				'0932',
				'0933',
				'0934',
				'0935',
				'0936',
				'0937',
				'0938',
				'0939',
				'0940',
				'0941',
				'0942',
				'0943',
				'0944',
				'0945',
				'0946',
				'0947',
				'0948',
				'0949',
				'0950',
				'0951',
				'0952',
				'0953',
				'0954',
				'0955',
				'0956',
				'0957',
				'0958',
				'0959',
				'0960',
				'0961',
				'0962',
				'0963',
				'0964',
				'0965',
				'0966',
				'0967',
				'0968',
				'0969',
				'0970',
				'0971',
				'0972',
				'01002',
			],
			[
				'rules.0',
				'rules.1',
				'rules.2',
				'rules.3',
				'rules.4',
				'rules.5',
				'rules.6',
				'rules.7',
				'rules.8',
				'rules.9',
				'rules.10',
				'rules.11',
				'rules.12',
				'rules.13',
				'rules.14',
				'rules.15',
				'rules.16',
				'rules.17',
				'rules.18',
				'0462',
				'0463',
				'0464',
				'0465',
				'0466',
				'0467',
				'0468',
				'0469',
				'0470',
				'0471',
				'0472',
				'0473',
				'0474',
				'0475',
				'0476',
				'0477',
				'0478',
				'0479',
				'0480',
				'0481',
				'0482',
				'0483',
				'0484',
				'0485',
				'0486',
				'0487',
				'0488',
				'0489',
				'0490',
				'0491',
				'0492',
				'0493',
				'0494',
				'0495',
				'0496',
				'0497',
				'0498',
				'0499',
				'0500',
				'0501',
				'0502',
				'0503',
				'0504',
				'0505',
				'0506',
				'0507',
				'0508',
				'0509',
				'0510',
				'0511',
				'0512',
				'0513',
				'0514',
				'0515',
				'0516',
				'0517',
				'0518',
				'0519',
				'0520',
				'0521',
				'0522',
				'0523',
				'0524',
				'0525',
				'0526',
				'0527',
				'0528',
				'0529',
				'0530',
				'0531',
				'0532',
				'0533',
				'0534',
				'0535',
				'0536',
				'0537',
				'0538',
				'0539',
				'0540',
				'0541',
				'0542',
				'0543',
				'0544',
				'0545',
				'0546',
				'0547',
				'0548',
				'0549',
				'0550',
				'0551',
				'0552',
				'0553',
				'0554',
				'0555',
				'0556',
				'0557',
				'0558',
				'0559',
				'0560',
				'0561',
				'0562',
				'0563',
				'0564',
				'0565',
				'0566',
				'0567',
				'0568',
				'0569',
				'0570',
				'0571',
				'0572',
				'0573',
				'0574',
				'0575',
				'0576',
				'0577',
				'0578',
				'0579',
				'0580',
				'0581',
				'0582',
				'0583',
				'0584',
				'0585',
				'0586',
				'0587',
				'0588',
				'0589',
				'0590',
				'0591',
				'0592',
				'0593',
				'0594',
				'0595',
				'0596',
				'0597',
				'0598',
				'0599',
				'0600',
				'0601',
				'0602',
				'0603',
				'0604',
				'0605',
				'0606',
				'0607',
				'0608',
				'0609',
				'0610',
				'0611',
				'0612',
				'0613',
				'0614',
				'0615',
				'0616',
				'0617',
				'0618',
				'0619',
				'0620',
				'0621',
				'0622',
				'0623',
				'0624',
				'0625',
				'0626',
				'0627',
				'0628',
				'0629',
				'0630',
				'0631',
				'0632',
				'0633',
				'0634',
				'0635',
				'0636',
				'0637',
				'0638',
				'0639',
				'0640',
				'0641',
				'0642',
				'0643',
				'0644',
				'0645',
				'0646',
				'0647',
				'0648',
				'0649',
				'0650',
				'0651',
				'0652',
				'0653',
				'0654',
				'0655',
				'0656',
				'0657',
				'0658',
				'0659',
				'0660',
				'0661',
				'0662',
				'0663',
				'0664',
				'0665',
				'0666',
				'0667',
				'0668',
				'0669',
				'0670',
				'0671',
				'0672',
				'0673',
				'0674',
				'0675',
				'0676',
				'0677',
				'0678',
				'0679',
				'0680',
				'0681',
				'0682',
				'0683',
				'0684',
				'0685',
				'0686',
				'0687',
				'0688',
				'0689',
				'0690',
				'0691',
				'0692',
				'0693',
				'0694',
				'0695',
				'0696',
				'0697',
				'0698',
				'0699',
				'0700',
				'0701',
				'0702',
				'0703',
				'0704',
				'0705',
				'0706',
				'0707',
				'0708',
				'0709',
				'0710',
				'0711',
				'0712',
				'0713',
				'0714',
				'0715',
				'0716',
				'0717',
				'0718',
				'0719',
				'0720',
				'0721',
				'0722',
				'0723',
				'0724',
				'0725',
				'0726',
				'0727',
				'0728',
				'0729',
				'0730',
				'0731',
				'0732',
				'0733',
				'0734',
				'0735',
				'0736',
				'0737',
				'0738',
				'0739',
				'0740',
				'0741',
				'0742',
				'0743',
				'0744',
				'0745',
				'0746',
				'0747',
				'0748',
				'0749',
				'0750',
				'0751',
				'0752',
				'0753',
				'0754',
				'0755',
				'0756',
				'0757',
				'0758',
				'0759',
				'0760',
				'0761',
				'0762',
				'0763',
				'0764',
				'0765',
				'0766',
				'0767',
				'0768',
				'0769',
			],
			[
				342 => '0779',
				'0780',
				'0781',
				'0782',
				'0783',
				'0784',
				'0785',
				'0786',
				'0787',
				'0788',
				'0789',
				'0790',
				'0791',
				'0792',
				'0793',
				'0794',
				'0795',
				'0796',
				'0797',
				'0798',
				'0799',
				'0800',
				'0801',
				'0802',
				'0803',
				'0804',
				'0805',
				'0806',
				'0807',
				'0808',
				'0809',
				'0810',
				'0811',
				'0812',
				'0813',
				'0814',
				'0815',
				'0816',
				'0817',
				'0818',
				'0819',
				'0820',
				'0821',
				'0822',
				'0823',
				'0824',
				'0825',
				'0826',
				'0827',
				'0828',
				'0829',
				'0830',
				'0831',
				'0832',
				'0833',
				'0834',
				'0835',
				'0836',
				'0837',
				'0838',
				'0839',
				'0840',
			],
		],
		'PHPStan\Rules\Deprecations\FetchingDeprecatedConstRule' => [['rules.0']],
		'PHPStan\Rule\Nette\DoNotExtendNetteObjectRule' => [['rules.1']],
		'PHPStan\Rule\Nette\RegularExpressionPatternRule' => [['rules.2']],
		'PHPStan\Rules\PHPUnit\AssertSameBooleanExpectedRule' => [['rules.3']],
		'PHPStan\Rules\PHPUnit\AssertSameNullExpectedRule' => [['rules.4']],
		'PHPStan\Rules\PHPUnit\AssertSameWithCountRule' => [['rules.5']],
		'PHPStan\Rules\PHPUnit\ClassCoversExistsRule' => [['rules.6']],
		'PHPStan\Rules\PHPUnit\ClassMethodCoversExistsRule' => [['rules.7']],
		'PHPStan\Rules\PHPUnit\MockMethodCallRule' => [['rules.8']],
		'PHPStan\Rules\PHPUnit\NoMissingSpaceInClassAnnotationRule' => [['rules.9']],
		'PHPStan\Rules\PHPUnit\NoMissingSpaceInMethodAnnotationRule' => [['rules.10']],
		'PHPStan\Rules\PHPUnit\ShouldCallParentMethodsRule' => [['rules.11']],
		'PHPStan\Build\FinalClassRule' => [['rules.12']],
		'PHPStan\Build\AttributeNamedArgumentsRule' => [['rules.13']],
		'PHPStan\Build\NamedArgumentsRule' => [['rules.14']],
		'PHPStan\Build\OverrideAttributeThirdPartyMethodRule' => [['rules.15']],
		'PHPStan\Build\SkipTestsWithRequiresPhpAttributeRule' => [['rules.16']],
		'PHPStan\Build\MemoizationPropertyRule' => [['rules.17']],
		'PHPStan\Build\OrChainIdenticalComparisonToInArrayRule' => [['rules.18']],
		'PHPStan\Cache\Cache' => [['01']],
		'PHPStan\Fixable\Patcher' => [['02']],
		'PHPStan\Fixable\PhpDoc\PhpDocEditor' => [['03']],
		'PHPStan\Internal\HttpClientFactory' => [['04']],
		'PHPStan\Parallel\ParallelAnalyser' => [['05']],
		'PHPStan\Diagnose\DiagnoseExtension' => [['06', '08', '01002']],
		'PHPStan\Parallel\ForkParallelChecker' => [['06']],
		'PHPStan\Parallel\WorkerRunner' => [['07']],
		'PHPStan\Parallel\Scheduler' => [['08']],
		'PHPStan\Rules\IssetCheck' => [['09']],
		'PHPStan\Rules\Pure\FunctionPurityCheck' => [['010']],
		'PHPStan\Rules\FunctionDefinitionCheck' => [['011']],
		'PHPStan\Rules\ParameterCastableToStringCheck' => [['012']],
		'PHPStan\Rules\Generics\VarianceCheck' => [['013']],
		'PHPStan\Rules\Generics\MethodTagTemplateTypeCheck' => [['014']],
		'PHPStan\Rules\Generics\GenericAncestorsCheck' => [['015']],
		'PHPStan\Rules\Generics\CrossCheckInterfacesHelper' => [['016']],
		'PHPStan\Rules\Generics\GenericObjectTypeCheck' => [['017']],
		'PHPStan\Rules\Generics\TemplateTypeCheck' => [['018']],
		'PHPStan\Rules\MissingTypehintCheck' => [['019']],
		'PHPStan\Rules\RuleLevelHelper' => [['020']],
		'PHPStan\Rules\RestrictedUsage\RestrictedStaticMethodUsageRule' => [['021']],
		'PHPStan\Rules\RestrictedUsage\RestrictedFunctionCallableUsageRule' => [['022']],
		'PHPStan\Rules\RestrictedUsage\RestrictedMethodUsageRule' => [['023']],
		'PHPStan\Rules\RestrictedUsage\RestrictedPropertyUsageRule' => [['024']],
		'PHPStan\Rules\RestrictedUsage\RestrictedUsageOfDeprecatedStringCastRule' => [['025']],
		'PHPStan\Rules\RestrictedUsage\RestrictedMethodCallableUsageRule' => [['026']],
		'PHPStan\Rules\RestrictedUsage\RestrictedStaticPropertyUsageRule' => [['027']],
		'PHPStan\Rules\RestrictedUsage\RestrictedClassConstantUsageRule' => [['028']],
		'PHPStan\Rules\RestrictedUsage\RestrictedStaticMethodCallableUsageRule' => [['029']],
		'PHPStan\Rules\RestrictedUsage\RestrictedFunctionUsageRule' => [['030']],
		'PHPStan\Rules\Classes\LocalTypeAliasesCheck' => [['031']],
		'PHPStan\Rules\Classes\MethodTagCheck' => [['032']],
		'PHPStan\Rules\Classes\DuplicateDeclarationHelper' => [['033']],
		'PHPStan\Rules\Classes\MixinCheck' => [['034']],
		'PHPStan\Rules\Classes\PropertyTagCheck' => [['035']],
		'PHPStan\Rules\Classes\ConsistentConstructorHelper' => [['036']],
		'PHPStan\Rules\Constants\AlwaysUsedClassConstantsExtensionProvider' => [['037']],
		'PHPStan\Rules\Constants\LazyAlwaysUsedClassConstantsExtensionProvider' => [['037']],
		'PHPStan\Rules\Functions\PrintfHelper' => [['038']],
		'PHPStan\Rules\Properties\PropertyDescriptor' => [['039']],
		'PHPStan\Rules\Properties\PropertyReflectionFinder' => [['040']],
		'PHPStan\Rules\Properties\AccessStaticPropertiesCheck' => [['041']],
		'PHPStan\Rules\Properties\AccessPropertiesCheck' => [['042']],
		'PHPStan\Rules\Properties\ReadWritePropertiesExtensionProvider' => [['043']],
		'PHPStan\Rules\Properties\LazyReadWritePropertiesExtensionProvider' => [['043']],
		'PHPStan\Rules\Playground\NeverRuleHelper' => [['044']],
		'PHPStan\Rules\InternalTag\RestrictedInternalUsageHelper' => [['045']],
		'PHPStan\Rules\TooWideTypehints\TooWideTypeCheck' => [['046']],
		'PHPStan\Rules\TooWideTypehints\TooWideParameterOutTypeCheck' => [['047']],
		'PHPStan\Rules\Debug\DumpNativeTypeRule' => [['048']],
		'PHPStan\Rules\Debug\DumpPhpDocTypeRule' => [['049']],
		'PHPStan\Rules\Debug\FileAssertRule' => [['050']],
		'PHPStan\Rules\Debug\DumpTypeRule' => [['051']],
		'PHPStan\Rules\Debug\DebugScopeRule' => [['052']],
		'PHPStan\Rules\Methods\StaticMethodCallCheck' => [['053']],
		'PHPStan\Rules\Methods\AlwaysUsedMethodExtensionProvider' => [['054']],
		'PHPStan\Rules\Methods\LazyAlwaysUsedMethodExtensionProvider' => [['054']],
		'PHPStan\Rules\Methods\MethodCallCheck' => [['055']],
		'PHPStan\Rules\Methods\MethodParameterComparisonHelper' => [['056']],
		'PHPStan\Rules\Methods\ParentMethodHelper' => [['057']],
		'PHPStan\Rules\Methods\MethodPrototypeFinder' => [['058']],
		'PHPStan\Rules\Methods\MethodVisibilityComparisonHelper' => [['059']],
		'PHPStan\Rules\ClassNameCheck' => [['060']],
		'PHPStan\Rules\ClassForbiddenNameCheck' => [['061']],
		'PHPStan\Rules\UnusedFunctionParametersCheck' => [['062']],
		'PHPStan\Rules\FunctionReturnTypeCheck' => [['063']],
		'PHPStan\Rules\FunctionCallParametersCheck' => [['064']],
		'PHPStan\Rules\PhpDoc\AssertRuleHelper' => [['065']],
		'PHPStan\Rules\PhpDoc\IncompatiblePhpDocTypeCheck' => [['066']],
		'PHPStan\Rules\PhpDoc\VarTagTypeRuleHelper' => [['067']],
		'PHPStan\Rules\PhpDoc\RequireExtendsCheck' => [['068']],
		'PHPStan\Rules\PhpDoc\GenericCallableRuleHelper' => [['069']],
		'PHPStan\Rules\PhpDoc\UnresolvableTypeHelper' => [['070']],
		'PHPStan\Rules\PhpDoc\ConditionalReturnTypeRuleHelper' => [['071']],
		'PHPStan\Rules\Registry' => [['registry']],
		'PHPStan\Rules\LazyRegistry' => [['registry']],
		'PHPStan\Rules\NullsafeCheck' => [['072']],
		'PHPStan\Rules\Api\ApiRuleHelper' => [['073']],
		'PHPStan\Rules\AttributesCheck' => [['074']],
		'PHPStan\Rules\Exceptions\ExceptionTypeResolver' => [1 => ['075'], [1 => 'exceptionTypeResolver']],
		'PHPStan\Rules\Exceptions\DefaultExceptionTypeResolver' => [['075']],
		'PHPStan\Rules\Exceptions\TooWideThrowTypeCheck' => [['076']],
		'PHPStan\Rules\Exceptions\MissingCheckedExceptionInThrowsCheck' => [['077']],
		'PHPStan\Rules\Arrays\NonexistentOffsetInArrayDimFetchCheck' => [['078']],
		'PHPStan\Rules\ClassCaseSensitivityCheck' => [['079']],
		'PHPStan\Rules\Comparison\ConstantConditionRuleHelper' => [['080']],
		'PHPStan\Rules\Comparison\PossiblyImpureTipHelper' => [['081']],
		'PHPStan\Rules\Comparison\ImpossibleCheckTypeHelper' => [['082']],
		'PHPStan\Analyser\AnalyserResultFinalizer' => [['083']],
		'PHPStan\Analyser\RuleErrorTransformer' => [['084']],
		'PHPStan\Analyser\NodeScopeResolver' => [0 => ['085'], 2 => [1 => '0167']],
		'PHPStan\Analyser\Fiber\FiberNodeScopeResolver' => [['085']],
		'PHPStan\Analyser\ExprHandler' => [
			[
				'086',
				'087',
				'088',
				'089',
				'090',
				'091',
				'092',
				'093',
				'094',
				'095',
				'096',
				'097',
				'098',
				'099',
				'0100',
				'0101',
				'0102',
				'0103',
				'0104',
				'0105',
				'0106',
				'0107',
				'0108',
				'0109',
				'0110',
				'0111',
				'0112',
				'0113',
				'0114',
				'0115',
				'0116',
				'0117',
				'0118',
				'0119',
				'0120',
				'0121',
				'0122',
				'0123',
				'0124',
				'0125',
				'0126',
				'0127',
				'0128',
				'0129',
				'0130',
				'0131',
				'0132',
				'0133',
				'0134',
				'0135',
				'0136',
				'0137',
				'0138',
				'0139',
				'0145',
				'0146',
				'0147',
				'0148',
				'0149',
				'0150',
				'0151',
				'0152',
				'0153',
				'0154',
				'0155',
				'0156',
				'0157',
			],
		],
		'PHPStan\Analyser\ExprHandler\FirstClassCallableFuncCallHandler' => [['086']],
		'PHPStan\Analyser\ExprHandler\YieldHandler' => [['087']],
		'PHPStan\Analyser\ExprHandler\CastHandler' => [['088']],
		'PHPStan\Analyser\ExprHandler\EmptyHandler' => [['089']],
		'PHPStan\Analyser\ExprHandler\PreDecHandler' => [['090']],
		'PHPStan\Analyser\ExprHandler\EvalHandler' => [['091']],
		'PHPStan\Analyser\ExprHandler\AssignHandler' => [['092']],
		'PHPStan\Analyser\ExprHandler\StaticPropertyFetchHandler' => [['093']],
		'PHPStan\Analyser\ExprHandler\CloneHandler' => [['094']],
		'PHPStan\Analyser\ExprHandler\ThrowHandler' => [['095']],
		'PHPStan\Analyser\ExprHandler\ScalarHandler' => [['096']],
		'PHPStan\Analyser\ExprHandler\ErrorSuppressHandler' => [['097']],
		'PHPStan\Analyser\ExprHandler\UnaryMinusHandler' => [['098']],
		'PHPStan\Analyser\ExprHandler\InterpolatedStringHandler' => [['099']],
		'PHPStan\Analyser\ExprHandler\VariableHandler' => [['0100']],
		'PHPStan\Analyser\ExprHandler\IncludeHandler' => [['0101']],
		'PHPStan\Analyser\ExprHandler\PipeHandler' => [['0102']],
		'PHPStan\Analyser\ExprHandler\NewHandler' => [['0103']],
		'PHPStan\Analyser\ExprHandler\AssignOpHandler' => [['0104']],
		'PHPStan\Analyser\ExprHandler\TernaryHandler' => [['0105']],
		'PHPStan\Analyser\ExprHandler\UnaryPlusHandler' => [['0106']],
		'PHPStan\Analyser\ExprHandler\FirstClassCallableNewHandler' => [['0107']],
		'PHPStan\Analyser\ExprHandler\PreIncHandler' => [['0108']],
		'PHPStan\Analyser\ExprHandler\ArrowFunctionHandler' => [['0109']],
		'PHPStan\Analyser\ExprHandler\IssetHandler' => [['0110']],
		'PHPStan\Analyser\ExprHandler\ClassConstFetchHandler' => [['0111']],
		'PHPStan\Analyser\ExprHandler\ExitHandler' => [['0112']],
		'PHPStan\Analyser\ExprHandler\FirstClassCallableMethodCallHandler' => [['0113']],
		'PHPStan\Analyser\ExprHandler\BitwiseNotHandler' => [['0114']],
		'PHPStan\Analyser\ExprHandler\MatchHandler' => [['0115']],
		'PHPStan\Analyser\ExprHandler\ClosureHandler' => [['0116']],
		'PHPStan\Analyser\ExprHandler\FirstClassCallableStaticCallHandler' => [['0117']],
		'PHPStan\Analyser\ExprHandler\YieldFromHandler' => [['0118']],
		'PHPStan\Analyser\ExprHandler\PostDecHandler' => [['0119']],
		'PHPStan\Analyser\ExprHandler\Virtual\UnsetOffsetExprHandler' => [['0120']],
		'PHPStan\Analyser\ExprHandler\Virtual\SetOffsetValueTypeExprHandler' => [['0121']],
		'PHPStan\Analyser\ExprHandler\Virtual\GetIterableKeyTypeExprHandler' => [['0122']],
		'PHPStan\Analyser\ExprHandler\Virtual\FunctionCallableNodeHandler' => [['0123']],
		'PHPStan\Analyser\ExprHandler\Virtual\OriginalPropertyTypeExprHandler' => [['0124']],
		'PHPStan\Analyser\ExprHandler\Virtual\InstantiationCallableNodeHandler' => [['0125']],
		'PHPStan\Analyser\ExprHandler\Virtual\SetExistingOffsetValueTypeExprHandler' => [['0126']],
		'PHPStan\Analyser\ExprHandler\Virtual\GetIterableValueTypeExprHandler' => [['0127']],
		'PHPStan\Analyser\ExprHandler\Virtual\GetOffsetValueTypeExprHandler' => [['0128']],
		'PHPStan\Analyser\ExprHandler\Virtual\TypeExprHandler' => [['0129']],
		'PHPStan\Analyser\ExprHandler\Virtual\StaticMethodCallableNodeHandler' => [['0130']],
		'PHPStan\Analyser\ExprHandler\Virtual\ExistingArrayDimFetchHandler' => [['0131']],
		'PHPStan\Analyser\ExprHandler\Virtual\AlwaysRememberedExprHandler' => [['0132']],
		'PHPStan\Analyser\ExprHandler\Virtual\NativeTypeExprHandler' => [['0133']],
		'PHPStan\Analyser\ExprHandler\Virtual\MethodCallableNodeHandler' => [['0134']],
		'PHPStan\Analyser\ExprHandler\CoalesceHandler' => [['0135']],
		'PHPStan\Analyser\ExprHandler\BooleanOrHandler' => [['0136']],
		'PHPStan\Analyser\ExprHandler\ArrayHandler' => [['0137']],
		'PHPStan\Analyser\ExprHandler\PostIncHandler' => [['0138']],
		'PHPStan\Analyser\ExprHandler\BooleanAndHandler' => [['0139']],
		'PHPStan\Analyser\ExprHandler\Helper\ImplicitToStringCallHelper' => [['0140']],
		'PHPStan\Analyser\ExprHandler\Helper\ClosureTypeResolver' => [['0141']],
		'PHPStan\Analyser\ExprHandler\Helper\NonNullabilityHelper' => [['0142']],
		'PHPStan\Analyser\ExprHandler\Helper\MethodCallReturnTypeHelper' => [['0143']],
		'PHPStan\Analyser\ExprHandler\Helper\MethodThrowPointHelper' => [['0144']],
		'PHPStan\Analyser\ExprHandler\NullsafePropertyFetchHandler' => [['0145']],
		'PHPStan\Analyser\ExprHandler\MethodCallHandler' => [['0146']],
		'PHPStan\Analyser\ExprHandler\PropertyFetchHandler' => [['0147']],
		'PHPStan\Analyser\ExprHandler\CastStringHandler' => [['0148']],
		'PHPStan\Analyser\ExprHandler\ConstFetchHandler' => [['0149']],
		'PHPStan\Analyser\ExprHandler\BinaryOpHandler' => [['0150']],
		'PHPStan\Analyser\ExprHandler\StaticCallHandler' => [['0151']],
		'PHPStan\Analyser\ExprHandler\NullsafeMethodCallHandler' => [['0152']],
		'PHPStan\Analyser\ExprHandler\BooleanNotHandler' => [['0153']],
		'PHPStan\Analyser\ExprHandler\FuncCallHandler' => [['0154']],
		'PHPStan\Analyser\ExprHandler\PrintHandler' => [['0155']],
		'PHPStan\Analyser\ExprHandler\InstanceofHandler' => [['0156']],
		'PHPStan\Analyser\ExprHandler\ArrayDimFetchHandler' => [['0157']],
		'PHPStan\Analyser\RicherScopeGetTypeHelper' => [['0158']],
		'PHPStan\Analyser\ConstantResolver' => [['0159']],
		'PHPStan\Analyser\TypeSpecifier' => [['typeSpecifier']],
		'PHPStan\Analyser\ScopeFactory' => [['0160']],
		'PHPStan\Analyser\FileAnalyser' => [['0161']],
		'PHPStan\Analyser\LocalIgnoresProcessor' => [['0162']],
		'PHPStan\Analyser\Analyser' => [['0163']],
		'PHPStan\Analyser\IgnoreErrorExtensionProvider' => [['0164']],
		'PHPStan\Analyser\ResultCache\ResultCacheClearer' => [['0165']],
		'PHPStan\Analyser\ConstantResolverFactory' => [['0166']],
		'PHPStan\Analyser\TypeSpecifierFactory' => [['typeSpecifierFactory']],
		'PHPStan\Analyser\Ignore\IgnoredErrorHelper' => [['0168']],
		'PHPStan\Analyser\Ignore\IgnoreLexer' => [['0169']],
		'PHPStan\Node\Printer\ExprPrinter' => [['0170']],
		'PhpParser\PrettyPrinter\Standard' => [1 => ['0171']],
		'PhpParser\PrettyPrinterAbstract' => [1 => ['0171']],
		'PhpParser\PrettyPrinter' => [1 => ['0171']],
		'PHPStan\Node\Printer\Printer' => [['0171']],
		'PHPStan\Node\DeepNodeCloner' => [['0172']],
		'PHPStan\File\FileHelper' => [['0173']],
		'PHPStan\File\FileMonitor' => [['0174']],
		'PHPStan\File\RelativePathHelper' => [
			0 => ['relativePathHelper'],
			2 => [1 => 'parentDirectoryRelativePathHelper', 'simpleRelativePathHelper'],
		],
		'PHPStan\File\FuzzyRelativePathHelper' => [['relativePathHelper']],
		'PHPStan\File\FileExcluderFactory' => [['0175']],
		'PHPStan\Dependency\ExportedNodeFetcher' => [['0176']],
		'PHPStan\Dependency\ExportedNodeResolver' => [['0177']],
		'PHPStan\Dependency\DependencyResolver' => [['0178']],
		'PHPStan\DependencyInjection\Container' => [['0179'], ['0181']],
		'PHPStan\DependencyInjection\MemoizingContainer' => [['0179']],
		'PHPStan\DependencyInjection\DerivativeContainerFactory' => [['0180']],
		'PHPStan\DependencyInjection\Nette\NetteContainer' => [['0181']],
		'PHPStan\DependencyInjection\Reflection\ClassReflectionExtensionRegistryProvider' => [['0182']],
		'PHPStan\DependencyInjection\Reflection\LazyClassReflectionExtensionRegistryProvider' => [['0182']],
		'PHPStan\DependencyInjection\Type\ExpressionTypeResolverExtensionRegistryProvider' => [['0183']],
		'PHPStan\DependencyInjection\Type\LazyExpressionTypeResolverExtensionRegistryProvider' => [['0183']],
		'PHPStan\DependencyInjection\Type\ParameterClosureTypeExtensionProvider' => [['0184']],
		'PHPStan\DependencyInjection\Type\LazyParameterClosureTypeExtensionProvider' => [['0184']],
		'PHPStan\DependencyInjection\Type\ParameterOutTypeExtensionProvider' => [['0185']],
		'PHPStan\DependencyInjection\Type\LazyParameterOutTypeExtensionProvider' => [['0185']],
		'PHPStan\DependencyInjection\Type\OperatorTypeSpecifyingExtensionRegistryProvider' => [['0186']],
		'PHPStan\DependencyInjection\Type\LazyOperatorTypeSpecifyingExtensionRegistryProvider' => [['0186']],
		'PHPStan\DependencyInjection\Type\ParameterClosureThisExtensionProvider' => [['0187']],
		'PHPStan\DependencyInjection\Type\LazyParameterClosureThisExtensionProvider' => [['0187']],
		'PHPStan\DependencyInjection\Type\DynamicReturnTypeExtensionRegistryProvider' => [['0188']],
		'PHPStan\DependencyInjection\Type\LazyDynamicReturnTypeExtensionRegistryProvider' => [['0188']],
		'PHPStan\DependencyInjection\Type\UnaryOperatorTypeSpecifyingExtensionRegistryProvider' => [['0189']],
		'PHPStan\DependencyInjection\Type\LazyUnaryOperatorTypeSpecifyingExtensionRegistryProvider' => [['0189']],
		'PHPStan\DependencyInjection\Type\DynamicThrowTypeExtensionProvider' => [['0190']],
		'PHPStan\DependencyInjection\Type\LazyDynamicThrowTypeExtensionProvider' => [['0190']],
		'PHPStan\Broker\AnonymousClassNameHelper' => [['0191']],
		'PHPStan\Reflection\ConstructorsHelper' => [['0192']],
		'PHPStan\Reflection\ReflectionProvider\ReflectionProviderProvider' => [['0193']],
		'PHPStan\Reflection\ReflectionProvider\LazyReflectionProviderProvider' => [['0193']],
		'PHPStan\Reflection\ReflectionProvider\ReflectionProviderFactory' => [['reflectionProviderFactory']],
		'PHPStan\Reflection\BetterReflection\SourceStubber\ReflectionSourceStubberFactory' => [['0194']],
		'PHPStan\Reflection\BetterReflection\SourceStubber\PhpStormStubsSourceStubberFactory' => [['0195']],
		'PHPStan\BetterReflection\Reflector\Reflector' => [
			0 => ['betterReflectionReflector'],
			2 => [1 => 'originalBetterReflectionReflector', 'nodeScopeResolverReflector', 'stubReflector'],
		],
		'PHPStan\Reflection\BetterReflection\Reflector\MemoizingReflector' => [['betterReflectionReflector']],
		'PHPStan\Reflection\BetterReflection\BetterReflectionSourceLocatorFactory' => [['0196']],
		'PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedSingleFileSourceLocatorRepository' => [['0197']],
		'PHPStan\Reflection\BetterReflection\SourceLocator\FileNodesFetcher' => [['0198']],
		'PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedDirectorySourceLocatorFactory' => [['0199']],
		'PHPStan\Reflection\BetterReflection\SourceLocator\ComposerJsonAndInstalledJsonSourceLocatorMaker' => [['0200']],
		'PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedDirectorySourceLocatorRepository' => [['0201']],
		'PHPStan\Type\DynamicMethodReturnTypeExtension' => [
			[
				'0202',
				'0267',
				'0273',
				'0305',
				'0338',
				'0351',
				'0391',
				'0401',
				'0405',
				'0417',
				'0435',
				'0438',
				'0444',
				'0863',
				'0864',
				'0865',
				'0866',
				'0867',
				'0868',
				'0869',
				'0870',
				'0871',
				'0872',
				'0873',
				'0911',
				'0912',
				'01005',
				'01006',
			],
		],
		'PHPStan\Reflection\BetterReflection\Type\AdapterReflectionEnumDynamicReturnTypeExtension' => [['0202']],
		'PHPStan\Reflection\AttributeReflectionFactory' => [['0203']],
		'PHPStan\Reflection\InitializerExprTypeResolver' => [['0204']],
		'PHPStan\Reflection\Deprecation\DeprecationProvider' => [['0205']],
		'PHPStan\Reflection\SignatureMap\NativeFunctionReflectionProvider' => [['0206']],
		'PHPStan\Reflection\SignatureMap\SignatureMapParser' => [['0207']],
		'PHPStan\Reflection\SignatureMap\SignatureMapProvider' => [['0208'], ['0210', '0211']],
		'PHPStan\Reflection\SignatureMap\SignatureMapProviderFactory' => [['0209']],
		'PHPStan\Reflection\SignatureMap\Php8SignatureMapProvider' => [['0210']],
		'PHPStan\Reflection\SignatureMap\FunctionSignatureMapProvider' => [['0211']],
		'PHPStan\Reflection\AllowedSubTypesClassReflectionExtension' => [['0212', '0213']],
		'PHPStan\Reflection\Php\EnumAllowedSubTypesClassReflectionExtension' => [['0212']],
		'PHPStan\Reflection\Php\SealedAllowedSubTypesClassReflectionExtension' => [['0213']],
		'PHPStan\Process\CpuCoreCounter' => [['0214']],
		'PHPStan\Command\AnalyseApplication' => [['0215']],
		'PHPStan\Command\AnalyserRunner' => [['0216']],
		'PHPStan\Command\ErrorFormatter\ErrorFormatter' => [
			[
				'errorFormatter.teamcity',
				'errorFormatter.junit',
				'errorFormatter.raw',
				'errorFormatter.checkstyle',
				'errorFormatter.table',
				'errorFormatter.github',
				'errorFormatter.gitlab',
				'errorFormatter.json',
				'errorFormatter.prettyJson',
				'errorFormatter.removeDeadCode',
				'errorFormatter.filterOutUnmatchedInlineIgnoresDuringPartialAnalysis',
			],
			['0217'],
		],
		'PHPStan\Command\ErrorFormatter\TeamcityErrorFormatter' => [['errorFormatter.teamcity']],
		'PHPStan\Command\ErrorFormatter\JunitErrorFormatter' => [['errorFormatter.junit']],
		'PHPStan\Command\ErrorFormatter\RawErrorFormatter' => [['errorFormatter.raw']],
		'PHPStan\Command\ErrorFormatter\CheckstyleErrorFormatter' => [['errorFormatter.checkstyle']],
		'PHPStan\Command\ErrorFormatter\TableErrorFormatter' => [['errorFormatter.table']],
		'PHPStan\Command\ErrorFormatter\GithubErrorFormatter' => [['errorFormatter.github']],
		'PHPStan\Command\ErrorFormatter\GitlabErrorFormatter' => [['errorFormatter.gitlab']],
		'PHPStan\Command\ErrorFormatter\CiDetectedErrorFormatter' => [['0217']],
		'PHPStan\Command\FixerWorkerRunner' => [['0218']],
		'PHPStan\Command\FixerApplication' => [['0219']],
		'PHPStan\Collectors\Registry' => [['0220']],
		'PHPStan\Collectors\RegistryFactory' => [['0221']],
		'PHPStan\PhpDoc\PhpDocInheritanceResolver' => [['0222']],
		'PHPStan\PhpDoc\TypeNodeResolverExtensionRegistryProvider' => [['0223']],
		'PHPStan\PhpDoc\LazyTypeNodeResolverExtensionRegistryProvider' => [['0223']],
		'PHPStan\PhpDoc\StubFilesExtension' => [['0224', '0225', '0227', '0229', '0230']],
		'PHPStan\PhpDoc\BcMathNumberStubFilesExtension' => [['0224']],
		'PHPStan\PhpDoc\SocketSelectStubFilesExtension' => [['0225']],
		'PHPStan\PhpDoc\StubPhpDocProvider' => [['stubPhpDocProvider']],
		'PHPStan\PhpDoc\StubValidator' => [['0226']],
		'PHPStan\PhpDoc\ReflectionClassStubFilesExtension' => [['0227']],
		'PHPStan\PhpDoc\StubFilesProvider' => [['0228']],
		'PHPStan\PhpDoc\DefaultStubFilesProvider' => [['0228']],
		'PHPStan\PhpDoc\ReflectionEnumStubFilesExtension' => [['0229']],
		'PHPStan\PhpDoc\JsonValidateStubFilesExtension' => [['0230']],
		'PHPStan\PhpDoc\TypeStringResolver' => [['0231']],
		'PHPStan\PhpDoc\PhpDocNodeResolver' => [['0232']],
		'PHPStan\PhpDoc\TypeNodeResolver' => [['0233']],
		'PHPStan\PhpDoc\PhpDocStringResolver' => [['0234']],
		'PHPStan\PhpDoc\ConstExprNodeResolver' => [['0235']],
		'PhpParser\NodeVisitorAbstract' => [
			[
				'0236',
				'0237',
				'0238',
				'0239',
				'0240',
				'0241',
				'0242',
				'0243',
				'0244',
				'0245',
				'0246',
				'0247',
				'0248',
				'0249',
				'0250',
				'0251',
				'0252',
				'0253',
				'0255',
				'0256',
				'0257',
				'0258',
				'0259',
				'0842',
				'0851',
				'0852',
				'01004',
			],
		],
		'PhpParser\NodeVisitor' => [
			[
				'0236',
				'0237',
				'0238',
				'0239',
				'0240',
				'0241',
				'0242',
				'0243',
				'0244',
				'0245',
				'0246',
				'0247',
				'0248',
				'0249',
				'0250',
				'0251',
				'0252',
				'0253',
				'0255',
				'0256',
				'0257',
				'0258',
				'0259',
				'0842',
				'0851',
				'0852',
				'01004',
			],
		],
		'PHPStan\Parser\UseAliasVisitor' => [['0236']],
		'PHPStan\Parser\TypeTraverserInstanceofVisitor' => [['0237']],
		'PHPStan\Parser\CurlSetOptArgVisitor' => [['0238']],
		'PHPStan\Parser\GotoLabelVisitor' => [['0239']],
		'PHPStan\Parser\ArrayMapArgVisitor' => [['0240']],
		'PHPStan\Parser\MagicConstantParamDefaultVisitor' => [['0241']],
		'PHPStan\Parser\ArrayFilterArgVisitor' => [['0242']],
		'PHPStan\Parser\TryCatchTypeVisitor' => [['0243']],
		'PHPStan\Parser\CurlSetOptArrayArgVisitor' => [['0244']],
		'PHPStan\Parser\ClosureBindArgVisitor' => [['0245']],
		'PHPStan\Parser\ArrayFindArgVisitor' => [['0246']],
		'PHPStan\Parser\AnonymousClassVisitor' => [['0247']],
		'PHPStan\Parser\ClosureArgVisitor' => [['0248']],
		'PHPStan\Parser\StandaloneThrowExprVisitor' => [['0249']],
		'PHPStan\Parser\ParentStmtTypesVisitor' => [['0250']],
		'PHPStan\Parser\DeclarePositionVisitor' => [['0251']],
		'PHPStan\Parser\ArrowFunctionArgVisitor' => [['0252']],
		'PHPStan\Parser\ClosureBindToVarVisitor' => [['0253']],
		'PHPStan\Parser\LexerFactory' => [['0254']],
		'PHPStan\Parser\NewAssignedToPropertyVisitor' => [['0255']],
		'PHPStan\Parser\ImplodeArgVisitor' => [['0256']],
		'PHPStan\Parser\LastConditionVisitor' => [['0257']],
		'PHPStan\Parser\ImmediatelyInvokedClosureVisitor' => [['0258']],
		'PHPStan\Parser\ArrayWalkArgVisitor' => [['0259']],
		'PHPStan\Type\BitwiseFlagHelper' => [['0260']],
		'PHPStan\Type\TypeAliasResolverProvider' => [['0261']],
		'PHPStan\Type\LazyTypeAliasResolverProvider' => [['0261']],
		'PHPStan\Type\Regex\RegexGroupParser' => [['0262']],
		'PHPStan\Type\Regex\RegexExpressionHelper' => [['0263']],
		'PHPStan\Type\Constant\OversizedArrayBuilder' => [['0264']],
		'PHPStan\Type\ClosureTypeFactory' => [['0265']],
		'PHPStan\Type\TypeAliasResolver' => [['0266']],
		'PHPStan\Type\UsefulTypeAliasResolver' => [['0266']],
		'PHPStan\Type\PHPStan\ClassNameUsageLocationCreateIdentifierDynamicReturnTypeExtension' => [['0267']],
		'PHPStan\Type\DynamicFunctionReturnTypeExtension' => [
			[
				'0268',
				'0269',
				'0271',
				'0272',
				'0276',
				'0278',
				'0281',
				'0283',
				'0284',
				'0285',
				'0288',
				'0289',
				'0290',
				'0291',
				'0292',
				'0293',
				'0294',
				'0295',
				'0297',
				'0298',
				'0299',
				'0302',
				'0303',
				'0306',
				'0308',
				'0310',
				'0311',
				'0312',
				'0313',
				'0316',
				'0319',
				'0320',
				'0322',
				'0323',
				'0324',
				'0326',
				'0329',
				'0330',
				'0332',
				'0333',
				'0334',
				'0335',
				'0342',
				'0343',
				'0344',
				'0346',
				'0347',
				'0349',
				'0352',
				'0353',
				'0355',
				'0356',
				'0357',
				'0358',
				'0360',
				'0361',
				'0363',
				'0365',
				'0366',
				'0367',
				'0369',
				'0371',
				'0372',
				'0374',
				'0375',
				'0377',
				'0380',
				'0381',
				'0384',
				'0385',
				'0386',
				'0388',
				'0390',
				'0394',
				'0395',
				'0396',
				'0397',
				'0398',
				'0399',
				'0401',
				'0402',
				'0404',
				'0406',
				'0409',
				'0411',
				'0412',
				'0413',
				'0415',
				'0416',
				'0419',
				'0420',
				'0421',
				'0422',
				'0423',
				'0424',
				'0427',
				'0430',
				'0431',
				'0432',
				'0434',
				'0437',
				'0441',
				'0443',
				'0445',
				'0446',
			],
		],
		'PHPStan\Type\Php\ArrayKeyDynamicReturnTypeExtension' => [['0268']],
		'PHPStan\Type\Php\DateFunctionReturnTypeExtension' => [['0269']],
		'PHPStan\Type\DynamicStaticMethodReturnTypeExtension' => [
			['0270', '0286', '0304', '0315', '0318', '0327', '0392', '0444', '0912'],
		],
		'PHPStan\Type\Php\BackedEnumFromMethodDynamicReturnTypeExtension' => [['0270']],
		'PHPStan\Type\Php\ArrayPopFunctionReturnTypeExtension' => [['0271']],
		'PHPStan\Type\Php\FilterVarDynamicReturnTypeExtension' => [['0272']],
		'PHPStan\Type\Php\DateIntervalFormatDynamicReturnTypeExtension' => [['0273']],
		'PHPStan\Type\Php\ArrayColumnHelper' => [['0274']],
		'PHPStan\Type\FunctionTypeSpecifyingExtension' => [
			[
				'0275',
				'0277',
				'0280',
				'0307',
				'0331',
				'0341',
				'0364',
				'0373',
				'0378',
				'0382',
				'0389',
				'0393',
				'0400',
				'0403',
				'0410',
				'0414',
				'0436',
				'0442',
				'0447',
				'0448',
				'0908',
			],
		],
		'PHPStan\Analyser\TypeSpecifierAwareExtension' => [
			[
				'0275',
				'0277',
				'0280',
				'0284',
				'0307',
				'0331',
				'0341',
				'0362',
				'0364',
				'0373',
				'0378',
				'0382',
				'0389',
				'0393',
				'0400',
				'0403',
				'0410',
				'0414',
				'0436',
				'0442',
				'0447',
				'0448',
				'0908',
				'0909',
				'0910',
			],
		],
		'PHPStan\Type\Php\CtypeDigitFunctionTypeSpecifyingExtension' => [['0275']],
		'PHPStan\Type\Php\ArraySumFunctionDynamicReturnTypeExtension' => [['0276']],
		'PHPStan\Type\Php\IsCallableFunctionTypeSpecifyingExtension' => [['0277']],
		'PHPStan\Type\Php\ArrayFindKeyFunctionReturnTypeExtension' => [['0278']],
		'PHPStan\Type\DynamicStaticMethodThrowTypeExtension' => [
			['0279', '0296', '0301', '0325', '0328', '0339', '0354', '0418', '0439'],
		],
		'PHPStan\Type\Php\SimpleXMLElementConstructorThrowTypeExtension' => [['0279']],
		'PHPStan\Type\Php\PropertyExistsTypeSpecifyingExtension' => [['0280']],
		'PHPStan\Type\Php\StrtotimeFunctionReturnTypeExtension' => [['0281']],
		'PHPStan\Type\FunctionParameterClosureTypeExtension' => [['0282']],
		'PHPStan\Type\Php\PregReplaceCallbackClosureTypeExtension' => [['0282']],
		'PHPStan\Type\Php\GetDefinedVarsFunctionReturnTypeExtension' => [['0283']],
		'PHPStan\Type\Php\TypeSpecifyingFunctionsDynamicReturnTypeExtension' => [['0284']],
		'PHPStan\Type\Php\BcMathStringOrNullReturnTypeExtension' => [['0285']],
		'PHPStan\Type\Php\ClosureFromCallableDynamicReturnTypeExtension' => [['0286']],
		'PHPStan\Type\DynamicFunctionThrowTypeExtension' => [['0287', '0300', '0359', '0376', '0408', '0440']],
		'PHPStan\Type\Php\VersionCompareFunctionDynamicThrowTypeExtension' => [['0287']],
		'PHPStan\Type\Php\StrPadFunctionReturnTypeExtension' => [['0288']],
		'PHPStan\Type\Php\PathinfoFunctionDynamicReturnTypeExtension' => [['0289']],
		'PHPStan\Type\Php\ArrayKeysFunctionDynamicReturnTypeExtension' => [['0290']],
		'PHPStan\Type\Php\ArrayMapFunctionReturnTypeExtension' => [['0291']],
		'PHPStan\Type\Php\ArrayMergeFunctionDynamicReturnTypeExtension' => [['0292']],
		'PHPStan\Type\Php\GettypeFunctionReturnTypeExtension' => [['0293']],
		'PHPStan\Type\Php\DateTimeCreateDynamicReturnTypeExtension' => [['0294']],
		'PHPStan\Type\Php\StrSplitFunctionReturnTypeExtension' => [['0295']],
		'PHPStan\Type\Php\ReflectionMethodConstructorThrowTypeExtension' => [['0296']],
		'PHPStan\Type\Php\PowFunctionReturnTypeExtension' => [['0297']],
		'PHPStan\Type\Php\StrIncrementDecrementFunctionReturnTypeExtension' => [['0298']],
		'PHPStan\Type\Php\StrWordCountFunctionDynamicReturnTypeExtension' => [['0299']],
		'PHPStan\Type\Php\FilterVarThrowTypeExtension' => [['0300']],
		'PHPStan\Type\Php\ReflectionPropertyConstructorThrowTypeExtension' => [['0301']],
		'PHPStan\Type\Php\ArrayCombineFunctionReturnTypeExtension' => [['0302']],
		'PHPStan\Type\Php\GetParentClassDynamicFunctionReturnTypeExtension' => [['0303']],
		'PHPStan\Type\Php\DateIntervalDynamicReturnTypeExtension' => [['0304']],
		'PHPStan\Type\Php\SimpleXMLElementAsXMLMethodReturnTypeExtension' => [['0305']],
		'PHPStan\Type\Php\FilterVarArrayDynamicReturnTypeExtension' => [['0306']],
		'PHPStan\Type\Php\IsIterableFunctionTypeSpecifyingExtension' => [['0307']],
		'PHPStan\Type\Php\MbSubstituteCharacterDynamicReturnTypeExtension' => [['0308']],
		'PHPStan\Type\Php\OpenSslCipherMethodsProvider' => [['0309']],
		'PHPStan\Type\Php\MicrotimeFunctionReturnTypeExtension' => [['0310']],
		'PHPStan\Type\Php\ClassImplementsFunctionReturnTypeExtension' => [['0311']],
		'PHPStan\Type\Php\ArrayFilterFunctionReturnTypeExtension' => [['0312']],
		'PHPStan\Type\Php\Base64DecodeDynamicFunctionReturnTypeExtension' => [['0313']],
		'PHPStan\Type\Php\DateIntervalFormatReturnTypeHelper' => [['0314']],
		'PHPStan\Type\Php\ClosureGetCurrentDynamicReturnTypeExtension' => [['0315']],
		'PHPStan\Type\Php\ArrayCountValuesDynamicReturnTypeExtension' => [['0316']],
		'PHPStan\Reflection\PropertiesClassReflectionExtension' => [['0317', '0855', '0856', '0858']],
		'PHPStan\Type\Php\SimpleXMLElementClassPropertyReflectionExtension' => [['0317']],
		'PHPStan\Type\Php\PDOConnectReturnTypeExtension' => [['0318']],
		'PHPStan\Type\Php\MbStrlenFunctionReturnTypeExtension' => [['0319']],
		'PHPStan\Type\Php\GetDebugTypeFunctionReturnTypeExtension' => [['0320']],
		'PHPStan\Type\DynamicMethodThrowTypeExtension' => [['0321', '0345', '0368', '0426']],
		'PHPStan\Type\Php\DateTimeSubMethodThrowTypeExtension' => [['0321']],
		'PHPStan\Type\Php\ConstantFunctionReturnTypeExtension' => [['0322']],
		'PHPStan\Type\Php\PregFilterFunctionReturnTypeExtension' => [['0323']],
		'PHPStan\Type\Php\VersionCompareFunctionDynamicReturnTypeExtension' => [['0324']],
		'PHPStan\Type\Php\DateTimeZoneConstructorThrowTypeExtension' => [['0325']],
		'PHPStan\Type\Php\ArrayShiftFunctionReturnTypeExtension' => [['0326']],
		'PHPStan\Type\Php\DatePeriodConstructorReturnTypeExtension' => [['0327']],
		'PHPStan\Type\Php\ReflectionFunctionConstructorThrowTypeExtension' => [['0328']],
		'PHPStan\Type\Php\ArraySliceFunctionReturnTypeExtension' => [['0329']],
		'PHPStan\Type\Php\FilterInputDynamicReturnTypeExtension' => [['0330']],
		'PHPStan\Type\Php\CountFunctionTypeSpecifyingExtension' => [['0331']],
		'PHPStan\Type\Php\StrlenFunctionReturnTypeExtension' => [['0332']],
		'PHPStan\Type\Php\IteratorToArrayFunctionReturnTypeExtension' => [['0333']],
		'PHPStan\Type\Php\AbsFunctionDynamicReturnTypeExtension' => [['0334']],
		'PHPStan\Type\Php\IdateFunctionReturnTypeExtension' => [['0335']],
		'PHPStan\Type\Php\ArrayFilterFunctionReturnTypeHelper' => [['0336']],
		'PHPStan\Type\OperatorTypeSpecifyingExtension' => [['0337', '0379']],
		'PHPStan\Type\Php\GmpOperatorTypeSpecifyingExtension' => [['0337']],
		'PHPStan\Type\Php\ThrowableReturnTypeExtension' => [['0338']],
		'PHPStan\Type\Php\DateIntervalCreateFromDateStringThrowTypeExtension' => [['0339']],
		'PHPStan\Type\Php\RegexArrayShapeMatcher' => [['0340']],
		'PHPStan\Type\Php\ClassExistsFunctionTypeSpecifyingExtension' => [['0341']],
		'PHPStan\Type\Php\ImplodeFunctionReturnTypeExtension' => [['0342']],
		'PHPStan\Type\Php\HighlightStringDynamicReturnTypeExtension' => [['0343']],
		'PHPStan\Type\Php\ArrayRandFunctionReturnTypeExtension' => [['0344']],
		'PHPStan\Type\Php\DsMapDynamicMethodThrowTypeExtension' => [['0345']],
		'PHPStan\Type\Php\RangeFunctionReturnTypeExtension' => [['0346']],
		'PHPStan\Type\Php\GettimeofdayDynamicFunctionReturnTypeExtension' => [['0347']],
		'PHPStan\Type\Php\ArrayCombineHelper' => [['0348']],
		'PHPStan\Type\Php\ArraySearchFunctionDynamicReturnTypeExtension' => [['0349']],
		'PHPStan\Type\Php\DateFunctionReturnTypeHelper' => [['0350']],
		'PHPStan\Type\Php\DsMapDynamicReturnTypeExtension' => [['0351']],
		'PHPStan\Type\Php\LtrimFunctionReturnTypeExtension' => [['0352']],
		'PHPStan\Type\Php\RoundFunctionReturnTypeExtension' => [['0353']],
		'PHPStan\Type\Php\DateTimeConstructorThrowTypeExtension' => [['0354']],
		'PHPStan\Type\Php\JsonThrowOnErrorDynamicReturnTypeExtension' => [['0355']],
		'PHPStan\Type\Php\StrvalFamilyFunctionReturnTypeExtension' => [['0356']],
		'PHPStan\Type\Php\ArrayReduceFunctionReturnTypeExtension' => [['0357']],
		'PHPStan\Type\Php\RandomIntFunctionReturnTypeExtension' => [['0358']],
		'PHPStan\Type\Php\IntdivThrowTypeExtension' => [['0359']],
		'PHPStan\Type\Php\CountCharsFunctionDynamicReturnTypeExtension' => [['0360']],
		'PHPStan\Type\Php\ArrayFillFunctionReturnTypeExtension' => [['0361']],
		'PHPStan\Type\MethodTypeSpecifyingExtension' => [['0362', '0909']],
		'PHPStan\Type\Php\ReflectionClassIsSubclassOfTypeSpecifyingExtension' => [['0362']],
		'PHPStan\Type\Php\MinMaxFunctionReturnTypeExtension' => [['0363']],
		'PHPStan\Type\Php\SetTypeFunctionTypeSpecifyingExtension' => [['0364']],
		'PHPStan\Type\Php\SubstrDynamicReturnTypeExtension' => [['0365']],
		'PHPStan\Type\Php\ArrayColumnFunctionReturnTypeExtension' => [['0366']],
		'PHPStan\Type\Php\ParseUrlFunctionDynamicReturnTypeExtension' => [['0367']],
		'PHPStan\Type\Php\DomDocumentCreateElementDynamicThrowTypeExtension' => [['0368']],
		'PHPStan\Type\Php\HrtimeFunctionReturnTypeExtension' => [['0369']],
		'PHPStan\Type\Php\ConstantHelper' => [['0370']],
		'PHPStan\Type\Php\ExplodeFunctionDynamicReturnTypeExtension' => [['0371']],
		'PHPStan\Type\Php\DateIntervalFormatFunctionReturnTypeExtension' => [['0372']],
		'PHPStan\Type\Php\MethodExistsTypeSpecifyingExtension' => [['0373']],
		'PHPStan\Type\Php\ArrayChangeKeyCaseFunctionReturnTypeExtension' => [['0374']],
		'PHPStan\Type\Php\StrTokFunctionReturnTypeExtension' => [['0375']],
		'PHPStan\Type\Php\AssertThrowTypeExtension' => [['0376']],
		'PHPStan\Type\Php\ArrayPadDynamicReturnTypeExtension' => [['0377']],
		'PHPStan\Type\Php\FunctionExistsFunctionTypeSpecifyingExtension' => [['0378']],
		'PHPStan\Type\Php\BcMathNumberOperatorTypeSpecifyingExtension' => [['0379']],
		'PHPStan\Type\Php\ArrayCurrentDynamicReturnTypeExtension' => [['0380']],
		'PHPStan\Type\Php\ArrayFillKeysFunctionReturnTypeExtension' => [['0381']],
		'PHPStan\Type\Php\IsAFunctionTypeSpecifyingExtension' => [['0382']],
		'PHPStan\Type\FunctionParameterOutTypeExtension' => [['0383', '0407', '0428']],
		'PHPStan\Type\Php\PregMatchParameterOutTypeExtension' => [['0383']],
		'PHPStan\Type\Php\ArrayPointerFunctionsDynamicReturnTypeExtension' => [['0384']],
		'PHPStan\Type\Php\StrCaseFunctionsReturnTypeExtension' => [['0385']],
		'PHPStan\Type\Php\SscanfFunctionDynamicReturnTypeExtension' => [['0386']],
		'PHPStan\Type\Php\IsAFunctionTypeSpecifyingHelper' => [['0387']],
		'PHPStan\Type\Php\ArrayChunkFunctionReturnTypeExtension' => [['0388']],
		'PHPStan\Type\Php\ArrayKeyExistsFunctionTypeSpecifyingExtension' => [['0389']],
		'PHPStan\Type\Php\NonEmptyStringFunctionsReturnTypeExtension' => [['0390']],
		'PHPStan\Type\Php\SimpleXMLElementXpathMethodReturnTypeExtension' => [['0391']],
		'PHPStan\Type\Php\ClosureBindDynamicReturnTypeExtension' => [['0392']],
		'PHPStan\Type\Php\InArrayFunctionTypeSpecifyingExtension' => [['0393']],
		'PHPStan\Type\Php\CompactFunctionReturnTypeExtension' => [['0394']],
		'PHPStan\Type\Php\ArrayFlipFunctionReturnTypeExtension' => [['0395']],
		'PHPStan\Type\Php\DioStatDynamicFunctionReturnTypeExtension' => [['0396']],
		'PHPStan\Type\Php\CurlGetinfoFunctionDynamicReturnTypeExtension' => [['0397']],
		'PHPStan\Type\Php\CountFunctionReturnTypeExtension' => [['0398']],
		'PHPStan\Type\Php\OpensslCipherFunctionsReturnTypeExtension' => [['0399']],
		'PHPStan\Type\Php\ArraySearchFunctionTypeSpecifyingExtension' => [['0400']],
		'PHPStan\Type\Php\StatDynamicReturnTypeExtension' => [['0401']],
		'PHPStan\Type\Php\ArraySpliceFunctionReturnTypeExtension' => [['0402']],
		'PHPStan\Type\Php\IsArrayFunctionTypeSpecifyingExtension' => [['0403']],
		'PHPStan\Type\Php\StrrevFunctionReturnTypeExtension' => [['0404']],
		'PHPStan\Type\Php\PdoStatementFetchAllReturnTypeExtension' => [['0405']],
		'PHPStan\Type\Php\ArrayIntersectKeyFunctionReturnTypeExtension' => [['0406']],
		'PHPStan\Type\Php\OpenSslEncryptParameterOutTypeExtension' => [['0407']],
		'PHPStan\Type\Php\JsonThrowTypeExtension' => [['0408']],
		'PHPStan\Type\Php\NumberFormatFunctionDynamicReturnTypeExtension' => [['0409']],
		'PHPStan\Type\Php\DefinedConstantTypeSpecifyingExtension' => [['0410']],
		'PHPStan\Type\Php\GetCalledClassDynamicReturnTypeExtension' => [['0411']],
		'PHPStan\Type\Php\HashFunctionsReturnTypeExtension' => [['0412']],
		'PHPStan\Type\Php\DateTimeDynamicReturnTypeExtension' => [['0413']],
		'PHPStan\Type\Php\AssertFunctionTypeSpecifyingExtension' => [['0414']],
		'PHPStan\Type\Php\SprintfFunctionDynamicReturnTypeExtension' => [['0415']],
		'PHPStan\Type\Php\MbFunctionsReturnTypeExtension' => [['0416']],
		'PHPStan\Type\Php\DomDocumentCreateElementDynamicReturnTypeExtension' => [['0417']],
		'PHPStan\Type\Php\ReflectionClassConstructorThrowTypeExtension' => [['0418']],
		'PHPStan\Type\Php\TrimFunctionDynamicReturnTypeExtension' => [['0419']],
		'PHPStan\Type\Php\ArrayReverseFunctionReturnTypeExtension' => [['0420']],
		'PHPStan\Type\Php\ArrayFindFunctionReturnTypeExtension' => [['0421']],
		'PHPStan\Type\Php\TriggerErrorDynamicReturnTypeExtension' => [['0422']],
		'PHPStan\Type\Php\MbConvertEncodingFunctionReturnTypeExtension' => [['0423']],
		'PHPStan\Type\Php\StrRepeatFunctionReturnTypeExtension' => [['0424']],
		'PHPStan\Type\Php\FilterFunctionReturnTypeHelper' => [['0425']],
		'PHPStan\Type\Php\DateTimeModifyMethodThrowTypeExtension' => [['0426']],
		'PHPStan\Type\Php\PregSplitDynamicReturnTypeExtension' => [['0427']],
		'PHPStan\Type\Php\ParseStrParameterOutTypeExtension' => [['0428']],
		'PHPStan\Type\UnaryOperatorTypeSpecifyingExtension' => [['0429']],
		'PHPStan\Type\Php\GmpUnaryOperatorTypeSpecifyingExtension' => [['0429']],
		'PHPStan\Type\Php\ArrayValuesFunctionDynamicReturnTypeExtension' => [['0430']],
		'PHPStan\Type\Php\ArgumentBasedFunctionReturnTypeExtension' => [['0431']],
		'PHPStan\Type\Php\DateFormatFunctionReturnTypeExtension' => [['0432']],
		'PHPStan\Type\Php\IdateFunctionReturnTypeHelper' => [['0433']],
		'PHPStan\Type\Php\ArrayNextDynamicReturnTypeExtension' => [['0434']],
		'PHPStan\Type\Php\ClosureBindToDynamicReturnTypeExtension' => [['0435']],
		'PHPStan\Type\Php\DefineConstantTypeSpecifyingExtension' => [['0436']],
		'PHPStan\Type\Php\GetClassDynamicReturnTypeExtension' => [['0437']],
		'PHPStan\Type\Php\DateFormatMethodReturnTypeExtension' => [['0438']],
		'PHPStan\Type\Php\DateIntervalConstructorThrowTypeExtension' => [['0439']],
		'PHPStan\Type\Php\ArrayCombineFunctionThrowTypeExtension' => [['0440']],
		'PHPStan\Type\Php\IniGetReturnTypeExtension' => [['0441']],
		'PHPStan\Type\Php\PregMatchTypeSpecifyingExtension' => [['0442']],
		'PHPStan\Type\Php\ArrayFirstLastDynamicReturnTypeExtension' => [['0443']],
		'PHPStan\Type\Php\XMLReaderOpenReturnTypeExtension' => [['0444']],
		'PHPStan\Type\Php\ReplaceFunctionsDynamicReturnTypeExtension' => [['0445']],
		'PHPStan\Type\Php\ArrayReplaceFunctionReturnTypeExtension' => [['0446']],
		'PHPStan\Type\Php\StrContainingTypeSpecifyingExtension' => [['0447']],
		'PHPStan\Type\Php\IsSubclassOfFunctionTypeSpecifyingExtension' => [['0448']],
		'PHPStan\Type\FileTypeMapper' => [0 => ['0449'], 2 => [1 => 'stubFileTypeMapper']],
		'PHPStan\Php\PhpVersionFactory' => [['0450']],
		'PHPStan\Php\PhpVersionFactoryFactory' => [['0451']],
		'PHPStan\Php\ComposerPhpVersionFactory' => [['0452']],
		'PHPStan\Php\PhpVersion' => [['0453']],
		'PHPStan\File\ParentDirectoryRelativePathHelper' => [2 => ['parentDirectoryRelativePathHelper']],
		'PHPStan\File\SimpleRelativePathHelper' => [2 => ['simpleRelativePathHelper']],
		'PHPStan\Reflection\ReflectionProvider' => [
			0 => ['reflectionProvider'],
			2 => [0 => 'betterReflectionProvider', 2 => 'stubBetterReflectionProvider'],
		],
		'PHPStan\Reflection\BetterReflection\BetterReflectionProvider' => [
			0 => ['reflectionProvider'],
			2 => [0 => 'betterReflectionProvider', 2 => 'stubBetterReflectionProvider'],
		],
		'PHPStan\Analyser\ResultCache\ResultCacheManagerFactory' => [['0454']],
		'PHPStan\Analyser\InternalScopeFactoryFactory' => [['0455']],
		'PHPStan\File\FileExcluderRawFactory' => [['0456']],
		'PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedSingleFileSourceLocatorFactory' => [['0457']],
		'PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedPsrAutoloaderLocatorFactory' => [['0458']],
		'PHPStan\Reflection\ClassReflectionFactory' => [['0459']],
		'PHPStan\Reflection\Php\PhpMethodReflectionFactory' => [['0460']],
		'PHPStan\Reflection\FunctionReflectionFactory' => [['0461']],
		'PHPStan\Rules\Variables\UnsetRule' => [['0462']],
		'PHPStan\Rules\Variables\ThisInStaticStatementRule' => [['0463']],
		'PHPStan\Rules\Variables\InvalidVariableAssignRule' => [['0464']],
		'PHPStan\Rules\Variables\EmptyRule' => [['0465']],
		'PHPStan\Rules\Variables\ParameterOutAssignedTypeRule' => [['0466']],
		'PHPStan\Rules\Variables\NullCoalesceRule' => [['0467']],
		'PHPStan\Rules\Variables\IssetRule' => [['0468']],
		'PHPStan\Rules\Variables\ThisInGlobalStatementRule' => [['0469']],
		'PHPStan\Rules\Variables\ParameterOutExecutionEndTypeRule' => [['0470']],
		'PHPStan\Rules\Variables\DefinedVariableRule' => [['0471']],
		'PHPStan\Rules\Variables\CompactVariablesRule' => [['0472']],
		'PHPStan\Rules\Variables\VariableCloningRule' => [['0473']],
		'PHPStan\Rules\EnumCases\EnumCaseAttributesRule' => [['0474']],
		'PHPStan\Rules\EnumCases\EnumCaseOutsideEnumRule' => [['0475']],
		'PHPStan\Rules\Keywords\ContinueBreakInLoopRule' => [['0476']],
		'PHPStan\Rules\Keywords\GotoUndefinedLabelRule' => [['0477']],
		'PHPStan\Rules\Keywords\DeclareStrictTypesRule' => [['0478']],
		'PHPStan\Rules\Keywords\RequireFileExistsRule' => [['0479']],
		'PHPStan\Rules\Missing\MissingReturnRule' => [['0480']],
		'PHPStan\Rules\Pure\PureMethodRule' => [['0481']],
		'PHPStan\Rules\Pure\PureFunctionRule' => [['0482']],
		'PHPStan\Rules\Names\UsedNamesRule' => [['0483']],
		'PHPStan\Rules\Generics\MethodTagTemplateTypeTraitRule' => [0 => ['0484'], 2 => [1 => '0779']],
		'PHPStan\Rules\Generics\EnumTemplateTypeRule' => [0 => ['0485'], 2 => [1 => '0780']],
		'PHPStan\Rules\Generics\FunctionTemplateTypeRule' => [0 => ['0486'], 2 => [1 => '0781']],
		'PHPStan\Rules\Generics\MethodTagTemplateTypeRule' => [0 => ['0487'], 2 => [1 => '0782']],
		'PHPStan\Rules\Generics\MethodSignatureVarianceRule' => [0 => ['0488'], 2 => [1 => '0783']],
		'PHPStan\Rules\Generics\UsedTraitsRule' => [0 => ['0489'], 2 => [1 => '0784']],
		'PHPStan\Rules\Generics\TraitTemplateTypeRule' => [0 => ['0490'], 2 => [1 => '0785']],
		'PHPStan\Rules\Generics\MethodTemplateTypeRule' => [0 => ['0491'], 2 => [1 => '0786']],
		'PHPStan\Rules\Generics\PropertyVarianceRule' => [0 => ['0492'], 2 => [1 => '0787']],
		'PHPStan\Rules\Generics\EnumAncestorsRule' => [0 => ['0493'], 2 => [1 => '0788']],
		'PHPStan\Rules\Generics\ClassTemplateTypeRule' => [0 => ['0494'], 2 => [1 => '0789']],
		'PHPStan\Rules\Generics\FunctionSignatureVarianceRule' => [0 => ['0495'], 2 => [1 => '0790']],
		'PHPStan\Rules\Generics\ClassAncestorsRule' => [0 => ['0496'], 2 => [1 => '0791']],
		'PHPStan\Rules\Generics\InterfaceAncestorsRule' => [0 => ['0497'], 2 => [1 => '0792']],
		'PHPStan\Rules\Generics\InterfaceTemplateTypeRule' => [0 => ['0498'], 2 => [1 => '0793']],
		'PHPStan\Rules\DateTimeInstantiationRule' => [['0499']],
		'PHPStan\Rules\Namespaces\ExistingNamesInUseRule' => [['0500']],
		'PHPStan\Rules\Namespaces\ExistingNamesInGroupUseRule' => [['0501']],
		'PHPStan\Rules\Regexp\RegularExpressionPatternRule' => [['0502']],
		'PHPStan\Rules\Regexp\RegularExpressionQuotingRule' => [['0503']],
		'PHPStan\Rules\Whitespace\FileWhitespaceRule' => [['0504']],
		'PHPStan\Rules\Classes\DuplicateTraitDeclarationRule' => [['0505']],
		'PHPStan\Rules\Classes\AccessPrivateConstantThroughStaticRule' => [['0506']],
		'PHPStan\Rules\Classes\AllowedSubTypesRule' => [['0507']],
		'PHPStan\Rules\Classes\MethodTagTraitUseRule' => [0 => ['0508'], 2 => [1 => '0794']],
		'PHPStan\Rules\Classes\UnusedConstructorParametersRule' => [['0509']],
		'PHPStan\Rules\Classes\ClassAttributesRule' => [['0510']],
		'PHPStan\Rules\Classes\InstantiationRule' => [['0511']],
		'PHPStan\Rules\Classes\PropertyTagTraitRule' => [0 => ['0512'], 2 => [1 => '0795']],
		'PHPStan\Rules\Classes\MixinTraitUseRule' => [0 => ['0513'], 2 => [1 => '0796']],
		'PHPStan\Rules\Classes\PropertyTagRule' => [0 => ['0514'], 2 => [1 => '0797']],
		'PHPStan\Rules\Classes\ExistingClassesInInterfaceExtendsRule' => [0 => ['0515'], 2 => [1 => '0798']],
		'PHPStan\Rules\Classes\ReadOnlyClassRule' => [['0516']],
		'PHPStan\Rules\Classes\ClassConstantRule' => [['0517']],
		'PHPStan\Rules\Classes\ExistingClassInInstanceOfRule' => [['0518']],
		'PHPStan\Rules\Classes\MixinTraitRule' => [0 => ['0519'], 2 => [1 => '0800']],
		'PHPStan\Rules\Classes\RequireExtendsRule' => [['0520']],
		'PHPStan\Rules\Classes\InstantiationCallableRule' => [['0521']],
		'PHPStan\Rules\Classes\LocalTypeAliasesRule' => [0 => ['0522'], 2 => [1 => '0801']],
		'PHPStan\Rules\Classes\MixinRule' => [0 => ['0523'], 2 => [1 => '0802']],
		'PHPStan\Rules\Classes\InvalidPromotedPropertiesRule' => [['0524']],
		'PHPStan\Rules\Classes\ClassConstantAttributesRule' => [['0525']],
		'PHPStan\Rules\Classes\LocalTypeTraitAliasesRule' => [0 => ['0526'], 2 => [1 => '0803']],
		'PHPStan\Rules\Classes\MethodTagRule' => [0 => ['0527'], 2 => [1 => '0804']],
		'PHPStan\Rules\Classes\MethodTagTraitRule' => [0 => ['0528'], 2 => [1 => '0805']],
		'PHPStan\Rules\Classes\ImpossibleInstanceOfRule' => [['0529']],
		'PHPStan\Rules\Classes\RequireImplementsRule' => [['0530']],
		'PHPStan\Rules\Classes\ExistingClassesInClassImplementsRule' => [0 => ['0531'], 2 => [1 => '0806']],
		'PHPStan\Rules\Classes\LocalTypeTraitUseAliasesRule' => [0 => ['0532'], 2 => [1 => '0807']],
		'PHPStan\Rules\Classes\ExistingClassInClassExtendsRule' => [0 => ['0533'], 2 => [1 => '0808']],
		'PHPStan\Rules\Classes\TraitAttributeClassRule' => [['0534']],
		'PHPStan\Rules\Classes\ExistingClassesInEnumImplementsRule' => [['0535']],
		'PHPStan\Rules\Classes\ExistingClassInTraitUseRule' => [0 => ['0536'], 2 => [1 => '0809']],
		'PHPStan\Rules\Classes\EnumSanityRule' => [['0537']],
		'PHPStan\Rules\Classes\NonClassAttributeClassRule' => [['0538']],
		'PHPStan\Rules\Classes\NewStaticRule' => [['0539']],
		'PHPStan\Rules\Classes\DuplicateDeclarationRule' => [0 => ['0540'], 2 => [1 => '0810']],
		'PHPStan\Rules\Classes\PropertyTagTraitUseRule' => [0 => ['0541'], 2 => [1 => '0811']],
		'PHPStan\Rules\Constants\ConstantAttributesRule' => [['0542']],
		'PHPStan\Rules\Constants\OverridingConstantRule' => [['0543']],
		'PHPStan\Rules\Constants\ConstantRule' => [['0544']],
		'PHPStan\Rules\Constants\NativeTypedClassConstantRule' => [['0545']],
		'PHPStan\Rules\Constants\MagicConstantContextRule' => [['0546']],
		'PHPStan\Rules\Constants\ClassAsClassConstantRule' => [['0547']],
		'PHPStan\Rules\Constants\FinalPrivateConstantRule' => [['0548']],
		'PHPStan\Rules\Constants\ValueAssignedToClassConstantRule' => [['0549']],
		'PHPStan\Rules\Constants\FinalConstantRule' => [['0550']],
		'PHPStan\Rules\Constants\DynamicClassConstantFetchRule' => [['0551']],
		'PHPStan\Rules\Constants\MissingClassConstantTypehintRule' => [['0552']],
		'PHPStan\Rules\Functions\ExistingClassesInArrowFunctionTypehintsRule' => [['0553']],
		'PHPStan\Rules\Functions\CallToFunctionParametersRule' => [['0554']],
		'PHPStan\Rules\Functions\ParamAttributesRule' => [['0555']],
		'PHPStan\Rules\Functions\ExistingClassesInTypehintsRule' => [0 => ['0556'], 2 => [1 => '0812']],
		'PHPStan\Rules\Functions\ArrayValuesRule' => [['0557']],
		'PHPStan\Rules\Functions\PrintfArrayParametersRule' => [['0558']],
		'PHPStan\Rules\Functions\UselessFunctionReturnValueRule' => [['0559']],
		'PHPStan\Rules\Functions\CallToFunctionStatementWithNoDiscardRule' => [['0560']],
		'PHPStan\Rules\Functions\FilterVarRule' => [['0561']],
		'PHPStan\Rules\Functions\ReturnNullsafeByRefRule' => [['0562']],
		'PHPStan\Rules\Functions\ExistingClassesInClosureTypehintsRule' => [['0563']],
		'PHPStan\Rules\Functions\IncompatibleDefaultParameterTypeRule' => [['0564']],
		'PHPStan\Rules\Functions\CallToNonExistentFunctionRule' => [['0565']],
		'PHPStan\Rules\Functions\UnusedClosureUsesRule' => [['0566']],
		'PHPStan\Rules\Functions\PrintfParametersRule' => [['0567']],
		'PHPStan\Rules\Functions\MissingFunctionReturnTypehintRule' => [0 => ['0568'], 2 => [1 => '0813']],
		'PHPStan\Rules\Functions\VariadicParametersDeclarationRule' => [['0569']],
		'PHPStan\Rules\Functions\CallUserFuncRule' => [['0570']],
		'PHPStan\Rules\Functions\InnerFunctionRule' => [['0571']],
		'PHPStan\Rules\Functions\FunctionCallableRule' => [['0572']],
		'PHPStan\Rules\Functions\DefineParametersRule' => [['0573']],
		'PHPStan\Rules\Functions\ArrowFunctionReturnNullsafeByRefRule' => [['0574']],
		'PHPStan\Rules\Functions\ImplodeParameterCastableToStringRule' => [['0575']],
		'PHPStan\Rules\Functions\SortParameterCastableToStringRule' => [['0576']],
		'PHPStan\Rules\Functions\CallToFunctionStatementWithoutSideEffectsRule' => [['0577']],
		'PHPStan\Rules\Functions\InvalidParameterNameRule' => [['0578']],
		'PHPStan\Rules\Functions\ArrowFunctionReturnTypeRule' => [['0579']],
		'PHPStan\Rules\Functions\InvalidLexicalVariablesInClosureUseRule' => [['0580']],
		'PHPStan\Rules\Functions\ParameterCastableToStringRule' => [['0581']],
		'PHPStan\Rules\Functions\FunctionAttributesRule' => [['0582']],
		'PHPStan\Rules\Functions\RandomIntParametersRule' => [['0583']],
		'PHPStan\Rules\Functions\ReturnTypeRule' => [['0584']],
		'PHPStan\Rules\Functions\CallCallablesRule' => [['0585']],
		'PHPStan\Rules\Functions\ClosureReturnTypeRule' => [['0586']],
		'PHPStan\Rules\Functions\IncompatibleArrowFunctionDefaultParameterTypeRule' => [['0587']],
		'PHPStan\Rules\Functions\IncompatibleClosureDefaultParameterTypeRule' => [['0588']],
		'PHPStan\Rules\Functions\ArrowFunctionAttributesRule' => [['0589']],
		'PHPStan\Rules\Functions\MissingFunctionParameterTypehintRule' => [0 => ['0590'], 2 => [1 => '0815']],
		'PHPStan\Rules\Functions\ArrayFilterRule' => [['0591']],
		'PHPStan\Rules\Functions\RedefinedParametersRule' => [['0592']],
		'PHPStan\Rules\Functions\ClosureAttributesRule' => [['0593']],
		'PHPStan\Rules\Operators\InvalidIncDecOperationRule' => [['0594']],
		'PHPStan\Rules\Operators\InvalidBinaryOperationRule' => [['0595']],
		'PHPStan\Rules\Operators\InvalidAssignVarRule' => [['0596']],
		'PHPStan\Rules\Operators\BacktickRule' => [['0597']],
		'PHPStan\Rules\Operators\PipeOperatorRule' => [['0598']],
		'PHPStan\Rules\Operators\InvalidUnaryOperationRule' => [['0599']],
		'PHPStan\Rules\Operators\InvalidComparisonOperationRule' => [['0600']],
		'PHPStan\Rules\Generators\YieldTypeRule' => [['0601']],
		'PHPStan\Rules\Generators\YieldInGeneratorRule' => [['0602']],
		'PHPStan\Rules\Generators\YieldFromTypeRule' => [['0603']],
		'PHPStan\Rules\Properties\ReadOnlyByPhpDocPropertyAssignRefRule' => [['0604']],
		'PHPStan\Rules\Properties\GetNonVirtualPropertyHookReadRule' => [['0605']],
		'PHPStan\Rules\Properties\ReadOnlyByPhpDocPropertyRule' => [['0606']],
		'PHPStan\Rules\Properties\ReadingWriteOnlyPropertiesRule' => [['0607']],
		'PHPStan\Rules\Properties\PropertyHookAttributesRule' => [['0608']],
		'PHPStan\Rules\Properties\InvalidCallablePropertyTypeRule' => [['0609']],
		'PHPStan\Rules\Properties\ExistingClassesInPropertyHookTypehintsRule' => [['0610']],
		'PHPStan\Rules\Properties\PropertyAttributesRule' => [['0611']],
		'PHPStan\Rules\Properties\AccessPropertiesRule' => [['0612']],
		'PHPStan\Rules\Properties\NullsafePropertyFetchRule' => [['0613']],
		'PHPStan\Rules\Properties\PropertyAssignRefRule' => [['0614']],
		'PHPStan\Rules\Properties\SetPropertyHookParameterRule' => [['0615']],
		'PHPStan\Rules\Properties\ReadOnlyPropertyAssignRefRule' => [['0616']],
		'PHPStan\Rules\Properties\MissingPropertyTypehintRule' => [0 => ['0617'], 2 => [1 => '0816']],
		'PHPStan\Rules\Properties\DefaultValueTypesAssignedToPropertiesRule' => [['0618']],
		'PHPStan\Rules\Properties\ExistingClassesInPropertiesRule' => [0 => ['0619'], 2 => [1 => '0817']],
		'PHPStan\Rules\Properties\MissingReadOnlyByPhpDocPropertyAssignRule' => [['0620']],
		'PHPStan\Rules\Properties\OverridingPropertyRule' => [['0621']],
		'PHPStan\Rules\Properties\TypesAssignedToPropertiesRule' => [['0622']],
		'PHPStan\Rules\Properties\AccessPrivatePropertyThroughStaticRule' => [['0623']],
		'PHPStan\Rules\Properties\PropertyInClassRule' => [['0624']],
		'PHPStan\Rules\Properties\AccessStaticPropertiesRule' => [['0625']],
		'PHPStan\Rules\Properties\MissingReadOnlyPropertyAssignRule' => [['0626']],
		'PHPStan\Rules\Properties\ReadOnlyByPhpDocPropertyAssignRule' => [['0627']],
		'PHPStan\Rules\Properties\AccessStaticPropertiesInAssignRule' => [['0628']],
		'PHPStan\Rules\Properties\WritingToReadOnlyPropertiesRule' => [['0629']],
		'PHPStan\Rules\Properties\ReadOnlyPropertyRule' => [['0630']],
		'PHPStan\Rules\Properties\AccessPropertiesInAssignRule' => [['0631']],
		'PHPStan\Rules\Properties\ReadOnlyPropertyAssignRule' => [['0632']],
		'PHPStan\Rules\Properties\PropertiesInInterfaceRule' => [['0633']],
		'PHPStan\Rules\Properties\SetNonVirtualPropertyHookAssignRule' => [['0634']],
		'PHPStan\Rules\Types\InvalidTypesInUnionRule' => [['0635']],
		'PHPStan\Rules\Traits\ConflictingTraitConstantsRule' => [['0636']],
		'PHPStan\Rules\Traits\TraitAttributesRule' => [['0637']],
		'PHPStan\Rules\Traits\ConstantsInTraitsRule' => [['0638']],
		'PHPStan\Rules\Traits\NotAnalysedTraitRule' => [['0639']],
		'PHPStan\Rules\TooWideTypehints\TooWideMethodReturnTypehintRule' => [['0640']],
		'PHPStan\Rules\TooWideTypehints\TooWideMethodParameterOutTypeRule' => [['0641']],
		'PHPStan\Rules\TooWideTypehints\TooWideFunctionReturnTypehintRule' => [['0642']],
		'PHPStan\Rules\TooWideTypehints\TooWideArrowFunctionReturnTypehintRule' => [['0643']],
		'PHPStan\Rules\TooWideTypehints\TooWideClosureReturnTypehintRule' => [['0644']],
		'PHPStan\Rules\TooWideTypehints\TooWideFunctionParameterOutTypeRule' => [['0645']],
		'PHPStan\Rules\TooWideTypehints\TooWidePropertyTypeRule' => [['0646']],
		'PHPStan\Rules\Methods\ExistingClassesInTypehintsRule' => [0 => ['0647'], 2 => [1 => '0818']],
		'PHPStan\Rules\Methods\MissingMethodReturnTypehintRule' => [0 => ['0648'], 2 => [1 => '0819']],
		'PHPStan\Rules\Methods\CallToConstructorStatementWithoutSideEffectsRule' => [['0649']],
		'PHPStan\Rules\Methods\ConsistentConstructorDeclarationRule' => [['0650']],
		'PHPStan\Rules\Methods\CallToMethodStatementWithNoDiscardRule' => [['0651']],
		'PHPStan\Rules\Methods\CallToStaticMethodStatementWithoutSideEffectsRule' => [['0652']],
		'PHPStan\Rules\Methods\IncompatibleDefaultParameterTypeRule' => [['0653']],
		'PHPStan\Rules\Methods\MissingMethodParameterTypehintRule' => [0 => ['0654'], 2 => [1 => '0820']],
		'PHPStan\Rules\Methods\MethodAttributesRule' => [['0655']],
		'PHPStan\Rules\Methods\CallToMethodStatementWithoutSideEffectsRule' => [['0656']],
		'PHPStan\Rules\Methods\NullsafeMethodCallRule' => [['0657']],
		'PHPStan\Rules\Methods\CallToStaticMethodStatementWithNoDiscardRule' => [['0658']],
		'PHPStan\Rules\Methods\CallPrivateMethodThroughStaticRule' => [['0659']],
		'PHPStan\Rules\Methods\AbstractMethodInNonAbstractClassRule' => [['0660']],
		'PHPStan\Rules\Methods\AbstractPrivateMethodRule' => [['0661']],
		'PHPStan\Rules\Methods\StaticMethodCallableRule' => [['0662']],
		'PHPStan\Rules\Methods\CallMethodsRule' => [['0663']],
		'PHPStan\Rules\Methods\ReturnTypeRule' => [['0664']],
		'PHPStan\Rules\Methods\MissingMethodSelfOutTypeRule' => [0 => ['0665'], 2 => [1 => '0821']],
		'PHPStan\Rules\Methods\FinalPrivateMethodRule' => [['0666']],
		'PHPStan\Rules\Methods\OverridingMethodRule' => [0 => ['0667'], 2 => [1 => '0822']],
		'PHPStan\Rules\Methods\ConstructorReturnTypeRule' => [['0668']],
		'PHPStan\Rules\Methods\MethodVisibilityInInterfaceRule' => [['0669']],
		'PHPStan\Rules\Methods\MissingMethodImplementationRule' => [['0670']],
		'PHPStan\Rules\Methods\ConsistentConstructorRule' => [['0671']],
		'PHPStan\Rules\Methods\MethodCallableRule' => [['0672']],
		'PHPStan\Rules\Methods\MissingMagicSerializationMethodsRule' => [['0673']],
		'PHPStan\Rules\Methods\CallStaticMethodsRule' => [['0674']],
		'PHPStan\Rules\DeadCode\CallToConstructorStatementWithoutImpurePointsRule' => [['0675']],
		'PHPStan\Rules\DeadCode\CallToStaticMethodStatementWithoutImpurePointsRule' => [['0676']],
		'PHPStan\Rules\DeadCode\UnreachableStatementRule' => [['0677']],
		'PHPStan\Rules\DeadCode\CallToMethodStatementWithoutImpurePointsRule' => [['0678']],
		'PHPStan\Rules\DeadCode\NoopRule' => [['0679']],
		'PHPStan\Rules\DeadCode\UnusedPrivatePropertyRule' => [['0680']],
		'PHPStan\Rules\DeadCode\UnusedPrivateConstantRule' => [['0681']],
		'PHPStan\Rules\DeadCode\CallToFunctionStatementWithoutImpurePointsRule' => [['0682']],
		'PHPStan\Rules\DeadCode\UnusedPrivateMethodRule' => [['0683']],
		'PHPStan\Rules\Cast\VoidCastRule' => [['0684']],
		'PHPStan\Rules\Cast\PrintRule' => [['0685']],
		'PHPStan\Rules\Cast\UnsetCastRule' => [['0686']],
		'PHPStan\Rules\Cast\InvalidPartOfEncapsedStringRule' => [['0687']],
		'PHPStan\Rules\Cast\DeprecatedCastRule' => [['0688']],
		'PHPStan\Rules\Cast\InvalidCastRule' => [['0689']],
		'PHPStan\Rules\Cast\EchoRule' => [['0690']],
		'PHPStan\Rules\PhpDoc\WrongVariableNameInVarTagRule' => [['0691']],
		'PHPStan\Rules\PhpDoc\InvalidPhpDocTagValueRule' => [0 => ['0692'], 2 => [1 => '0823']],
		'PHPStan\Rules\PhpDoc\RequireExtendsDefinitionTraitRule' => [0 => ['0693'], 2 => [1 => '0824']],
		'PHPStan\Rules\PhpDoc\IncompatiblePropertyPhpDocTypeRule' => [0 => ['0694'], 2 => [1 => '0825']],
		'PHPStan\Rules\PhpDoc\IncompatiblePhpDocTypeRule' => [0 => ['0695'], 2 => [1 => '0826']],
		'PHPStan\Rules\PhpDoc\SealedDefinitionClassRule' => [0 => ['0696'], 2 => [1 => '0827']],
		'PHPStan\Rules\PhpDoc\RequireImplementsDefinitionTraitRule' => [0 => ['0697'], 2 => [1 => '0828']],
		'PHPStan\Rules\PhpDoc\InvalidPHPStanDocTagRule' => [0 => ['0698'], 2 => [1 => '0829']],
		'PHPStan\Rules\PhpDoc\InvalidPhpDocVarTagTypeRule' => [['0699']],
		'PHPStan\Rules\PhpDoc\IncompatibleClassConstantPhpDocTypeRule' => [0 => ['0700'], 2 => [1 => '0830']],
		'PHPStan\Rules\PhpDoc\FunctionConditionalReturnTypeRule' => [0 => ['0701'], 2 => [1 => '0831']],
		'PHPStan\Rules\PhpDoc\IncompatibleSelfOutTypeRule' => [0 => ['0702'], 2 => [1 => '0832']],
		'PHPStan\Rules\PhpDoc\InvalidThrowsPhpDocValueRule' => [0 => ['0703'], 2 => [1 => '0833']],
		'PHPStan\Rules\PhpDoc\RequireExtendsDefinitionClassRule' => [0 => ['0704'], 2 => [1 => '0834']],
		'PHPStan\Rules\PhpDoc\MethodAssertRule' => [0 => ['0705'], 2 => [1 => '0835']],
		'PHPStan\Rules\PhpDoc\IncompatiblePropertyHookPhpDocTypeRule' => [['0706']],
		'PHPStan\Rules\PhpDoc\IncompatibleParamImmediatelyInvokedCallableRule' => [0 => ['0707'], 2 => [1 => '0836']],
		'PHPStan\Rules\PhpDoc\SealedDefinitionTraitRule' => [0 => ['0708'], 2 => [1 => '0837']],
		'PHPStan\Rules\PhpDoc\MethodConditionalReturnTypeRule' => [0 => ['0709'], 2 => [1 => '0838']],
		'PHPStan\Rules\PhpDoc\VarTagChangedExpressionTypeRule' => [['0710']],
		'PHPStan\Rules\PhpDoc\RequireImplementsDefinitionClassRule' => [0 => ['0711'], 2 => [1 => '0839']],
		'PHPStan\Rules\PhpDoc\FunctionAssertRule' => [0 => ['0712'], 2 => [1 => '0840']],
		'PHPStan\Rules\Api\RuntimeReflectionFunctionRule' => [['0713']],
		'PHPStan\Rules\Api\ApiInstantiationRule' => [['0714']],
		'PHPStan\Rules\Api\NodeConnectingVisitorAttributesRule' => [['0715']],
		'PHPStan\Rules\Api\ApiClassConstFetchRule' => [['0716']],
		'PHPStan\Rules\Api\OldPhpParser4ClassRule' => [['0717']],
		'PHPStan\Rules\Api\ApiClassExtendsRule' => [['0718']],
		'PHPStan\Rules\Api\GetTemplateTypeRule' => [['0719']],
		'PHPStan\Rules\Api\ApiInterfaceExtendsRule' => [['0720']],
		'PHPStan\Rules\Api\ApiTraitUseRule' => [['0721']],
		'PHPStan\Rules\Api\ApiMethodCallRule' => [['0722']],
		'PHPStan\Rules\Api\ApiStaticCallRule' => [['0723']],
		'PHPStan\Rules\Api\PhpStanNamespaceIn3rdPartyPackageRule' => [['0724']],
		'PHPStan\Rules\Api\ApiInstanceofRule' => [['0725']],
		'PHPStan\Rules\Api\RuntimeReflectionInstantiationRule' => [['0726']],
		'PHPStan\Rules\Api\ApiInstanceofTypeRule' => [['0727']],
		'PHPStan\Rules\Api\ApiClassImplementsRule' => [['0728']],
		'PHPStan\Rules\Exceptions\ThrowsVoidMethodWithExplicitThrowPointRule' => [['0729']],
		'PHPStan\Rules\Exceptions\ThrowsVoidFunctionWithExplicitThrowPointRule' => [['0730']],
		'PHPStan\Rules\Exceptions\ThrowExprTypeRule' => [['0731']],
		'PHPStan\Rules\Exceptions\CaughtExceptionExistenceRule' => [['0732']],
		'PHPStan\Rules\Exceptions\OverwrittenExitPointByFinallyRule' => [['0733']],
		'PHPStan\Rules\Exceptions\NoncapturingCatchRule' => [['0734']],
		'PHPStan\Rules\Exceptions\ThrowsVoidPropertyHookWithExplicitThrowPointRule' => [['0735']],
		'PHPStan\Rules\Exceptions\ThrowExpressionRule' => [['0736']],
		'PHPStan\Rules\Exceptions\CatchWithUnthrownExceptionRule' => [['0737']],
		'PHPStan\Rules\Arrays\OffsetAccessValueAssignmentRule' => [['0738']],
		'PHPStan\Rules\Arrays\ArrayDestructuringRule' => [['0739']],
		'PHPStan\Rules\Arrays\UnpackIterableInArrayRule' => [['0740']],
		'PHPStan\Rules\Arrays\OffsetAccessAssignOpRule' => [['0741']],
		'PHPStan\Rules\Arrays\InvalidKeyInArrayItemRule' => [['0742']],
		'PHPStan\Rules\Arrays\OffsetAccessAssignmentRule' => [['0743']],
		'PHPStan\Rules\Arrays\OffsetAccessWithoutDimForReadingRule' => [['0744']],
		'PHPStan\Rules\Arrays\InvalidKeyInArrayDimFetchRule' => [['0745']],
		'PHPStan\Rules\Arrays\DeadForeachRule' => [['0746']],
		'PHPStan\Rules\Arrays\ArrayUnpackingRule' => [['0747']],
		'PHPStan\Rules\Arrays\IterableInForeachRule' => [['0748']],
		'PHPStan\Rules\Arrays\DuplicateKeysInLiteralArraysRule' => [['0749']],
		'PHPStan\Rules\Arrays\NonexistentOffsetInArrayDimFetchRule' => [['0750']],
		'PHPStan\Rules\Ignore\IgnoreParseErrorRule' => [['0751']],
		'PHPStan\Rules\Comparison\ImpossibleCheckTypeFunctionCallRule' => [['0752']],
		'PHPStan\Rules\Comparison\StrictComparisonOfDifferentTypesRule' => [['0753']],
		'PHPStan\Rules\Comparison\ImpossibleCheckTypeStaticMethodCallRule' => [['0754']],
		'PHPStan\Rules\Comparison\BooleanAndConstantConditionRule' => [['0755']],
		'PHPStan\Rules\Comparison\WhileLoopAlwaysTrueConditionRule' => [['0756']],
		'PHPStan\Rules\Comparison\UsageOfVoidMatchExpressionRule' => [['0757']],
		'PHPStan\Rules\Comparison\DoWhileLoopConstantConditionRule' => [['0758']],
		'PHPStan\Rules\Comparison\BooleanOrConstantConditionRule' => [['0759']],
		'PHPStan\Rules\Comparison\IfConstantConditionRule' => [['0760']],
		'PHPStan\Rules\Comparison\ElseIfConstantConditionRule' => [['0761']],
		'PHPStan\Rules\Comparison\BooleanNotConstantConditionRule' => [['0762']],
		'PHPStan\Rules\Comparison\TernaryOperatorConstantConditionRule' => [['0763']],
		'PHPStan\Rules\Comparison\WhileLoopAlwaysFalseConditionRule' => [['0764']],
		'PHPStan\Rules\Comparison\MatchExpressionRule' => [['0765']],
		'PHPStan\Rules\Comparison\ConstantLooseComparisonRule' => [['0766']],
		'PHPStan\Rules\Comparison\LogicalXorConstantConditionRule' => [['0767']],
		'PHPStan\Rules\Comparison\NumberComparisonOperatorsConstantConditionRule' => [['0768']],
		'PHPStan\Rules\Comparison\ImpossibleCheckTypeMethodCallRule' => [['0769']],
		'PHPStan\Collectors\Collector' => [
			['0997', '0998', '0999', '01000', '01001'],
			['0770', '0771', '0772', '0773', '0774', '0775', '0776', '0777', '0778'],
		],
		'PHPStan\Rules\Traits\TraitUseCollector' => [['0770']],
		'PHPStan\Rules\Traits\TraitDeclarationCollector' => [['0771']],
		'PHPStan\Rules\DeadCode\PossiblyPureStaticCallCollector' => [['0772']],
		'PHPStan\Rules\DeadCode\PossiblyPureMethodCallCollector' => [['0773']],
		'PHPStan\Rules\DeadCode\PossiblyPureFuncCallCollector' => [['0774']],
		'PHPStan\Rules\DeadCode\MethodWithoutImpurePointsCollector' => [['0775']],
		'PHPStan\Rules\DeadCode\FunctionWithoutImpurePointsCollector' => [['0776']],
		'PHPStan\Rules\DeadCode\ConstructorWithoutImpurePointsCollector' => [['0777']],
		'PHPStan\Rules\DeadCode\PossiblyPureNewCollector' => [['0778']],
		'PHPStan\Rules\Classes\DuplicateClassDeclarationRule' => [2 => ['0799']],
		'PHPStan\Rules\Functions\DuplicateFunctionDeclarationRule' => [2 => ['0814']],
		'PhpParser\BuilderFactory' => [['0841']],
		'PhpParser\NodeVisitor\NameResolver' => [['0842']],
		'PHPStan\PhpDocParser\ParserConfig' => [['0843']],
		'PHPStan\PhpDocParser\Lexer\Lexer' => [['0844']],
		'PHPStan\PhpDocParser\Parser\TypeParser' => [['0845']],
		'PHPStan\PhpDocParser\Parser\ConstExprParser' => [['0846']],
		'PHPStan\PhpDocParser\Parser\PhpDocParser' => [['0847']],
		'PHPStan\PhpDocParser\Printer\Printer' => [['0848']],
		'PHPStan\BetterReflection\SourceLocator\SourceStubber\SourceStubber' => [1 => ['0849', '0850']],
		'PHPStan\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber' => [['0849']],
		'PHPStan\BetterReflection\SourceLocator\SourceStubber\ReflectionSourceStubber' => [['0850']],
		'PHPStan\BetterReflection\Reflector\DefaultReflector' => [
			2 => ['originalBetterReflectionReflector', 'nodeScopeResolverReflector', 'stubReflector'],
		],
		'PHPStan\Dependency\ExportedNodeVisitor' => [['0851']],
		'PHPStan\Reflection\BetterReflection\SourceLocator\CachingVisitor' => [['0852']],
		'PHPStan\Reflection\Php\PhpClassReflectionExtension' => [['0853']],
		'PHPStan\Reflection\MethodsClassReflectionExtension' => [['0854', '0857', '0859', '0860']],
		'PHPStan\Reflection\Annotations\AnnotationsMethodsClassReflectionExtension' => [['0854']],
		'PHPStan\Reflection\Annotations\AnnotationsPropertiesClassReflectionExtension' => [['0855']],
		'PHPStan\Reflection\Php\UniversalObjectCratesClassReflectionExtension' => [['0856']],
		'PHPStan\Reflection\Mixin\MixinMethodsClassReflectionExtension' => [['0857']],
		'PHPStan\Reflection\Mixin\MixinPropertiesClassReflectionExtension' => [['0858']],
		'PHPStan\Reflection\Php\Soap\SoapClientMethodsClassReflectionExtension' => [['0859']],
		'PHPStan\Reflection\RequireExtension\RequireExtendsMethodsClassReflectionExtension' => [['0860']],
		'PHPStan\Reflection\RequireExtension\RequireExtendsPropertiesClassReflectionExtension' => [['0861']],
		'PHPStan\Rules\Methods\MethodSignatureRule' => [['0862']],
		'PHPStan\Diagnose\PHPStanDiagnoseExtension' => [2 => ['phpstanDiagnoseExtension']],
		'PHPStan\Type\Php\ReflectionGetAttributesMethodReturnTypeExtension' => [['0863', '0864', '0865', '0866', '0867']],
		'PHPStan\Type\Php\DateTimeModifyReturnTypeExtension' => [['0868', '0869']],
		'PHPStan\Reflection\PHPStan\NativeReflectionEnumReturnDynamicReturnTypeExtension' => [['0870', '0871']],
		'PHPStan\Reflection\BetterReflection\Type\AdapterReflectionEnumCaseDynamicReturnTypeExtension' => [
			['0872', '0873'],
		],
		'PHPStan\Command\ErrorFormatter\JsonErrorFormatter' => [['errorFormatter.json', 'errorFormatter.prettyJson']],
		'PHPStan\File\FileExcluder' => [2 => ['fileExcluderAnalyse', 'fileExcluderScan']],
		'PHPStan\File\FileFinder' => [2 => ['fileFinderAnalyse', 'fileFinderScan']],
		'PHPStan\Cache\CacheStorage' => [2 => ['cacheStorage']],
		'PHPStan\Cache\FileCacheStorage' => [2 => ['cacheStorage']],
		'PHPStan\BetterReflection\SourceLocator\Type\SourceLocator' => [
			2 => ['betterReflectionSourceLocator', 'stubSourceLocator'],
		],
		'PHPStan\Parser\Parser' => [
			2 => [
				'php8Parser',
				'currentPhpVersionSimpleDirectParser',
				'currentPhpVersionSimpleParser',
				'currentPhpVersionRichParser',
				'pathRoutingParser',
				'defaultAnalysisParser',
				'freshStubParser',
				'stubParser',
			],
		],
		'PHPStan\Parser\SimpleParser' => [2 => ['php8Parser', 'currentPhpVersionSimpleDirectParser']],
		'PhpParser\Lexer' => [2 => ['php8Lexer', 'currentPhpVersionLexer']],
		'PhpParser\Lexer\Emulative' => [2 => ['php8Lexer']],
		'PhpParser\ParserAbstract' => [2 => ['php8PhpParser', 'currentPhpVersionPhpParser']],
		'PhpParser\Parser' => [2 => ['php8PhpParser', 'currentPhpVersionPhpParser', 'phpParserDecorator']],
		'PhpParser\Parser\Php8' => [2 => ['php8PhpParser', 'currentPhpVersionPhpParser']],
		'PHPStan\Parser\PhpParserFactory' => [2 => ['currentPhpVersionPhpParserFactory']],
		'PHPStan\Parser\CleaningParser' => [2 => ['currentPhpVersionSimpleParser']],
		'PHPStan\Parser\RichParser' => [2 => ['currentPhpVersionRichParser']],
		'PHPStan\Parser\PathRoutingParser' => [2 => ['pathRoutingParser']],
		'PHPStan\Parser\PhpParserDecorator' => [2 => ['phpParserDecorator']],
		'PHPStan\Parser\CachedParser' => [2 => ['defaultAnalysisParser', 'stubParser']],
		'PHPStan\Parser\StubParser' => [2 => ['freshStubParser']],
		'PHPStan\Reflection\BetterReflection\SourceLocator\SymbolFinderInFiles' => [['0874']],
		'PHPStan\Reflection\BetterReflection\SourceLocator\PhpFileCleaner' => [['0875']],
		'PHPStan\Rules\Exceptions\MissingCheckedExceptionInFunctionThrowsRule' => [['0876']],
		'PHPStan\Rules\Exceptions\MissingCheckedExceptionInMethodThrowsRule' => [['0877']],
		'PHPStan\Rules\Exceptions\MissingCheckedExceptionInPropertyHookThrowsRule' => [['0878']],
		'PHPStan\Rules\Properties\UninitializedPropertyRule' => [['0879']],
		'PHPStan\Rules\Exceptions\MethodThrowTypeCovarianceRule' => [['0880']],
		'PHPStan\Rules\Classes\NewStaticInAbstractClassStaticMethodRule' => [['0881']],
		'PHPStan\Rules\RestrictedUsage\RestrictedClassConstantUsageExtension' => [['0882', '0901']],
		'PHPStan\Rules\InternalTag\RestrictedInternalClassConstantUsageExtension' => [['0882']],
		'PHPStan\Rules\RestrictedUsage\RestrictedClassNameUsageExtension' => [['0883', '0905']],
		'PHPStan\Rules\InternalTag\RestrictedInternalClassNameUsageExtension' => [['0883']],
		'PHPStan\Rules\RestrictedUsage\RestrictedFunctionUsageExtension' => [['0884', '0902']],
		'PHPStan\Rules\InternalTag\RestrictedInternalFunctionUsageExtension' => [['0884']],
		'PHPStan\Rules\Variables\AssignToByRefExprFromForeachRule' => [['0885']],
		'PHPStan\Rules\RestrictedUsage\RestrictedPropertyUsageExtension' => [['0886', '0904']],
		'PHPStan\Rules\InternalTag\RestrictedInternalPropertyUsageExtension' => [['0886']],
		'PHPStan\Rules\RestrictedUsage\RestrictedMethodUsageExtension' => [['0887', '0903']],
		'PHPStan\Rules\InternalTag\RestrictedInternalMethodUsageExtension' => [['0887']],
		'PHPStan\Rules\Constants\ValueAssignedToDefineRule' => [['0888']],
		'PHPStan\Rules\Constants\ValueAssignedToGlobalConstantRule' => [['0889']],
		'PHPStan\Rules\Exceptions\TooWideFunctionThrowTypeRule' => [['0890']],
		'PHPStan\Rules\Exceptions\TooWideMethodThrowTypeRule' => [['0891']],
		'PHPStan\Rules\Exceptions\TooWidePropertyHookThrowTypeRule' => [['0892']],
		'PHPStan\Rules\Keywords\UnusedLabelRule' => [['0893']],
		'PHPStan\Rules\Functions\ParameterCastableToNumberRule' => [['0894']],
		'PHPStan\Rules\Functions\PrintfParameterTypeRule' => [['0895']],
		'PHPStan\Rules\DateIntervalInstantiationRule' => [['0896']],
		'PHPStan\DependencyInjection\LazyDeprecatedScopeResolverProvider' => [['0897']],
		'PHPStan\Rules\Deprecations\DeprecatedScopeHelper' => [['0898']],
		'PHPStan\Rules\Deprecations\DeprecatedScopeResolver' => [['0899']],
		'PHPStan\Rules\Deprecations\DefaultDeprecatedScopeResolver' => [['0899']],
		'PHPStan\Rules\Deprecations\CallWithDeprecatedIniOptionRule' => [['0900']],
		'PHPStan\Rules\Deprecations\RestrictedDeprecatedClassConstantUsageExtension' => [['0901']],
		'PHPStan\Rules\Deprecations\RestrictedDeprecatedFunctionUsageExtension' => [['0902']],
		'PHPStan\Rules\Deprecations\RestrictedDeprecatedMethodUsageExtension' => [['0903']],
		'PHPStan\Rules\Deprecations\RestrictedDeprecatedPropertyUsageExtension' => [['0904']],
		'PHPStan\Rules\Deprecations\RestrictedDeprecatedClassNameUsageExtension' => [['0905']],
		'PHPStan\Rule\Nette\RethrowExceptionRule' => [['0906']],
		'PHPStan\PhpDoc\TypeNodeResolverExtension' => [['0907']],
		'PHPStan\PhpDoc\TypeNodeResolverAwareExtension' => [['0907']],
		'PHPStan\PhpDoc\PHPUnit\MockObjectTypeNodeResolverExtension' => [['0907']],
		'PHPStan\Type\PHPUnit\Assert\AssertFunctionTypeSpecifyingExtension' => [['0908']],
		'PHPStan\Type\PHPUnit\Assert\AssertMethodTypeSpecifyingExtension' => [['0909']],
		'PHPStan\Type\StaticMethodTypeSpecifyingExtension' => [['0910']],
		'PHPStan\Type\PHPUnit\Assert\AssertStaticMethodTypeSpecifyingExtension' => [['0910']],
		'PHPStan\Type\PHPUnit\MockBuilderDynamicReturnTypeExtension' => [['0911']],
		'PHPStan\Type\PHPUnit\MockForIntersectionDynamicReturnTypeExtension' => [['0912']],
		'PHPStan\Rules\PHPUnit\CoversHelper' => [['0913']],
		'PHPStan\Rules\PHPUnit\AnnotationHelper' => [['0914']],
		'PHPStan\Rules\PHPUnit\TestMethodsHelper' => [['0915']],
		'PHPStan\Rules\PHPUnit\PHPUnitVersion' => [['0916']],
		'PHPStan\Rules\PHPUnit\PHPUnitVersionDetector' => [['0917']],
		'PHPStan\Rules\PHPUnit\DataProviderHelper' => [['0918']],
		'PHPStan\Rules\PHPUnit\DataProviderHelperFactory' => [['0919']],
		'PHPStan\Analyser\IgnoreErrorExtension' => [['0920', '0921']],
		'PHPStan\Type\PHPUnit\DataProviderReturnTypeIgnoreExtension' => [['0920']],
		'PHPStan\Type\PHPUnit\DynamicCallToAssertionIgnoreExtension' => [['0921']],
		'PHPStan\Rules\PHPUnit\DataProviderDeclarationRule' => [['0922']],
		'PHPStan\Rules\PHPUnit\AttributeRequiresPhpVersionRule' => [['0923']],
		'PHPStan\Rules\PHPUnit\AssertEqualsIsDiscouragedRule' => [['0924']],
		'PHPStan\Rules\PHPUnit\DataProviderDataRule' => [['0925']],
		'PHPStan\Rules\BooleansInConditions\BooleanRuleHelper' => [['0926']],
		'PHPStan\Rules\Operators\OperatorRuleHelper' => [['0927']],
		'PHPStan\Rules\VariableVariables\VariablePropertyFetchRule' => [['0928']],
		'PHPStan\Rules\DisallowedConstructs\DisallowedLooseComparisonRule' => [['0929']],
		'PHPStan\Rules\BooleansInConditions\BooleanInBooleanAndRule' => [['0930']],
		'PHPStan\Rules\BooleansInConditions\BooleanInBooleanNotRule' => [['0931']],
		'PHPStan\Rules\BooleansInConditions\BooleanInBooleanOrRule' => [['0932']],
		'PHPStan\Rules\BooleansInConditions\BooleanInDoWhileConditionRule' => [['0933']],
		'PHPStan\Rules\BooleansInConditions\BooleanInElseIfConditionRule' => [['0934']],
		'PHPStan\Rules\BooleansInConditions\BooleanInIfConditionRule' => [['0935']],
		'PHPStan\Rules\BooleansInConditions\BooleanInTernaryOperatorRule' => [['0936']],
		'PHPStan\Rules\BooleansInConditions\BooleanInWhileConditionRule' => [['0937']],
		'PHPStan\Rules\Cast\UselessCastRule' => [['0938']],
		'PHPStan\Rules\Classes\RequireParentConstructCallRule' => [['0939']],
		'PHPStan\Rules\DisallowedConstructs\DisallowedBacktickRule' => [['0940']],
		'PHPStan\Rules\DisallowedConstructs\DisallowedEmptyRule' => [['0941']],
		'PHPStan\Rules\DisallowedConstructs\DisallowedImplicitArrayCreationRule' => [['0942']],
		'PHPStan\Rules\DisallowedConstructs\DisallowedShortTernaryRule' => [['0943']],
		'PHPStan\Rules\ForeachLoop\OverwriteVariablesWithForeachRule' => [['0944']],
		'PHPStan\Rules\ForLoop\OverwriteVariablesWithForLoopInitRule' => [['0945']],
		'PHPStan\Rules\Functions\ArrayFilterStrictRule' => [['0946']],
		'PHPStan\Rules\Functions\ClosureUsesThisRule' => [['0947']],
		'PHPStan\Rules\Methods\WrongCaseOfInheritedMethodRule' => [['0948']],
		'PHPStan\Rules\Methods\IllegalConstructorMethodCallRule' => [['0949']],
		'PHPStan\Rules\Methods\IllegalConstructorStaticCallRule' => [['0950']],
		'PHPStan\Rules\Operators\OperandInArithmeticIncrementOrDecrementRule' => [['0951', '0952', '0953', '0954']],
		'PHPStan\Rules\Operators\OperandInArithmeticPostDecrementRule' => [['0951']],
		'PHPStan\Rules\Operators\OperandInArithmeticPostIncrementRule' => [['0952']],
		'PHPStan\Rules\Operators\OperandInArithmeticPreDecrementRule' => [['0953']],
		'PHPStan\Rules\Operators\OperandInArithmeticPreIncrementRule' => [['0954']],
		'PHPStan\Rules\Operators\OperandInArithmeticUnaryMinusRule' => [['0955']],
		'PHPStan\Rules\Operators\OperandInArithmeticUnaryPlusRule' => [['0956']],
		'PHPStan\Rules\Operators\OperandsInArithmeticAdditionRule' => [['0957']],
		'PHPStan\Rules\Operators\OperandsInArithmeticDivisionRule' => [['0958']],
		'PHPStan\Rules\Operators\OperandsInArithmeticExponentiationRule' => [['0959']],
		'PHPStan\Rules\Operators\OperandsInArithmeticModuloRule' => [['0960']],
		'PHPStan\Rules\Operators\OperandsInArithmeticMultiplicationRule' => [['0961']],
		'PHPStan\Rules\Operators\OperandsInArithmeticSubtractionRule' => [['0962']],
		'PHPStan\Rules\StrictCalls\DynamicCallOnStaticMethodsRule' => [['0963']],
		'PHPStan\Rules\StrictCalls\DynamicCallOnStaticMethodsCallableRule' => [['0964']],
		'PHPStan\Rules\StrictCalls\StrictFunctionCallsRule' => [['0965']],
		'PHPStan\Rules\SwitchConditions\MatchingTypeInSwitchCaseConditionRule' => [['0966']],
		'PHPStan\Rules\VariableVariables\VariableMethodCallRule' => [['0967']],
		'PHPStan\Rules\VariableVariables\VariableMethodCallableRule' => [['0968']],
		'PHPStan\Rules\VariableVariables\VariableStaticMethodCallRule' => [['0969']],
		'PHPStan\Rules\VariableVariables\VariableStaticMethodCallableRule' => [['0970']],
		'PHPStan\Rules\VariableVariables\VariableStaticPropertyFetchRule' => [['0971']],
		'PHPStan\Rules\VariableVariables\VariableVariablesRule' => [['0972']],
		'ShipMonk\PHPStan\DeadCode\Formatter\RemoveDeadCodeFormatter' => [['errorFormatter.removeDeadCode']],
		'ShipMonk\PHPStan\DeadCode\Formatter\FilterOutUnmatchedInlineIgnoresFormatter' => [
			['errorFormatter.filterOutUnmatchedInlineIgnoresDuringPartialAnalysis'],
		],
		'ShipMonk\PHPStan\DeadCode\Cache\UsageCacheStorage' => [['0973']],
		'ShipMonk\PHPStan\DeadCode\Hierarchy\ClassHierarchy' => [['0974']],
		'ShipMonk\PHPStan\DeadCode\Transformer\FileSystem' => [['0975']],
		'ShipMonk\PHPStan\DeadCode\Output\OutputEnhancer' => [['0976']],
		'ShipMonk\PHPStan\DeadCode\Debug\DebugUsagePrinter' => [['0977']],
		'ShipMonk\PHPStan\DeadCode\Provider\ReflectionBasedMemberUsageProvider' => [
			['0978', '0980', '0981', '0989', '0992'],
		],
		'ShipMonk\PHPStan\DeadCode\Provider\MemberUsageProvider' => [
			[
				'0978',
				'0979',
				'0980',
				'0981',
				'0982',
				'0983',
				'0984',
				'0985',
				'0986',
				'0987',
				'0988',
				'0989',
				'0990',
				'0991',
				'0992',
				'0993',
				'0994',
			],
		],
		'ShipMonk\PHPStan\DeadCode\Provider\ApiPhpDocUsageProvider' => [['0978']],
		'ShipMonk\PHPStan\DeadCode\Provider\EnumUsageProvider' => [['0979']],
		'ShipMonk\PHPStan\DeadCode\Provider\VendorUsageProvider' => [['0980']],
		'ShipMonk\PHPStan\DeadCode\Provider\BuiltinUsageProvider' => [['0981']],
		'ShipMonk\PHPStan\DeadCode\Provider\ReflectionUsageProvider' => [['0982']],
		'ShipMonk\PHPStan\DeadCode\Provider\PhpUnitUsageProvider' => [['0983']],
		'ShipMonk\PHPStan\DeadCode\Provider\PhpBenchUsageProvider' => [['0984']],
		'ShipMonk\PHPStan\DeadCode\Provider\BehatUsageProvider' => [['0985']],
		'ShipMonk\PHPStan\DeadCode\Provider\SymfonyUsageProvider' => [['0986']],
		'ShipMonk\PHPStan\DeadCode\Provider\TwigUsageProvider' => [['0987']],
		'ShipMonk\PHPStan\DeadCode\Provider\DoctrineUsageProvider' => [['0988']],
		'ShipMonk\PHPStan\DeadCode\Provider\PhpStanUsageProvider' => [['0989']],
		'ShipMonk\PHPStan\DeadCode\Provider\EloquentUsageProvider' => [['0990']],
		'ShipMonk\PHPStan\DeadCode\Provider\LaravelUsageProvider' => [['0991']],
		'ShipMonk\PHPStan\DeadCode\Provider\NetteUsageProvider' => [['0992']],
		'ShipMonk\PHPStan\DeadCode\Provider\NetteTesterUsageProvider' => [['0993']],
		'ShipMonk\PHPStan\DeadCode\Provider\StreamWrapperUsageProvider' => [['0994']],
		'ShipMonk\PHPStan\DeadCode\Excluder\MemberUsageExcluder' => [['0995', '0996']],
		'ShipMonk\PHPStan\DeadCode\Excluder\TestsUsageExcluder' => [['0995']],
		'ShipMonk\PHPStan\DeadCode\Excluder\MixedUsageExcluder' => [['0996']],
		'ShipMonk\PHPStan\DeadCode\Collector\MethodCallCollector' => [['0997']],
		'ShipMonk\PHPStan\DeadCode\Collector\ConstantFetchCollector' => [['0998']],
		'ShipMonk\PHPStan\DeadCode\Collector\PropertyAccessCollector' => [['0999']],
		'ShipMonk\PHPStan\DeadCode\Collector\ClassDefinitionCollector' => [['01000']],
		'ShipMonk\PHPStan\DeadCode\Collector\ProvidedUsagesCollector' => [['01001']],
		'ShipMonk\PHPStan\DeadCode\Rule\DeadCodeRule' => [['01002']],
		'ShipMonk\PHPStan\DeadCode\Compatibility\BackwardCompatibilityChecker' => [['01003']],
		'ShipMonk\PHPStan\DeadCode\Visitor\PropertyWriteVisitor' => [['01004']],
		'PHPStan\Build\ServiceLocatorDynamicReturnTypeExtension' => [['01005']],
		'PHPStan\Build\ContainerDynamicReturnTypeExtension' => [['01006']],
		'PHPStan\PhpDoc\StubSourceLocatorFactory' => [['01007']],
	];


	public function __construct(array $params = [])
	{
		parent::__construct($params);
	}


	public function createService01(): PHPStan\Cache\Cache
	{
		return new PHPStan\Cache\Cache($this->getService('cacheStorage'));
	}


	public function createService02(): PHPStan\Fixable\Patcher
	{
		return new PHPStan\Fixable\Patcher;
	}


	public function createService03(): PHPStan\Fixable\PhpDoc\PhpDocEditor
	{
		return new PHPStan\Fixable\PhpDoc\PhpDocEditor($this->getService('0848'), $this->getService('0844'), $this->getService('0847'));
	}


	public function createService04(): PHPStan\Internal\HttpClientFactory
	{
		return new PHPStan\Internal\HttpClientFactory;
	}


	public function createService05(): PHPStan\Parallel\ParallelAnalyser
	{
		return new PHPStan\Parallel\ParallelAnalyser(50, 600.0, 134217728, $this->getService('06'), $this->getService('07'));
	}


	public function createService06(): PHPStan\Parallel\ForkParallelChecker
	{
		return new PHPStan\Parallel\ForkParallelChecker;
	}


	public function createService07(): PHPStan\Parallel\WorkerRunner
	{
		return new PHPStan\Parallel\WorkerRunner(
			$this->getService('0161'),
			$this->getService('registry'),
			$this->getService('0220'),
			$this->getService('085'),
			134217728
		);
	}


	public function createService08(): PHPStan\Parallel\Scheduler
	{
		return new PHPStan\Parallel\Scheduler(20, 8, 2);
	}


	public function createService09(): PHPStan\Rules\IssetCheck
	{
		return new PHPStan\Rules\IssetCheck($this->getService('039'), $this->getService('040'), true, true);
	}


	public function createService010(): PHPStan\Rules\Pure\FunctionPurityCheck
	{
		return new PHPStan\Rules\Pure\FunctionPurityCheck;
	}


	public function createService011(): PHPStan\Rules\FunctionDefinitionCheck
	{
		return new PHPStan\Rules\FunctionDefinitionCheck(
			$this->getService('reflectionProvider'),
			$this->getService('060'),
			$this->getService('070'),
			$this->getService('0453'),
			true,
			false
		);
	}


	public function createService012(): PHPStan\Rules\ParameterCastableToStringCheck
	{
		return new PHPStan\Rules\ParameterCastableToStringCheck($this->getService('020'));
	}


	public function createService013(): PHPStan\Rules\Generics\VarianceCheck
	{
		return new PHPStan\Rules\Generics\VarianceCheck;
	}


	public function createService014(): PHPStan\Rules\Generics\MethodTagTemplateTypeCheck
	{
		return new PHPStan\Rules\Generics\MethodTagTemplateTypeCheck($this->getService('0449'), $this->getService('018'));
	}


	public function createService015(): PHPStan\Rules\Generics\GenericAncestorsCheck
	{
		return new PHPStan\Rules\Generics\GenericAncestorsCheck(
			$this->getService('reflectionProvider'),
			$this->getService('017'),
			$this->getService('013'),
			$this->getService('070'),
			[],
			true
		);
	}


	public function createService016(): PHPStan\Rules\Generics\CrossCheckInterfacesHelper
	{
		return new PHPStan\Rules\Generics\CrossCheckInterfacesHelper;
	}


	public function createService017(): PHPStan\Rules\Generics\GenericObjectTypeCheck
	{
		return new PHPStan\Rules\Generics\GenericObjectTypeCheck;
	}


	public function createService018(): PHPStan\Rules\Generics\TemplateTypeCheck
	{
		return new PHPStan\Rules\Generics\TemplateTypeCheck(
			$this->getService('reflectionProvider'),
			$this->getService('060'),
			$this->getService('017'),
			$this->getService('0266'),
			true
		);
	}


	public function createService019(): PHPStan\Rules\MissingTypehintCheck
	{
		return new PHPStan\Rules\MissingTypehintCheck(false, [], true);
	}


	public function createService020(): PHPStan\Rules\RuleLevelHelper
	{
		return new PHPStan\Rules\RuleLevelHelper($this->getService('reflectionProvider'), true, false, true, false, false, false, true);
	}


	public function createService021(): PHPStan\Rules\RestrictedUsage\RestrictedStaticMethodUsageRule
	{
		return new PHPStan\Rules\RestrictedUsage\RestrictedStaticMethodUsageRule(
			$this->getService('0179'),
			$this->getService('reflectionProvider'),
			$this->getService('020')
		);
	}


	public function createService022(): PHPStan\Rules\RestrictedUsage\RestrictedFunctionCallableUsageRule
	{
		return new PHPStan\Rules\RestrictedUsage\RestrictedFunctionCallableUsageRule(
			$this->getService('0179'),
			$this->getService('reflectionProvider')
		);
	}


	public function createService023(): PHPStan\Rules\RestrictedUsage\RestrictedMethodUsageRule
	{
		return new PHPStan\Rules\RestrictedUsage\RestrictedMethodUsageRule(
			$this->getService('0179'),
			$this->getService('reflectionProvider')
		);
	}


	public function createService024(): PHPStan\Rules\RestrictedUsage\RestrictedPropertyUsageRule
	{
		return new PHPStan\Rules\RestrictedUsage\RestrictedPropertyUsageRule(
			$this->getService('0179'),
			$this->getService('reflectionProvider')
		);
	}


	public function createService025(): PHPStan\Rules\RestrictedUsage\RestrictedUsageOfDeprecatedStringCastRule
	{
		return new PHPStan\Rules\RestrictedUsage\RestrictedUsageOfDeprecatedStringCastRule(
			$this->getService('0179'),
			$this->getService('reflectionProvider')
		);
	}


	public function createService026(): PHPStan\Rules\RestrictedUsage\RestrictedMethodCallableUsageRule
	{
		return new PHPStan\Rules\RestrictedUsage\RestrictedMethodCallableUsageRule(
			$this->getService('0179'),
			$this->getService('reflectionProvider')
		);
	}


	public function createService027(): PHPStan\Rules\RestrictedUsage\RestrictedStaticPropertyUsageRule
	{
		return new PHPStan\Rules\RestrictedUsage\RestrictedStaticPropertyUsageRule(
			$this->getService('0179'),
			$this->getService('reflectionProvider'),
			$this->getService('020')
		);
	}


	public function createService028(): PHPStan\Rules\RestrictedUsage\RestrictedClassConstantUsageRule
	{
		return new PHPStan\Rules\RestrictedUsage\RestrictedClassConstantUsageRule(
			$this->getService('0179'),
			$this->getService('reflectionProvider'),
			$this->getService('020')
		);
	}


	public function createService029(): PHPStan\Rules\RestrictedUsage\RestrictedStaticMethodCallableUsageRule
	{
		return new PHPStan\Rules\RestrictedUsage\RestrictedStaticMethodCallableUsageRule(
			$this->getService('0179'),
			$this->getService('reflectionProvider'),
			$this->getService('020')
		);
	}


	public function createService030(): PHPStan\Rules\RestrictedUsage\RestrictedFunctionUsageRule
	{
		return new PHPStan\Rules\RestrictedUsage\RestrictedFunctionUsageRule(
			$this->getService('0179'),
			$this->getService('reflectionProvider')
		);
	}


	public function createService031(): PHPStan\Rules\Classes\LocalTypeAliasesCheck
	{
		return new PHPStan\Rules\Classes\LocalTypeAliasesCheck(
			[],
			$this->getService('reflectionProvider'),
			$this->getService('0233'),
			$this->getService('019'),
			$this->getService('060'),
			$this->getService('070'),
			$this->getService('017'),
			true,
			true,
			true
		);
	}


	public function createService032(): PHPStan\Rules\Classes\MethodTagCheck
	{
		return new PHPStan\Rules\Classes\MethodTagCheck(
			$this->getService('reflectionProvider'),
			$this->getService('060'),
			$this->getService('017'),
			$this->getService('019'),
			$this->getService('070'),
			true,
			true,
			true
		);
	}


	public function createService033(): PHPStan\Rules\Classes\DuplicateDeclarationHelper
	{
		return new PHPStan\Rules\Classes\DuplicateDeclarationHelper;
	}


	public function createService034(): PHPStan\Rules\Classes\MixinCheck
	{
		return new PHPStan\Rules\Classes\MixinCheck(
			$this->getService('reflectionProvider'),
			$this->getService('060'),
			$this->getService('017'),
			$this->getService('019'),
			$this->getService('070'),
			true,
			true,
			true
		);
	}


	public function createService035(): PHPStan\Rules\Classes\PropertyTagCheck
	{
		return new PHPStan\Rules\Classes\PropertyTagCheck(
			$this->getService('reflectionProvider'),
			$this->getService('060'),
			$this->getService('017'),
			$this->getService('019'),
			$this->getService('070'),
			true,
			true,
			true
		);
	}


	public function createService036(): PHPStan\Rules\Classes\ConsistentConstructorHelper
	{
		return new PHPStan\Rules\Classes\ConsistentConstructorHelper;
	}


	public function createService037(): PHPStan\Rules\Constants\LazyAlwaysUsedClassConstantsExtensionProvider
	{
		return new PHPStan\Rules\Constants\LazyAlwaysUsedClassConstantsExtensionProvider($this->getService('0179'));
	}


	public function createService038(): PHPStan\Rules\Functions\PrintfHelper
	{
		return new PHPStan\Rules\Functions\PrintfHelper($this->getService('0453'));
	}


	public function createService039(): PHPStan\Rules\Properties\PropertyDescriptor
	{
		return new PHPStan\Rules\Properties\PropertyDescriptor;
	}


	public function createService040(): PHPStan\Rules\Properties\PropertyReflectionFinder
	{
		return new PHPStan\Rules\Properties\PropertyReflectionFinder;
	}


	public function createService041(): PHPStan\Rules\Properties\AccessStaticPropertiesCheck
	{
		return new PHPStan\Rules\Properties\AccessStaticPropertiesCheck(
			$this->getService('reflectionProvider'),
			$this->getService('020'),
			$this->getService('060'),
			$this->getService('0453'),
			true
		);
	}


	public function createService042(): PHPStan\Rules\Properties\AccessPropertiesCheck
	{
		return new PHPStan\Rules\Properties\AccessPropertiesCheck(
			$this->getService('reflectionProvider'),
			$this->getService('020'),
			$this->getService('0453'),
			true,
			true,
			true
		);
	}


	public function createService043(): PHPStan\Rules\Properties\LazyReadWritePropertiesExtensionProvider
	{
		return new PHPStan\Rules\Properties\LazyReadWritePropertiesExtensionProvider($this->getService('0179'));
	}


	public function createService044(): PHPStan\Rules\Playground\NeverRuleHelper
	{
		return new PHPStan\Rules\Playground\NeverRuleHelper;
	}


	public function createService045(): PHPStan\Rules\InternalTag\RestrictedInternalUsageHelper
	{
		return new PHPStan\Rules\InternalTag\RestrictedInternalUsageHelper;
	}


	public function createService046(): PHPStan\Rules\TooWideTypehints\TooWideTypeCheck
	{
		return new PHPStan\Rules\TooWideTypehints\TooWideTypeCheck($this->getService('040'), true, false);
	}


	public function createService047(): PHPStan\Rules\TooWideTypehints\TooWideParameterOutTypeCheck
	{
		return new PHPStan\Rules\TooWideTypehints\TooWideParameterOutTypeCheck($this->getService('046'));
	}


	public function createService048(): PHPStan\Rules\Debug\DumpNativeTypeRule
	{
		return new PHPStan\Rules\Debug\DumpNativeTypeRule($this->getService('reflectionProvider'));
	}


	public function createService049(): PHPStan\Rules\Debug\DumpPhpDocTypeRule
	{
		return new PHPStan\Rules\Debug\DumpPhpDocTypeRule($this->getService('reflectionProvider'), $this->getService('0848'));
	}


	public function createService050(): PHPStan\Rules\Debug\FileAssertRule
	{
		return new PHPStan\Rules\Debug\FileAssertRule($this->getService('reflectionProvider'), $this->getService('0231'));
	}


	public function createService051(): PHPStan\Rules\Debug\DumpTypeRule
	{
		return new PHPStan\Rules\Debug\DumpTypeRule($this->getService('reflectionProvider'));
	}


	public function createService052(): PHPStan\Rules\Debug\DebugScopeRule
	{
		return new PHPStan\Rules\Debug\DebugScopeRule($this->getService('reflectionProvider'));
	}


	public function createService053(): PHPStan\Rules\Methods\StaticMethodCallCheck
	{
		return new PHPStan\Rules\Methods\StaticMethodCallCheck(
			$this->getService('reflectionProvider'),
			$this->getService('020'),
			$this->getService('060'),
			true,
			true,
			true
		);
	}


	public function createService054(): PHPStan\Rules\Methods\LazyAlwaysUsedMethodExtensionProvider
	{
		return new PHPStan\Rules\Methods\LazyAlwaysUsedMethodExtensionProvider($this->getService('0179'));
	}


	public function createService055(): PHPStan\Rules\Methods\MethodCallCheck
	{
		return new PHPStan\Rules\Methods\MethodCallCheck($this->getService('reflectionProvider'), $this->getService('020'), true, true);
	}


	public function createService056(): PHPStan\Rules\Methods\MethodParameterComparisonHelper
	{
		return new PHPStan\Rules\Methods\MethodParameterComparisonHelper($this->getService('0453'));
	}


	public function createService057(): PHPStan\Rules\Methods\ParentMethodHelper
	{
		return new PHPStan\Rules\Methods\ParentMethodHelper($this->getService('0853'));
	}


	public function createService058(): PHPStan\Rules\Methods\MethodPrototypeFinder
	{
		return new PHPStan\Rules\Methods\MethodPrototypeFinder($this->getService('0453'), $this->getService('0853'));
	}


	public function createService059(): PHPStan\Rules\Methods\MethodVisibilityComparisonHelper
	{
		return new PHPStan\Rules\Methods\MethodVisibilityComparisonHelper;
	}


	public function createService060(): PHPStan\Rules\ClassNameCheck
	{
		return new PHPStan\Rules\ClassNameCheck(
			$this->getService('079'),
			$this->getService('061'),
			$this->getService('reflectionProvider'),
			$this->getService('0179')
		);
	}


	public function createService061(): PHPStan\Rules\ClassForbiddenNameCheck
	{
		return new PHPStan\Rules\ClassForbiddenNameCheck($this->getService('0179'));
	}


	public function createService062(): PHPStan\Rules\UnusedFunctionParametersCheck
	{
		return new PHPStan\Rules\UnusedFunctionParametersCheck($this->getService('reflectionProvider'), true);
	}


	public function createService063(): PHPStan\Rules\FunctionReturnTypeCheck
	{
		return new PHPStan\Rules\FunctionReturnTypeCheck($this->getService('020'));
	}


	public function createService064(): PHPStan\Rules\FunctionCallParametersCheck
	{
		return new PHPStan\Rules\FunctionCallParametersCheck(
			$this->getService('020'),
			$this->getService('072'),
			$this->getService('070'),
			$this->getService('040'),
			$this->getService('reflectionProvider'),
			true,
			true,
			true,
			true
		);
	}


	public function createService065(): PHPStan\Rules\PhpDoc\AssertRuleHelper
	{
		return new PHPStan\Rules\PhpDoc\AssertRuleHelper(
			$this->getService('reflectionProvider'),
			$this->getService('070'),
			$this->getService('060'),
			$this->getService('019'),
			$this->getService('017'),
			true,
			true
		);
	}


	public function createService066(): PHPStan\Rules\PhpDoc\IncompatiblePhpDocTypeCheck
	{
		return new PHPStan\Rules\PhpDoc\IncompatiblePhpDocTypeCheck(
			$this->getService('017'),
			$this->getService('070'),
			$this->getService('069')
		);
	}


	public function createService067(): PHPStan\Rules\PhpDoc\VarTagTypeRuleHelper
	{
		return new PHPStan\Rules\PhpDoc\VarTagTypeRuleHelper(
			$this->getService('0233'),
			$this->getService('0449'),
			$this->getService('reflectionProvider'),
			true,
			false
		);
	}


	public function createService068(): PHPStan\Rules\PhpDoc\RequireExtendsCheck
	{
		return new PHPStan\Rules\PhpDoc\RequireExtendsCheck(
			$this->getService('reflectionProvider'),
			$this->getService('060'),
			true,
			true
		);
	}


	public function createService069(): PHPStan\Rules\PhpDoc\GenericCallableRuleHelper
	{
		return new PHPStan\Rules\PhpDoc\GenericCallableRuleHelper($this->getService('018'));
	}


	public function createService070(): PHPStan\Rules\PhpDoc\UnresolvableTypeHelper
	{
		return new PHPStan\Rules\PhpDoc\UnresolvableTypeHelper;
	}


	public function createService071(): PHPStan\Rules\PhpDoc\ConditionalReturnTypeRuleHelper
	{
		return new PHPStan\Rules\PhpDoc\ConditionalReturnTypeRuleHelper;
	}


	public function createService072(): PHPStan\Rules\NullsafeCheck
	{
		return new PHPStan\Rules\NullsafeCheck;
	}


	public function createService073(): PHPStan\Rules\Api\ApiRuleHelper
	{
		return new PHPStan\Rules\Api\ApiRuleHelper;
	}


	public function createService074(): PHPStan\Rules\AttributesCheck
	{
		return new PHPStan\Rules\AttributesCheck(
			$this->getService('reflectionProvider'),
			$this->getService('064'),
			$this->getService('060'),
			true
		);
	}


	public function createService075(): PHPStan\Rules\Exceptions\DefaultExceptionTypeResolver
	{
		return new PHPStan\Rules\Exceptions\DefaultExceptionTypeResolver(
			$this->getService('reflectionProvider'),
			['#^PHPUnit\\\#', '#^SebastianBergmann\\\#'],
			[
				'PHPStan\ShouldNotHappenException',
				'Symfony\Component\Console\Exception\InvalidArgumentException',
				'PHPStan\BetterReflection\SourceLocator\Exception\InvalidFileLocation',
				'PHPStan\BetterReflection\SourceLocator\Exception\InvalidArgumentException',
				'Symfony\Component\Finder\Exception\DirectoryNotFoundException',
				'InvalidArgumentException',
				'PHPStan\DependencyInjection\ParameterNotFoundException',
				'PHPStan\DependencyInjection\DuplicateIncludedFilesException',
				'PHPStan\Analyser\UndefinedVariableException',
				'RuntimeException',
				'Nette\Neon\Exception',
				'Nette\Utils\JsonException',
				'PHPStan\File\CouldNotReadFileException',
				'PHPStan\File\CouldNotWriteFileException',
				'PHPStan\Parser\ParserErrorsException',
				'ReflectionException',
				'Nette\Utils\AssertionException',
				'PHPStan\File\PathNotFoundException',
				'PHPStan\Broker\ClassNotFoundException',
				'PHPStan\Broker\FunctionNotFoundException',
				'PHPStan\Broker\ConstantNotFoundException',
				'PHPStan\DependencyInjection\MissingServiceException',
				'PHPStan\Reflection\MissingMethodFromReflectionException',
				'PHPStan\Reflection\MissingPropertyFromReflectionException',
				'PHPStan\Reflection\MissingConstantFromReflectionException',
				'PHPStan\Type\CircularTypeAliasDefinitionException',
				'PHPStan\Reflection\MissingStaticAccessorInstanceException',
				'LogicException',
				'Error',
				'PHPStan\Analyser\Generator\TrampolineException',
			],
			[],
			[]
		);
	}


	public function createService076(): PHPStan\Rules\Exceptions\TooWideThrowTypeCheck
	{
		return new PHPStan\Rules\Exceptions\TooWideThrowTypeCheck(true);
	}


	public function createService077(): PHPStan\Rules\Exceptions\MissingCheckedExceptionInThrowsCheck
	{
		return new PHPStan\Rules\Exceptions\MissingCheckedExceptionInThrowsCheck($this->getService('exceptionTypeResolver'));
	}


	public function createService078(): PHPStan\Rules\Arrays\NonexistentOffsetInArrayDimFetchCheck
	{
		return new PHPStan\Rules\Arrays\NonexistentOffsetInArrayDimFetchCheck($this->getService('020'), true, false, true);
	}


	public function createService079(): PHPStan\Rules\ClassCaseSensitivityCheck
	{
		return new PHPStan\Rules\ClassCaseSensitivityCheck($this->getService('reflectionProvider'), true);
	}


	public function createService080(): PHPStan\Rules\Comparison\ConstantConditionRuleHelper
	{
		return new PHPStan\Rules\Comparison\ConstantConditionRuleHelper($this->getService('082'), true);
	}


	public function createService081(): PHPStan\Rules\Comparison\PossiblyImpureTipHelper
	{
		return new PHPStan\Rules\Comparison\PossiblyImpureTipHelper(true);
	}


	public function createService082(): PHPStan\Rules\Comparison\ImpossibleCheckTypeHelper
	{
		return new PHPStan\Rules\Comparison\ImpossibleCheckTypeHelper(
			$this->getService('reflectionProvider'),
			$this->getService('typeSpecifier'),
			true
		);
	}


	public function createService083(): PHPStan\Analyser\AnalyserResultFinalizer
	{
		return new PHPStan\Analyser\AnalyserResultFinalizer(
			$this->getService('registry'),
			$this->getService('0164'),
			$this->getService('084'),
			$this->getService('0160'),
			$this->getService('0162'),
			true
		);
	}


	public function createService084(): PHPStan\Analyser\RuleErrorTransformer
	{
		return new PHPStan\Analyser\RuleErrorTransformer($this->getService('currentPhpVersionPhpParser'));
	}


	public function createService085(): PHPStan\Analyser\Fiber\FiberNodeScopeResolver
	{
		return new PHPStan\Analyser\Fiber\FiberNodeScopeResolver(
			$this->getService('0179'),
			$this->getService('reflectionProvider'),
			$this->getService('0204'),
			$this->getService('nodeScopeResolverReflector'),
			$this->getService('0459'),
			$this->getService('0185'),
			$this->getService('defaultAnalysisParser'),
			$this->getService('0449'),
			$this->getService('0222'),
			$this->getService('0173'),
			$this->getService('typeSpecifier'),
			$this->getService('043'),
			$this->getService('0187'),
			$this->getService('0184'),
			$this->getService('0160'),
			$this->getService('0172'),
			false,
			false,
			false,
			['PHPUnit\Framework\Assert' => ['fail', 'markTestIncomplete', 'markTestSkipped']],
			[],
			true,
			true,
			$this->getService('0140')
		);
	}


	public function createService086(): PHPStan\Analyser\ExprHandler\FirstClassCallableFuncCallHandler
	{
		return new PHPStan\Analyser\ExprHandler\FirstClassCallableFuncCallHandler($this->getService('0204'));
	}


	public function createService087(): PHPStan\Analyser\ExprHandler\YieldHandler
	{
		return new PHPStan\Analyser\ExprHandler\YieldHandler;
	}


	public function createService088(): PHPStan\Analyser\ExprHandler\CastHandler
	{
		return new PHPStan\Analyser\ExprHandler\CastHandler($this->getService('0204'));
	}


	public function createService089(): PHPStan\Analyser\ExprHandler\EmptyHandler
	{
		return new PHPStan\Analyser\ExprHandler\EmptyHandler($this->getService('0142'));
	}


	public function createService090(): PHPStan\Analyser\ExprHandler\PreDecHandler
	{
		return new PHPStan\Analyser\ExprHandler\PreDecHandler;
	}


	public function createService091(): PHPStan\Analyser\ExprHandler\EvalHandler
	{
		return new PHPStan\Analyser\ExprHandler\EvalHandler;
	}


	public function createService092(): PHPStan\Analyser\ExprHandler\AssignHandler
	{
		return new PHPStan\Analyser\ExprHandler\AssignHandler($this->getService('typeSpecifier'), $this->getService('0453'));
	}


	public function createService093(): PHPStan\Analyser\ExprHandler\StaticPropertyFetchHandler
	{
		return new PHPStan\Analyser\ExprHandler\StaticPropertyFetchHandler($this->getService('040'));
	}


	public function createService094(): PHPStan\Analyser\ExprHandler\CloneHandler
	{
		return new PHPStan\Analyser\ExprHandler\CloneHandler;
	}


	public function createService095(): PHPStan\Analyser\ExprHandler\ThrowHandler
	{
		return new PHPStan\Analyser\ExprHandler\ThrowHandler;
	}


	public function createService096(): PHPStan\Analyser\ExprHandler\ScalarHandler
	{
		return new PHPStan\Analyser\ExprHandler\ScalarHandler($this->getService('0204'));
	}


	public function createService097(): PHPStan\Analyser\ExprHandler\ErrorSuppressHandler
	{
		return new PHPStan\Analyser\ExprHandler\ErrorSuppressHandler;
	}


	public function createService098(): PHPStan\Analyser\ExprHandler\UnaryMinusHandler
	{
		return new PHPStan\Analyser\ExprHandler\UnaryMinusHandler($this->getService('0204'));
	}


	public function createService099(): PHPStan\Analyser\ExprHandler\InterpolatedStringHandler
	{
		return new PHPStan\Analyser\ExprHandler\InterpolatedStringHandler($this->getService('0204'), $this->getService('0140'));
	}


	public function createService0100(): PHPStan\Analyser\ExprHandler\VariableHandler
	{
		return new PHPStan\Analyser\ExprHandler\VariableHandler;
	}


	public function createService0101(): PHPStan\Analyser\ExprHandler\IncludeHandler
	{
		return new PHPStan\Analyser\ExprHandler\IncludeHandler;
	}


	public function createService0102(): PHPStan\Analyser\ExprHandler\PipeHandler
	{
		return new PHPStan\Analyser\ExprHandler\PipeHandler;
	}


	public function createService0103(): PHPStan\Analyser\ExprHandler\NewHandler
	{
		return new PHPStan\Analyser\ExprHandler\NewHandler(
			$this->getService('reflectionProvider'),
			$this->getService('0190'),
			$this->getService('0188'),
			$this->getService('040'),
			true
		);
	}


	public function createService0104(): PHPStan\Analyser\ExprHandler\AssignOpHandler
	{
		return new PHPStan\Analyser\ExprHandler\AssignOpHandler(
			$this->getService('092'),
			$this->getService('0204'),
			$this->getService('0140')
		);
	}


	public function createService0105(): PHPStan\Analyser\ExprHandler\TernaryHandler
	{
		return new PHPStan\Analyser\ExprHandler\TernaryHandler($this->getService('085'));
	}


	public function createService0106(): PHPStan\Analyser\ExprHandler\UnaryPlusHandler
	{
		return new PHPStan\Analyser\ExprHandler\UnaryPlusHandler($this->getService('0204'));
	}


	public function createService0107(): PHPStan\Analyser\ExprHandler\FirstClassCallableNewHandler
	{
		return new PHPStan\Analyser\ExprHandler\FirstClassCallableNewHandler($this->getService('0204'));
	}


	public function createService0108(): PHPStan\Analyser\ExprHandler\PreIncHandler
	{
		return new PHPStan\Analyser\ExprHandler\PreIncHandler;
	}


	public function createService0109(): PHPStan\Analyser\ExprHandler\ArrowFunctionHandler
	{
		return new PHPStan\Analyser\ExprHandler\ArrowFunctionHandler($this->getService('0141'));
	}


	public function createService0110(): PHPStan\Analyser\ExprHandler\IssetHandler
	{
		return new PHPStan\Analyser\ExprHandler\IssetHandler($this->getService('0142'));
	}


	public function createService0111(): PHPStan\Analyser\ExprHandler\ClassConstFetchHandler
	{
		return new PHPStan\Analyser\ExprHandler\ClassConstFetchHandler($this->getService('0204'));
	}


	public function createService0112(): PHPStan\Analyser\ExprHandler\ExitHandler
	{
		return new PHPStan\Analyser\ExprHandler\ExitHandler;
	}


	public function createService0113(): PHPStan\Analyser\ExprHandler\FirstClassCallableMethodCallHandler
	{
		return new PHPStan\Analyser\ExprHandler\FirstClassCallableMethodCallHandler($this->getService('0204'));
	}


	public function createService0114(): PHPStan\Analyser\ExprHandler\BitwiseNotHandler
	{
		return new PHPStan\Analyser\ExprHandler\BitwiseNotHandler($this->getService('0204'));
	}


	public function createService0115(): PHPStan\Analyser\ExprHandler\MatchHandler
	{
		return new PHPStan\Analyser\ExprHandler\MatchHandler(true);
	}


	public function createService0116(): PHPStan\Analyser\ExprHandler\ClosureHandler
	{
		return new PHPStan\Analyser\ExprHandler\ClosureHandler($this->getService('0141'));
	}


	public function createService0117(): PHPStan\Analyser\ExprHandler\FirstClassCallableStaticCallHandler
	{
		return new PHPStan\Analyser\ExprHandler\FirstClassCallableStaticCallHandler($this->getService('0204'));
	}


	public function createService0118(): PHPStan\Analyser\ExprHandler\YieldFromHandler
	{
		return new PHPStan\Analyser\ExprHandler\YieldFromHandler;
	}


	public function createService0119(): PHPStan\Analyser\ExprHandler\PostDecHandler
	{
		return new PHPStan\Analyser\ExprHandler\PostDecHandler;
	}


	public function createService0120(): PHPStan\Analyser\ExprHandler\Virtual\UnsetOffsetExprHandler
	{
		return new PHPStan\Analyser\ExprHandler\Virtual\UnsetOffsetExprHandler;
	}


	public function createService0121(): PHPStan\Analyser\ExprHandler\Virtual\SetOffsetValueTypeExprHandler
	{
		return new PHPStan\Analyser\ExprHandler\Virtual\SetOffsetValueTypeExprHandler;
	}


	public function createService0122(): PHPStan\Analyser\ExprHandler\Virtual\GetIterableKeyTypeExprHandler
	{
		return new PHPStan\Analyser\ExprHandler\Virtual\GetIterableKeyTypeExprHandler;
	}


	public function createService0123(): PHPStan\Analyser\ExprHandler\Virtual\FunctionCallableNodeHandler
	{
		return new PHPStan\Analyser\ExprHandler\Virtual\FunctionCallableNodeHandler;
	}


	public function createService0124(): PHPStan\Analyser\ExprHandler\Virtual\OriginalPropertyTypeExprHandler
	{
		return new PHPStan\Analyser\ExprHandler\Virtual\OriginalPropertyTypeExprHandler($this->getService('040'));
	}


	public function createService0125(): PHPStan\Analyser\ExprHandler\Virtual\InstantiationCallableNodeHandler
	{
		return new PHPStan\Analyser\ExprHandler\Virtual\InstantiationCallableNodeHandler;
	}


	public function createService0126(): PHPStan\Analyser\ExprHandler\Virtual\SetExistingOffsetValueTypeExprHandler
	{
		return new PHPStan\Analyser\ExprHandler\Virtual\SetExistingOffsetValueTypeExprHandler;
	}


	public function createService0127(): PHPStan\Analyser\ExprHandler\Virtual\GetIterableValueTypeExprHandler
	{
		return new PHPStan\Analyser\ExprHandler\Virtual\GetIterableValueTypeExprHandler;
	}


	public function createService0128(): PHPStan\Analyser\ExprHandler\Virtual\GetOffsetValueTypeExprHandler
	{
		return new PHPStan\Analyser\ExprHandler\Virtual\GetOffsetValueTypeExprHandler;
	}


	public function createService0129(): PHPStan\Analyser\ExprHandler\Virtual\TypeExprHandler
	{
		return new PHPStan\Analyser\ExprHandler\Virtual\TypeExprHandler;
	}


	public function createService0130(): PHPStan\Analyser\ExprHandler\Virtual\StaticMethodCallableNodeHandler
	{
		return new PHPStan\Analyser\ExprHandler\Virtual\StaticMethodCallableNodeHandler;
	}


	public function createService0131(): PHPStan\Analyser\ExprHandler\Virtual\ExistingArrayDimFetchHandler
	{
		return new PHPStan\Analyser\ExprHandler\Virtual\ExistingArrayDimFetchHandler;
	}


	public function createService0132(): PHPStan\Analyser\ExprHandler\Virtual\AlwaysRememberedExprHandler
	{
		return new PHPStan\Analyser\ExprHandler\Virtual\AlwaysRememberedExprHandler;
	}


	public function createService0133(): PHPStan\Analyser\ExprHandler\Virtual\NativeTypeExprHandler
	{
		return new PHPStan\Analyser\ExprHandler\Virtual\NativeTypeExprHandler;
	}


	public function createService0134(): PHPStan\Analyser\ExprHandler\Virtual\MethodCallableNodeHandler
	{
		return new PHPStan\Analyser\ExprHandler\Virtual\MethodCallableNodeHandler;
	}


	public function createService0135(): PHPStan\Analyser\ExprHandler\CoalesceHandler
	{
		return new PHPStan\Analyser\ExprHandler\CoalesceHandler($this->getService('0142'));
	}


	public function createService0136(): PHPStan\Analyser\ExprHandler\BooleanOrHandler
	{
		return new PHPStan\Analyser\ExprHandler\BooleanOrHandler($this->getService('085'));
	}


	public function createService0137(): PHPStan\Analyser\ExprHandler\ArrayHandler
	{
		return new PHPStan\Analyser\ExprHandler\ArrayHandler($this->getService('0204'));
	}


	public function createService0138(): PHPStan\Analyser\ExprHandler\PostIncHandler
	{
		return new PHPStan\Analyser\ExprHandler\PostIncHandler;
	}


	public function createService0139(): PHPStan\Analyser\ExprHandler\BooleanAndHandler
	{
		return new PHPStan\Analyser\ExprHandler\BooleanAndHandler($this->getService('085'));
	}


	public function createService0140(): PHPStan\Analyser\ExprHandler\Helper\ImplicitToStringCallHelper
	{
		return new PHPStan\Analyser\ExprHandler\Helper\ImplicitToStringCallHelper($this->getService('0453'), $this->getService('0144'));
	}


	public function createService0141(): PHPStan\Analyser\ExprHandler\Helper\ClosureTypeResolver
	{
		return new PHPStan\Analyser\ExprHandler\Helper\ClosureTypeResolver($this->getService('085'));
	}


	public function createService0142(): PHPStan\Analyser\ExprHandler\Helper\NonNullabilityHelper
	{
		return new PHPStan\Analyser\ExprHandler\Helper\NonNullabilityHelper;
	}


	public function createService0143(): PHPStan\Analyser\ExprHandler\Helper\MethodCallReturnTypeHelper
	{
		return new PHPStan\Analyser\ExprHandler\Helper\MethodCallReturnTypeHelper($this->getService('0188'));
	}


	public function createService0144(): PHPStan\Analyser\ExprHandler\Helper\MethodThrowPointHelper
	{
		return new PHPStan\Analyser\ExprHandler\Helper\MethodThrowPointHelper($this->getService('0190'), true);
	}


	public function createService0145(): PHPStan\Analyser\ExprHandler\NullsafePropertyFetchHandler
	{
		return new PHPStan\Analyser\ExprHandler\NullsafePropertyFetchHandler($this->getService('0142'));
	}


	public function createService0146(): PHPStan\Analyser\ExprHandler\MethodCallHandler
	{
		return new PHPStan\Analyser\ExprHandler\MethodCallHandler($this->getService('0143'), $this->getService('0144'), true);
	}


	public function createService0147(): PHPStan\Analyser\ExprHandler\PropertyFetchHandler
	{
		return new PHPStan\Analyser\ExprHandler\PropertyFetchHandler($this->getService('0453'), $this->getService('040'));
	}


	public function createService0148(): PHPStan\Analyser\ExprHandler\CastStringHandler
	{
		return new PHPStan\Analyser\ExprHandler\CastStringHandler($this->getService('0204'), $this->getService('0140'));
	}


	public function createService0149(): PHPStan\Analyser\ExprHandler\ConstFetchHandler
	{
		return new PHPStan\Analyser\ExprHandler\ConstFetchHandler($this->getService('0159'));
	}


	public function createService0150(): PHPStan\Analyser\ExprHandler\BinaryOpHandler
	{
		return new PHPStan\Analyser\ExprHandler\BinaryOpHandler(
			$this->getService('0204'),
			$this->getService('0158'),
			$this->getService('0453'),
			$this->getService('0140')
		);
	}


	public function createService0151(): PHPStan\Analyser\ExprHandler\StaticCallHandler
	{
		return new PHPStan\Analyser\ExprHandler\StaticCallHandler($this->getService('0143'), $this->getService('0144'), true);
	}


	public function createService0152(): PHPStan\Analyser\ExprHandler\NullsafeMethodCallHandler
	{
		return new PHPStan\Analyser\ExprHandler\NullsafeMethodCallHandler($this->getService('0142'));
	}


	public function createService0153(): PHPStan\Analyser\ExprHandler\BooleanNotHandler
	{
		return new PHPStan\Analyser\ExprHandler\BooleanNotHandler;
	}


	public function createService0154(): PHPStan\Analyser\ExprHandler\FuncCallHandler
	{
		return new PHPStan\Analyser\ExprHandler\FuncCallHandler(
			$this->getService('reflectionProvider'),
			$this->getService('0190'),
			$this->getService('0188'),
			true,
			true
		);
	}


	public function createService0155(): PHPStan\Analyser\ExprHandler\PrintHandler
	{
		return new PHPStan\Analyser\ExprHandler\PrintHandler($this->getService('0140'));
	}


	public function createService0156(): PHPStan\Analyser\ExprHandler\InstanceofHandler
	{
		return new PHPStan\Analyser\ExprHandler\InstanceofHandler;
	}


	public function createService0157(): PHPStan\Analyser\ExprHandler\ArrayDimFetchHandler
	{
		return new PHPStan\Analyser\ExprHandler\ArrayDimFetchHandler;
	}


	public function createService0158(): PHPStan\Analyser\RicherScopeGetTypeHelper
	{
		return new PHPStan\Analyser\RicherScopeGetTypeHelper($this->getService('0204'), $this->getService('040'));
	}


	public function createService0159(): PHPStan\Analyser\ConstantResolver
	{
		return $this->getService('0166')->create();
	}


	public function createService0160(): PHPStan\Analyser\ScopeFactory
	{
		return new PHPStan\Analyser\ScopeFactory($this->getService('0455'));
	}


	public function createService0161(): PHPStan\Analyser\FileAnalyser
	{
		return new PHPStan\Analyser\FileAnalyser(
			$this->getService('0160'),
			$this->getService('085'),
			$this->getService('defaultAnalysisParser'),
			$this->getService('0178'),
			$this->getService('0164'),
			$this->getService('084'),
			$this->getService('0162'),
			false
		);
	}


	public function createService0162(): PHPStan\Analyser\LocalIgnoresProcessor
	{
		return new PHPStan\Analyser\LocalIgnoresProcessor;
	}


	public function createService0163(): PHPStan\Analyser\Analyser
	{
		return new PHPStan\Analyser\Analyser(
			$this->getService('0161'),
			$this->getService('registry'),
			$this->getService('0220'),
			$this->getService('085'),
			50
		);
	}


	public function createService0164(): PHPStan\Analyser\IgnoreErrorExtensionProvider
	{
		return new PHPStan\Analyser\IgnoreErrorExtensionProvider($this->getService('0179'));
	}


	public function createService0165(): PHPStan\Analyser\ResultCache\ResultCacheClearer
	{
		return new PHPStan\Analyser\ResultCache\ResultCacheClearer('/home/runner/work/phpstan-src/phpstan-src/tmp/resultCache.php');
	}


	public function createService0166(): PHPStan\Analyser\ConstantResolverFactory
	{
		return new PHPStan\Analyser\ConstantResolverFactory($this->getService('0193'), $this->getService('0179'));
	}


	public function createService0167(): PHPStan\Analyser\NodeScopeResolver
	{
		return new PHPStan\Analyser\NodeScopeResolver(
			$this->getService('0179'),
			$this->getService('reflectionProvider'),
			$this->getService('0204'),
			$this->getService('nodeScopeResolverReflector'),
			$this->getService('0459'),
			$this->getService('0185'),
			$this->getService('defaultAnalysisParser'),
			$this->getService('0449'),
			$this->getService('0222'),
			$this->getService('0173'),
			$this->getService('typeSpecifier'),
			$this->getService('043'),
			$this->getService('0187'),
			$this->getService('0184'),
			$this->getService('0160'),
			$this->getService('0172'),
			false,
			false,
			false,
			['PHPUnit\Framework\Assert' => ['fail', 'markTestIncomplete', 'markTestSkipped']],
			[],
			true,
			true,
			$this->getService('0140')
		);
	}


	public function createService0168(): PHPStan\Analyser\Ignore\IgnoredErrorHelper
	{
		return new PHPStan\Analyser\Ignore\IgnoredErrorHelper(
			$this->getService('0173'),
			[
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/build/PHPStan/Build/ContainerDynamicReturnTypeExtension.php',
				],
				[
					'rawMessage' => 'Method PHPStan\Analyser\AnalyserResultFinalizer::finalize() throws checked exception Throwable but it\'s missing from the PHPDoc @throws tag.',
					'identifier' => 'missingType.checkedException',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Analyser/AnalyserResultFinalizer.php',
				],
				[
					'rawMessage' => 'PHPDoc tag @var with type int|string is not subtype of type string.',
					'identifier' => 'varTag.type',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Analyser/ArgumentsNormalizer.php',
				],
				[
					'rawMessage' => 'Casting to string something that\'s already string.',
					'identifier' => 'cast.useless',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Analyser/ExprHandler/AssignHandler.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Analyser/ExprHandler/AssignHandler.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantBooleanType is error-prone and deprecated. Use Type::isTrue() or Type::isFalse() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Analyser/ExprHandler/BinaryOpHandler.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantBooleanType is error-prone and deprecated. Use Type::isTrue() or Type::isFalse() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Analyser/ExprHandler/BooleanNotHandler.php',
				],
				[
					'rawMessage' => 'Only numeric types are allowed in pre-increment, float|int|string|null given.',
					'identifier' => 'preInc.nonNumeric',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Analyser/ExprHandler/PreIncHandler.php',
				],
				[
					'rawMessage' => 'Cannot assign offset \'realCount\' to array<mixed>|string.',
					'identifier' => 'offsetAssign.dimType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Analyser/Ignore/IgnoredErrorHelperResult.php',
				],
				[
					'rawMessage' => 'Casting to string something that\'s already string.',
					'identifier' => 'cast.useless',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Analyser/MutatingScope.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantBooleanType is error-prone and deprecated. Use Type::isTrue() or Type::isFalse() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Analyser/MutatingScope.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Analyser/MutatingScope.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Analyser/MutatingScope.php',
				],
				[
					'rawMessage' => 'Parameter #2 $node of method PHPStan\BetterReflection\SourceLocator\Ast\Strategy\NodeToReflection::__invoke() expects PhpParser\Node\Expr\ArrowFunction|PhpParser\Node\Expr\Closure|PhpParser\Node\Expr\FuncCall|PhpParser\Node\Stmt\Class_|PhpParser\Node\Stmt\Const_|PhpParser\Node\Stmt\Enum_|PhpParser\Node\Stmt\Function_|PhpParser\Node\Stmt\Interface_|PhpParser\Node\Stmt\Trait_, PhpParser\Node\Stmt\ClassLike given.',
					'identifier' => 'argument.type',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Analyser/NodeScopeResolver.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantBooleanType is error-prone and deprecated. Use Type::isTrue() or Type::isFalse() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Analyser/RicherScopeGetTypeHelper.php',
				],
				[
					'rawMessage' => 'Call to method __construct() of internal class PhpParser\Internal\TokenStream from outside its root namespace PhpParser.',
					'identifier' => 'method.internalClass',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Analyser/RuleErrorTransformer.php',
				],
				[
					'rawMessage' => 'Instantiation of internal class PhpParser\Internal\TokenStream.',
					'identifier' => 'new.internalClass',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Analyser/RuleErrorTransformer.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ConstantScalarType is error-prone and deprecated. Use Type::isConstantScalarValue() or Type::getConstantScalarTypes() or Type::getConstantScalarValues() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Analyser/TypeSpecifier.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantBooleanType is error-prone and deprecated. Use Type::isTrue() or Type::isFalse() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 5,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Analyser/TypeSpecifier.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 5,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Analyser/TypeSpecifier.php',
				],
				[
					'rawMessage' => 'Template type TNodeType is declared as covariant, but occurs in contravariant position in parameter node of method PHPStan\Collectors\Collector::processNode().',
					'identifier' => 'generics.variance',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Collectors/Collector.php',
				],
				[
					'rawMessage' => 'Method PHPStan\Collectors\Registry::__construct() has parameter $collectors with generic interface PHPStan\Collectors\Collector but does not specify its types: TNodeType, TValue',
					'identifier' => 'missingType.generics',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Collectors/Registry.php',
				],
				[
					'rawMessage' => 'Property PHPStan\Collectors\Registry::$cache with generic interface PHPStan\Collectors\Collector does not specify its types: TNodeType, TValue',
					'identifier' => 'missingType.generics',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Collectors/Registry.php',
				],
				[
					'rawMessage' => 'Property PHPStan\Collectors\Registry::$collectors with generic interface PHPStan\Collectors\Collector does not specify its types: TNodeType, TValue',
					'identifier' => 'missingType.generics',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Collectors/Registry.php',
				],
				[
					'rawMessage' => 'Anonymous function has an unused use $container.',
					'identifier' => 'closure.unusedUse',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Command/CommandHelper.php',
				],
				[
					'rawMessage' => 'Call to static method expand() of internal class Nette\DI\Helpers from outside its root namespace Nette.',
					'identifier' => 'staticMethod.internalClass',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Command/CommandHelper.php',
				],
				[
					'rawMessage' => 'Parameter #1 $path of function dirname expects string, string|false given.',
					'identifier' => 'argument.type',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Command/CommandHelper.php',
				],
				[
					'rawMessage' => 'Static property PHPStan\Command\CommandHelper::$reservedMemory is never read, only written.',
					'identifier' => 'property.onlyWritten',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Command/CommandHelper.php',
				],
				[
					'rawMessage' => 'Call to static method escape() of internal class Nette\DI\Helpers from outside its root namespace Nette.',
					'identifier' => 'staticMethod.internalClass',
					'count' => 4,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Command/ErrorFormatter/BaselineNeonErrorFormatter.php',
				],
				[
					'rawMessage' => 'Call to static method escape() of internal class Nette\DI\Helpers from outside its root namespace Nette.',
					'identifier' => 'staticMethod.internalClass',
					'count' => 5,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Command/ErrorFormatter/BaselinePhpErrorFormatter.php',
				],
				[
					'rawMessage' => 'Parameter #1 $headers (array<string>) of method PHPStan\Command\ErrorsConsoleStyle::table() should be contravariant with parameter $headers (array) of method Symfony\Component\Console\Style\StyleInterface::table()',
					'identifier' => 'method.childParameterType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Command/ErrorsConsoleStyle.php',
				],
				[
					'rawMessage' => 'Parameter #1 $headers (array<string>) of method PHPStan\Command\ErrorsConsoleStyle::table() should be contravariant with parameter $headers (array) of method Symfony\Component\Console\Style\SymfonyStyle::table()',
					'identifier' => 'method.childParameterType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Command/ErrorsConsoleStyle.php',
				],
				[
					'rawMessage' => 'Parameter #2 $rows (array<array<string>>) of method PHPStan\Command\ErrorsConsoleStyle::table() should be contravariant with parameter $rows (array) of method Symfony\Component\Console\Style\StyleInterface::table()',
					'identifier' => 'method.childParameterType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Command/ErrorsConsoleStyle.php',
				],
				[
					'rawMessage' => 'Parameter #2 $rows (array<array<string>>) of method PHPStan\Command\ErrorsConsoleStyle::table() should be contravariant with parameter $rows (array) of method Symfony\Component\Console\Style\SymfonyStyle::table()',
					'identifier' => 'method.childParameterType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Command/ErrorsConsoleStyle.php',
				],
				[
					'rawMessage' => 'Call to static method escape() of internal class Nette\DI\Helpers from outside its root namespace Nette.',
					'identifier' => 'staticMethod.internalClass',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/DependencyInjection/AutowiredAttributeServicesExtension.php',
				],
				[
					'rawMessage' => 'Call to static method expand() of internal class Nette\DI\Helpers from outside its root namespace Nette.',
					'identifier' => 'staticMethod.internalClass',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/DependencyInjection/AutowiredAttributeServicesExtension.php',
				],
				[
					'rawMessage' => 'Call to static method expand() of internal class Nette\DI\Helpers from outside its root namespace Nette.',
					'identifier' => 'staticMethod.internalClass',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/DependencyInjection/ContainerFactory.php',
				],
				[
					'rawMessage' => 'Call to static method merge() of internal class Nette\Schema\Helpers from outside its root namespace Nette.',
					'identifier' => 'staticMethod.internalClass',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/DependencyInjection/ContainerFactory.php',
				],
				[
					'rawMessage' => 'Variable method call on Nette\Schema\Elements\AnyOf|Nette\Schema\Elements\Structure|Nette\Schema\Elements\Type.',
					'identifier' => 'method.dynamicName',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/DependencyInjection/ContainerFactory.php',
				],
				[
					'rawMessage' => 'Variable static method call on Nette\Schema\Expect.',
					'identifier' => 'staticMethod.dynamicName',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/DependencyInjection/ContainerFactory.php',
				],
				[
					'rawMessage' => 'Fetching class constant PREVENT_MERGING of deprecated class Nette\DI\Config\Helpers.',
					'identifier' => 'classConstant.deprecatedClass',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/DependencyInjection/NeonAdapter.php',
				],
				[
					'rawMessage' => 'Parameter #1 $path of function dirname expects string, string|false given.',
					'identifier' => 'argument.type',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Diagnose/PHPStanDiagnoseExtension.php',
				],
				[
					'rawMessage' => 'Call to method getContent() of internal class PhpMerge\internal\Line from outside its root namespace PhpMerge.',
					'identifier' => 'method.internalClass',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Fixable/Patcher.php',
				],
				[
					'rawMessage' => 'Call to static method createArray() of internal class PhpMerge\internal\Hunk from outside its root namespace PhpMerge.',
					'identifier' => 'staticMethod.internalClass',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Fixable/Patcher.php',
				],
				[
					'rawMessage' => 'Call to static method createArray() of internal class PhpMerge\internal\Line from outside its root namespace PhpMerge.',
					'identifier' => 'staticMethod.internalClass',
					'count' => 5,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Fixable/Patcher.php',
				],
				[
					'rawMessage' => 'Call to method getTokenCode() of internal class PhpParser\Internal\TokenStream from outside its root namespace PhpParser.',
					'identifier' => 'method.internalClass',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Fixable/PhpPrinterIndentationDetectorVisitor.php',
				],
				[
					'rawMessage' => 'Parameter $origTokens of method PHPStan\Fixable\PhpPrinterIndentationDetectorVisitor::__construct() has typehint with internal class PhpParser\Internal\TokenStream.',
					'identifier' => 'parameter.internalClass',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Fixable/PhpPrinterIndentationDetectorVisitor.php',
				],
				[
					'rawMessage' => 'Property $origTokens references internal class PhpParser\Internal\TokenStream in its type.',
					'identifier' => 'property.internalClass',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Fixable/PhpPrinterIndentationDetectorVisitor.php',
				],
				[
					'rawMessage' => 'Call to function method_exists() with PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode and \'getParamOutTypeTagV…\' will always evaluate to true.',
					'identifier' => 'function.alreadyNarrowedType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/PhpDoc/PhpDocNodeResolver.php',
				],
				[
					'rawMessage' => 'Call to function method_exists() with PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode and \'getSelfOutTypeTagVa…\' will always evaluate to true.',
					'identifier' => 'function.alreadyNarrowedType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/PhpDoc/PhpDocNodeResolver.php',
				],
				[
					'rawMessage' => 'Method PHPStan\PhpDoc\ResolvedPhpDocBlock::getNameScope() should return PHPStan\Analyser\NameScope but returns PHPStan\Analyser\NameScope|null.',
					'identifier' => 'return.type',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/PhpDoc/ResolvedPhpDocBlock.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ArrayType is error-prone and deprecated. Use Type::isArray() or Type::getArrays() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/PhpDoc/TypeNodeResolver.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\CallableType is error-prone and deprecated. Use Type::isCallable() and Type::getCallableParametersAcceptors() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/PhpDoc/TypeNodeResolver.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantArrayType is error-prone and deprecated. Use Type::getConstantArrays() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/PhpDoc/TypeNodeResolver.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/PhpDoc/TypeNodeResolver.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Generic\GenericObjectType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/PhpDoc/TypeNodeResolver.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IterableType is error-prone and deprecated. Use Type::isIterable() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/PhpDoc/TypeNodeResolver.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ObjectType is error-prone and deprecated. Use Type::isObject() or Type::getObjectClassNames() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/PhpDoc/TypeNodeResolver.php',
				],
				[
					'rawMessage' => 'Dead catch - PHPStan\BetterReflection\Identifier\Exception\InvalidIdentifierName is never thrown in the try block.',
					'identifier' => 'catch.neverThrown',
					'count' => 4,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/BetterReflection/BetterReflectionProvider.php',
				],
				[
					'rawMessage' => 'Dead catch - PHPStan\BetterReflection\NodeCompiler\Exception\UnableToCompileNode is never thrown in the try block.',
					'identifier' => 'catch.neverThrown',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/BetterReflection/BetterReflectionProvider.php',
				],
				[
					'rawMessage' => 'Creating new ReflectionClass is a runtime reflection concept that might not work in PHPStan because it uses fully static reflection engine. Use objects retrieved from ReflectionProvider instead.',
					'identifier' => 'phpstanApi.runtimeReflection',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/BetterReflection/SourceLocator/AutoloadSourceLocator.php',
				],
				[
					'rawMessage' => 'Creating new ReflectionFunction is a runtime reflection concept that might not work in PHPStan because it uses fully static reflection engine. Use objects retrieved from ReflectionProvider instead.',
					'identifier' => 'phpstanApi.runtimeReflection',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/BetterReflection/SourceLocator/AutoloadSourceLocator.php',
				],
				[
					'rawMessage' => 'Parameter #2 $node of method PHPStan\BetterReflection\SourceLocator\Ast\Strategy\NodeToReflection::__invoke() expects PhpParser\Node\Expr\ArrowFunction|PhpParser\Node\Expr\Closure|PhpParser\Node\Expr\FuncCall|PhpParser\Node\Stmt\Class_|PhpParser\Node\Stmt\Const_|PhpParser\Node\Stmt\Enum_|PhpParser\Node\Stmt\Function_|PhpParser\Node\Stmt\Interface_|PhpParser\Node\Stmt\Trait_, PhpParser\Node\Stmt\ClassLike given.',
					'identifier' => 'argument.type',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/BetterReflection/SourceLocator/AutoloadSourceLocator.php',
				],
				[
					'rawMessage' => 'Method PHPStan\Reflection\BetterReflection\SourceLocator\FileReadTrapStreamWrapper::invokeWithRealFileStreamWrapper() has parameter $cb with no signature specified for callable.',
					'identifier' => 'missingType.callable',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/BetterReflection/SourceLocator/FileReadTrapStreamWrapper.php',
				],
				[
					'rawMessage' => 'Parameter #2 $node of method PHPStan\BetterReflection\SourceLocator\Ast\Strategy\NodeToReflection::__invoke() expects PhpParser\Node\Expr\ArrowFunction|PhpParser\Node\Expr\Closure|PhpParser\Node\Expr\FuncCall|PhpParser\Node\Stmt\Class_|PhpParser\Node\Stmt\Const_|PhpParser\Node\Stmt\Enum_|PhpParser\Node\Stmt\Function_|PhpParser\Node\Stmt\Interface_|PhpParser\Node\Stmt\Trait_, PhpParser\Node\Expr\FuncCall|PhpParser\Node\Stmt\ClassLike|PhpParser\Node\Stmt\Const_|PhpParser\Node\Stmt\Function_ given.',
					'identifier' => 'argument.type',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/BetterReflection/SourceLocator/OptimizedDirectorySourceLocator.php',
				],
				[
					'rawMessage' => 'Parameter #2 $node of method PHPStan\BetterReflection\SourceLocator\Ast\Strategy\NodeToReflection::__invoke() expects PhpParser\Node\Expr\ArrowFunction|PhpParser\Node\Expr\Closure|PhpParser\Node\Expr\FuncCall|PhpParser\Node\Stmt\Class_|PhpParser\Node\Stmt\Const_|PhpParser\Node\Stmt\Enum_|PhpParser\Node\Stmt\Function_|PhpParser\Node\Stmt\Interface_|PhpParser\Node\Stmt\Trait_, PhpParser\Node\Stmt\ClassLike given.',
					'identifier' => 'argument.type',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/BetterReflection/SourceLocator/OptimizedSingleFileSourceLocator.php',
				],
				[
					'rawMessage' => 'Creating new ReflectionClass is a runtime reflection concept that might not work in PHPStan because it uses fully static reflection engine. Use objects retrieved from ReflectionProvider instead.',
					'identifier' => 'phpstanApi.runtimeReflection',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/BetterReflection/SourceLocator/ReflectionClassSourceLocator.php',
				],
				[
					'rawMessage' => 'Creating new ReflectionClass is a runtime reflection concept that might not work in PHPStan because it uses fully static reflection engine. Use objects retrieved from ReflectionProvider instead.',
					'identifier' => 'phpstanApi.runtimeReflection',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/BetterReflection/SourceLocator/RewriteClassAliasSourceLocator.php',
				],
				[
					'rawMessage' => 'Creating new ReflectionClass is a runtime reflection concept that might not work in PHPStan because it uses fully static reflection engine. Use objects retrieved from ReflectionProvider instead.',
					'identifier' => 'phpstanApi.runtimeReflection',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/BetterReflection/SourceLocator/SkipClassAliasSourceLocator.php',
				],
				[
					'rawMessage' => "Call to deprecated method isSubclassOf() of class PHPStan\\Reflection\\ClassReflection:\nUse isSubclassOfClass instead.",
					'identifier' => 'method.deprecated',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/ClassReflection.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Generic\GenericObjectType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/ClassReflection.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ObjectType is error-prone and deprecated. Use Type::isObject() or Type::getObjectClassNames() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/ClassReflection.php',
				],
				[
					'rawMessage' => 'Binary operation "&" between bool|float|int|string|null and bool|float|int|string|null results in an error.',
					'identifier' => 'binaryOp.invalid',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/InitializerExprTypeResolver.php',
				],
				[
					'rawMessage' => 'Binary operation "*" between bool|float|int|string|null and bool|float|int|string|null results in an error.',
					'identifier' => 'binaryOp.invalid',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/InitializerExprTypeResolver.php',
				],
				[
					'rawMessage' => 'Binary operation "+" between bool|float|int|string|null and bool|float|int|string|null results in an error.',
					'identifier' => 'binaryOp.invalid',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/InitializerExprTypeResolver.php',
				],
				[
					'rawMessage' => 'Binary operation "-" between bool|float|int|string|null and bool|float|int|string|null results in an error.',
					'identifier' => 'binaryOp.invalid',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/InitializerExprTypeResolver.php',
				],
				[
					'rawMessage' => 'Binary operation "^" between bool|float|int|string|null and bool|float|int|string|null results in an error.',
					'identifier' => 'binaryOp.invalid',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/InitializerExprTypeResolver.php',
				],
				[
					'rawMessage' => 'Binary operation "|" between bool|float|int|string|null and bool|float|int|string|null results in an error.',
					'identifier' => 'binaryOp.invalid',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/InitializerExprTypeResolver.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ConstantScalarType is error-prone and deprecated. Use Type::isConstantScalarValue() or Type::getConstantScalarTypes() or Type::getConstantScalarValues() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 18,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/InitializerExprTypeResolver.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantArrayType is error-prone and deprecated. Use Type::getConstantArrays() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 4,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/InitializerExprTypeResolver.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantBooleanType is error-prone and deprecated. Use Type::isTrue() or Type::isFalse() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/InitializerExprTypeResolver.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 6,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/InitializerExprTypeResolver.php',
				],
				[
					'rawMessage' => 'PHPDoc tag @var with type float|int is not subtype of native type int.',
					'identifier' => 'varTag.nativeType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/InitializerExprTypeResolver.php',
				],
				[
					'rawMessage' => 'PHPDoc tag @var with type float|int is not subtype of type int.',
					'identifier' => 'varTag.type',
					'count' => 4,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/InitializerExprTypeResolver.php',
				],
				[
					'rawMessage' => 'PHPDoc tag @var with type float|int|null is not subtype of type int|null.',
					'identifier' => 'varTag.type',
					'count' => 6,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/InitializerExprTypeResolver.php',
				],
				[
					'rawMessage' => 'Creating new PHPStan\Php8StubsMap is not covered by backward compatibility promise. The class might change in a minor PHPStan version.',
					'identifier' => 'phpstanApi.constructor',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/SignatureMap/Php8SignatureMapProvider.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantBooleanType is error-prone and deprecated. Use Type::isTrue() or Type::isFalse() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/Classes/ImpossibleInstanceOfRule.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ObjectType is error-prone and deprecated. Use Type::isObject() or Type::getObjectClassNames() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/Classes/RequireImplementsRule.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantBooleanType is error-prone and deprecated. Use Type::isTrue() or Type::isFalse() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 6,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/Comparison/BooleanAndConstantConditionRule.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantBooleanType is error-prone and deprecated. Use Type::isTrue() or Type::isFalse() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/Comparison/BooleanNotConstantConditionRule.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantBooleanType is error-prone and deprecated. Use Type::isTrue() or Type::isFalse() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 6,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/Comparison/BooleanOrConstantConditionRule.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantBooleanType is error-prone and deprecated. Use Type::isTrue() or Type::isFalse() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/Comparison/DoWhileLoopConstantConditionRule.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantBooleanType is error-prone and deprecated. Use Type::isTrue() or Type::isFalse() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/Comparison/ElseIfConstantConditionRule.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantBooleanType is error-prone and deprecated. Use Type::isTrue() or Type::isFalse() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/Comparison/IfConstantConditionRule.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantArrayType is error-prone and deprecated. Use Type::getConstantArrays() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/Comparison/ImpossibleCheckTypeHelper.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantBooleanType is error-prone and deprecated. Use Type::isTrue() or Type::isFalse() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/Comparison/ImpossibleCheckTypeHelper.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/Comparison/ImpossibleCheckTypeHelper.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\TypeWithClassName is error-prone and deprecated. Use Type::getObjectClassNames() or Type::getObjectClassReflections() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/Comparison/ImpossibleCheckTypeHelper.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantBooleanType is error-prone and deprecated. Use Type::isTrue() or Type::isFalse() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 4,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/Comparison/LogicalXorConstantConditionRule.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantBooleanType is error-prone and deprecated. Use Type::isTrue() or Type::isFalse() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 4,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/Comparison/MatchExpressionRule.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantBooleanType is error-prone and deprecated. Use Type::isTrue() or Type::isFalse() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/Comparison/NumberComparisonOperatorsConstantConditionRule.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantBooleanType is error-prone and deprecated. Use Type::isTrue() or Type::isFalse() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/Comparison/StrictComparisonOfDifferentTypesRule.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantBooleanType is error-prone and deprecated. Use Type::isTrue() or Type::isFalse() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/Comparison/TernaryOperatorConstantConditionRule.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantBooleanType is error-prone and deprecated. Use Type::isTrue() or Type::isFalse() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/Comparison/WhileLoopAlwaysFalseConditionRule.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantBooleanType is error-prone and deprecated. Use Type::isTrue() or Type::isFalse() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/Comparison/WhileLoopAlwaysTrueConditionRule.php',
				],
				[
					'rawMessage' => 'Function class_implements() is a runtime reflection concept that might not work in PHPStan because it uses fully static reflection engine. Use objects retrieved from ReflectionProvider instead.',
					'identifier' => 'phpstanApi.runtimeReflection',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/DirectRegistry.php',
				],
				[
					'rawMessage' => 'Function class_parents() is a runtime reflection concept that might not work in PHPStan because it uses fully static reflection engine. Use objects retrieved from ReflectionProvider instead.',
					'identifier' => 'phpstanApi.runtimeReflection',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/DirectRegistry.php',
				],
				[
					'rawMessage' => 'Method PHPStan\Rules\DirectRegistry::__construct() has parameter $rules with generic interface PHPStan\Rules\Rule but does not specify its types: TNodeType',
					'identifier' => 'missingType.generics',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/DirectRegistry.php',
				],
				[
					'rawMessage' => 'Property PHPStan\Rules\DirectRegistry::$cache with generic interface PHPStan\Rules\Rule does not specify its types: TNodeType',
					'identifier' => 'missingType.generics',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/DirectRegistry.php',
				],
				[
					'rawMessage' => 'Property PHPStan\Rules\DirectRegistry::$rules with generic interface PHPStan\Rules\Rule does not specify its types: TNodeType',
					'identifier' => 'missingType.generics',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/DirectRegistry.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Generic\GenericObjectType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/Generics/GenericAncestorsCheck.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/Generics/TemplateTypeCheck.php',
				],
				[
					'rawMessage' => 'Function class_implements() is a runtime reflection concept that might not work in PHPStan because it uses fully static reflection engine. Use objects retrieved from ReflectionProvider instead.',
					'identifier' => 'phpstanApi.runtimeReflection',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/LazyRegistry.php',
				],
				[
					'rawMessage' => 'Function class_parents() is a runtime reflection concept that might not work in PHPStan because it uses fully static reflection engine. Use objects retrieved from ReflectionProvider instead.',
					'identifier' => 'phpstanApi.runtimeReflection',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/LazyRegistry.php',
				],
				[
					'rawMessage' => 'Method PHPStan\Rules\LazyRegistry::getRulesFromContainer() return type with generic interface PHPStan\Rules\Rule does not specify its types: TNodeType',
					'identifier' => 'missingType.generics',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/LazyRegistry.php',
				],
				[
					'rawMessage' => 'Property PHPStan\Rules\LazyRegistry::$cache with generic interface PHPStan\Rules\Rule does not specify its types: TNodeType',
					'identifier' => 'missingType.generics',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/LazyRegistry.php',
				],
				[
					'rawMessage' => 'Property PHPStan\Rules\LazyRegistry::$rules with generic interface PHPStan\Rules\Rule does not specify its types: TNodeType',
					'identifier' => 'missingType.generics',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/LazyRegistry.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ArrayType is error-prone and deprecated. Use Type::isArray() or Type::getArrays() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/Methods/MethodParameterComparisonHelper.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantArrayType is error-prone and deprecated. Use Type::getConstantArrays() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/Methods/MethodParameterComparisonHelper.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IterableType is error-prone and deprecated. Use Type::isIterable() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/Methods/MethodParameterComparisonHelper.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Generic\GenericClassStringType is error-prone and deprecated. Use Type::isClassStringType() and Type::getClassStringObjectType() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/Methods/StaticMethodCallCheck.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Generic\GenericObjectType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/PhpDoc/VarTagTypeRuleHelper.php',
				],
				[
					'rawMessage' => 'Access to an undefined property T of PHPStan\Rules\RuleError::$tip.',
					'identifier' => 'property.notFound',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/RuleErrorBuilder.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/RuleLevelHelper.php',
				],
				[
					'rawMessage' => 'Call to function method_exists() with \'PHPUnit\\\Framework\\\TestCase\' and \'assertFileDoesNotEx…\' will always evaluate to true.',
					'identifier' => 'function.alreadyNarrowedType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Testing/LevelsTestCase.php',
				],
				[
					'rawMessage' => 'Catching internal class PHPUnit\Framework\AssertionFailedError.',
					'identifier' => 'catch.internalClass',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Testing/LevelsTestCase.php',
				],
				[
					'rawMessage' => 'Return type of method PHPStan\Testing\LevelsTestCase::compareFiles() has typehint with internal class PHPUnit\Framework\AssertionFailedError.',
					'identifier' => 'return.internalClass',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Testing/LevelsTestCase.php',
				],
				[
					'rawMessage' => 'Anonymous function has an unused use $container.',
					'identifier' => 'closure.unusedUse',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Testing/PHPStanTestCase.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ConstantScalarType is error-prone and deprecated. Use Type::isConstantScalarValue() or Type::getConstantScalarTypes() or Type::getConstantScalarValues() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Testing/TypeInferenceTestCase.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Accessory/AccessoryArrayListType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Accessory/AccessoryLiteralStringType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Accessory/AccessoryLowercaseStringType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Accessory/AccessoryNonEmptyStringType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Accessory/AccessoryNonEmptyStringType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Accessory/AccessoryNonFalsyStringType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Accessory/AccessoryNumericStringType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Accessory/AccessoryNumericStringType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Accessory/AccessoryUppercaseStringType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Accessory/HasMethodType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Accessory/HasOffsetType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Accessory/HasOffsetType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ConstantScalarType is error-prone and deprecated. Use Type::isConstantScalarValue() or Type::getConstantScalarTypes() or Type::getConstantScalarValues() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Accessory/HasOffsetValueType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Accessory/HasOffsetValueType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Accessory/HasOffsetValueType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Accessory/HasPropertyType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Accessory/NonEmptyArrayType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Accessory/OversizedArrayType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ArrayType is error-prone and deprecated. Use Type::isArray() or Type::getArrays() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/ArrayType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantArrayType is error-prone and deprecated. Use Type::getConstantArrays() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/ArrayType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/ArrayType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/ArrayType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\BooleanType is error-prone and deprecated. Use Type::isBoolean() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/BooleanType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantBooleanType is error-prone and deprecated. Use Type::isTrue() or Type::isFalse() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/BooleanType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\CallableType is error-prone and deprecated. Use Type::isCallable() and Type::getCallableParametersAcceptors() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 4,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/CallableType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/CallableType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/ClosureType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ArrayType is error-prone and deprecated. Use Type::isArray() or Type::getArrays() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Constant/ConstantArrayType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ConstantScalarType is error-prone and deprecated. Use Type::isConstantScalarValue() or Type::getConstantScalarTypes() or Type::getConstantScalarValues() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Constant/ConstantArrayType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantArrayType is error-prone and deprecated. Use Type::getConstantArrays() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 6,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Constant/ConstantArrayType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Constant/ConstantArrayType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Constant/ConstantArrayType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Constant/ConstantArrayTypeBuilder.php',
				],
				[
					'rawMessage' => 'PHPDoc tag @var assumes the expression with type PHPStan\Type\Type is always PHPStan\Type\Constant\ConstantIntegerType|PHPStan\Type\Constant\ConstantStringType but it\'s error-prone and dangerous.',
					'identifier' => 'phpstanApi.varTagAssumption',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Constant/ConstantArrayTypeBuilder.php',
				],
				[
					'rawMessage' => 'PHPDoc tag @var with type float|int is not subtype of native type int.',
					'identifier' => 'varTag.nativeType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Constant/ConstantArrayTypeBuilder.php',
				],
				[
					'rawMessage' => 'PHPDoc tag @var with type float|int is not subtype of type int.',
					'identifier' => 'varTag.type',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Constant/ConstantArrayTypeBuilder.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\BooleanType is error-prone and deprecated. Use Type::isBoolean() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Constant/ConstantBooleanType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ConstantScalarType is error-prone and deprecated. Use Type::isConstantScalarValue() or Type::getConstantScalarTypes() or Type::getConstantScalarValues() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 4,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Constant/ConstantBooleanType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantBooleanType is error-prone and deprecated. Use Type::isTrue() or Type::isFalse() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Constant/ConstantBooleanType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ConstantScalarType is error-prone and deprecated. Use Type::isConstantScalarValue() or Type::getConstantScalarTypes() or Type::getConstantScalarValues() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 4,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Constant/ConstantFloatType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\FloatType is error-prone and deprecated. Use Type::isFloat() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Constant/ConstantFloatType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ConstantScalarType is error-prone and deprecated. Use Type::isConstantScalarValue() or Type::getConstantScalarTypes() or Type::getConstantScalarValues() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 4,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Constant/ConstantIntegerType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntegerType is error-prone and deprecated. Use Type::isInteger() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Constant/ConstantIntegerType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ClassStringType is error-prone and deprecated. Use Type::isClassStringType() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Constant/ConstantStringType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ConstantScalarType is error-prone and deprecated. Use Type::isConstantScalarValue() or Type::getConstantScalarTypes() or Type::getConstantScalarValues() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 4,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Constant/ConstantStringType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 4,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Constant/ConstantStringType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Generic\GenericClassStringType is error-prone and deprecated. Use Type::isClassStringType() and Type::getClassStringObjectType() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Constant/ConstantStringType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\StringType is error-prone and deprecated. Use Type::isString() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Constant/ConstantStringType.php',
				],
				[
					'rawMessage' => 'PHPDoc tag @var with type int|string is not subtype of type string.',
					'identifier' => 'varTag.type',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Constant/ConstantStringType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantArrayType is error-prone and deprecated. Use Type::getConstantArrays() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Constant/OversizedArrayBuilder.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Enum\EnumCaseObjectType is error-prone and deprecated. Use Type::getEnumCases() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Enum/EnumCaseObjectType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ConstantScalarType is error-prone and deprecated. Use Type::isConstantScalarValue() or Type::getConstantScalarTypes() or Type::getConstantScalarValues() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/ExponentiateHelper.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Generic\GenericObjectType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/FileTypeMapper.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\FloatType is error-prone and deprecated. Use Type::isFloat() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/FloatType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ClassStringType is error-prone and deprecated. Use Type::isClassStringType() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/GenericClassStringType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 4,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/GenericClassStringType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Generic\GenericClassStringType is error-prone and deprecated. Use Type::isClassStringType() and Type::getClassStringObjectType() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 4,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/GenericClassStringType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/GenericClassStringType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\StringType is error-prone and deprecated. Use Type::isString() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/GenericClassStringType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Generic\GenericObjectType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/GenericObjectType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/GenericObjectType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ObjectType is error-prone and deprecated. Use Type::isObject() or Type::getObjectClassNames() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/GenericObjectType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\TypeWithClassName is error-prone and deprecated. Use Type::getObjectClassNames() or Type::getObjectClassReflections() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/GenericObjectType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ObjectType is error-prone and deprecated. Use Type::isObject() or Type::getObjectClassNames() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/GenericStaticType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\TypeWithClassName is error-prone and deprecated. Use Type::getObjectClassNames() or Type::getObjectClassReflections() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/GenericStaticType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateArrayType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateBenevolentUnionType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateBooleanType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateConstantArrayType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateConstantIntegerType.php',
				],
				[
					'rawMessage' => 'Method PHPStan\Type\Generic\TemplateConstantIntegerType::toPhpDocNode() should return PHPStan\PhpDocParser\Ast\Type\ConstTypeNode but returns PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode.',
					'identifier' => 'return.type',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateConstantIntegerType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateConstantStringType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateFloatType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateGenericObjectType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateIntegerType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateIntersectionType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateIterableType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateKeyOfType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateMixedType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateNullType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateObjectShapeType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateObjectType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateObjectWithoutClassType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateStrictMixedType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateStringType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ArrayType is error-prone and deprecated. Use Type::isArray() or Type::getArrays() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateTypeFactory.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\BooleanType is error-prone and deprecated. Use Type::isBoolean() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateTypeFactory.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantArrayType is error-prone and deprecated. Use Type::getConstantArrays() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateTypeFactory.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateTypeFactory.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\FloatType is error-prone and deprecated. Use Type::isFloat() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateTypeFactory.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Generic\GenericObjectType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateTypeFactory.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntegerType is error-prone and deprecated. Use Type::isInteger() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateTypeFactory.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateTypeFactory.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IterableType is error-prone and deprecated. Use Type::isIterable() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateTypeFactory.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\NullType is error-prone and deprecated. Use Type::isNull() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateTypeFactory.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ObjectShapeType is error-prone and deprecated. Use Type::isObject() and Type::hasProperty() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateTypeFactory.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ObjectType is error-prone and deprecated. Use Type::isObject() or Type::getObjectClassNames() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateTypeFactory.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ObjectWithoutClassType is error-prone and deprecated. Use Type::isObject() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateTypeFactory.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\StringType is error-prone and deprecated. Use Type::isString() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateTypeFactory.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateUnionType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ConstantScalarType is error-prone and deprecated. Use Type::isConstantScalarValue() or Type::getConstantScalarTypes() or Type::getConstantScalarValues() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/IntegerRangeType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntegerType is error-prone and deprecated. Use Type::isInteger() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/IntegerRangeType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/IntegerRangeType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntegerType is error-prone and deprecated. Use Type::isInteger() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/IntegerType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ArrayType is error-prone and deprecated. Use Type::isArray() or Type::getArrays() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 4,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/IntersectionType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\BooleanType is error-prone and deprecated. Use Type::isBoolean() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/IntersectionType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\CallableType is error-prone and deprecated. Use Type::isCallable() and Type::getCallableParametersAcceptors() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/IntersectionType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantArrayType is error-prone and deprecated. Use Type::getConstantArrays() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 4,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/IntersectionType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/IntersectionType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Enum\EnumCaseObjectType is error-prone and deprecated. Use Type::getEnumCases() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/IntersectionType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/IntersectionType.php',
				],
				[
					'rawMessage' => 'Method PHPStan\Type\IntersectionType::getConstantArrays() should return list<PHPStan\Type\Constant\ConstantArrayType> but returns array{PHPStan\Type\Type}.',
					'identifier' => 'return.type',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/IntersectionType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/IterableType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IterableType is error-prone and deprecated. Use Type::isIterable() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/IterableType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ConstantScalarType is error-prone and deprecated. Use Type::isConstantScalarValue() or Type::getConstantScalarTypes() or Type::getConstantScalarValues() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/NullType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\NullType is error-prone and deprecated. Use Type::isNull() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/NullType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/ObjectShapeType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ObjectShapeType is error-prone and deprecated. Use Type::isObject() and Type::hasProperty() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/ObjectShapeType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ObjectWithoutClassType is error-prone and deprecated. Use Type::isObject() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/ObjectShapeType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Generic\GenericObjectType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/ObjectType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ObjectType is error-prone and deprecated. Use Type::isObject() or Type::getObjectClassNames() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/ObjectType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ObjectWithoutClassType is error-prone and deprecated. Use Type::isObject() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/ObjectType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ObjectShapeType is error-prone and deprecated. Use Type::isObject() and Type::hasProperty() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/ObjectWithoutClassType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ObjectWithoutClassType is error-prone and deprecated. Use Type::isObject() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 4,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/ObjectWithoutClassType.php',
				],
				[
					'rawMessage' => 'Creating new ReflectionClass is a runtime reflection concept that might not work in PHPStan because it uses fully static reflection engine. Use objects retrieved from ReflectionProvider instead.',
					'identifier' => 'phpstanApi.runtimeReflection',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/PHPStan/ClassNameUsageLocationCreateIdentifierDynamicReturnTypeExtension.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/ArrayKeyExistsFunctionTypeSpecifyingExtension.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ConstantScalarType is error-prone and deprecated. Use Type::isConstantScalarValue() or Type::getConstantScalarTypes() or Type::getConstantScalarValues() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 16,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/BcMathStringOrNullReturnTypeExtension.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/ClassExistsFunctionTypeSpecifyingExtension.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantArrayType is error-prone and deprecated. Use Type::getConstantArrays() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/CompactFunctionReturnTypeExtension.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/CompactFunctionReturnTypeExtension.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/DefineConstantTypeSpecifyingExtension.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/DefinedConstantTypeSpecifyingExtension.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\TypeWithClassName is error-prone and deprecated. Use Type::getObjectClassNames() or Type::getObjectClassReflections() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/DsMapDynamicReturnTypeExtension.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ConstantScalarType is error-prone and deprecated. Use Type::isConstantScalarValue() or Type::getConstantScalarTypes() or Type::getConstantScalarValues() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/FilterFunctionReturnTypeHelper.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/FilterFunctionReturnTypeHelper.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/FunctionExistsFunctionTypeSpecifyingExtension.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantArrayType is error-prone and deprecated. Use Type::getConstantArrays() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/InArrayFunctionTypeSpecifyingExtension.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/IsAFunctionTypeSpecifyingExtension.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/MbSubstituteCharacterDynamicReturnTypeExtension.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/MethodExistsTypeSpecifyingExtension.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ConstantScalarType is error-prone and deprecated. Use Type::isConstantScalarValue() or Type::getConstantScalarTypes() or Type::getConstantScalarValues() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/MinMaxFunctionReturnTypeExtension.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantArrayType is error-prone and deprecated. Use Type::getConstantArrays() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/MinMaxFunctionReturnTypeExtension.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ConstantScalarType is error-prone and deprecated. Use Type::isConstantScalarValue() or Type::getConstantScalarTypes() or Type::getConstantScalarValues() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/NumberFormatFunctionDynamicReturnTypeExtension.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/NumberFormatFunctionDynamicReturnTypeExtension.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/PropertyExistsTypeSpecifyingExtension.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/RangeFunctionReturnTypeExtension.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/ReflectionMethodConstructorThrowTypeExtension.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Generic\GenericClassStringType is error-prone and deprecated. Use Type::isClassStringType() and Type::getClassStringObjectType() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/ReflectionMethodConstructorThrowTypeExtension.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/SscanfFunctionDynamicReturnTypeExtension.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/StrRepeatFunctionReturnTypeExtension.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ObjectType is error-prone and deprecated. Use Type::isObject() or Type::getObjectClassNames() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/StaticType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ObjectWithoutClassType is error-prone and deprecated. Use Type::isObject() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/StaticType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/StringType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\StringType is error-prone and deprecated. Use Type::isString() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/StringType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ArrayType is error-prone and deprecated. Use Type::isArray() or Type::getArrays() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 5,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/TypeCombinator.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\CallableType is error-prone and deprecated. Use Type::isCallable() and Type::getCallableParametersAcceptors() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/TypeCombinator.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantArrayType is error-prone and deprecated. Use Type::getConstantArrays() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 19,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/TypeCombinator.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/TypeCombinator.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Generic\GenericClassStringType is error-prone and deprecated. Use Type::isClassStringType() and Type::getClassStringObjectType() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/TypeCombinator.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 5,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/TypeCombinator.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IterableType is error-prone and deprecated. Use Type::isIterable() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 8,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/TypeCombinator.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\NullType is error-prone and deprecated. Use Type::isNull() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/TypeCombinator.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ObjectShapeType is error-prone and deprecated. Use Type::isObject() and Type::hasProperty() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/TypeCombinator.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/TypeUtils.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ArrayType is error-prone and deprecated. Use Type::isArray() or Type::getArrays() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/TypehintHelper.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantArrayType is error-prone and deprecated. Use Type::getConstantArrays() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/TypehintHelper.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IterableType is error-prone and deprecated. Use Type::isIterable() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/TypehintHelper.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\CallableType is error-prone and deprecated. Use Type::isCallable() and Type::getCallableParametersAcceptors() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/UnionType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Generic\GenericClassStringType is error-prone and deprecated. Use Type::isClassStringType() and Type::getClassStringObjectType() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/UnionType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/UnionType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IterableType is error-prone and deprecated. Use Type::isIterable() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/UnionType.php',
				],
				[
					'rawMessage' => 'PHPDoc tag @var assumes the expression with type PHPStan\Type\Type is always PHPStan\Type\BooleanType but it\'s error-prone and dangerous.',
					'identifier' => 'phpstanApi.varTagAssumption',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/UnionType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\CallableType is error-prone and deprecated. Use Type::isCallable() and Type::getCallableParametersAcceptors() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/UnionTypeHelper.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ConstantScalarType is error-prone and deprecated. Use Type::isConstantScalarValue() or Type::getConstantScalarTypes() or Type::getConstantScalarValues() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 4,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/UnionTypeHelper.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantBooleanType is error-prone and deprecated. Use Type::isTrue() or Type::isFalse() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/UnionTypeHelper.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/UnionTypeHelper.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntegerType is error-prone and deprecated. Use Type::isInteger() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/UnionTypeHelper.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\NullType is error-prone and deprecated. Use Type::isNull() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/UnionTypeHelper.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\VoidType is error-prone and deprecated. Use Type::isVoid() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/VoidType.php',
				],
				[
					'rawMessage' => 'Class PHPStan\Analyser\AnonymousClassNameRuleTest extends generic class PHPStan\Testing\RuleTestCase but does not specify its types: TRule',
					'identifier' => 'missingType.generics',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Analyser/AnonymousClassNameRuleTest.php',
				],
				[
					'rawMessage' => 'Method PHPStan\Analyser\AnonymousClassNameRuleTest::getRule() return type with generic interface PHPStan\Rules\Rule does not specify its types: TNodeType',
					'identifier' => 'missingType.generics',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Analyser/AnonymousClassNameRuleTest.php',
				],
				[
					'rawMessage' => 'Class PHPStan\Analyser\EvaluationOrderTest extends generic class PHPStan\Testing\RuleTestCase but does not specify its types: TRule',
					'identifier' => 'missingType.generics',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Analyser/EvaluationOrderTest.php',
				],
				[
					'rawMessage' => 'Method PHPStan\Analyser\EvaluationOrderTest::getRule() return type with generic interface PHPStan\Rules\Rule does not specify its types: TNodeType',
					'identifier' => 'missingType.generics',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Analyser/EvaluationOrderTest.php',
				],
				[
					'rawMessage' => 'Constant SOME_CONSTANT_IN_AUTOLOAD_FILE not found.',
					'identifier' => 'constant.notFound',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Command/AnalyseCommandTest.php',
				],
				[
					'rawMessage' => 'Class PHPStan\Node\FileNodeTest extends generic class PHPStan\Testing\RuleTestCase but does not specify its types: TRule',
					'identifier' => 'missingType.generics',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Node/FileNodeTest.php',
				],
				[
					'rawMessage' => 'Method PHPStan\Node\FileNodeTest::getRule() return type with generic interface PHPStan\Rules\Rule does not specify its types: TNodeType',
					'identifier' => 'missingType.generics',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Node/FileNodeTest.php',
				],
				[
					'rawMessage' => 'Access to constant on internal class InternalAnnotations\InternalFoo.',
					'identifier' => 'classConstant.internalClass',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Reflection/Annotations/InternalAnnotationsTest.php',
				],
				[
					'rawMessage' => 'Access to constant on internal interface InternalAnnotations\InternalFooInterface.',
					'identifier' => 'classConstant.internalInterface',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Reflection/Annotations/InternalAnnotationsTest.php',
				],
				[
					'rawMessage' => 'Access to constant on internal trait InternalAnnotations\InternalFooTrait.',
					'identifier' => 'classConstant.internalTrait',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Reflection/Annotations/InternalAnnotationsTest.php',
				],
				[
					'rawMessage' => 'PHPDoc tag @var with type string is not subtype of type class-string.',
					'identifier' => 'varTag.type',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Reflection/BetterReflection/SourceLocator/OptimizedDirectorySourceLocatorTest.php',
				],
				[
					'rawMessage' => 'Creating new PHPStan\Php8StubsMap is not covered by backward compatibility promise. The class might change in a minor PHPStan version.',
					'identifier' => 'phpstanApi.constructor',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Reflection/ReflectionProviderGoldenTest.php',
				],
				[
					'rawMessage' => 'Creating new PHPStan\Php8StubsMap is not covered by backward compatibility promise. The class might change in a minor PHPStan version.',
					'identifier' => 'phpstanApi.constructor',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Reflection/SignatureMap/Php8SignatureMapProviderTest.php',
				],
				[
					'rawMessage' => 'Access to constant on internal class PHPUnit\Framework\AssertionFailedError.',
					'identifier' => 'classConstant.internalClass',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Testing/TypeInferenceTestCaseTest.php',
				],
				[
					'rawMessage' => 'Catching internal class PHPUnit\Framework\AssertionFailedError.',
					'identifier' => 'catch.internalClass',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Testing/TypeInferenceTestCaseTest.php',
				],
				[
					'rawMessage' => 'PHPDoc tag @var assumes the expression with type PHPStan\Type\Generic\TemplateType is always PHPStan\Type\Generic\TemplateMixedType but it\'s error-prone and dangerous.',
					'identifier' => 'phpstanApi.varTagAssumption',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Type/IterableTypeTest.php',
				],
				[
					'message' => '#^Strict comparison using \=\=\= between list\<callable\(string\)\: void\> and false will always evaluate to false\.$#',
					'identifier' => 'identical.alwaysFalse',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/BetterReflection/SourceLocator/AutoloadSourceLocator.php',
				],
				[
					'message' => '#^Call to function method_exists\(\) with ReflectionFunction and \'getClosureCalledCla…\' will always evaluate to true\.$#',
					'identifier' => 'function.alreadyNarrowedType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/ClosureTypeFactory.php',
				],
				[
					'message' => '#^Strict comparison using \=\=\= between list\<non\-falsy\-string\> and false will always evaluate to false\.$#',
					'identifier' => 'identical.alwaysFalse',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/MbFunctionsReturnTypeExtension.php',
				],
				[
					'message' => '#^Strict comparison using \=\=\= between int\<0, max\> and false will always evaluate to false\.$#',
					'identifier' => 'identical.alwaysFalse',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/MbStrlenFunctionReturnTypeExtension.php',
				],
				[
					'message' => '#^Strict comparison using \=\=\= between list\<non\-falsy\-string\> and false will always evaluate to false\.$#',
					'identifier' => 'identical.alwaysFalse',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/MbStrlenFunctionReturnTypeExtension.php',
				],
				[
					'message' => '#^Strict comparison using \=\=\= between list\<non\-falsy\-string\> and false will always evaluate to false\.$#',
					'identifier' => 'identical.alwaysFalse',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/StrSplitFunctionReturnTypeExtension.php',
				],
				[
					'message' => '#^Class PHPStan\\\Command\\\ErrorsConsoleStyle has an uninitialized property \$progressBar\. Give it default value or assign it in the constructor\.$#',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Command/ErrorsConsoleStyle.php',
				],
				[
					'message' => '#^Class PHPStan\\\Parallel\\\ParallelAnalyser has an uninitialized property \$processPool\. Give it default value or assign it in the constructor\.$#',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Parallel/ParallelAnalyser.php',
				],
				[
					'message' => '#^Class PHPStan\\\Parallel\\\SpawnedProcess has an uninitialized property \$process\. Give it default value or assign it in the constructor\.$#',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Parallel/SpawnedProcess.php',
				],
				[
					'message' => '#^Class PHPStan\\\PhpDoc\\\ResolvedPhpDocBlock has an uninitialized property \$phpDocNodes\. Give it default value or assign it in the constructor\.$#',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/PhpDoc/ResolvedPhpDocBlock.php',
				],
				[
					'message' => '#^Class PHPStan\\\PhpDoc\\\ResolvedPhpDocBlock has an uninitialized property \$phpDocNode\. Give it default value or assign it in the constructor\.$#',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/PhpDoc/ResolvedPhpDocBlock.php',
				],
				[
					'message' => '#^Class PHPStan\\\PhpDoc\\\ResolvedPhpDocBlock has an uninitialized property \$phpDocString\. Give it default value or assign it in the constructor\.$#',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/PhpDoc/ResolvedPhpDocBlock.php',
				],
				[
					'message' => '#^Class PHPStan\\\PhpDoc\\\ResolvedPhpDocBlock has an uninitialized property \$filename\. Give it default value or assign it in the constructor\.$#',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/PhpDoc/ResolvedPhpDocBlock.php',
				],
				[
					'message' => '#^Class PHPStan\\\PhpDoc\\\ResolvedPhpDocBlock has an uninitialized property \$templateTypeMap\. Give it default value or assign it in the constructor\.$#',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/PhpDoc/ResolvedPhpDocBlock.php',
				],
				[
					'message' => '#^Class PHPStan\\\PhpDoc\\\ResolvedPhpDocBlock has an uninitialized property \$templateTags\. Give it default value or assign it in the constructor\.$#',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/PhpDoc/ResolvedPhpDocBlock.php',
				],
				[
					'message' => '#^Class PHPStan\\\PhpDoc\\\ResolvedPhpDocBlock has an uninitialized property \$phpDocNodeResolver\. Give it default value or assign it in the constructor\.$#',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/PhpDoc/ResolvedPhpDocBlock.php',
				],
				[
					'message' => '#^Class PHPStan\\\PhpDoc\\\ResolvedPhpDocBlock has an uninitialized property \$reflectionProvider\. Give it default value or assign it in the constructor\.$#',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/PhpDoc/ResolvedPhpDocBlock.php',
				],
				[
					'message' => '#^Class PHPStan\\\Reflection\\\BetterReflection\\\SourceLocator\\\CachingVisitor has an uninitialized property \$fileName\. Give it default value or assign it in the constructor\.$#',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/BetterReflection/SourceLocator/CachingVisitor.php',
				],
				[
					'message' => '#^Class PHPStan\\\Reflection\\\BetterReflection\\\SourceLocator\\\CachingVisitor has an uninitialized property \$contents\. Give it default value or assign it in the constructor\.$#',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/BetterReflection/SourceLocator/CachingVisitor.php',
				],
				[
					'message' => '#^Class PHPStan\\\Reflection\\\BetterReflection\\\SourceLocator\\\CachingVisitor has an uninitialized property \$classNodes\. Give it default value or assign it in the constructor\.$#',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/BetterReflection/SourceLocator/CachingVisitor.php',
				],
				[
					'message' => '#^Class PHPStan\\\Reflection\\\BetterReflection\\\SourceLocator\\\CachingVisitor has an uninitialized property \$functionNodes\. Give it default value or assign it in the constructor\.$#',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/BetterReflection/SourceLocator/CachingVisitor.php',
				],
				[
					'message' => '#^Class PHPStan\\\Reflection\\\BetterReflection\\\SourceLocator\\\CachingVisitor has an uninitialized property \$constantNodes\. Give it default value or assign it in the constructor\.$#',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/BetterReflection/SourceLocator/CachingVisitor.php',
				],
				'#^Class PHPStan\\\Rules\\\RuleErrors\\\RuleError(?:\d+) has an uninitialized property (?:\$message|\$line|\$identifier|\$tip|\$file|\$metadata|\$originalNode)#',
				'#Extension has an uninitialized property (?:\$typeSpecifier|\$broker)#',
				['message' => '#has an uninitialized property#', 'path' => '/home/runner/work/phpstan-src/phpstan-src/tests'],
				[
					'message' => '#^PHPDoc tag @var with type list\<callable\(string\): void\>\|false is not subtype of native type list\<callable\(string\): void\>\.$#',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Command/CommandHelper.php',
				],
				[
					'message' => '#^Use of constant E_STRICT is deprecated\.$#',
					'identifier' => 'constant.deprecated',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Analyser/FileAnalyser.php',
				],
				[
					'message' => '#^Call to an undefined static method PHPUnit\\\Framework\\\TestCase\:\:assertFileNotExists\(\)\.$#',
					'identifier' => 'staticMethod.notFound',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Testing/LevelsTestCase.php',
				],
				'#^Dynamic call to static method PHPUnit\\\Framework\\\\\S+\(\)\.$#',
				'#should be contravariant with parameter \$node \(PhpParser\\\Node\) of method PHPStan\\\Rules\\\Rule<PhpParser\\\Node>::processNode\(\)$#',
				'#Variable property access on PhpParser\\\Node#',
				[
					'identifier' => 'shipmonk.deadMethod',
					'message' => '#^Unused .*?Factory::create#',
					'reportUnmatched' => false,
				],
				[
					'identifier' => 'shipmonk.deadMethod',
					'message' => '#^Unused PHPStan\\\DependencyInjection\\\BleedingEdgeToggle::isBleedingEdge#',
					'reportUnmatched' => false,
				],
				[
					'identifier' => 'shipmonk.deadMethod',
					'paths' => [
						'/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Tests',
						'/home/runner/work/phpstan-src/phpstan-src/tests/e2e',
					],
					'reportUnmatched' => false,
				],
				[
					'identifier' => 'shipmonk.deadConstant',
					'paths' => ['/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Fixture'],
					'reportUnmatched' => false,
				],
				[
					'identifier' => 'shipmonk.deadEnumCase',
					'paths' => ['/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Fixture'],
					'reportUnmatched' => false,
				],
				[
					'message' => "#^Access to constant on deprecated class DeprecatedAnnotations\\\\DeprecatedFoo\\:\nin 1\\.0\\.0\\.\$#",
					'path' => '/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Reflection/Annotations/DeprecatedAnnotationsTest.php',
				],
				[
					'message' => "#^Access to constant on deprecated class DeprecatedAnnotations\\\\DeprecatedWithMultipleTags\\:\nin Foo 1\\.1\\.0 and will be removed in 1\\.5\\.0, use\n  \\\\Foo\\\\Bar\\\\NotDeprecated instead\\.\$#",
					'path' => '/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Reflection/Annotations/DeprecatedAnnotationsTest.php',
				],
				[
					'message' => '#^Variable property access on T of PHPStan\\\Rules\\\RuleError\.$#',
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/RuleErrorBuilder.php',
				],
				[
					'message' => '#^Parameter \#1 (?:\$argument|\$objectOrClass) of class ReflectionClass constructor expects class\-string\<PHPStan\\\ExtensionInstaller\\\GeneratedConfig\>\|PHPStan\\\ExtensionInstaller\\\GeneratedConfig, string given\.$#',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Command/CommandHelper.php',
				],
				[
					'message' => '#^Parameter \#1 (?:\$argument|\$objectOrClass) of class ReflectionClass constructor expects class\-string\<PHPStan\\\ExtensionInstaller\\\GeneratedConfig\>\|PHPStan\\\ExtensionInstaller\\\GeneratedConfig, string given\.$#',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Diagnose/PHPStanDiagnoseExtension.php',
				],
				['identifier' => 'ternary.shortNotAllowed'],
				[
					'identifier' => 'shipmonk.deadMethod',
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Internal/CombinationsHelper.php',
					'reportUnmatched' => false,
				],
				[
					'rawMessage' => 'Property PHPStan\Command\CommandHelper::$reservedMemory is never read',
					'reportUnmatched' => false,
				],
			],
			true
		);
	}


	public function createService0169(): PHPStan\Analyser\Ignore\IgnoreLexer
	{
		return new PHPStan\Analyser\Ignore\IgnoreLexer;
	}


	public function createService0170(): PHPStan\Node\Printer\ExprPrinter
	{
		return new PHPStan\Node\Printer\ExprPrinter($this->getService('0171'));
	}


	public function createService0171(): PHPStan\Node\Printer\Printer
	{
		return new PHPStan\Node\Printer\Printer;
	}


	public function createService0172(): PHPStan\Node\DeepNodeCloner
	{
		return new PHPStan\Node\DeepNodeCloner;
	}


	public function createService0173(): PHPStan\File\FileHelper
	{
		return new PHPStan\File\FileHelper('/home/runner/work/phpstan-src/phpstan-src');
	}


	public function createService0174(): PHPStan\File\FileMonitor
	{
		return new PHPStan\File\FileMonitor(
			$this->getService('fileFinderAnalyse'),
			$this->getService('fileFinderScan'),
			$this->getParameter('analysedPaths'),
			$this->getParameter('analysedPathsFromConfig'),
			[],
			[]
		);
	}


	public function createService0175(): PHPStan\File\FileExcluderFactory
	{
		return new PHPStan\File\FileExcluderFactory(
			$this->getService('0456'),
			[
				'analyseAndScan' => [
					'/home/runner/work/phpstan-src/phpstan-src/src/Rules/Constants/ConstantAttributesRule.php',
					'/home/runner/work/phpstan-src/phpstan-src/tests/*/data/*',
					'/home/runner/work/phpstan-src/phpstan-src/tests/tmp/*',
					'/home/runner/work/phpstan-src/phpstan-src/tests/vendor/*',
					'/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Analyser/nsrt/*',
					'/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Analyser/traits/*',
					'/home/runner/work/phpstan-src/phpstan-src/tests/notAutoloaded/*',
					'/home/runner/work/phpstan-src/phpstan-src/tests/bench/*',
					'/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Reflection/UnionTypesTest.php',
					'/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Reflection/MixedTypeTest.php',
					'/home/runner/work/phpstan-src/phpstan-src/tests/e2e/magic-setter/*',
					'/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Rules/Properties/UninitializedPropertyRuleTest.php',
					'/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Command/IgnoredRegexValidatorTest.php',
					'/home/runner/work/phpstan-src/phpstan-src/src/Command/IgnoredRegexValidator.php',
				],
				'analyse' => [],
			]
		);
	}


	public function createService0176(): PHPStan\Dependency\ExportedNodeFetcher
	{
		return new PHPStan\Dependency\ExportedNodeFetcher($this->getService('defaultAnalysisParser'), $this->getService('0851'));
	}


	public function createService0177(): PHPStan\Dependency\ExportedNodeResolver
	{
		return new PHPStan\Dependency\ExportedNodeResolver(
			$this->getService('reflectionProvider'),
			$this->getService('0449'),
			$this->getService('0170')
		);
	}


	public function createService0178(): PHPStan\Dependency\DependencyResolver
	{
		return new PHPStan\Dependency\DependencyResolver(
			$this->getService('0173'),
			$this->getService('reflectionProvider'),
			$this->getService('0177'),
			$this->getService('0449')
		);
	}


	public function createService0179(): PHPStan\DependencyInjection\MemoizingContainer
	{
		return new PHPStan\DependencyInjection\MemoizingContainer($this->getService('0181'));
	}


	public function createService0180(): PHPStan\DependencyInjection\DerivativeContainerFactory
	{
		return new PHPStan\DependencyInjection\DerivativeContainerFactory(
			'/home/runner/work/phpstan-src/phpstan-src',
			'/home/runner/work/phpstan-src/phpstan-src/tmp',
			[
				'/home/runner/work/phpstan-src/phpstan-src/conf/config.level8.neon',
				'/home/runner/work/phpstan-src/phpstan-src/phpstan.neon.dist',
				'/home/runner/work/phpstan-src/phpstan-src/src/PhpDoc/../../conf/config.stubValidator.neon',
			],
			$this->getParameter('analysedPaths'),
			['/home/runner/work/phpstan-src/phpstan-src'],
			$this->getParameter('analysedPathsFromConfig'),
			'8',
			null,
			null,
			$this->getParameter('singleReflectionFile'),
			$this->getParameter('singleReflectionInsteadOfFile')
		);
	}


	public function createService0181(): PHPStan\DependencyInjection\Nette\NetteContainer
	{
		return new PHPStan\DependencyInjection\Nette\NetteContainer($this);
	}


	public function createService0182(): PHPStan\DependencyInjection\Reflection\LazyClassReflectionExtensionRegistryProvider
	{
		return new PHPStan\DependencyInjection\Reflection\LazyClassReflectionExtensionRegistryProvider($this->getService('0179'));
	}


	public function createService0183(): PHPStan\DependencyInjection\Type\LazyExpressionTypeResolverExtensionRegistryProvider
	{
		return new PHPStan\DependencyInjection\Type\LazyExpressionTypeResolverExtensionRegistryProvider($this->getService('0179'));
	}


	public function createService0184(): PHPStan\DependencyInjection\Type\LazyParameterClosureTypeExtensionProvider
	{
		return new PHPStan\DependencyInjection\Type\LazyParameterClosureTypeExtensionProvider($this->getService('0179'));
	}


	public function createService0185(): PHPStan\DependencyInjection\Type\LazyParameterOutTypeExtensionProvider
	{
		return new PHPStan\DependencyInjection\Type\LazyParameterOutTypeExtensionProvider($this->getService('0179'));
	}


	public function createService0186(): PHPStan\DependencyInjection\Type\LazyOperatorTypeSpecifyingExtensionRegistryProvider
	{
		return new PHPStan\DependencyInjection\Type\LazyOperatorTypeSpecifyingExtensionRegistryProvider($this->getService('0179'));
	}


	public function createService0187(): PHPStan\DependencyInjection\Type\LazyParameterClosureThisExtensionProvider
	{
		return new PHPStan\DependencyInjection\Type\LazyParameterClosureThisExtensionProvider($this->getService('0179'));
	}


	public function createService0188(): PHPStan\DependencyInjection\Type\LazyDynamicReturnTypeExtensionRegistryProvider
	{
		return new PHPStan\DependencyInjection\Type\LazyDynamicReturnTypeExtensionRegistryProvider($this->getService('0179'));
	}


	public function createService0189(): PHPStan\DependencyInjection\Type\LazyUnaryOperatorTypeSpecifyingExtensionRegistryProvider
	{
		return new PHPStan\DependencyInjection\Type\LazyUnaryOperatorTypeSpecifyingExtensionRegistryProvider($this->getService('0179'));
	}


	public function createService0190(): PHPStan\DependencyInjection\Type\LazyDynamicThrowTypeExtensionProvider
	{
		return new PHPStan\DependencyInjection\Type\LazyDynamicThrowTypeExtensionProvider($this->getService('0179'));
	}


	public function createService0191(): PHPStan\Broker\AnonymousClassNameHelper
	{
		return new PHPStan\Broker\AnonymousClassNameHelper($this->getService('0173'), $this->getService('simpleRelativePathHelper'));
	}


	public function createService0192(): PHPStan\Reflection\ConstructorsHelper
	{
		return new PHPStan\Reflection\ConstructorsHelper($this->getService('0179'), ['PHPUnit\Framework\TestCase::setUp']);
	}


	public function createService0193(): PHPStan\Reflection\ReflectionProvider\LazyReflectionProviderProvider
	{
		return new PHPStan\Reflection\ReflectionProvider\LazyReflectionProviderProvider($this->getService('0179'));
	}


	public function createService0194(): PHPStan\Reflection\BetterReflection\SourceStubber\ReflectionSourceStubberFactory
	{
		return new PHPStan\Reflection\BetterReflection\SourceStubber\ReflectionSourceStubberFactory(
			$this->getService('0171'),
			$this->getService('0453')
		);
	}


	public function createService0195(): PHPStan\Reflection\BetterReflection\SourceStubber\PhpStormStubsSourceStubberFactory
	{
		return new PHPStan\Reflection\BetterReflection\SourceStubber\PhpStormStubsSourceStubberFactory(
			$this->getService('php8PhpParser'),
			$this->getService('0171'),
			$this->getService('0453')
		);
	}


	public function createService0196(): PHPStan\Reflection\BetterReflection\BetterReflectionSourceLocatorFactory
	{
		return new PHPStan\Reflection\BetterReflection\BetterReflectionSourceLocatorFactory(
			$this->getService('phpParserDecorator'),
			$this->getService('php8PhpParser'),
			$this->getService('0453'),
			$this->getService('0849'),
			$this->getService('0850'),
			$this->getService('0197'),
			$this->getService('0201'),
			$this->getService('0200'),
			$this->getService('0458'),
			$this->getService('0198'),
			[],
			[],
			$this->getParameter('analysedPaths'),
			['/home/runner/work/phpstan-src/phpstan-src'],
			$this->getParameter('analysedPathsFromConfig'),
			false,
			$this->getParameter('singleReflectionFile')
		);
	}


	public function createService0197(): PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedSingleFileSourceLocatorRepository
	{
		return new PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedSingleFileSourceLocatorRepository($this->getService('0457'));
	}


	public function createService0198(): PHPStan\Reflection\BetterReflection\SourceLocator\FileNodesFetcher
	{
		return new PHPStan\Reflection\BetterReflection\SourceLocator\FileNodesFetcher(
			$this->getService('0852'),
			$this->getService('defaultAnalysisParser')
		);
	}


	public function createService0199(): PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedDirectorySourceLocatorFactory
	{
		return new PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedDirectorySourceLocatorFactory(
			$this->getService('0198'),
			$this->getService('fileFinderScan'),
			$this->getService('0453'),
			$this->getService('0874'),
			$this->getService('01')
		);
	}


	public function createService0200(): PHPStan\Reflection\BetterReflection\SourceLocator\ComposerJsonAndInstalledJsonSourceLocatorMaker
	{
		return new PHPStan\Reflection\BetterReflection\SourceLocator\ComposerJsonAndInstalledJsonSourceLocatorMaker(
			$this->getService('0201'),
			$this->getService('0458'),
			$this->getService('0199'),
			$this->getService('0453')
		);
	}


	public function createService0201(): PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedDirectorySourceLocatorRepository
	{
		return new PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedDirectorySourceLocatorRepository($this->getService('0199'));
	}


	public function createService0202(): PHPStan\Reflection\BetterReflection\Type\AdapterReflectionEnumDynamicReturnTypeExtension
	{
		return new PHPStan\Reflection\BetterReflection\Type\AdapterReflectionEnumDynamicReturnTypeExtension($this->getService('0453'));
	}


	public function createService0203(): PHPStan\Reflection\AttributeReflectionFactory
	{
		return new PHPStan\Reflection\AttributeReflectionFactory($this->getService('0204'), $this->getService('0193'));
	}


	public function createService0204(): PHPStan\Reflection\InitializerExprTypeResolver
	{
		return new PHPStan\Reflection\InitializerExprTypeResolver(
			$this->getService('0159'),
			$this->getService('0193'),
			$this->getService('0453'),
			$this->getService('0186'),
			$this->getService('0189'),
			$this->getService('0264'),
			false
		);
	}


	public function createService0205(): PHPStan\Reflection\Deprecation\DeprecationProvider
	{
		return new PHPStan\Reflection\Deprecation\DeprecationProvider($this->getService('0179'));
	}


	public function createService0206(): PHPStan\Reflection\SignatureMap\NativeFunctionReflectionProvider
	{
		return new PHPStan\Reflection\SignatureMap\NativeFunctionReflectionProvider(
			$this->getService('0208'),
			$this->getService('betterReflectionReflector'),
			$this->getService('0449'),
			$this->getService('stubPhpDocProvider'),
			$this->getService('0203')
		);
	}


	public function createService0207(): PHPStan\Reflection\SignatureMap\SignatureMapParser
	{
		return new PHPStan\Reflection\SignatureMap\SignatureMapParser($this->getService('0231'));
	}


	public function createService0208(): PHPStan\Reflection\SignatureMap\SignatureMapProvider
	{
		return $this->getService('0209')->create();
	}


	public function createService0209(): PHPStan\Reflection\SignatureMap\SignatureMapProviderFactory
	{
		return new PHPStan\Reflection\SignatureMap\SignatureMapProviderFactory(
			$this->getService('0453'),
			$this->getService('0211'),
			$this->getService('0210')
		);
	}


	public function createService0210(): PHPStan\Reflection\SignatureMap\Php8SignatureMapProvider
	{
		return new PHPStan\Reflection\SignatureMap\Php8SignatureMapProvider(
			$this->getService('0211'),
			$this->getService('0198'),
			$this->getService('0449'),
			$this->getService('0453'),
			$this->getService('0204'),
			$this->getService('0193')
		);
	}


	public function createService0211(): PHPStan\Reflection\SignatureMap\FunctionSignatureMapProvider
	{
		return new PHPStan\Reflection\SignatureMap\FunctionSignatureMapProvider(
			$this->getService('0207'),
			$this->getService('0204'),
			$this->getService('0453'),
			true
		);
	}


	public function createService0212(): PHPStan\Reflection\Php\EnumAllowedSubTypesClassReflectionExtension
	{
		return new PHPStan\Reflection\Php\EnumAllowedSubTypesClassReflectionExtension;
	}


	public function createService0213(): PHPStan\Reflection\Php\SealedAllowedSubTypesClassReflectionExtension
	{
		return new PHPStan\Reflection\Php\SealedAllowedSubTypesClassReflectionExtension;
	}


	public function createService0214(): PHPStan\Process\CpuCoreCounter
	{
		return new PHPStan\Process\CpuCoreCounter(1.0);
	}


	public function createService0215(): PHPStan\Command\AnalyseApplication
	{
		return new PHPStan\Command\AnalyseApplication(
			$this->getService('0216'),
			$this->getService('083'),
			$this->getService('0226'),
			$this->getService('0454'),
			$this->getService('0168'),
			$this->getService('0228')
		);
	}


	public function createService0216(): PHPStan\Command\AnalyserRunner
	{
		return new PHPStan\Command\AnalyserRunner(
			$this->getService('08'),
			$this->getService('0163'),
			$this->getService('05'),
			$this->getService('0214')
		);
	}


	public function createService0217(): PHPStan\Command\ErrorFormatter\CiDetectedErrorFormatter
	{
		return new PHPStan\Command\ErrorFormatter\CiDetectedErrorFormatter(
			$this->getService('errorFormatter.github'),
			$this->getService('errorFormatter.teamcity')
		);
	}


	public function createService0218(): PHPStan\Command\FixerWorkerRunner
	{
		return new PHPStan\Command\FixerWorkerRunner(
			$this->getService('0168'),
			$this->getService('0454'),
			$this->getService('083'),
			$this->getService('05'),
			$this->getService('08'),
			$this->getService('0214')
		);
	}


	public function createService0219(): PHPStan\Command\FixerApplication
	{
		return new PHPStan\Command\FixerApplication(
			$this->getService('0174'),
			$this->getService('0168'),
			$this->getService('0228'),
			$this->getParameter('analysedPaths'),
			'/home/runner/work/phpstan-src/phpstan-src',
			($this->getParameter('sysGetTempDir')) . '/phpstan-fixer',
			['/home/runner/work/phpstan-src/phpstan-src'],
			[
				'/home/runner/work/phpstan-src/phpstan-src/conf/parametersSchema.neon',
				'/home/runner/work/phpstan-src/phpstan-src/conf/config.level8.neon',
				'/home/runner/work/phpstan-src/phpstan-src/conf/config.level7.neon',
				'/home/runner/work/phpstan-src/phpstan-src/conf/config.level6.neon',
				'/home/runner/work/phpstan-src/phpstan-src/conf/config.level5.neon',
				'/home/runner/work/phpstan-src/phpstan-src/conf/config.level4.neon',
				'/home/runner/work/phpstan-src/phpstan-src/conf/config.level3.neon',
				'/home/runner/work/phpstan-src/phpstan-src/conf/config.level2.neon',
				'/home/runner/work/phpstan-src/phpstan-src/conf/config.level1.neon',
				'/home/runner/work/phpstan-src/phpstan-src/conf/config.level0.neon',
				'/home/runner/work/phpstan-src/phpstan-src/phpstan.neon.dist',
				'/home/runner/work/phpstan-src/phpstan-src/build/phpstan.neon',
				'/home/runner/work/phpstan-src/phpstan-src/vendor/phpstan/phpstan-deprecation-rules/rules.neon',
				'/home/runner/work/phpstan-src/phpstan-src/vendor/phpstan/phpstan-nette/rules.neon',
				'/home/runner/work/phpstan-src/phpstan-src/vendor/phpstan/phpstan-phpunit/extension.neon',
				'/home/runner/work/phpstan-src/phpstan-src/vendor/phpstan/phpstan-phpunit/rules.neon',
				'/home/runner/work/phpstan-src/phpstan-src/vendor/phpstan/phpstan-strict-rules/rules.neon',
				'/home/runner/work/phpstan-src/phpstan-src/conf/bleedingEdge.neon',
				'/home/runner/work/phpstan-src/phpstan-src/phpstan-baseline.neon',
				'/home/runner/work/phpstan-src/phpstan-src/phpstan-baseline.php',
				'/home/runner/work/phpstan-src/phpstan-src/build/ignore-by-php-version.neon.php',
				'/home/runner/work/phpstan-src/phpstan-src/build/baseline-8.0.neon',
				'/home/runner/work/phpstan-src/phpstan-src/build/baseline-8.1.neon',
				'/home/runner/work/phpstan-src/phpstan-src/vendor/shipmonk/dead-code-detector/rules.neon',
				'/home/runner/work/phpstan-src/phpstan-src/build/ignore-gte-php7.4-errors.neon',
				'/home/runner/work/phpstan-src/phpstan-src/build/baseline-7.4.neon',
				'/home/runner/work/phpstan-src/phpstan-src/build/spl-autoload-functions-php-8.neon',
				'/home/runner/work/phpstan-src/phpstan-src/build/deprecated-8.4.neon',
				'/home/runner/work/phpstan-src/phpstan-src/build/new-phpunit.neon',
				'/home/runner/work/phpstan-src/phpstan-src/build/pre-php-85.neon',
				'/home/runner/work/phpstan-src/phpstan-src/build/ignore-by-architecture.neon.php',
				'/home/runner/work/phpstan-src/phpstan-src/conf/config.stubValidator.neon',
			],
			null,
			[
				'/home/runner/work/phpstan-src/phpstan-src/stubs/runtime/ReflectionUnionType.php',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/runtime/ReflectionAttribute.php',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/runtime/Attribute85.php',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/runtime/ReflectionIntersectionType.php',
				'/home/runner/work/phpstan-src/phpstan-src/tests/phpstan-bootstrap.php',
			],
			null,
			'8',
			$this->getService('04'),
			$this->getService('06'),
			$this->getService('0218')
		);
	}


	public function createService0220(): PHPStan\Collectors\Registry
	{
		return $this->getService('0221')->create();
	}


	public function createService0221(): PHPStan\Collectors\RegistryFactory
	{
		return new PHPStan\Collectors\RegistryFactory($this->getService('0179'));
	}


	public function createService0222(): PHPStan\PhpDoc\PhpDocInheritanceResolver
	{
		return new PHPStan\PhpDoc\PhpDocInheritanceResolver($this->getService('0449'));
	}


	public function createService0223(): PHPStan\PhpDoc\LazyTypeNodeResolverExtensionRegistryProvider
	{
		return new PHPStan\PhpDoc\LazyTypeNodeResolverExtensionRegistryProvider($this->getService('0179'));
	}


	public function createService0224(): PHPStan\PhpDoc\BcMathNumberStubFilesExtension
	{
		return new PHPStan\PhpDoc\BcMathNumberStubFilesExtension($this->getService('0453'));
	}


	public function createService0225(): PHPStan\PhpDoc\SocketSelectStubFilesExtension
	{
		return new PHPStan\PhpDoc\SocketSelectStubFilesExtension($this->getService('0453'));
	}


	public function createService0226(): PHPStan\PhpDoc\StubValidator
	{
		return new PHPStan\PhpDoc\StubValidator($this->getService('0180'), $this->getService('0228'));
	}


	public function createService0227(): PHPStan\PhpDoc\ReflectionClassStubFilesExtension
	{
		return new PHPStan\PhpDoc\ReflectionClassStubFilesExtension($this->getService('0453'));
	}


	public function createService0228(): PHPStan\PhpDoc\DefaultStubFilesProvider
	{
		return new PHPStan\PhpDoc\DefaultStubFilesProvider(
			$this->getService('0179'),
			$this->getService('0173'),
			[
				'/home/runner/work/phpstan-src/phpstan-src/stubs/Memcached.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/Redis.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/ReflectionAttribute.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/ReflectionClassConstant.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/ReflectionFunctionAbstract.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/ReflectionMethod.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/ReflectionParameter.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/ReflectionProperty.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/iterable.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/ArrayObject.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/WeakReference.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/ext-ds.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/ImagickPixel.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/PDOStatement.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/date.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/ibm_db2.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/mysqli.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/zip.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/dom.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/spl.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/SplObjectStorage.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/Exception.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/arrayFunctions.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/core.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/typeCheckingFunctions.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/Countable.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/file.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/stream_socket_client.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/stream_socket_server.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/ctype.stub',
				'/home/runner/work/phpstan-src/phpstan-src/vendor/phpstan/phpstan-phpunit/stubs/Assert.stub',
				'/home/runner/work/phpstan-src/phpstan-src/vendor/phpstan/phpstan-phpunit/stubs/AssertionFailedError.stub',
				'/home/runner/work/phpstan-src/phpstan-src/vendor/phpstan/phpstan-phpunit/stubs/ExpectationFailedException.stub',
				'/home/runner/work/phpstan-src/phpstan-src/vendor/phpstan/phpstan-phpunit/stubs/MockBuilder.stub',
				'/home/runner/work/phpstan-src/phpstan-src/vendor/phpstan/phpstan-phpunit/stubs/MockObject.stub',
				'/home/runner/work/phpstan-src/phpstan-src/vendor/phpstan/phpstan-phpunit/stubs/Stub.stub',
				'/home/runner/work/phpstan-src/phpstan-src/vendor/phpstan/phpstan-phpunit/stubs/TestCase.stub',
				'/home/runner/work/phpstan-src/phpstan-src/build/stubs/ReactChildProcess.stub',
				'/home/runner/work/phpstan-src/phpstan-src/build/stubs/ReactStreams.stub',
				'/home/runner/work/phpstan-src/phpstan-src/build/stubs/NetteDIContainer.stub',
				'/home/runner/work/phpstan-src/phpstan-src/build/stubs/PhpParserName.stub',
				'/home/runner/work/phpstan-src/phpstan-src/build/stubs/PhpParserNode.stub',
				'/home/runner/work/phpstan-src/phpstan-src/build/stubs/PhpParserExpr.stub',
				'/home/runner/work/phpstan-src/phpstan-src/build/stubs/PhpParserStmt.stub',
				'/home/runner/work/phpstan-src/phpstan-src/build/stubs/Identifier.stub',
			],
			['/home/runner/work/phpstan-src/phpstan-src']
		);
	}


	public function createService0229(): PHPStan\PhpDoc\ReflectionEnumStubFilesExtension
	{
		return new PHPStan\PhpDoc\ReflectionEnumStubFilesExtension($this->getService('0453'));
	}


	public function createService0230(): PHPStan\PhpDoc\JsonValidateStubFilesExtension
	{
		return new PHPStan\PhpDoc\JsonValidateStubFilesExtension($this->getService('0453'));
	}


	public function createService0231(): PHPStan\PhpDoc\TypeStringResolver
	{
		return new PHPStan\PhpDoc\TypeStringResolver($this->getService('0844'), $this->getService('0845'), $this->getService('0233'));
	}


	public function createService0232(): PHPStan\PhpDoc\PhpDocNodeResolver
	{
		return new PHPStan\PhpDoc\PhpDocNodeResolver($this->getService('0233'), $this->getService('0235'), $this->getService('070'));
	}


	public function createService0233(): PHPStan\PhpDoc\TypeNodeResolver
	{
		return new PHPStan\PhpDoc\TypeNodeResolver(
			$this->getService('0223'),
			$this->getService('0193'),
			$this->getService('0261'),
			$this->getService('0159'),
			$this->getService('0204')
		);
	}


	public function createService0234(): PHPStan\PhpDoc\PhpDocStringResolver
	{
		return new PHPStan\PhpDoc\PhpDocStringResolver($this->getService('0844'), $this->getService('0847'));
	}


	public function createService0235(): PHPStan\PhpDoc\ConstExprNodeResolver
	{
		return new PHPStan\PhpDoc\ConstExprNodeResolver($this->getService('0193'), $this->getService('0204'));
	}


	public function createService0236(): PHPStan\Parser\UseAliasVisitor
	{
		return new PHPStan\Parser\UseAliasVisitor;
	}


	public function createService0237(): PHPStan\Parser\TypeTraverserInstanceofVisitor
	{
		return new PHPStan\Parser\TypeTraverserInstanceofVisitor;
	}


	public function createService0238(): PHPStan\Parser\CurlSetOptArgVisitor
	{
		return new PHPStan\Parser\CurlSetOptArgVisitor;
	}


	public function createService0239(): PHPStan\Parser\GotoLabelVisitor
	{
		return new PHPStan\Parser\GotoLabelVisitor;
	}


	public function createService0240(): PHPStan\Parser\ArrayMapArgVisitor
	{
		return new PHPStan\Parser\ArrayMapArgVisitor;
	}


	public function createService0241(): PHPStan\Parser\MagicConstantParamDefaultVisitor
	{
		return new PHPStan\Parser\MagicConstantParamDefaultVisitor;
	}


	public function createService0242(): PHPStan\Parser\ArrayFilterArgVisitor
	{
		return new PHPStan\Parser\ArrayFilterArgVisitor;
	}


	public function createService0243(): PHPStan\Parser\TryCatchTypeVisitor
	{
		return new PHPStan\Parser\TryCatchTypeVisitor;
	}


	public function createService0244(): PHPStan\Parser\CurlSetOptArrayArgVisitor
	{
		return new PHPStan\Parser\CurlSetOptArrayArgVisitor;
	}


	public function createService0245(): PHPStan\Parser\ClosureBindArgVisitor
	{
		return new PHPStan\Parser\ClosureBindArgVisitor;
	}


	public function createService0246(): PHPStan\Parser\ArrayFindArgVisitor
	{
		return new PHPStan\Parser\ArrayFindArgVisitor;
	}


	public function createService0247(): PHPStan\Parser\AnonymousClassVisitor
	{
		return new PHPStan\Parser\AnonymousClassVisitor;
	}


	public function createService0248(): PHPStan\Parser\ClosureArgVisitor
	{
		return new PHPStan\Parser\ClosureArgVisitor;
	}


	public function createService0249(): PHPStan\Parser\StandaloneThrowExprVisitor
	{
		return new PHPStan\Parser\StandaloneThrowExprVisitor;
	}


	public function createService0250(): PHPStan\Parser\ParentStmtTypesVisitor
	{
		return new PHPStan\Parser\ParentStmtTypesVisitor;
	}


	public function createService0251(): PHPStan\Parser\DeclarePositionVisitor
	{
		return new PHPStan\Parser\DeclarePositionVisitor;
	}


	public function createService0252(): PHPStan\Parser\ArrowFunctionArgVisitor
	{
		return new PHPStan\Parser\ArrowFunctionArgVisitor;
	}


	public function createService0253(): PHPStan\Parser\ClosureBindToVarVisitor
	{
		return new PHPStan\Parser\ClosureBindToVarVisitor;
	}


	public function createService0254(): PHPStan\Parser\LexerFactory
	{
		return new PHPStan\Parser\LexerFactory($this->getService('0453'));
	}


	public function createService0255(): PHPStan\Parser\NewAssignedToPropertyVisitor
	{
		return new PHPStan\Parser\NewAssignedToPropertyVisitor;
	}


	public function createService0256(): PHPStan\Parser\ImplodeArgVisitor
	{
		return new PHPStan\Parser\ImplodeArgVisitor;
	}


	public function createService0257(): PHPStan\Parser\LastConditionVisitor
	{
		return new PHPStan\Parser\LastConditionVisitor;
	}


	public function createService0258(): PHPStan\Parser\ImmediatelyInvokedClosureVisitor
	{
		return new PHPStan\Parser\ImmediatelyInvokedClosureVisitor;
	}


	public function createService0259(): PHPStan\Parser\ArrayWalkArgVisitor
	{
		return new PHPStan\Parser\ArrayWalkArgVisitor;
	}


	public function createService0260(): PHPStan\Type\BitwiseFlagHelper
	{
		return new PHPStan\Type\BitwiseFlagHelper($this->getService('reflectionProvider'));
	}


	public function createService0261(): PHPStan\Type\LazyTypeAliasResolverProvider
	{
		return new PHPStan\Type\LazyTypeAliasResolverProvider($this->getService('0179'));
	}


	public function createService0262(): PHPStan\Type\Regex\RegexGroupParser
	{
		return new PHPStan\Type\Regex\RegexGroupParser($this->getService('0453'), $this->getService('0263'));
	}


	public function createService0263(): PHPStan\Type\Regex\RegexExpressionHelper
	{
		return new PHPStan\Type\Regex\RegexExpressionHelper($this->getService('0204'));
	}


	public function createService0264(): PHPStan\Type\Constant\OversizedArrayBuilder
	{
		return new PHPStan\Type\Constant\OversizedArrayBuilder;
	}


	public function createService0265(): PHPStan\Type\ClosureTypeFactory
	{
		return new PHPStan\Type\ClosureTypeFactory(
			$this->getService('0204'),
			$this->getService('0850'),
			$this->getService('betterReflectionReflector'),
			$this->getService('0193'),
			$this->getService('currentPhpVersionPhpParser')
		);
	}


	public function createService0266(): PHPStan\Type\UsefulTypeAliasResolver
	{
		return new PHPStan\Type\UsefulTypeAliasResolver(
			[],
			$this->getService('0231'),
			$this->getService('0233'),
			$this->getService('reflectionProvider')
		);
	}


	public function createService0267(): PHPStan\Type\PHPStan\ClassNameUsageLocationCreateIdentifierDynamicReturnTypeExtension
	{
		return new PHPStan\Type\PHPStan\ClassNameUsageLocationCreateIdentifierDynamicReturnTypeExtension;
	}


	public function createService0268(): PHPStan\Type\Php\ArrayKeyDynamicReturnTypeExtension
	{
		return new PHPStan\Type\Php\ArrayKeyDynamicReturnTypeExtension;
	}


	public function createService0269(): PHPStan\Type\Php\DateFunctionReturnTypeExtension
	{
		return new PHPStan\Type\Php\DateFunctionReturnTypeExtension($this->getService('0350'));
	}


	public function createService0270(): PHPStan\Type\Php\BackedEnumFromMethodDynamicReturnTypeExtension
	{
		return new PHPStan\Type\Php\BackedEnumFromMethodDynamicReturnTypeExtension;
	}


	public function createService0271(): PHPStan\Type\Php\ArrayPopFunctionReturnTypeExtension
	{
		return new PHPStan\Type\Php\ArrayPopFunctionReturnTypeExtension;
	}


	public function createService0272(): PHPStan\Type\Php\FilterVarDynamicReturnTypeExtension
	{
		return new PHPStan\Type\Php\FilterVarDynamicReturnTypeExtension($this->getService('0425'));
	}


	public function createService0273(): PHPStan\Type\Php\DateIntervalFormatDynamicReturnTypeExtension
	{
		return new PHPStan\Type\Php\DateIntervalFormatDynamicReturnTypeExtension($this->getService('0314'));
	}


	public function createService0274(): PHPStan\Type\Php\ArrayColumnHelper
	{
		return new PHPStan\Type\Php\ArrayColumnHelper($this->getService('0453'));
	}


	public function createService0275(): PHPStan\Type\Php\CtypeDigitFunctionTypeSpecifyingExtension
	{
		return new PHPStan\Type\Php\CtypeDigitFunctionTypeSpecifyingExtension;
	}


	public function createService0276(): PHPStan\Type\Php\ArraySumFunctionDynamicReturnTypeExtension
	{
		return new PHPStan\Type\Php\ArraySumFunctionDynamicReturnTypeExtension;
	}


	public function createService0277(): PHPStan\Type\Php\IsCallableFunctionTypeSpecifyingExtension
	{
		return new PHPStan\Type\Php\IsCallableFunctionTypeSpecifyingExtension($this->getService('0373'));
	}


	public function createService0278(): PHPStan\Type\Php\ArrayFindKeyFunctionReturnTypeExtension
	{
		return new PHPStan\Type\Php\ArrayFindKeyFunctionReturnTypeExtension;
	}


	public function createService0279(): PHPStan\Type\Php\SimpleXMLElementConstructorThrowTypeExtension
	{
		return new PHPStan\Type\Php\SimpleXMLElementConstructorThrowTypeExtension;
	}


	public function createService0280(): PHPStan\Type\Php\PropertyExistsTypeSpecifyingExtension
	{
		return new PHPStan\Type\Php\PropertyExistsTypeSpecifyingExtension($this->getService('040'));
	}


	public function createService0281(): PHPStan\Type\Php\StrtotimeFunctionReturnTypeExtension
	{
		return new PHPStan\Type\Php\StrtotimeFunctionReturnTypeExtension;
	}


	public function createService0282(): PHPStan\Type\Php\PregReplaceCallbackClosureTypeExtension
	{
		return new PHPStan\Type\Php\PregReplaceCallbackClosureTypeExtension($this->getService('0340'));
	}


	public function createService0283(): PHPStan\Type\Php\GetDefinedVarsFunctionReturnTypeExtension
	{
		return new PHPStan\Type\Php\GetDefinedVarsFunctionReturnTypeExtension;
	}


	public function createService0284(): PHPStan\Type\Php\TypeSpecifyingFunctionsDynamicReturnTypeExtension
	{
		return new PHPStan\Type\Php\TypeSpecifyingFunctionsDynamicReturnTypeExtension($this->getService('reflectionProvider'), true);
	}


	public function createService0285(): PHPStan\Type\Php\BcMathStringOrNullReturnTypeExtension
	{
		return new PHPStan\Type\Php\BcMathStringOrNullReturnTypeExtension($this->getService('0453'));
	}


	public function createService0286(): PHPStan\Type\Php\ClosureFromCallableDynamicReturnTypeExtension
	{
		return new PHPStan\Type\Php\ClosureFromCallableDynamicReturnTypeExtension;
	}


	public function createService0287(): PHPStan\Type\Php\VersionCompareFunctionDynamicThrowTypeExtension
	{
		return new PHPStan\Type\Php\VersionCompareFunctionDynamicThrowTypeExtension($this->getService('0453'));
	}


	public function createService0288(): PHPStan\Type\Php\StrPadFunctionReturnTypeExtension
	{
		return new PHPStan\Type\Php\StrPadFunctionReturnTypeExtension;
	}


	public function createService0289(): PHPStan\Type\Php\PathinfoFunctionDynamicReturnTypeExtension
	{
		return new PHPStan\Type\Php\PathinfoFunctionDynamicReturnTypeExtension($this->getService('reflectionProvider'));
	}


	public function createService0290(): PHPStan\Type\Php\ArrayKeysFunctionDynamicReturnTypeExtension
	{
		return new PHPStan\Type\Php\ArrayKeysFunctionDynamicReturnTypeExtension($this->getService('0453'));
	}


	public function createService0291(): PHPStan\Type\Php\ArrayMapFunctionReturnTypeExtension
	{
		return new PHPStan\Type\Php\ArrayMapFunctionReturnTypeExtension;
	}


	public function createService0292(): PHPStan\Type\Php\ArrayMergeFunctionDynamicReturnTypeExtension
	{
		return new PHPStan\Type\Php\ArrayMergeFunctionDynamicReturnTypeExtension;
	}


	public function createService0293(): PHPStan\Type\Php\GettypeFunctionReturnTypeExtension
	{
		return new PHPStan\Type\Php\GettypeFunctionReturnTypeExtension;
	}


	public function createService0294(): PHPStan\Type\Php\DateTimeCreateDynamicReturnTypeExtension
	{
		return new PHPStan\Type\Php\DateTimeCreateDynamicReturnTypeExtension;
	}


	public function createService0295(): PHPStan\Type\Php\StrSplitFunctionReturnTypeExtension
	{
		return new PHPStan\Type\Php\StrSplitFunctionReturnTypeExtension($this->getService('0453'));
	}


	public function createService0296(): PHPStan\Type\Php\ReflectionMethodConstructorThrowTypeExtension
	{
		return new PHPStan\Type\Php\ReflectionMethodConstructorThrowTypeExtension($this->getService('reflectionProvider'));
	}


	public function createService0297(): PHPStan\Type\Php\PowFunctionReturnTypeExtension
	{
		return new PHPStan\Type\Php\PowFunctionReturnTypeExtension;
	}


	public function createService0298(): PHPStan\Type\Php\StrIncrementDecrementFunctionReturnTypeExtension
	{
		return new PHPStan\Type\Php\StrIncrementDecrementFunctionReturnTypeExtension;
	}


	public function createService0299(): PHPStan\Type\Php\StrWordCountFunctionDynamicReturnTypeExtension
	{
		return new PHPStan\Type\Php\StrWordCountFunctionDynamicReturnTypeExtension;
	}


	public function createService0300(): PHPStan\Type\Php\FilterVarThrowTypeExtension
	{
		return new PHPStan\Type\Php\FilterVarThrowTypeExtension($this->getService('reflectionProvider'), $this->getService('0453'));
	}


	public function createService0301(): PHPStan\Type\Php\ReflectionPropertyConstructorThrowTypeExtension
	{
		return new PHPStan\Type\Php\ReflectionPropertyConstructorThrowTypeExtension($this->getService('reflectionProvider'));
	}


	public function createService0302(): PHPStan\Type\Php\ArrayCombineFunctionReturnTypeExtension
	{
		return new PHPStan\Type\Php\ArrayCombineFunctionReturnTypeExtension($this->getService('0348'), $this->getService('0453'));
	}


	public function createService0303(): PHPStan\Type\Php\GetParentClassDynamicFunctionReturnTypeExtension
	{
		return new PHPStan\Type\Php\GetParentClassDynamicFunctionReturnTypeExtension($this->getService('reflectionProvider'));
	}


	public function createService0304(): PHPStan\Type\Php\DateIntervalDynamicReturnTypeExtension
	{
		return new PHPStan\Type\Php\DateIntervalDynamicReturnTypeExtension($this->getService('0453'));
	}


	public function createService0305(): PHPStan\Type\Php\SimpleXMLElementAsXMLMethodReturnTypeExtension
	{
		return new PHPStan\Type\Php\SimpleXMLElementAsXMLMethodReturnTypeExtension;
	}


	public function createService0306(): PHPStan\Type\Php\FilterVarArrayDynamicReturnTypeExtension
	{
		return new PHPStan\Type\Php\FilterVarArrayDynamicReturnTypeExtension(
			$this->getService('0425'),
			$this->getService('reflectionProvider')
		);
	}


	public function createService0307(): PHPStan\Type\Php\IsIterableFunctionTypeSpecifyingExtension
	{
		return new PHPStan\Type\Php\IsIterableFunctionTypeSpecifyingExtension;
	}


	public function createService0308(): PHPStan\Type\Php\MbSubstituteCharacterDynamicReturnTypeExtension
	{
		return new PHPStan\Type\Php\MbSubstituteCharacterDynamicReturnTypeExtension($this->getService('0453'));
	}


	public function createService0309(): PHPStan\Type\Php\OpenSslCipherMethodsProvider
	{
		return new PHPStan\Type\Php\OpenSslCipherMethodsProvider;
	}


	public function createService0310(): PHPStan\Type\Php\MicrotimeFunctionReturnTypeExtension
	{
		return new PHPStan\Type\Php\MicrotimeFunctionReturnTypeExtension;
	}


	public function createService0311(): PHPStan\Type\Php\ClassImplementsFunctionReturnTypeExtension
	{
		return new PHPStan\Type\Php\ClassImplementsFunctionReturnTypeExtension;
	}


	public function createService0312(): PHPStan\Type\Php\ArrayFilterFunctionReturnTypeExtension
	{
		return new PHPStan\Type\Php\ArrayFilterFunctionReturnTypeExtension($this->getService('0336'));
	}


	public function createService0313(): PHPStan\Type\Php\Base64DecodeDynamicFunctionReturnTypeExtension
	{
		return new PHPStan\Type\Php\Base64DecodeDynamicFunctionReturnTypeExtension;
	}


	public function createService0314(): PHPStan\Type\Php\DateIntervalFormatReturnTypeHelper
	{
		return new PHPStan\Type\Php\DateIntervalFormatReturnTypeHelper;
	}


	public function createService0315(): PHPStan\Type\Php\ClosureGetCurrentDynamicReturnTypeExtension
	{
		return new PHPStan\Type\Php\ClosureGetCurrentDynamicReturnTypeExtension;
	}


	public function createService0316(): PHPStan\Type\Php\ArrayCountValuesDynamicReturnTypeExtension
	{
		return new PHPStan\Type\Php\ArrayCountValuesDynamicReturnTypeExtension;
	}


	public function createService0317(): PHPStan\Type\Php\SimpleXMLElementClassPropertyReflectionExtension
	{
		return new PHPStan\Type\Php\SimpleXMLElementClassPropertyReflectionExtension;
	}


	public function createService0318(): PHPStan\Type\Php\PDOConnectReturnTypeExtension
	{
		return new PHPStan\Type\Php\PDOConnectReturnTypeExtension($this->getService('0453'));
	}


	public function createService0319(): PHPStan\Type\Php\MbStrlenFunctionReturnTypeExtension
	{
		return new PHPStan\Type\Php\MbStrlenFunctionReturnTypeExtension($this->getService('0453'));
	}


	public function createService0320(): PHPStan\Type\Php\GetDebugTypeFunctionReturnTypeExtension
	{
		return new PHPStan\Type\Php\GetDebugTypeFunctionReturnTypeExtension;
	}


	public function createService0321(): PHPStan\Type\Php\DateTimeSubMethodThrowTypeExtension
	{
		return new PHPStan\Type\Php\DateTimeSubMethodThrowTypeExtension($this->getService('0453'));
	}


	public function createService0322(): PHPStan\Type\Php\ConstantFunctionReturnTypeExtension
	{
		return new PHPStan\Type\Php\ConstantFunctionReturnTypeExtension($this->getService('0370'));
	}


	public function createService0323(): PHPStan\Type\Php\PregFilterFunctionReturnTypeExtension
	{
		return new PHPStan\Type\Php\PregFilterFunctionReturnTypeExtension;
	}


	public function createService0324(): PHPStan\Type\Php\VersionCompareFunctionDynamicReturnTypeExtension
	{
		return new PHPStan\Type\Php\VersionCompareFunctionDynamicReturnTypeExtension(
			80421,
			$this->getService('0452'),
			$this->getService('0453')
		);
	}


	public function createService0325(): PHPStan\Type\Php\DateTimeZoneConstructorThrowTypeExtension
	{
		return new PHPStan\Type\Php\DateTimeZoneConstructorThrowTypeExtension($this->getService('0453'));
	}


	public function createService0326(): PHPStan\Type\Php\ArrayShiftFunctionReturnTypeExtension
	{
		return new PHPStan\Type\Php\ArrayShiftFunctionReturnTypeExtension;
	}


	public function createService0327(): PHPStan\Type\Php\DatePeriodConstructorReturnTypeExtension
	{
		return new PHPStan\Type\Php\DatePeriodConstructorReturnTypeExtension;
	}


	public function createService0328(): PHPStan\Type\Php\ReflectionFunctionConstructorThrowTypeExtension
	{
		return new PHPStan\Type\Php\ReflectionFunctionConstructorThrowTypeExtension($this->getService('reflectionProvider'));
	}


	public function createService0329(): PHPStan\Type\Php\ArraySliceFunctionReturnTypeExtension
	{
		return new PHPStan\Type\Php\ArraySliceFunctionReturnTypeExtension($this->getService('0453'));
	}


	public function createService0330(): PHPStan\Type\Php\FilterInputDynamicReturnTypeExtension
	{
		return new PHPStan\Type\Php\FilterInputDynamicReturnTypeExtension($this->getService('0425'));
	}


	public function createService0331(): PHPStan\Type\Php\CountFunctionTypeSpecifyingExtension
	{
		return new PHPStan\Type\Php\CountFunctionTypeSpecifyingExtension;
	}


	public function createService0332(): PHPStan\Type\Php\StrlenFunctionReturnTypeExtension
	{
		return new PHPStan\Type\Php\StrlenFunctionReturnTypeExtension;
	}


	public function createService0333(): PHPStan\Type\Php\IteratorToArrayFunctionReturnTypeExtension
	{
		return new PHPStan\Type\Php\IteratorToArrayFunctionReturnTypeExtension;
	}


	public function createService0334(): PHPStan\Type\Php\AbsFunctionDynamicReturnTypeExtension
	{
		return new PHPStan\Type\Php\AbsFunctionDynamicReturnTypeExtension;
	}


	public function createService0335(): PHPStan\Type\Php\IdateFunctionReturnTypeExtension
	{
		return new PHPStan\Type\Php\IdateFunctionReturnTypeExtension($this->getService('0433'));
	}


	public function createService0336(): PHPStan\Type\Php\ArrayFilterFunctionReturnTypeHelper
	{
		return new PHPStan\Type\Php\ArrayFilterFunctionReturnTypeHelper(
			$this->getService('reflectionProvider'),
			$this->getService('0453')
		);
	}


	public function createService0337(): PHPStan\Type\Php\GmpOperatorTypeSpecifyingExtension
	{
		return new PHPStan\Type\Php\GmpOperatorTypeSpecifyingExtension;
	}


	public function createService0338(): PHPStan\Type\Php\ThrowableReturnTypeExtension
	{
		return new PHPStan\Type\Php\ThrowableReturnTypeExtension;
	}


	public function createService0339(): PHPStan\Type\Php\DateIntervalCreateFromDateStringThrowTypeExtension
	{
		return new PHPStan\Type\Php\DateIntervalCreateFromDateStringThrowTypeExtension($this->getService('0453'));
	}


	public function createService0340(): PHPStan\Type\Php\RegexArrayShapeMatcher
	{
		return new PHPStan\Type\Php\RegexArrayShapeMatcher(
			$this->getService('0262'),
			$this->getService('0263'),
			$this->getService('0453')
		);
	}


	public function createService0341(): PHPStan\Type\Php\ClassExistsFunctionTypeSpecifyingExtension
	{
		return new PHPStan\Type\Php\ClassExistsFunctionTypeSpecifyingExtension;
	}


	public function createService0342(): PHPStan\Type\Php\ImplodeFunctionReturnTypeExtension
	{
		return new PHPStan\Type\Php\ImplodeFunctionReturnTypeExtension;
	}


	public function createService0343(): PHPStan\Type\Php\HighlightStringDynamicReturnTypeExtension
	{
		return new PHPStan\Type\Php\HighlightStringDynamicReturnTypeExtension($this->getService('0453'));
	}


	public function createService0344(): PHPStan\Type\Php\ArrayRandFunctionReturnTypeExtension
	{
		return new PHPStan\Type\Php\ArrayRandFunctionReturnTypeExtension;
	}


	public function createService0345(): PHPStan\Type\Php\DsMapDynamicMethodThrowTypeExtension
	{
		return new PHPStan\Type\Php\DsMapDynamicMethodThrowTypeExtension;
	}


	public function createService0346(): PHPStan\Type\Php\RangeFunctionReturnTypeExtension
	{
		return new PHPStan\Type\Php\RangeFunctionReturnTypeExtension;
	}


	public function createService0347(): PHPStan\Type\Php\GettimeofdayDynamicFunctionReturnTypeExtension
	{
		return new PHPStan\Type\Php\GettimeofdayDynamicFunctionReturnTypeExtension;
	}


	public function createService0348(): PHPStan\Type\Php\ArrayCombineHelper
	{
		return new PHPStan\Type\Php\ArrayCombineHelper;
	}


	public function createService0349(): PHPStan\Type\Php\ArraySearchFunctionDynamicReturnTypeExtension
	{
		return new PHPStan\Type\Php\ArraySearchFunctionDynamicReturnTypeExtension($this->getService('0453'));
	}


	public function createService0350(): PHPStan\Type\Php\DateFunctionReturnTypeHelper
	{
		return new PHPStan\Type\Php\DateFunctionReturnTypeHelper;
	}


	public function createService0351(): PHPStan\Type\Php\DsMapDynamicReturnTypeExtension
	{
		return new PHPStan\Type\Php\DsMapDynamicReturnTypeExtension;
	}


	public function createService0352(): PHPStan\Type\Php\LtrimFunctionReturnTypeExtension
	{
		return new PHPStan\Type\Php\LtrimFunctionReturnTypeExtension;
	}


	public function createService0353(): PHPStan\Type\Php\RoundFunctionReturnTypeExtension
	{
		return new PHPStan\Type\Php\RoundFunctionReturnTypeExtension($this->getService('0453'));
	}


	public function createService0354(): PHPStan\Type\Php\DateTimeConstructorThrowTypeExtension
	{
		return new PHPStan\Type\Php\DateTimeConstructorThrowTypeExtension($this->getService('0453'));
	}


	public function createService0355(): PHPStan\Type\Php\JsonThrowOnErrorDynamicReturnTypeExtension
	{
		return new PHPStan\Type\Php\JsonThrowOnErrorDynamicReturnTypeExtension(
			$this->getService('reflectionProvider'),
			$this->getService('0260')
		);
	}


	public function createService0356(): PHPStan\Type\Php\StrvalFamilyFunctionReturnTypeExtension
	{
		return new PHPStan\Type\Php\StrvalFamilyFunctionReturnTypeExtension;
	}


	public function createService0357(): PHPStan\Type\Php\ArrayReduceFunctionReturnTypeExtension
	{
		return new PHPStan\Type\Php\ArrayReduceFunctionReturnTypeExtension;
	}


	public function createService0358(): PHPStan\Type\Php\RandomIntFunctionReturnTypeExtension
	{
		return new PHPStan\Type\Php\RandomIntFunctionReturnTypeExtension;
	}


	public function createService0359(): PHPStan\Type\Php\IntdivThrowTypeExtension
	{
		return new PHPStan\Type\Php\IntdivThrowTypeExtension;
	}


	public function createService0360(): PHPStan\Type\Php\CountCharsFunctionDynamicReturnTypeExtension
	{
		return new PHPStan\Type\Php\CountCharsFunctionDynamicReturnTypeExtension($this->getService('0453'));
	}


	public function createService0361(): PHPStan\Type\Php\ArrayFillFunctionReturnTypeExtension
	{
		return new PHPStan\Type\Php\ArrayFillFunctionReturnTypeExtension($this->getService('0453'));
	}


	public function createService0362(): PHPStan\Type\Php\ReflectionClassIsSubclassOfTypeSpecifyingExtension
	{
		return new PHPStan\Type\Php\ReflectionClassIsSubclassOfTypeSpecifyingExtension;
	}


	public function createService0363(): PHPStan\Type\Php\MinMaxFunctionReturnTypeExtension
	{
		return new PHPStan\Type\Php\MinMaxFunctionReturnTypeExtension($this->getService('0453'));
	}


	public function createService0364(): PHPStan\Type\Php\SetTypeFunctionTypeSpecifyingExtension
	{
		return new PHPStan\Type\Php\SetTypeFunctionTypeSpecifyingExtension;
	}


	public function createService0365(): PHPStan\Type\Php\SubstrDynamicReturnTypeExtension
	{
		return new PHPStan\Type\Php\SubstrDynamicReturnTypeExtension($this->getService('0453'));
	}


	public function createService0366(): PHPStan\Type\Php\ArrayColumnFunctionReturnTypeExtension
	{
		return new PHPStan\Type\Php\ArrayColumnFunctionReturnTypeExtension($this->getService('0274'));
	}


	public function createService0367(): PHPStan\Type\Php\ParseUrlFunctionDynamicReturnTypeExtension
	{
		return new PHPStan\Type\Php\ParseUrlFunctionDynamicReturnTypeExtension;
	}


	public function createService0368(): PHPStan\Type\Php\DomDocumentCreateElementDynamicThrowTypeExtension
	{
		return new PHPStan\Type\Php\DomDocumentCreateElementDynamicThrowTypeExtension;
	}


	public function createService0369(): PHPStan\Type\Php\HrtimeFunctionReturnTypeExtension
	{
		return new PHPStan\Type\Php\HrtimeFunctionReturnTypeExtension;
	}


	public function createService0370(): PHPStan\Type\Php\ConstantHelper
	{
		return new PHPStan\Type\Php\ConstantHelper;
	}


	public function createService0371(): PHPStan\Type\Php\ExplodeFunctionDynamicReturnTypeExtension
	{
		return new PHPStan\Type\Php\ExplodeFunctionDynamicReturnTypeExtension($this->getService('0453'));
	}


	public function createService0372(): PHPStan\Type\Php\DateIntervalFormatFunctionReturnTypeExtension
	{
		return new PHPStan\Type\Php\DateIntervalFormatFunctionReturnTypeExtension($this->getService('0314'));
	}


	public function createService0373(): PHPStan\Type\Php\MethodExistsTypeSpecifyingExtension
	{
		return new PHPStan\Type\Php\MethodExistsTypeSpecifyingExtension;
	}


	public function createService0374(): PHPStan\Type\Php\ArrayChangeKeyCaseFunctionReturnTypeExtension
	{
		return new PHPStan\Type\Php\ArrayChangeKeyCaseFunctionReturnTypeExtension;
	}


	public function createService0375(): PHPStan\Type\Php\StrTokFunctionReturnTypeExtension
	{
		return new PHPStan\Type\Php\StrTokFunctionReturnTypeExtension;
	}


	public function createService0376(): PHPStan\Type\Php\AssertThrowTypeExtension
	{
		return new PHPStan\Type\Php\AssertThrowTypeExtension;
	}


	public function createService0377(): PHPStan\Type\Php\ArrayPadDynamicReturnTypeExtension
	{
		return new PHPStan\Type\Php\ArrayPadDynamicReturnTypeExtension;
	}


	public function createService0378(): PHPStan\Type\Php\FunctionExistsFunctionTypeSpecifyingExtension
	{
		return new PHPStan\Type\Php\FunctionExistsFunctionTypeSpecifyingExtension;
	}


	public function createService0379(): PHPStan\Type\Php\BcMathNumberOperatorTypeSpecifyingExtension
	{
		return new PHPStan\Type\Php\BcMathNumberOperatorTypeSpecifyingExtension($this->getService('0453'));
	}


	public function createService0380(): PHPStan\Type\Php\ArrayCurrentDynamicReturnTypeExtension
	{
		return new PHPStan\Type\Php\ArrayCurrentDynamicReturnTypeExtension;
	}


	public function createService0381(): PHPStan\Type\Php\ArrayFillKeysFunctionReturnTypeExtension
	{
		return new PHPStan\Type\Php\ArrayFillKeysFunctionReturnTypeExtension($this->getService('0453'));
	}


	public function createService0382(): PHPStan\Type\Php\IsAFunctionTypeSpecifyingExtension
	{
		return new PHPStan\Type\Php\IsAFunctionTypeSpecifyingExtension($this->getService('0387'));
	}


	public function createService0383(): PHPStan\Type\Php\PregMatchParameterOutTypeExtension
	{
		return new PHPStan\Type\Php\PregMatchParameterOutTypeExtension($this->getService('0340'));
	}


	public function createService0384(): PHPStan\Type\Php\ArrayPointerFunctionsDynamicReturnTypeExtension
	{
		return new PHPStan\Type\Php\ArrayPointerFunctionsDynamicReturnTypeExtension;
	}


	public function createService0385(): PHPStan\Type\Php\StrCaseFunctionsReturnTypeExtension
	{
		return new PHPStan\Type\Php\StrCaseFunctionsReturnTypeExtension;
	}


	public function createService0386(): PHPStan\Type\Php\SscanfFunctionDynamicReturnTypeExtension
	{
		return new PHPStan\Type\Php\SscanfFunctionDynamicReturnTypeExtension;
	}


	public function createService0387(): PHPStan\Type\Php\IsAFunctionTypeSpecifyingHelper
	{
		return new PHPStan\Type\Php\IsAFunctionTypeSpecifyingHelper;
	}


	public function createService0388(): PHPStan\Type\Php\ArrayChunkFunctionReturnTypeExtension
	{
		return new PHPStan\Type\Php\ArrayChunkFunctionReturnTypeExtension($this->getService('0453'));
	}


	public function createService0389(): PHPStan\Type\Php\ArrayKeyExistsFunctionTypeSpecifyingExtension
	{
		return new PHPStan\Type\Php\ArrayKeyExistsFunctionTypeSpecifyingExtension($this->getService('0453'));
	}


	public function createService0390(): PHPStan\Type\Php\NonEmptyStringFunctionsReturnTypeExtension
	{
		return new PHPStan\Type\Php\NonEmptyStringFunctionsReturnTypeExtension;
	}


	public function createService0391(): PHPStan\Type\Php\SimpleXMLElementXpathMethodReturnTypeExtension
	{
		return new PHPStan\Type\Php\SimpleXMLElementXpathMethodReturnTypeExtension;
	}


	public function createService0392(): PHPStan\Type\Php\ClosureBindDynamicReturnTypeExtension
	{
		return new PHPStan\Type\Php\ClosureBindDynamicReturnTypeExtension;
	}


	public function createService0393(): PHPStan\Type\Php\InArrayFunctionTypeSpecifyingExtension
	{
		return new PHPStan\Type\Php\InArrayFunctionTypeSpecifyingExtension;
	}


	public function createService0394(): PHPStan\Type\Php\CompactFunctionReturnTypeExtension
	{
		return new PHPStan\Type\Php\CompactFunctionReturnTypeExtension(true);
	}


	public function createService0395(): PHPStan\Type\Php\ArrayFlipFunctionReturnTypeExtension
	{
		return new PHPStan\Type\Php\ArrayFlipFunctionReturnTypeExtension($this->getService('0453'));
	}


	public function createService0396(): PHPStan\Type\Php\DioStatDynamicFunctionReturnTypeExtension
	{
		return new PHPStan\Type\Php\DioStatDynamicFunctionReturnTypeExtension;
	}


	public function createService0397(): PHPStan\Type\Php\CurlGetinfoFunctionDynamicReturnTypeExtension
	{
		return new PHPStan\Type\Php\CurlGetinfoFunctionDynamicReturnTypeExtension($this->getService('reflectionProvider'));
	}


	public function createService0398(): PHPStan\Type\Php\CountFunctionReturnTypeExtension
	{
		return new PHPStan\Type\Php\CountFunctionReturnTypeExtension;
	}


	public function createService0399(): PHPStan\Type\Php\OpensslCipherFunctionsReturnTypeExtension
	{
		return new PHPStan\Type\Php\OpensslCipherFunctionsReturnTypeExtension($this->getService('0453'), $this->getService('0309'));
	}


	public function createService0400(): PHPStan\Type\Php\ArraySearchFunctionTypeSpecifyingExtension
	{
		return new PHPStan\Type\Php\ArraySearchFunctionTypeSpecifyingExtension;
	}


	public function createService0401(): PHPStan\Type\Php\StatDynamicReturnTypeExtension
	{
		return new PHPStan\Type\Php\StatDynamicReturnTypeExtension;
	}


	public function createService0402(): PHPStan\Type\Php\ArraySpliceFunctionReturnTypeExtension
	{
		return new PHPStan\Type\Php\ArraySpliceFunctionReturnTypeExtension($this->getService('0453'));
	}


	public function createService0403(): PHPStan\Type\Php\IsArrayFunctionTypeSpecifyingExtension
	{
		return new PHPStan\Type\Php\IsArrayFunctionTypeSpecifyingExtension;
	}


	public function createService0404(): PHPStan\Type\Php\StrrevFunctionReturnTypeExtension
	{
		return new PHPStan\Type\Php\StrrevFunctionReturnTypeExtension;
	}


	public function createService0405(): PHPStan\Type\Php\PdoStatementFetchAllReturnTypeExtension
	{
		return new PHPStan\Type\Php\PdoStatementFetchAllReturnTypeExtension;
	}


	public function createService0406(): PHPStan\Type\Php\ArrayIntersectKeyFunctionReturnTypeExtension
	{
		return new PHPStan\Type\Php\ArrayIntersectKeyFunctionReturnTypeExtension($this->getService('0453'));
	}


	public function createService0407(): PHPStan\Type\Php\OpenSslEncryptParameterOutTypeExtension
	{
		return new PHPStan\Type\Php\OpenSslEncryptParameterOutTypeExtension($this->getService('0309'));
	}


	public function createService0408(): PHPStan\Type\Php\JsonThrowTypeExtension
	{
		return new PHPStan\Type\Php\JsonThrowTypeExtension($this->getService('reflectionProvider'), $this->getService('0260'));
	}


	public function createService0409(): PHPStan\Type\Php\NumberFormatFunctionDynamicReturnTypeExtension
	{
		return new PHPStan\Type\Php\NumberFormatFunctionDynamicReturnTypeExtension;
	}


	public function createService0410(): PHPStan\Type\Php\DefinedConstantTypeSpecifyingExtension
	{
		return new PHPStan\Type\Php\DefinedConstantTypeSpecifyingExtension($this->getService('0370'));
	}


	public function createService0411(): PHPStan\Type\Php\GetCalledClassDynamicReturnTypeExtension
	{
		return new PHPStan\Type\Php\GetCalledClassDynamicReturnTypeExtension;
	}


	public function createService0412(): PHPStan\Type\Php\HashFunctionsReturnTypeExtension
	{
		return new PHPStan\Type\Php\HashFunctionsReturnTypeExtension($this->getService('0453'));
	}


	public function createService0413(): PHPStan\Type\Php\DateTimeDynamicReturnTypeExtension
	{
		return new PHPStan\Type\Php\DateTimeDynamicReturnTypeExtension;
	}


	public function createService0414(): PHPStan\Type\Php\AssertFunctionTypeSpecifyingExtension
	{
		return new PHPStan\Type\Php\AssertFunctionTypeSpecifyingExtension;
	}


	public function createService0415(): PHPStan\Type\Php\SprintfFunctionDynamicReturnTypeExtension
	{
		return new PHPStan\Type\Php\SprintfFunctionDynamicReturnTypeExtension;
	}


	public function createService0416(): PHPStan\Type\Php\MbFunctionsReturnTypeExtension
	{
		return new PHPStan\Type\Php\MbFunctionsReturnTypeExtension($this->getService('0453'));
	}


	public function createService0417(): PHPStan\Type\Php\DomDocumentCreateElementDynamicReturnTypeExtension
	{
		return new PHPStan\Type\Php\DomDocumentCreateElementDynamicReturnTypeExtension;
	}


	public function createService0418(): PHPStan\Type\Php\ReflectionClassConstructorThrowTypeExtension
	{
		return new PHPStan\Type\Php\ReflectionClassConstructorThrowTypeExtension;
	}


	public function createService0419(): PHPStan\Type\Php\TrimFunctionDynamicReturnTypeExtension
	{
		return new PHPStan\Type\Php\TrimFunctionDynamicReturnTypeExtension;
	}


	public function createService0420(): PHPStan\Type\Php\ArrayReverseFunctionReturnTypeExtension
	{
		return new PHPStan\Type\Php\ArrayReverseFunctionReturnTypeExtension($this->getService('0453'));
	}


	public function createService0421(): PHPStan\Type\Php\ArrayFindFunctionReturnTypeExtension
	{
		return new PHPStan\Type\Php\ArrayFindFunctionReturnTypeExtension($this->getService('0336'));
	}


	public function createService0422(): PHPStan\Type\Php\TriggerErrorDynamicReturnTypeExtension
	{
		return new PHPStan\Type\Php\TriggerErrorDynamicReturnTypeExtension($this->getService('0453'));
	}


	public function createService0423(): PHPStan\Type\Php\MbConvertEncodingFunctionReturnTypeExtension
	{
		return new PHPStan\Type\Php\MbConvertEncodingFunctionReturnTypeExtension($this->getService('0453'));
	}


	public function createService0424(): PHPStan\Type\Php\StrRepeatFunctionReturnTypeExtension
	{
		return new PHPStan\Type\Php\StrRepeatFunctionReturnTypeExtension;
	}


	public function createService0425(): PHPStan\Type\Php\FilterFunctionReturnTypeHelper
	{
		return new PHPStan\Type\Php\FilterFunctionReturnTypeHelper($this->getService('reflectionProvider'), $this->getService('0453'));
	}


	public function createService0426(): PHPStan\Type\Php\DateTimeModifyMethodThrowTypeExtension
	{
		return new PHPStan\Type\Php\DateTimeModifyMethodThrowTypeExtension($this->getService('0453'));
	}


	public function createService0427(): PHPStan\Type\Php\PregSplitDynamicReturnTypeExtension
	{
		return new PHPStan\Type\Php\PregSplitDynamicReturnTypeExtension($this->getService('0260'));
	}


	public function createService0428(): PHPStan\Type\Php\ParseStrParameterOutTypeExtension
	{
		return new PHPStan\Type\Php\ParseStrParameterOutTypeExtension;
	}


	public function createService0429(): PHPStan\Type\Php\GmpUnaryOperatorTypeSpecifyingExtension
	{
		return new PHPStan\Type\Php\GmpUnaryOperatorTypeSpecifyingExtension;
	}


	public function createService0430(): PHPStan\Type\Php\ArrayValuesFunctionDynamicReturnTypeExtension
	{
		return new PHPStan\Type\Php\ArrayValuesFunctionDynamicReturnTypeExtension($this->getService('0453'));
	}


	public function createService0431(): PHPStan\Type\Php\ArgumentBasedFunctionReturnTypeExtension
	{
		return new PHPStan\Type\Php\ArgumentBasedFunctionReturnTypeExtension;
	}


	public function createService0432(): PHPStan\Type\Php\DateFormatFunctionReturnTypeExtension
	{
		return new PHPStan\Type\Php\DateFormatFunctionReturnTypeExtension($this->getService('0350'));
	}


	public function createService0433(): PHPStan\Type\Php\IdateFunctionReturnTypeHelper
	{
		return new PHPStan\Type\Php\IdateFunctionReturnTypeHelper;
	}


	public function createService0434(): PHPStan\Type\Php\ArrayNextDynamicReturnTypeExtension
	{
		return new PHPStan\Type\Php\ArrayNextDynamicReturnTypeExtension;
	}


	public function createService0435(): PHPStan\Type\Php\ClosureBindToDynamicReturnTypeExtension
	{
		return new PHPStan\Type\Php\ClosureBindToDynamicReturnTypeExtension;
	}


	public function createService0436(): PHPStan\Type\Php\DefineConstantTypeSpecifyingExtension
	{
		return new PHPStan\Type\Php\DefineConstantTypeSpecifyingExtension;
	}


	public function createService0437(): PHPStan\Type\Php\GetClassDynamicReturnTypeExtension
	{
		return new PHPStan\Type\Php\GetClassDynamicReturnTypeExtension;
	}


	public function createService0438(): PHPStan\Type\Php\DateFormatMethodReturnTypeExtension
	{
		return new PHPStan\Type\Php\DateFormatMethodReturnTypeExtension($this->getService('0350'));
	}


	public function createService0439(): PHPStan\Type\Php\DateIntervalConstructorThrowTypeExtension
	{
		return new PHPStan\Type\Php\DateIntervalConstructorThrowTypeExtension($this->getService('0453'));
	}


	public function createService0440(): PHPStan\Type\Php\ArrayCombineFunctionThrowTypeExtension
	{
		return new PHPStan\Type\Php\ArrayCombineFunctionThrowTypeExtension($this->getService('0348'));
	}


	public function createService0441(): PHPStan\Type\Php\IniGetReturnTypeExtension
	{
		return new PHPStan\Type\Php\IniGetReturnTypeExtension;
	}


	public function createService0442(): PHPStan\Type\Php\PregMatchTypeSpecifyingExtension
	{
		return new PHPStan\Type\Php\PregMatchTypeSpecifyingExtension($this->getService('0340'));
	}


	public function createService0443(): PHPStan\Type\Php\ArrayFirstLastDynamicReturnTypeExtension
	{
		return new PHPStan\Type\Php\ArrayFirstLastDynamicReturnTypeExtension;
	}


	public function createService0444(): PHPStan\Type\Php\XMLReaderOpenReturnTypeExtension
	{
		return new PHPStan\Type\Php\XMLReaderOpenReturnTypeExtension;
	}


	public function createService0445(): PHPStan\Type\Php\ReplaceFunctionsDynamicReturnTypeExtension
	{
		return new PHPStan\Type\Php\ReplaceFunctionsDynamicReturnTypeExtension;
	}


	public function createService0446(): PHPStan\Type\Php\ArrayReplaceFunctionReturnTypeExtension
	{
		return new PHPStan\Type\Php\ArrayReplaceFunctionReturnTypeExtension;
	}


	public function createService0447(): PHPStan\Type\Php\StrContainingTypeSpecifyingExtension
	{
		return new PHPStan\Type\Php\StrContainingTypeSpecifyingExtension;
	}


	public function createService0448(): PHPStan\Type\Php\IsSubclassOfFunctionTypeSpecifyingExtension
	{
		return new PHPStan\Type\Php\IsSubclassOfFunctionTypeSpecifyingExtension($this->getService('0387'));
	}


	public function createService0449(): PHPStan\Type\FileTypeMapper
	{
		return new PHPStan\Type\FileTypeMapper(
			$this->getService('0193'),
			$this->getService('defaultAnalysisParser'),
			$this->getService('0234'),
			$this->getService('0232'),
			$this->getService('0191'),
			$this->getService('0173'),
			$this->getService('01'),
			2048,
			2048
		);
	}


	public function createService0450(): PHPStan\Php\PhpVersionFactory
	{
		return $this->getService('0451')->create();
	}


	public function createService0451(): PHPStan\Php\PhpVersionFactoryFactory
	{
		return new PHPStan\Php\PhpVersionFactoryFactory(80421, ['/home/runner/work/phpstan-src/phpstan-src']);
	}


	public function createService0452(): PHPStan\Php\ComposerPhpVersionFactory
	{
		return new PHPStan\Php\ComposerPhpVersionFactory(['/home/runner/work/phpstan-src/phpstan-src']);
	}


	public function createService0453(): PHPStan\Php\PhpVersion
	{
		return $this->getService('0450')->create();
	}


	public function createService0454(): PHPStan\Analyser\ResultCache\ResultCacheManagerFactory
	{
		return new class ($this) implements PHPStan\Analyser\ResultCache\ResultCacheManagerFactory {
			private $container;


			public function __construct(Container_c66912be09 $container)
			{
				$this->container = $container;
			}


			public function create(array $fileReplacements): PHPStan\Analyser\ResultCache\ResultCacheManager
			{
				return new PHPStan\Analyser\ResultCache\ResultCacheManager(
					$this->container->getService('0179'),
					$this->container->getService('0176'),
					$this->container->getService('fileFinderScan'),
					$this->container->getService('0228'),
					$this->container->getService('0173'),
					'/home/runner/work/phpstan-src/phpstan-src/tmp/resultCache.php',
					$this->container->getParameter('analysedPaths'),
					$this->container->getParameter('analysedPathsFromConfig'),
					['/home/runner/work/phpstan-src/phpstan-src'],
					'8',
					null,
					[
						'/home/runner/work/phpstan-src/phpstan-src/stubs/runtime/ReflectionUnionType.php',
						'/home/runner/work/phpstan-src/phpstan-src/stubs/runtime/ReflectionAttribute.php',
						'/home/runner/work/phpstan-src/phpstan-src/stubs/runtime/Attribute85.php',
						'/home/runner/work/phpstan-src/phpstan-src/stubs/runtime/ReflectionIntersectionType.php',
						'/home/runner/work/phpstan-src/phpstan-src/tests/phpstan-bootstrap.php',
					],
					[],
					[],
					$fileReplacements,
					false,
					[
						['parameters', 'editorUrl'],
						['parameters', 'editorUrlTitle'],
						['parameters', 'errorFormat'],
						['parameters', 'ignoreErrors'],
						['parameters', 'reportUnmatchedIgnoredErrors'],
						['parameters', 'tipsOfTheDay'],
						['parameters', 'parallel'],
						['parameters', 'internalErrorsCountLimit'],
						['parameters', 'cache'],
						['parameters', 'memoryLimitFile'],
						['parameters', 'pro'],
						'parametersSchema',
						'parameters.shipmonkDeadCode.debug.usagesOf',
						'parameters.shipmonkDeadCode.reportTransitivelyDeadMethodAsSeparateError',
					],
					7
				);
			}
		};
	}


	public function createService0455(): PHPStan\Analyser\InternalScopeFactoryFactory
	{
		return new class ($this) implements PHPStan\Analyser\InternalScopeFactoryFactory {
			private $container;


			public function __construct(Container_c66912be09 $container)
			{
				$this->container = $container;
			}


			public function create(?callable $nodeCallback): PHPStan\Analyser\InternalScopeFactory
			{
				return new PHPStan\Analyser\LazyInternalScopeFactory($this->container->getService('0179'), $nodeCallback);
			}
		};
	}


	public function createService0456(): PHPStan\File\FileExcluderRawFactory
	{
		return new class ($this) implements PHPStan\File\FileExcluderRawFactory {
			private $container;


			public function __construct(Container_c66912be09 $container)
			{
				$this->container = $container;
			}


			public function create(array $analyseExcludes): PHPStan\File\FileExcluder
			{
				return new PHPStan\File\FileExcluder($this->container->getService('0173'), $analyseExcludes);
			}
		};
	}


	public function createService0457(): PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedSingleFileSourceLocatorFactory
	{
		return new class ($this) implements PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedSingleFileSourceLocatorFactory {
			private $container;


			public function __construct(Container_c66912be09 $container)
			{
				$this->container = $container;
			}


			public function create(string $fileName): PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedSingleFileSourceLocator
			{
				return new PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedSingleFileSourceLocator(
					$this->container->getService('0198'),
					$this->container->getService('01'),
					$this->container->getService('0453'),
					$fileName
				);
			}
		};
	}


	public function createService0458(): PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedPsrAutoloaderLocatorFactory
	{
		return new class ($this) implements PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedPsrAutoloaderLocatorFactory {
			private $container;


			public function __construct(Container_c66912be09 $container)
			{
				$this->container = $container;
			}


			public function create(PHPStan\BetterReflection\SourceLocator\Type\Composer\Psr\PsrAutoloaderMapping $mapping): PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedPsrAutoloaderLocator
			{
				return new PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedPsrAutoloaderLocator($mapping, $this->container->getService('0197'));
			}
		};
	}


	public function createService0459(): PHPStan\Reflection\ClassReflectionFactory
	{
		return new class ($this) implements PHPStan\Reflection\ClassReflectionFactory {
			private $container;


			public function __construct(Container_c66912be09 $container)
			{
				$this->container = $container;
			}


			public function create(
				string $displayName,
				ReflectionClass $reflection,
				?string $anonymousFilename,
				?PHPStan\Type\Generic\TemplateTypeMap $resolvedTemplateTypeMap,
				?PHPStan\PhpDoc\ResolvedPhpDocBlock $stubPhpDocBlock,
				?string $extraCacheKey = null,
				?PHPStan\Type\Generic\TemplateTypeVarianceMap $resolvedCallSiteVarianceMap = null,
				?bool $finalByKeywordOverride = null
			): PHPStan\Reflection\ClassReflection {
				return new PHPStan\Reflection\ClassReflection(
					$this->container->getService('0459'),
					$this->container->getService('reflectionProvider'),
					$this->container->getService('0204'),
					$this->container->getService('0449'),
					$this->container->getService('stubPhpDocProvider'),
					$this->container->getService('0222'),
					$this->container->getService('0453'),
					$this->container->getService('0208'),
					$this->container->getService('0205'),
					$this->container->getService('0203'),
					$this->container->getService('0182'),
					$displayName,
					$reflection,
					$anonymousFilename,
					$resolvedTemplateTypeMap,
					$stubPhpDocBlock,
					$extraCacheKey,
					$resolvedCallSiteVarianceMap,
					$finalByKeywordOverride
				);
			}
		};
	}


	public function createService0460(): PHPStan\Reflection\Php\PhpMethodReflectionFactory
	{
		return new class ($this) implements PHPStan\Reflection\Php\PhpMethodReflectionFactory {
			private $container;


			public function __construct(Container_c66912be09 $container)
			{
				$this->container = $container;
			}


			public function create(
				PHPStan\Reflection\ClassReflection $declaringClass,
				?PHPStan\Reflection\ClassReflection $declaringTrait,
				PHPStan\BetterReflection\Reflection\Adapter\ReflectionMethod $reflection,
				PHPStan\Type\Generic\TemplateTypeMap $templateTypeMap,
				array $phpDocParameterTypes,
				?PHPStan\Type\Type $phpDocReturnType,
				?PHPStan\Type\Type $phpDocThrowType,
				?PHPStan\PhpDoc\ResolvedPhpDocBlock $resolvedPhpDocBlock,
				?string $deprecatedDescription,
				bool $isDeprecated,
				bool $isInternal,
				bool $isFinal,
				?bool $isPure,
				PHPStan\Reflection\Assertions $asserts,
				?PHPStan\Type\Type $selfOutType,
				?string $phpDocComment,
				array $phpDocParameterOutTypes,
				array $immediatelyInvokedCallableParameters,
				array $phpDocClosureThisTypeParameters,
				bool $acceptsNamedArguments,
				array $attributes
			): PHPStan\Reflection\Php\PhpMethodReflection {
				return new PHPStan\Reflection\Php\PhpMethodReflection(
					$this->container->getService('0204'),
					$declaringClass,
					$declaringTrait,
					$reflection,
					$this->container->getService('reflectionProvider'),
					$this->container->getService('0203'),
					$templateTypeMap,
					$phpDocParameterTypes,
					$phpDocReturnType,
					$phpDocThrowType,
					$resolvedPhpDocBlock,
					$deprecatedDescription,
					$isDeprecated,
					$isInternal,
					$isFinal,
					$isPure,
					$asserts,
					$acceptsNamedArguments,
					$selfOutType,
					$phpDocComment,
					$phpDocParameterOutTypes,
					$immediatelyInvokedCallableParameters,
					$phpDocClosureThisTypeParameters,
					$attributes
				);
			}
		};
	}


	public function createService0461(): PHPStan\Reflection\FunctionReflectionFactory
	{
		return new class ($this) implements PHPStan\Reflection\FunctionReflectionFactory {
			private $container;


			public function __construct(Container_c66912be09 $container)
			{
				$this->container = $container;
			}


			public function create(
				PHPStan\BetterReflection\Reflection\Adapter\ReflectionFunction $reflection,
				PHPStan\Type\Generic\TemplateTypeMap $templateTypeMap,
				array $phpDocParameterTypes,
				?PHPStan\Type\Type $phpDocReturnType,
				?PHPStan\Type\Type $phpDocThrowType,
				?string $deprecatedDescription,
				bool $isDeprecated,
				bool $isInternal,
				?string $filename,
				?bool $isPure,
				PHPStan\Reflection\Assertions $asserts,
				bool $acceptsNamedArguments,
				?string $phpDocComment,
				array $phpDocParameterOutTypes,
				array $phpDocParameterImmediatelyInvokedCallable,
				array $phpDocParameterClosureThisTypes,
				array $attributes
			): PHPStan\Reflection\Php\PhpFunctionReflection {
				return new PHPStan\Reflection\Php\PhpFunctionReflection(
					$this->container->getService('0204'),
					$reflection,
					$this->container->getService('0203'),
					$templateTypeMap,
					$phpDocParameterTypes,
					$phpDocReturnType,
					$phpDocThrowType,
					$deprecatedDescription,
					$isDeprecated,
					$isInternal,
					$filename,
					$isPure,
					$asserts,
					$acceptsNamedArguments,
					$phpDocComment,
					$phpDocParameterOutTypes,
					$phpDocParameterImmediatelyInvokedCallable,
					$phpDocParameterClosureThisTypes,
					$attributes
				);
			}
		};
	}


	public function createService0462(): PHPStan\Rules\Variables\UnsetRule
	{
		return new PHPStan\Rules\Variables\UnsetRule($this->getService('040'), $this->getService('0453'));
	}


	public function createService0463(): PHPStan\Rules\Variables\ThisInStaticStatementRule
	{
		return new PHPStan\Rules\Variables\ThisInStaticStatementRule;
	}


	public function createService0464(): PHPStan\Rules\Variables\InvalidVariableAssignRule
	{
		return new PHPStan\Rules\Variables\InvalidVariableAssignRule;
	}


	public function createService0465(): PHPStan\Rules\Variables\EmptyRule
	{
		return new PHPStan\Rules\Variables\EmptyRule($this->getService('09'));
	}


	public function createService0466(): PHPStan\Rules\Variables\ParameterOutAssignedTypeRule
	{
		return new PHPStan\Rules\Variables\ParameterOutAssignedTypeRule($this->getService('020'));
	}


	public function createService0467(): PHPStan\Rules\Variables\NullCoalesceRule
	{
		return new PHPStan\Rules\Variables\NullCoalesceRule($this->getService('09'));
	}


	public function createService0468(): PHPStan\Rules\Variables\IssetRule
	{
		return new PHPStan\Rules\Variables\IssetRule($this->getService('09'));
	}


	public function createService0469(): PHPStan\Rules\Variables\ThisInGlobalStatementRule
	{
		return new PHPStan\Rules\Variables\ThisInGlobalStatementRule;
	}


	public function createService0470(): PHPStan\Rules\Variables\ParameterOutExecutionEndTypeRule
	{
		return new PHPStan\Rules\Variables\ParameterOutExecutionEndTypeRule($this->getService('020'));
	}


	public function createService0471(): PHPStan\Rules\Variables\DefinedVariableRule
	{
		return new PHPStan\Rules\Variables\DefinedVariableRule(true, true);
	}


	public function createService0472(): PHPStan\Rules\Variables\CompactVariablesRule
	{
		return new PHPStan\Rules\Variables\CompactVariablesRule(true);
	}


	public function createService0473(): PHPStan\Rules\Variables\VariableCloningRule
	{
		return new PHPStan\Rules\Variables\VariableCloningRule($this->getService('020'));
	}


	public function createService0474(): PHPStan\Rules\EnumCases\EnumCaseAttributesRule
	{
		return new PHPStan\Rules\EnumCases\EnumCaseAttributesRule($this->getService('074'));
	}


	public function createService0475(): PHPStan\Rules\EnumCases\EnumCaseOutsideEnumRule
	{
		return new PHPStan\Rules\EnumCases\EnumCaseOutsideEnumRule;
	}


	public function createService0476(): PHPStan\Rules\Keywords\ContinueBreakInLoopRule
	{
		return new PHPStan\Rules\Keywords\ContinueBreakInLoopRule;
	}


	public function createService0477(): PHPStan\Rules\Keywords\GotoUndefinedLabelRule
	{
		return new PHPStan\Rules\Keywords\GotoUndefinedLabelRule;
	}


	public function createService0478(): PHPStan\Rules\Keywords\DeclareStrictTypesRule
	{
		return new PHPStan\Rules\Keywords\DeclareStrictTypesRule($this->getService('0170'));
	}


	public function createService0479(): PHPStan\Rules\Keywords\RequireFileExistsRule
	{
		return new PHPStan\Rules\Keywords\RequireFileExistsRule('/home/runner/work/phpstan-src/phpstan-src');
	}


	public function createService0480(): PHPStan\Rules\Missing\MissingReturnRule
	{
		return new PHPStan\Rules\Missing\MissingReturnRule(true, true);
	}


	public function createService0481(): PHPStan\Rules\Pure\PureMethodRule
	{
		return new PHPStan\Rules\Pure\PureMethodRule($this->getService('010'));
	}


	public function createService0482(): PHPStan\Rules\Pure\PureFunctionRule
	{
		return new PHPStan\Rules\Pure\PureFunctionRule($this->getService('010'));
	}


	public function createService0483(): PHPStan\Rules\Names\UsedNamesRule
	{
		return new PHPStan\Rules\Names\UsedNamesRule;
	}


	public function createService0484(): PHPStan\Rules\Generics\MethodTagTemplateTypeTraitRule
	{
		return new PHPStan\Rules\Generics\MethodTagTemplateTypeTraitRule(
			$this->getService('014'),
			$this->getService('reflectionProvider')
		);
	}


	public function createService0485(): PHPStan\Rules\Generics\EnumTemplateTypeRule
	{
		return new PHPStan\Rules\Generics\EnumTemplateTypeRule;
	}


	public function createService0486(): PHPStan\Rules\Generics\FunctionTemplateTypeRule
	{
		return new PHPStan\Rules\Generics\FunctionTemplateTypeRule($this->getService('0449'), $this->getService('018'));
	}


	public function createService0487(): PHPStan\Rules\Generics\MethodTagTemplateTypeRule
	{
		return new PHPStan\Rules\Generics\MethodTagTemplateTypeRule($this->getService('014'));
	}


	public function createService0488(): PHPStan\Rules\Generics\MethodSignatureVarianceRule
	{
		return new PHPStan\Rules\Generics\MethodSignatureVarianceRule($this->getService('013'));
	}


	public function createService0489(): PHPStan\Rules\Generics\UsedTraitsRule
	{
		return new PHPStan\Rules\Generics\UsedTraitsRule($this->getService('0449'), $this->getService('015'));
	}


	public function createService0490(): PHPStan\Rules\Generics\TraitTemplateTypeRule
	{
		return new PHPStan\Rules\Generics\TraitTemplateTypeRule($this->getService('0449'), $this->getService('018'));
	}


	public function createService0491(): PHPStan\Rules\Generics\MethodTemplateTypeRule
	{
		return new PHPStan\Rules\Generics\MethodTemplateTypeRule($this->getService('0449'), $this->getService('018'));
	}


	public function createService0492(): PHPStan\Rules\Generics\PropertyVarianceRule
	{
		return new PHPStan\Rules\Generics\PropertyVarianceRule($this->getService('013'));
	}


	public function createService0493(): PHPStan\Rules\Generics\EnumAncestorsRule
	{
		return new PHPStan\Rules\Generics\EnumAncestorsRule($this->getService('015'), $this->getService('016'));
	}


	public function createService0494(): PHPStan\Rules\Generics\ClassTemplateTypeRule
	{
		return new PHPStan\Rules\Generics\ClassTemplateTypeRule($this->getService('018'));
	}


	public function createService0495(): PHPStan\Rules\Generics\FunctionSignatureVarianceRule
	{
		return new PHPStan\Rules\Generics\FunctionSignatureVarianceRule($this->getService('013'));
	}


	public function createService0496(): PHPStan\Rules\Generics\ClassAncestorsRule
	{
		return new PHPStan\Rules\Generics\ClassAncestorsRule($this->getService('015'), $this->getService('016'));
	}


	public function createService0497(): PHPStan\Rules\Generics\InterfaceAncestorsRule
	{
		return new PHPStan\Rules\Generics\InterfaceAncestorsRule($this->getService('015'), $this->getService('016'));
	}


	public function createService0498(): PHPStan\Rules\Generics\InterfaceTemplateTypeRule
	{
		return new PHPStan\Rules\Generics\InterfaceTemplateTypeRule($this->getService('018'));
	}


	public function createService0499(): PHPStan\Rules\DateTimeInstantiationRule
	{
		return new PHPStan\Rules\DateTimeInstantiationRule;
	}


	public function createService0500(): PHPStan\Rules\Namespaces\ExistingNamesInUseRule
	{
		return new PHPStan\Rules\Namespaces\ExistingNamesInUseRule(
			$this->getService('reflectionProvider'),
			$this->getService('060'),
			true,
			true
		);
	}


	public function createService0501(): PHPStan\Rules\Namespaces\ExistingNamesInGroupUseRule
	{
		return new PHPStan\Rules\Namespaces\ExistingNamesInGroupUseRule(
			$this->getService('reflectionProvider'),
			$this->getService('060'),
			true,
			true
		);
	}


	public function createService0502(): PHPStan\Rules\Regexp\RegularExpressionPatternRule
	{
		return new PHPStan\Rules\Regexp\RegularExpressionPatternRule($this->getService('0263'));
	}


	public function createService0503(): PHPStan\Rules\Regexp\RegularExpressionQuotingRule
	{
		return new PHPStan\Rules\Regexp\RegularExpressionQuotingRule($this->getService('reflectionProvider'), $this->getService('0263'));
	}


	public function createService0504(): PHPStan\Rules\Whitespace\FileWhitespaceRule
	{
		return new PHPStan\Rules\Whitespace\FileWhitespaceRule;
	}


	public function createService0505(): PHPStan\Rules\Classes\DuplicateTraitDeclarationRule
	{
		return new PHPStan\Rules\Classes\DuplicateTraitDeclarationRule($this->getService('033'));
	}


	public function createService0506(): PHPStan\Rules\Classes\AccessPrivateConstantThroughStaticRule
	{
		return new PHPStan\Rules\Classes\AccessPrivateConstantThroughStaticRule;
	}


	public function createService0507(): PHPStan\Rules\Classes\AllowedSubTypesRule
	{
		return new PHPStan\Rules\Classes\AllowedSubTypesRule;
	}


	public function createService0508(): PHPStan\Rules\Classes\MethodTagTraitUseRule
	{
		return new PHPStan\Rules\Classes\MethodTagTraitUseRule($this->getService('032'));
	}


	public function createService0509(): PHPStan\Rules\Classes\UnusedConstructorParametersRule
	{
		return new PHPStan\Rules\Classes\UnusedConstructorParametersRule($this->getService('062'));
	}


	public function createService0510(): PHPStan\Rules\Classes\ClassAttributesRule
	{
		return new PHPStan\Rules\Classes\ClassAttributesRule($this->getService('074'));
	}


	public function createService0511(): PHPStan\Rules\Classes\InstantiationRule
	{
		return new PHPStan\Rules\Classes\InstantiationRule(
			$this->getService('0179'),
			$this->getService('reflectionProvider'),
			$this->getService('064'),
			$this->getService('060'),
			$this->getService('036'),
			true
		);
	}


	public function createService0512(): PHPStan\Rules\Classes\PropertyTagTraitRule
	{
		return new PHPStan\Rules\Classes\PropertyTagTraitRule($this->getService('035'), $this->getService('reflectionProvider'));
	}


	public function createService0513(): PHPStan\Rules\Classes\MixinTraitUseRule
	{
		return new PHPStan\Rules\Classes\MixinTraitUseRule($this->getService('034'));
	}


	public function createService0514(): PHPStan\Rules\Classes\PropertyTagRule
	{
		return new PHPStan\Rules\Classes\PropertyTagRule($this->getService('035'));
	}


	public function createService0515(): PHPStan\Rules\Classes\ExistingClassesInInterfaceExtendsRule
	{
		return new PHPStan\Rules\Classes\ExistingClassesInInterfaceExtendsRule(
			$this->getService('060'),
			$this->getService('reflectionProvider'),
			true
		);
	}


	public function createService0516(): PHPStan\Rules\Classes\ReadOnlyClassRule
	{
		return new PHPStan\Rules\Classes\ReadOnlyClassRule($this->getService('0453'));
	}


	public function createService0517(): PHPStan\Rules\Classes\ClassConstantRule
	{
		return new PHPStan\Rules\Classes\ClassConstantRule(
			$this->getService('reflectionProvider'),
			$this->getService('020'),
			$this->getService('060'),
			$this->getService('0453'),
			true
		);
	}


	public function createService0518(): PHPStan\Rules\Classes\ExistingClassInInstanceOfRule
	{
		return new PHPStan\Rules\Classes\ExistingClassInInstanceOfRule(
			$this->getService('reflectionProvider'),
			$this->getService('060'),
			true,
			true
		);
	}


	public function createService0519(): PHPStan\Rules\Classes\MixinTraitRule
	{
		return new PHPStan\Rules\Classes\MixinTraitRule($this->getService('034'), $this->getService('reflectionProvider'));
	}


	public function createService0520(): PHPStan\Rules\Classes\RequireExtendsRule
	{
		return new PHPStan\Rules\Classes\RequireExtendsRule;
	}


	public function createService0521(): PHPStan\Rules\Classes\InstantiationCallableRule
	{
		return new PHPStan\Rules\Classes\InstantiationCallableRule;
	}


	public function createService0522(): PHPStan\Rules\Classes\LocalTypeAliasesRule
	{
		return new PHPStan\Rules\Classes\LocalTypeAliasesRule($this->getService('031'));
	}


	public function createService0523(): PHPStan\Rules\Classes\MixinRule
	{
		return new PHPStan\Rules\Classes\MixinRule($this->getService('034'));
	}


	public function createService0524(): PHPStan\Rules\Classes\InvalidPromotedPropertiesRule
	{
		return new PHPStan\Rules\Classes\InvalidPromotedPropertiesRule($this->getService('0453'));
	}


	public function createService0525(): PHPStan\Rules\Classes\ClassConstantAttributesRule
	{
		return new PHPStan\Rules\Classes\ClassConstantAttributesRule($this->getService('074'));
	}


	public function createService0526(): PHPStan\Rules\Classes\LocalTypeTraitAliasesRule
	{
		return new PHPStan\Rules\Classes\LocalTypeTraitAliasesRule($this->getService('031'), $this->getService('reflectionProvider'));
	}


	public function createService0527(): PHPStan\Rules\Classes\MethodTagRule
	{
		return new PHPStan\Rules\Classes\MethodTagRule($this->getService('032'));
	}


	public function createService0528(): PHPStan\Rules\Classes\MethodTagTraitRule
	{
		return new PHPStan\Rules\Classes\MethodTagTraitRule($this->getService('032'), $this->getService('reflectionProvider'));
	}


	public function createService0529(): PHPStan\Rules\Classes\ImpossibleInstanceOfRule
	{
		return new PHPStan\Rules\Classes\ImpossibleInstanceOfRule($this->getService('020'), true, false, true);
	}


	public function createService0530(): PHPStan\Rules\Classes\RequireImplementsRule
	{
		return new PHPStan\Rules\Classes\RequireImplementsRule;
	}


	public function createService0531(): PHPStan\Rules\Classes\ExistingClassesInClassImplementsRule
	{
		return new PHPStan\Rules\Classes\ExistingClassesInClassImplementsRule(
			$this->getService('060'),
			$this->getService('reflectionProvider'),
			true
		);
	}


	public function createService0532(): PHPStan\Rules\Classes\LocalTypeTraitUseAliasesRule
	{
		return new PHPStan\Rules\Classes\LocalTypeTraitUseAliasesRule($this->getService('031'));
	}


	public function createService0533(): PHPStan\Rules\Classes\ExistingClassInClassExtendsRule
	{
		return new PHPStan\Rules\Classes\ExistingClassInClassExtendsRule(
			$this->getService('060'),
			$this->getService('reflectionProvider'),
			true
		);
	}


	public function createService0534(): PHPStan\Rules\Classes\TraitAttributeClassRule
	{
		return new PHPStan\Rules\Classes\TraitAttributeClassRule;
	}


	public function createService0535(): PHPStan\Rules\Classes\ExistingClassesInEnumImplementsRule
	{
		return new PHPStan\Rules\Classes\ExistingClassesInEnumImplementsRule(
			$this->getService('060'),
			$this->getService('reflectionProvider'),
			true
		);
	}


	public function createService0536(): PHPStan\Rules\Classes\ExistingClassInTraitUseRule
	{
		return new PHPStan\Rules\Classes\ExistingClassInTraitUseRule(
			$this->getService('060'),
			$this->getService('reflectionProvider'),
			true
		);
	}


	public function createService0537(): PHPStan\Rules\Classes\EnumSanityRule
	{
		return new PHPStan\Rules\Classes\EnumSanityRule($this->getService('0204'));
	}


	public function createService0538(): PHPStan\Rules\Classes\NonClassAttributeClassRule
	{
		return new PHPStan\Rules\Classes\NonClassAttributeClassRule;
	}


	public function createService0539(): PHPStan\Rules\Classes\NewStaticRule
	{
		return new PHPStan\Rules\Classes\NewStaticRule($this->getService('0453'), $this->getService('036'));
	}


	public function createService0540(): PHPStan\Rules\Classes\DuplicateDeclarationRule
	{
		return new PHPStan\Rules\Classes\DuplicateDeclarationRule($this->getService('033'));
	}


	public function createService0541(): PHPStan\Rules\Classes\PropertyTagTraitUseRule
	{
		return new PHPStan\Rules\Classes\PropertyTagTraitUseRule($this->getService('035'));
	}


	public function createService0542(): PHPStan\Rules\Constants\ConstantAttributesRule
	{
		return new PHPStan\Rules\Constants\ConstantAttributesRule($this->getService('074'), $this->getService('0453'));
	}


	public function createService0543(): PHPStan\Rules\Constants\OverridingConstantRule
	{
		return new PHPStan\Rules\Constants\OverridingConstantRule(true);
	}


	public function createService0544(): PHPStan\Rules\Constants\ConstantRule
	{
		return new PHPStan\Rules\Constants\ConstantRule(true);
	}


	public function createService0545(): PHPStan\Rules\Constants\NativeTypedClassConstantRule
	{
		return new PHPStan\Rules\Constants\NativeTypedClassConstantRule($this->getService('0453'));
	}


	public function createService0546(): PHPStan\Rules\Constants\MagicConstantContextRule
	{
		return new PHPStan\Rules\Constants\MagicConstantContextRule;
	}


	public function createService0547(): PHPStan\Rules\Constants\ClassAsClassConstantRule
	{
		return new PHPStan\Rules\Constants\ClassAsClassConstantRule;
	}


	public function createService0548(): PHPStan\Rules\Constants\FinalPrivateConstantRule
	{
		return new PHPStan\Rules\Constants\FinalPrivateConstantRule;
	}


	public function createService0549(): PHPStan\Rules\Constants\ValueAssignedToClassConstantRule
	{
		return new PHPStan\Rules\Constants\ValueAssignedToClassConstantRule($this->getService('0159'), true);
	}


	public function createService0550(): PHPStan\Rules\Constants\FinalConstantRule
	{
		return new PHPStan\Rules\Constants\FinalConstantRule($this->getService('0453'));
	}


	public function createService0551(): PHPStan\Rules\Constants\DynamicClassConstantFetchRule
	{
		return new PHPStan\Rules\Constants\DynamicClassConstantFetchRule($this->getService('0453'), $this->getService('020'));
	}


	public function createService0552(): PHPStan\Rules\Constants\MissingClassConstantTypehintRule
	{
		return new PHPStan\Rules\Constants\MissingClassConstantTypehintRule($this->getService('019'));
	}


	public function createService0553(): PHPStan\Rules\Functions\ExistingClassesInArrowFunctionTypehintsRule
	{
		return new PHPStan\Rules\Functions\ExistingClassesInArrowFunctionTypehintsRule(
			$this->getService('011'),
			$this->getService('0453')
		);
	}


	public function createService0554(): PHPStan\Rules\Functions\CallToFunctionParametersRule
	{
		return new PHPStan\Rules\Functions\CallToFunctionParametersRule(
			$this->getService('reflectionProvider'),
			$this->getService('064')
		);
	}


	public function createService0555(): PHPStan\Rules\Functions\ParamAttributesRule
	{
		return new PHPStan\Rules\Functions\ParamAttributesRule($this->getService('074'));
	}


	public function createService0556(): PHPStan\Rules\Functions\ExistingClassesInTypehintsRule
	{
		return new PHPStan\Rules\Functions\ExistingClassesInTypehintsRule($this->getService('011'));
	}


	public function createService0557(): PHPStan\Rules\Functions\ArrayValuesRule
	{
		return new PHPStan\Rules\Functions\ArrayValuesRule($this->getService('reflectionProvider'), true, true);
	}


	public function createService0558(): PHPStan\Rules\Functions\PrintfArrayParametersRule
	{
		return new PHPStan\Rules\Functions\PrintfArrayParametersRule($this->getService('038'), $this->getService('reflectionProvider'));
	}


	public function createService0559(): PHPStan\Rules\Functions\UselessFunctionReturnValueRule
	{
		return new PHPStan\Rules\Functions\UselessFunctionReturnValueRule($this->getService('reflectionProvider'));
	}


	public function createService0560(): PHPStan\Rules\Functions\CallToFunctionStatementWithNoDiscardRule
	{
		return new PHPStan\Rules\Functions\CallToFunctionStatementWithNoDiscardRule(
			$this->getService('reflectionProvider'),
			$this->getService('0453')
		);
	}


	public function createService0561(): PHPStan\Rules\Functions\FilterVarRule
	{
		return new PHPStan\Rules\Functions\FilterVarRule(
			$this->getService('reflectionProvider'),
			$this->getService('0425'),
			$this->getService('0453')
		);
	}


	public function createService0562(): PHPStan\Rules\Functions\ReturnNullsafeByRefRule
	{
		return new PHPStan\Rules\Functions\ReturnNullsafeByRefRule($this->getService('072'));
	}


	public function createService0563(): PHPStan\Rules\Functions\ExistingClassesInClosureTypehintsRule
	{
		return new PHPStan\Rules\Functions\ExistingClassesInClosureTypehintsRule($this->getService('011'));
	}


	public function createService0564(): PHPStan\Rules\Functions\IncompatibleDefaultParameterTypeRule
	{
		return new PHPStan\Rules\Functions\IncompatibleDefaultParameterTypeRule;
	}


	public function createService0565(): PHPStan\Rules\Functions\CallToNonExistentFunctionRule
	{
		return new PHPStan\Rules\Functions\CallToNonExistentFunctionRule($this->getService('reflectionProvider'), true, true);
	}


	public function createService0566(): PHPStan\Rules\Functions\UnusedClosureUsesRule
	{
		return new PHPStan\Rules\Functions\UnusedClosureUsesRule($this->getService('062'));
	}


	public function createService0567(): PHPStan\Rules\Functions\PrintfParametersRule
	{
		return new PHPStan\Rules\Functions\PrintfParametersRule($this->getService('038'), $this->getService('reflectionProvider'));
	}


	public function createService0568(): PHPStan\Rules\Functions\MissingFunctionReturnTypehintRule
	{
		return new PHPStan\Rules\Functions\MissingFunctionReturnTypehintRule($this->getService('019'));
	}


	public function createService0569(): PHPStan\Rules\Functions\VariadicParametersDeclarationRule
	{
		return new PHPStan\Rules\Functions\VariadicParametersDeclarationRule;
	}


	public function createService0570(): PHPStan\Rules\Functions\CallUserFuncRule
	{
		return new PHPStan\Rules\Functions\CallUserFuncRule($this->getService('reflectionProvider'), $this->getService('064'));
	}


	public function createService0571(): PHPStan\Rules\Functions\InnerFunctionRule
	{
		return new PHPStan\Rules\Functions\InnerFunctionRule;
	}


	public function createService0572(): PHPStan\Rules\Functions\FunctionCallableRule
	{
		return new PHPStan\Rules\Functions\FunctionCallableRule(
			$this->getService('reflectionProvider'),
			$this->getService('020'),
			$this->getService('0453'),
			true,
			true
		);
	}


	public function createService0573(): PHPStan\Rules\Functions\DefineParametersRule
	{
		return new PHPStan\Rules\Functions\DefineParametersRule($this->getService('0453'));
	}


	public function createService0574(): PHPStan\Rules\Functions\ArrowFunctionReturnNullsafeByRefRule
	{
		return new PHPStan\Rules\Functions\ArrowFunctionReturnNullsafeByRefRule($this->getService('072'));
	}


	public function createService0575(): PHPStan\Rules\Functions\ImplodeParameterCastableToStringRule
	{
		return new PHPStan\Rules\Functions\ImplodeParameterCastableToStringRule(
			$this->getService('reflectionProvider'),
			$this->getService('012')
		);
	}


	public function createService0576(): PHPStan\Rules\Functions\SortParameterCastableToStringRule
	{
		return new PHPStan\Rules\Functions\SortParameterCastableToStringRule(
			$this->getService('reflectionProvider'),
			$this->getService('012')
		);
	}


	public function createService0577(): PHPStan\Rules\Functions\CallToFunctionStatementWithoutSideEffectsRule
	{
		return new PHPStan\Rules\Functions\CallToFunctionStatementWithoutSideEffectsRule($this->getService('reflectionProvider'));
	}


	public function createService0578(): PHPStan\Rules\Functions\InvalidParameterNameRule
	{
		return new PHPStan\Rules\Functions\InvalidParameterNameRule;
	}


	public function createService0579(): PHPStan\Rules\Functions\ArrowFunctionReturnTypeRule
	{
		return new PHPStan\Rules\Functions\ArrowFunctionReturnTypeRule($this->getService('063'));
	}


	public function createService0580(): PHPStan\Rules\Functions\InvalidLexicalVariablesInClosureUseRule
	{
		return new PHPStan\Rules\Functions\InvalidLexicalVariablesInClosureUseRule;
	}


	public function createService0581(): PHPStan\Rules\Functions\ParameterCastableToStringRule
	{
		return new PHPStan\Rules\Functions\ParameterCastableToStringRule(
			$this->getService('reflectionProvider'),
			$this->getService('012')
		);
	}


	public function createService0582(): PHPStan\Rules\Functions\FunctionAttributesRule
	{
		return new PHPStan\Rules\Functions\FunctionAttributesRule($this->getService('074'));
	}


	public function createService0583(): PHPStan\Rules\Functions\RandomIntParametersRule
	{
		return new PHPStan\Rules\Functions\RandomIntParametersRule(
			$this->getService('reflectionProvider'),
			$this->getService('0453'),
			true
		);
	}


	public function createService0584(): PHPStan\Rules\Functions\ReturnTypeRule
	{
		return new PHPStan\Rules\Functions\ReturnTypeRule($this->getService('063'));
	}


	public function createService0585(): PHPStan\Rules\Functions\CallCallablesRule
	{
		return new PHPStan\Rules\Functions\CallCallablesRule($this->getService('064'), $this->getService('020'), true);
	}


	public function createService0586(): PHPStan\Rules\Functions\ClosureReturnTypeRule
	{
		return new PHPStan\Rules\Functions\ClosureReturnTypeRule($this->getService('063'));
	}


	public function createService0587(): PHPStan\Rules\Functions\IncompatibleArrowFunctionDefaultParameterTypeRule
	{
		return new PHPStan\Rules\Functions\IncompatibleArrowFunctionDefaultParameterTypeRule;
	}


	public function createService0588(): PHPStan\Rules\Functions\IncompatibleClosureDefaultParameterTypeRule
	{
		return new PHPStan\Rules\Functions\IncompatibleClosureDefaultParameterTypeRule;
	}


	public function createService0589(): PHPStan\Rules\Functions\ArrowFunctionAttributesRule
	{
		return new PHPStan\Rules\Functions\ArrowFunctionAttributesRule($this->getService('074'));
	}


	public function createService0590(): PHPStan\Rules\Functions\MissingFunctionParameterTypehintRule
	{
		return new PHPStan\Rules\Functions\MissingFunctionParameterTypehintRule($this->getService('019'));
	}


	public function createService0591(): PHPStan\Rules\Functions\ArrayFilterRule
	{
		return new PHPStan\Rules\Functions\ArrayFilterRule($this->getService('reflectionProvider'), true, true);
	}


	public function createService0592(): PHPStan\Rules\Functions\RedefinedParametersRule
	{
		return new PHPStan\Rules\Functions\RedefinedParametersRule;
	}


	public function createService0593(): PHPStan\Rules\Functions\ClosureAttributesRule
	{
		return new PHPStan\Rules\Functions\ClosureAttributesRule($this->getService('074'));
	}


	public function createService0594(): PHPStan\Rules\Operators\InvalidIncDecOperationRule
	{
		return new PHPStan\Rules\Operators\InvalidIncDecOperationRule($this->getService('020'), $this->getService('0453'));
	}


	public function createService0595(): PHPStan\Rules\Operators\InvalidBinaryOperationRule
	{
		return new PHPStan\Rules\Operators\InvalidBinaryOperationRule($this->getService('0170'), $this->getService('020'));
	}


	public function createService0596(): PHPStan\Rules\Operators\InvalidAssignVarRule
	{
		return new PHPStan\Rules\Operators\InvalidAssignVarRule($this->getService('072'));
	}


	public function createService0597(): PHPStan\Rules\Operators\BacktickRule
	{
		return new PHPStan\Rules\Operators\BacktickRule($this->getService('0453'));
	}


	public function createService0598(): PHPStan\Rules\Operators\PipeOperatorRule
	{
		return new PHPStan\Rules\Operators\PipeOperatorRule($this->getService('020'));
	}


	public function createService0599(): PHPStan\Rules\Operators\InvalidUnaryOperationRule
	{
		return new PHPStan\Rules\Operators\InvalidUnaryOperationRule($this->getService('020'));
	}


	public function createService0600(): PHPStan\Rules\Operators\InvalidComparisonOperationRule
	{
		return new PHPStan\Rules\Operators\InvalidComparisonOperationRule($this->getService('020'), $this->getService('0186'), true);
	}


	public function createService0601(): PHPStan\Rules\Generators\YieldTypeRule
	{
		return new PHPStan\Rules\Generators\YieldTypeRule($this->getService('020'));
	}


	public function createService0602(): PHPStan\Rules\Generators\YieldInGeneratorRule
	{
		return new PHPStan\Rules\Generators\YieldInGeneratorRule(true);
	}


	public function createService0603(): PHPStan\Rules\Generators\YieldFromTypeRule
	{
		return new PHPStan\Rules\Generators\YieldFromTypeRule($this->getService('020'), true);
	}


	public function createService0604(): PHPStan\Rules\Properties\ReadOnlyByPhpDocPropertyAssignRefRule
	{
		return new PHPStan\Rules\Properties\ReadOnlyByPhpDocPropertyAssignRefRule($this->getService('040'));
	}


	public function createService0605(): PHPStan\Rules\Properties\GetNonVirtualPropertyHookReadRule
	{
		return new PHPStan\Rules\Properties\GetNonVirtualPropertyHookReadRule;
	}


	public function createService0606(): PHPStan\Rules\Properties\ReadOnlyByPhpDocPropertyRule
	{
		return new PHPStan\Rules\Properties\ReadOnlyByPhpDocPropertyRule;
	}


	public function createService0607(): PHPStan\Rules\Properties\ReadingWriteOnlyPropertiesRule
	{
		return new PHPStan\Rules\Properties\ReadingWriteOnlyPropertiesRule(
			$this->getService('039'),
			$this->getService('040'),
			$this->getService('020'),
			false
		);
	}


	public function createService0608(): PHPStan\Rules\Properties\PropertyHookAttributesRule
	{
		return new PHPStan\Rules\Properties\PropertyHookAttributesRule($this->getService('074'));
	}


	public function createService0609(): PHPStan\Rules\Properties\InvalidCallablePropertyTypeRule
	{
		return new PHPStan\Rules\Properties\InvalidCallablePropertyTypeRule;
	}


	public function createService0610(): PHPStan\Rules\Properties\ExistingClassesInPropertyHookTypehintsRule
	{
		return new PHPStan\Rules\Properties\ExistingClassesInPropertyHookTypehintsRule($this->getService('011'));
	}


	public function createService0611(): PHPStan\Rules\Properties\PropertyAttributesRule
	{
		return new PHPStan\Rules\Properties\PropertyAttributesRule($this->getService('074'), $this->getService('0453'));
	}


	public function createService0612(): PHPStan\Rules\Properties\AccessPropertiesRule
	{
		return new PHPStan\Rules\Properties\AccessPropertiesRule($this->getService('042'));
	}


	public function createService0613(): PHPStan\Rules\Properties\NullsafePropertyFetchRule
	{
		return new PHPStan\Rules\Properties\NullsafePropertyFetchRule;
	}


	public function createService0614(): PHPStan\Rules\Properties\PropertyAssignRefRule
	{
		return new PHPStan\Rules\Properties\PropertyAssignRefRule($this->getService('0453'), $this->getService('040'));
	}


	public function createService0615(): PHPStan\Rules\Properties\SetPropertyHookParameterRule
	{
		return new PHPStan\Rules\Properties\SetPropertyHookParameterRule($this->getService('019'), true, true);
	}


	public function createService0616(): PHPStan\Rules\Properties\ReadOnlyPropertyAssignRefRule
	{
		return new PHPStan\Rules\Properties\ReadOnlyPropertyAssignRefRule($this->getService('040'));
	}


	public function createService0617(): PHPStan\Rules\Properties\MissingPropertyTypehintRule
	{
		return new PHPStan\Rules\Properties\MissingPropertyTypehintRule($this->getService('019'));
	}


	public function createService0618(): PHPStan\Rules\Properties\DefaultValueTypesAssignedToPropertiesRule
	{
		return new PHPStan\Rules\Properties\DefaultValueTypesAssignedToPropertiesRule($this->getService('020'));
	}


	public function createService0619(): PHPStan\Rules\Properties\ExistingClassesInPropertiesRule
	{
		return new PHPStan\Rules\Properties\ExistingClassesInPropertiesRule(
			$this->getService('reflectionProvider'),
			$this->getService('060'),
			$this->getService('070'),
			$this->getService('0453'),
			true,
			false,
			true
		);
	}


	public function createService0620(): PHPStan\Rules\Properties\MissingReadOnlyByPhpDocPropertyAssignRule
	{
		return new PHPStan\Rules\Properties\MissingReadOnlyByPhpDocPropertyAssignRule($this->getService('0192'));
	}


	public function createService0621(): PHPStan\Rules\Properties\OverridingPropertyRule
	{
		return new PHPStan\Rules\Properties\OverridingPropertyRule($this->getService('0453'), true, true, null, false);
	}


	public function createService0622(): PHPStan\Rules\Properties\TypesAssignedToPropertiesRule
	{
		return new PHPStan\Rules\Properties\TypesAssignedToPropertiesRule($this->getService('020'), $this->getService('040'));
	}


	public function createService0623(): PHPStan\Rules\Properties\AccessPrivatePropertyThroughStaticRule
	{
		return new PHPStan\Rules\Properties\AccessPrivatePropertyThroughStaticRule;
	}


	public function createService0624(): PHPStan\Rules\Properties\PropertyInClassRule
	{
		return new PHPStan\Rules\Properties\PropertyInClassRule($this->getService('0453'));
	}


	public function createService0625(): PHPStan\Rules\Properties\AccessStaticPropertiesRule
	{
		return new PHPStan\Rules\Properties\AccessStaticPropertiesRule($this->getService('041'));
	}


	public function createService0626(): PHPStan\Rules\Properties\MissingReadOnlyPropertyAssignRule
	{
		return new PHPStan\Rules\Properties\MissingReadOnlyPropertyAssignRule($this->getService('0192'));
	}


	public function createService0627(): PHPStan\Rules\Properties\ReadOnlyByPhpDocPropertyAssignRule
	{
		return new PHPStan\Rules\Properties\ReadOnlyByPhpDocPropertyAssignRule($this->getService('040'), $this->getService('0192'));
	}


	public function createService0628(): PHPStan\Rules\Properties\AccessStaticPropertiesInAssignRule
	{
		return new PHPStan\Rules\Properties\AccessStaticPropertiesInAssignRule($this->getService('041'));
	}


	public function createService0629(): PHPStan\Rules\Properties\WritingToReadOnlyPropertiesRule
	{
		return new PHPStan\Rules\Properties\WritingToReadOnlyPropertiesRule(
			$this->getService('020'),
			$this->getService('039'),
			$this->getService('040'),
			false
		);
	}


	public function createService0630(): PHPStan\Rules\Properties\ReadOnlyPropertyRule
	{
		return new PHPStan\Rules\Properties\ReadOnlyPropertyRule($this->getService('0453'));
	}


	public function createService0631(): PHPStan\Rules\Properties\AccessPropertiesInAssignRule
	{
		return new PHPStan\Rules\Properties\AccessPropertiesInAssignRule($this->getService('042'));
	}


	public function createService0632(): PHPStan\Rules\Properties\ReadOnlyPropertyAssignRule
	{
		return new PHPStan\Rules\Properties\ReadOnlyPropertyAssignRule(
			$this->getService('040'),
			$this->getService('0192'),
			$this->getService('0453')
		);
	}


	public function createService0633(): PHPStan\Rules\Properties\PropertiesInInterfaceRule
	{
		return new PHPStan\Rules\Properties\PropertiesInInterfaceRule($this->getService('0453'));
	}


	public function createService0634(): PHPStan\Rules\Properties\SetNonVirtualPropertyHookAssignRule
	{
		return new PHPStan\Rules\Properties\SetNonVirtualPropertyHookAssignRule;
	}


	public function createService0635(): PHPStan\Rules\Types\InvalidTypesInUnionRule
	{
		return new PHPStan\Rules\Types\InvalidTypesInUnionRule;
	}


	public function createService0636(): PHPStan\Rules\Traits\ConflictingTraitConstantsRule
	{
		return new PHPStan\Rules\Traits\ConflictingTraitConstantsRule(
			$this->getService('0204'),
			$this->getService('reflectionProvider')
		);
	}


	public function createService0637(): PHPStan\Rules\Traits\TraitAttributesRule
	{
		return new PHPStan\Rules\Traits\TraitAttributesRule($this->getService('074'), $this->getService('0453'));
	}


	public function createService0638(): PHPStan\Rules\Traits\ConstantsInTraitsRule
	{
		return new PHPStan\Rules\Traits\ConstantsInTraitsRule($this->getService('0453'));
	}


	public function createService0639(): PHPStan\Rules\Traits\NotAnalysedTraitRule
	{
		return new PHPStan\Rules\Traits\NotAnalysedTraitRule;
	}


	public function createService0640(): PHPStan\Rules\TooWideTypehints\TooWideMethodReturnTypehintRule
	{
		return new PHPStan\Rules\TooWideTypehints\TooWideMethodReturnTypehintRule(false, $this->getService('046'));
	}


	public function createService0641(): PHPStan\Rules\TooWideTypehints\TooWideMethodParameterOutTypeRule
	{
		return new PHPStan\Rules\TooWideTypehints\TooWideMethodParameterOutTypeRule($this->getService('047'), false);
	}


	public function createService0642(): PHPStan\Rules\TooWideTypehints\TooWideFunctionReturnTypehintRule
	{
		return new PHPStan\Rules\TooWideTypehints\TooWideFunctionReturnTypehintRule($this->getService('046'));
	}


	public function createService0643(): PHPStan\Rules\TooWideTypehints\TooWideArrowFunctionReturnTypehintRule
	{
		return new PHPStan\Rules\TooWideTypehints\TooWideArrowFunctionReturnTypehintRule($this->getService('046'));
	}


	public function createService0644(): PHPStan\Rules\TooWideTypehints\TooWideClosureReturnTypehintRule
	{
		return new PHPStan\Rules\TooWideTypehints\TooWideClosureReturnTypehintRule($this->getService('046'));
	}


	public function createService0645(): PHPStan\Rules\TooWideTypehints\TooWideFunctionParameterOutTypeRule
	{
		return new PHPStan\Rules\TooWideTypehints\TooWideFunctionParameterOutTypeRule($this->getService('047'));
	}


	public function createService0646(): PHPStan\Rules\TooWideTypehints\TooWidePropertyTypeRule
	{
		return new PHPStan\Rules\TooWideTypehints\TooWidePropertyTypeRule($this->getService('043'), $this->getService('046'));
	}


	public function createService0647(): PHPStan\Rules\Methods\ExistingClassesInTypehintsRule
	{
		return new PHPStan\Rules\Methods\ExistingClassesInTypehintsRule($this->getService('011'));
	}


	public function createService0648(): PHPStan\Rules\Methods\MissingMethodReturnTypehintRule
	{
		return new PHPStan\Rules\Methods\MissingMethodReturnTypehintRule($this->getService('019'));
	}


	public function createService0649(): PHPStan\Rules\Methods\CallToConstructorStatementWithoutSideEffectsRule
	{
		return new PHPStan\Rules\Methods\CallToConstructorStatementWithoutSideEffectsRule($this->getService('reflectionProvider'));
	}


	public function createService0650(): PHPStan\Rules\Methods\ConsistentConstructorDeclarationRule
	{
		return new PHPStan\Rules\Methods\ConsistentConstructorDeclarationRule;
	}


	public function createService0651(): PHPStan\Rules\Methods\CallToMethodStatementWithNoDiscardRule
	{
		return new PHPStan\Rules\Methods\CallToMethodStatementWithNoDiscardRule($this->getService('020'), $this->getService('0453'));
	}


	public function createService0652(): PHPStan\Rules\Methods\CallToStaticMethodStatementWithoutSideEffectsRule
	{
		return new PHPStan\Rules\Methods\CallToStaticMethodStatementWithoutSideEffectsRule(
			$this->getService('020'),
			$this->getService('reflectionProvider')
		);
	}


	public function createService0653(): PHPStan\Rules\Methods\IncompatibleDefaultParameterTypeRule
	{
		return new PHPStan\Rules\Methods\IncompatibleDefaultParameterTypeRule;
	}


	public function createService0654(): PHPStan\Rules\Methods\MissingMethodParameterTypehintRule
	{
		return new PHPStan\Rules\Methods\MissingMethodParameterTypehintRule($this->getService('019'));
	}


	public function createService0655(): PHPStan\Rules\Methods\MethodAttributesRule
	{
		return new PHPStan\Rules\Methods\MethodAttributesRule($this->getService('074'));
	}


	public function createService0656(): PHPStan\Rules\Methods\CallToMethodStatementWithoutSideEffectsRule
	{
		return new PHPStan\Rules\Methods\CallToMethodStatementWithoutSideEffectsRule($this->getService('020'));
	}


	public function createService0657(): PHPStan\Rules\Methods\NullsafeMethodCallRule
	{
		return new PHPStan\Rules\Methods\NullsafeMethodCallRule;
	}


	public function createService0658(): PHPStan\Rules\Methods\CallToStaticMethodStatementWithNoDiscardRule
	{
		return new PHPStan\Rules\Methods\CallToStaticMethodStatementWithNoDiscardRule(
			$this->getService('020'),
			$this->getService('reflectionProvider'),
			$this->getService('0453')
		);
	}


	public function createService0659(): PHPStan\Rules\Methods\CallPrivateMethodThroughStaticRule
	{
		return new PHPStan\Rules\Methods\CallPrivateMethodThroughStaticRule;
	}


	public function createService0660(): PHPStan\Rules\Methods\AbstractMethodInNonAbstractClassRule
	{
		return new PHPStan\Rules\Methods\AbstractMethodInNonAbstractClassRule;
	}


	public function createService0661(): PHPStan\Rules\Methods\AbstractPrivateMethodRule
	{
		return new PHPStan\Rules\Methods\AbstractPrivateMethodRule;
	}


	public function createService0662(): PHPStan\Rules\Methods\StaticMethodCallableRule
	{
		return new PHPStan\Rules\Methods\StaticMethodCallableRule($this->getService('053'), $this->getService('0453'));
	}


	public function createService0663(): PHPStan\Rules\Methods\CallMethodsRule
	{
		return new PHPStan\Rules\Methods\CallMethodsRule($this->getService('055'), $this->getService('064'));
	}


	public function createService0664(): PHPStan\Rules\Methods\ReturnTypeRule
	{
		return new PHPStan\Rules\Methods\ReturnTypeRule($this->getService('063'));
	}


	public function createService0665(): PHPStan\Rules\Methods\MissingMethodSelfOutTypeRule
	{
		return new PHPStan\Rules\Methods\MissingMethodSelfOutTypeRule($this->getService('019'));
	}


	public function createService0666(): PHPStan\Rules\Methods\FinalPrivateMethodRule
	{
		return new PHPStan\Rules\Methods\FinalPrivateMethodRule;
	}


	public function createService0667(): PHPStan\Rules\Methods\OverridingMethodRule
	{
		return new PHPStan\Rules\Methods\OverridingMethodRule(
			$this->getService('0453'),
			$this->getService('0862'),
			true,
			$this->getService('056'),
			$this->getService('059'),
			$this->getService('058'),
			false
		);
	}


	public function createService0668(): PHPStan\Rules\Methods\ConstructorReturnTypeRule
	{
		return new PHPStan\Rules\Methods\ConstructorReturnTypeRule;
	}


	public function createService0669(): PHPStan\Rules\Methods\MethodVisibilityInInterfaceRule
	{
		return new PHPStan\Rules\Methods\MethodVisibilityInInterfaceRule;
	}


	public function createService0670(): PHPStan\Rules\Methods\MissingMethodImplementationRule
	{
		return new PHPStan\Rules\Methods\MissingMethodImplementationRule;
	}


	public function createService0671(): PHPStan\Rules\Methods\ConsistentConstructorRule
	{
		return new PHPStan\Rules\Methods\ConsistentConstructorRule(
			$this->getService('036'),
			$this->getService('056'),
			$this->getService('059')
		);
	}


	public function createService0672(): PHPStan\Rules\Methods\MethodCallableRule
	{
		return new PHPStan\Rules\Methods\MethodCallableRule($this->getService('055'), $this->getService('0453'));
	}


	public function createService0673(): PHPStan\Rules\Methods\MissingMagicSerializationMethodsRule
	{
		return new PHPStan\Rules\Methods\MissingMagicSerializationMethodsRule($this->getService('0453'));
	}


	public function createService0674(): PHPStan\Rules\Methods\CallStaticMethodsRule
	{
		return new PHPStan\Rules\Methods\CallStaticMethodsRule($this->getService('053'), $this->getService('064'));
	}


	public function createService0675(): PHPStan\Rules\DeadCode\CallToConstructorStatementWithoutImpurePointsRule
	{
		return new PHPStan\Rules\DeadCode\CallToConstructorStatementWithoutImpurePointsRule;
	}


	public function createService0676(): PHPStan\Rules\DeadCode\CallToStaticMethodStatementWithoutImpurePointsRule
	{
		return new PHPStan\Rules\DeadCode\CallToStaticMethodStatementWithoutImpurePointsRule;
	}


	public function createService0677(): PHPStan\Rules\DeadCode\UnreachableStatementRule
	{
		return new PHPStan\Rules\DeadCode\UnreachableStatementRule;
	}


	public function createService0678(): PHPStan\Rules\DeadCode\CallToMethodStatementWithoutImpurePointsRule
	{
		return new PHPStan\Rules\DeadCode\CallToMethodStatementWithoutImpurePointsRule;
	}


	public function createService0679(): PHPStan\Rules\DeadCode\NoopRule
	{
		return new PHPStan\Rules\DeadCode\NoopRule($this->getService('0170'));
	}


	public function createService0680(): PHPStan\Rules\DeadCode\UnusedPrivatePropertyRule
	{
		return new PHPStan\Rules\DeadCode\UnusedPrivatePropertyRule($this->getService('043'), [], [], true);
	}


	public function createService0681(): PHPStan\Rules\DeadCode\UnusedPrivateConstantRule
	{
		return new PHPStan\Rules\DeadCode\UnusedPrivateConstantRule($this->getService('037'));
	}


	public function createService0682(): PHPStan\Rules\DeadCode\CallToFunctionStatementWithoutImpurePointsRule
	{
		return new PHPStan\Rules\DeadCode\CallToFunctionStatementWithoutImpurePointsRule;
	}


	public function createService0683(): PHPStan\Rules\DeadCode\UnusedPrivateMethodRule
	{
		return new PHPStan\Rules\DeadCode\UnusedPrivateMethodRule($this->getService('054'));
	}


	public function createService0684(): PHPStan\Rules\Cast\VoidCastRule
	{
		return new PHPStan\Rules\Cast\VoidCastRule($this->getService('0453'));
	}


	public function createService0685(): PHPStan\Rules\Cast\PrintRule
	{
		return new PHPStan\Rules\Cast\PrintRule($this->getService('020'));
	}


	public function createService0686(): PHPStan\Rules\Cast\UnsetCastRule
	{
		return new PHPStan\Rules\Cast\UnsetCastRule($this->getService('0453'));
	}


	public function createService0687(): PHPStan\Rules\Cast\InvalidPartOfEncapsedStringRule
	{
		return new PHPStan\Rules\Cast\InvalidPartOfEncapsedStringRule($this->getService('0170'), $this->getService('020'));
	}


	public function createService0688(): PHPStan\Rules\Cast\DeprecatedCastRule
	{
		return new PHPStan\Rules\Cast\DeprecatedCastRule($this->getService('0453'));
	}


	public function createService0689(): PHPStan\Rules\Cast\InvalidCastRule
	{
		return new PHPStan\Rules\Cast\InvalidCastRule($this->getService('reflectionProvider'), $this->getService('020'));
	}


	public function createService0690(): PHPStan\Rules\Cast\EchoRule
	{
		return new PHPStan\Rules\Cast\EchoRule($this->getService('020'));
	}


	public function createService0691(): PHPStan\Rules\PhpDoc\WrongVariableNameInVarTagRule
	{
		return new PHPStan\Rules\PhpDoc\WrongVariableNameInVarTagRule($this->getService('0449'), $this->getService('067'));
	}


	public function createService0692(): PHPStan\Rules\PhpDoc\InvalidPhpDocTagValueRule
	{
		return new PHPStan\Rules\PhpDoc\InvalidPhpDocTagValueRule($this->getService('0844'), $this->getService('0847'));
	}


	public function createService0693(): PHPStan\Rules\PhpDoc\RequireExtendsDefinitionTraitRule
	{
		return new PHPStan\Rules\PhpDoc\RequireExtendsDefinitionTraitRule(
			$this->getService('reflectionProvider'),
			$this->getService('068')
		);
	}


	public function createService0694(): PHPStan\Rules\PhpDoc\IncompatiblePropertyPhpDocTypeRule
	{
		return new PHPStan\Rules\PhpDoc\IncompatiblePropertyPhpDocTypeRule(
			$this->getService('017'),
			$this->getService('070'),
			$this->getService('069')
		);
	}


	public function createService0695(): PHPStan\Rules\PhpDoc\IncompatiblePhpDocTypeRule
	{
		return new PHPStan\Rules\PhpDoc\IncompatiblePhpDocTypeRule($this->getService('0449'), $this->getService('066'));
	}


	public function createService0696(): PHPStan\Rules\PhpDoc\SealedDefinitionClassRule
	{
		return new PHPStan\Rules\PhpDoc\SealedDefinitionClassRule(
			$this->getService('reflectionProvider'),
			$this->getService('060'),
			true,
			true
		);
	}


	public function createService0697(): PHPStan\Rules\PhpDoc\RequireImplementsDefinitionTraitRule
	{
		return new PHPStan\Rules\PhpDoc\RequireImplementsDefinitionTraitRule(
			$this->getService('reflectionProvider'),
			$this->getService('060'),
			true,
			true
		);
	}


	public function createService0698(): PHPStan\Rules\PhpDoc\InvalidPHPStanDocTagRule
	{
		return new PHPStan\Rules\PhpDoc\InvalidPHPStanDocTagRule($this->getService('0844'), $this->getService('0847'));
	}


	public function createService0699(): PHPStan\Rules\PhpDoc\InvalidPhpDocVarTagTypeRule
	{
		return new PHPStan\Rules\PhpDoc\InvalidPhpDocVarTagTypeRule(
			$this->getService('0449'),
			$this->getService('reflectionProvider'),
			$this->getService('060'),
			$this->getService('017'),
			$this->getService('019'),
			$this->getService('070'),
			true,
			true,
			true
		);
	}


	public function createService0700(): PHPStan\Rules\PhpDoc\IncompatibleClassConstantPhpDocTypeRule
	{
		return new PHPStan\Rules\PhpDoc\IncompatibleClassConstantPhpDocTypeRule($this->getService('017'), $this->getService('070'));
	}


	public function createService0701(): PHPStan\Rules\PhpDoc\FunctionConditionalReturnTypeRule
	{
		return new PHPStan\Rules\PhpDoc\FunctionConditionalReturnTypeRule($this->getService('071'));
	}


	public function createService0702(): PHPStan\Rules\PhpDoc\IncompatibleSelfOutTypeRule
	{
		return new PHPStan\Rules\PhpDoc\IncompatibleSelfOutTypeRule($this->getService('070'), $this->getService('017'));
	}


	public function createService0703(): PHPStan\Rules\PhpDoc\InvalidThrowsPhpDocValueRule
	{
		return new PHPStan\Rules\PhpDoc\InvalidThrowsPhpDocValueRule($this->getService('0449'));
	}


	public function createService0704(): PHPStan\Rules\PhpDoc\RequireExtendsDefinitionClassRule
	{
		return new PHPStan\Rules\PhpDoc\RequireExtendsDefinitionClassRule($this->getService('068'));
	}


	public function createService0705(): PHPStan\Rules\PhpDoc\MethodAssertRule
	{
		return new PHPStan\Rules\PhpDoc\MethodAssertRule($this->getService('065'));
	}


	public function createService0706(): PHPStan\Rules\PhpDoc\IncompatiblePropertyHookPhpDocTypeRule
	{
		return new PHPStan\Rules\PhpDoc\IncompatiblePropertyHookPhpDocTypeRule($this->getService('0449'), $this->getService('066'));
	}


	public function createService0707(): PHPStan\Rules\PhpDoc\IncompatibleParamImmediatelyInvokedCallableRule
	{
		return new PHPStan\Rules\PhpDoc\IncompatibleParamImmediatelyInvokedCallableRule($this->getService('0449'));
	}


	public function createService0708(): PHPStan\Rules\PhpDoc\SealedDefinitionTraitRule
	{
		return new PHPStan\Rules\PhpDoc\SealedDefinitionTraitRule($this->getService('reflectionProvider'));
	}


	public function createService0709(): PHPStan\Rules\PhpDoc\MethodConditionalReturnTypeRule
	{
		return new PHPStan\Rules\PhpDoc\MethodConditionalReturnTypeRule($this->getService('071'));
	}


	public function createService0710(): PHPStan\Rules\PhpDoc\VarTagChangedExpressionTypeRule
	{
		return new PHPStan\Rules\PhpDoc\VarTagChangedExpressionTypeRule($this->getService('067'));
	}


	public function createService0711(): PHPStan\Rules\PhpDoc\RequireImplementsDefinitionClassRule
	{
		return new PHPStan\Rules\PhpDoc\RequireImplementsDefinitionClassRule;
	}


	public function createService0712(): PHPStan\Rules\PhpDoc\FunctionAssertRule
	{
		return new PHPStan\Rules\PhpDoc\FunctionAssertRule($this->getService('065'));
	}


	public function createService0713(): PHPStan\Rules\Api\RuntimeReflectionFunctionRule
	{
		return new PHPStan\Rules\Api\RuntimeReflectionFunctionRule($this->getService('reflectionProvider'));
	}


	public function createService0714(): PHPStan\Rules\Api\ApiInstantiationRule
	{
		return new PHPStan\Rules\Api\ApiInstantiationRule($this->getService('073'), $this->getService('reflectionProvider'));
	}


	public function createService0715(): PHPStan\Rules\Api\NodeConnectingVisitorAttributesRule
	{
		return new PHPStan\Rules\Api\NodeConnectingVisitorAttributesRule;
	}


	public function createService0716(): PHPStan\Rules\Api\ApiClassConstFetchRule
	{
		return new PHPStan\Rules\Api\ApiClassConstFetchRule($this->getService('073'), $this->getService('reflectionProvider'));
	}


	public function createService0717(): PHPStan\Rules\Api\OldPhpParser4ClassRule
	{
		return new PHPStan\Rules\Api\OldPhpParser4ClassRule;
	}


	public function createService0718(): PHPStan\Rules\Api\ApiClassExtendsRule
	{
		return new PHPStan\Rules\Api\ApiClassExtendsRule($this->getService('073'), $this->getService('reflectionProvider'));
	}


	public function createService0719(): PHPStan\Rules\Api\GetTemplateTypeRule
	{
		return new PHPStan\Rules\Api\GetTemplateTypeRule($this->getService('reflectionProvider'));
	}


	public function createService0720(): PHPStan\Rules\Api\ApiInterfaceExtendsRule
	{
		return new PHPStan\Rules\Api\ApiInterfaceExtendsRule($this->getService('073'), $this->getService('reflectionProvider'));
	}


	public function createService0721(): PHPStan\Rules\Api\ApiTraitUseRule
	{
		return new PHPStan\Rules\Api\ApiTraitUseRule($this->getService('073'), $this->getService('reflectionProvider'));
	}


	public function createService0722(): PHPStan\Rules\Api\ApiMethodCallRule
	{
		return new PHPStan\Rules\Api\ApiMethodCallRule($this->getService('073'));
	}


	public function createService0723(): PHPStan\Rules\Api\ApiStaticCallRule
	{
		return new PHPStan\Rules\Api\ApiStaticCallRule($this->getService('073'), $this->getService('reflectionProvider'));
	}


	public function createService0724(): PHPStan\Rules\Api\PhpStanNamespaceIn3rdPartyPackageRule
	{
		return new PHPStan\Rules\Api\PhpStanNamespaceIn3rdPartyPackageRule($this->getService('073'));
	}


	public function createService0725(): PHPStan\Rules\Api\ApiInstanceofRule
	{
		return new PHPStan\Rules\Api\ApiInstanceofRule($this->getService('073'), $this->getService('reflectionProvider'));
	}


	public function createService0726(): PHPStan\Rules\Api\RuntimeReflectionInstantiationRule
	{
		return new PHPStan\Rules\Api\RuntimeReflectionInstantiationRule($this->getService('reflectionProvider'));
	}


	public function createService0727(): PHPStan\Rules\Api\ApiInstanceofTypeRule
	{
		return new PHPStan\Rules\Api\ApiInstanceofTypeRule($this->getService('reflectionProvider'));
	}


	public function createService0728(): PHPStan\Rules\Api\ApiClassImplementsRule
	{
		return new PHPStan\Rules\Api\ApiClassImplementsRule($this->getService('073'), $this->getService('reflectionProvider'));
	}


	public function createService0729(): PHPStan\Rules\Exceptions\ThrowsVoidMethodWithExplicitThrowPointRule
	{
		return new PHPStan\Rules\Exceptions\ThrowsVoidMethodWithExplicitThrowPointRule($this->getService('exceptionTypeResolver'), true);
	}


	public function createService0730(): PHPStan\Rules\Exceptions\ThrowsVoidFunctionWithExplicitThrowPointRule
	{
		return new PHPStan\Rules\Exceptions\ThrowsVoidFunctionWithExplicitThrowPointRule(
			$this->getService('exceptionTypeResolver'),
			true
		);
	}


	public function createService0731(): PHPStan\Rules\Exceptions\ThrowExprTypeRule
	{
		return new PHPStan\Rules\Exceptions\ThrowExprTypeRule($this->getService('020'));
	}


	public function createService0732(): PHPStan\Rules\Exceptions\CaughtExceptionExistenceRule
	{
		return new PHPStan\Rules\Exceptions\CaughtExceptionExistenceRule(
			$this->getService('reflectionProvider'),
			$this->getService('060'),
			true,
			true
		);
	}


	public function createService0733(): PHPStan\Rules\Exceptions\OverwrittenExitPointByFinallyRule
	{
		return new PHPStan\Rules\Exceptions\OverwrittenExitPointByFinallyRule;
	}


	public function createService0734(): PHPStan\Rules\Exceptions\NoncapturingCatchRule
	{
		return new PHPStan\Rules\Exceptions\NoncapturingCatchRule;
	}


	public function createService0735(): PHPStan\Rules\Exceptions\ThrowsVoidPropertyHookWithExplicitThrowPointRule
	{
		return new PHPStan\Rules\Exceptions\ThrowsVoidPropertyHookWithExplicitThrowPointRule(
			$this->getService('exceptionTypeResolver'),
			true
		);
	}


	public function createService0736(): PHPStan\Rules\Exceptions\ThrowExpressionRule
	{
		return new PHPStan\Rules\Exceptions\ThrowExpressionRule($this->getService('0453'));
	}


	public function createService0737(): PHPStan\Rules\Exceptions\CatchWithUnthrownExceptionRule
	{
		return new PHPStan\Rules\Exceptions\CatchWithUnthrownExceptionRule($this->getService('exceptionTypeResolver'), true);
	}


	public function createService0738(): PHPStan\Rules\Arrays\OffsetAccessValueAssignmentRule
	{
		return new PHPStan\Rules\Arrays\OffsetAccessValueAssignmentRule($this->getService('020'));
	}


	public function createService0739(): PHPStan\Rules\Arrays\ArrayDestructuringRule
	{
		return new PHPStan\Rules\Arrays\ArrayDestructuringRule($this->getService('020'), $this->getService('078'));
	}


	public function createService0740(): PHPStan\Rules\Arrays\UnpackIterableInArrayRule
	{
		return new PHPStan\Rules\Arrays\UnpackIterableInArrayRule($this->getService('020'));
	}


	public function createService0741(): PHPStan\Rules\Arrays\OffsetAccessAssignOpRule
	{
		return new PHPStan\Rules\Arrays\OffsetAccessAssignOpRule($this->getService('020'));
	}


	public function createService0742(): PHPStan\Rules\Arrays\InvalidKeyInArrayItemRule
	{
		return new PHPStan\Rules\Arrays\InvalidKeyInArrayItemRule($this->getService('020'), $this->getService('0453'), true);
	}


	public function createService0743(): PHPStan\Rules\Arrays\OffsetAccessAssignmentRule
	{
		return new PHPStan\Rules\Arrays\OffsetAccessAssignmentRule($this->getService('020'));
	}


	public function createService0744(): PHPStan\Rules\Arrays\OffsetAccessWithoutDimForReadingRule
	{
		return new PHPStan\Rules\Arrays\OffsetAccessWithoutDimForReadingRule;
	}


	public function createService0745(): PHPStan\Rules\Arrays\InvalidKeyInArrayDimFetchRule
	{
		return new PHPStan\Rules\Arrays\InvalidKeyInArrayDimFetchRule($this->getService('020'), $this->getService('0453'), true, true);
	}


	public function createService0746(): PHPStan\Rules\Arrays\DeadForeachRule
	{
		return new PHPStan\Rules\Arrays\DeadForeachRule;
	}


	public function createService0747(): PHPStan\Rules\Arrays\ArrayUnpackingRule
	{
		return new PHPStan\Rules\Arrays\ArrayUnpackingRule($this->getService('0453'), $this->getService('020'));
	}


	public function createService0748(): PHPStan\Rules\Arrays\IterableInForeachRule
	{
		return new PHPStan\Rules\Arrays\IterableInForeachRule($this->getService('020'));
	}


	public function createService0749(): PHPStan\Rules\Arrays\DuplicateKeysInLiteralArraysRule
	{
		return new PHPStan\Rules\Arrays\DuplicateKeysInLiteralArraysRule($this->getService('0170'));
	}


	public function createService0750(): PHPStan\Rules\Arrays\NonexistentOffsetInArrayDimFetchRule
	{
		return new PHPStan\Rules\Arrays\NonexistentOffsetInArrayDimFetchRule($this->getService('020'), $this->getService('078'), true);
	}


	public function createService0751(): PHPStan\Rules\Ignore\IgnoreParseErrorRule
	{
		return new PHPStan\Rules\Ignore\IgnoreParseErrorRule;
	}


	public function createService0752(): PHPStan\Rules\Comparison\ImpossibleCheckTypeFunctionCallRule
	{
		return new PHPStan\Rules\Comparison\ImpossibleCheckTypeFunctionCallRule(
			$this->getService('082'),
			$this->getService('081'),
			true,
			false,
			true
		);
	}


	public function createService0753(): PHPStan\Rules\Comparison\StrictComparisonOfDifferentTypesRule
	{
		return new PHPStan\Rules\Comparison\StrictComparisonOfDifferentTypesRule(
			$this->getService('0158'),
			$this->getService('081'),
			true,
			false,
			true
		);
	}


	public function createService0754(): PHPStan\Rules\Comparison\ImpossibleCheckTypeStaticMethodCallRule
	{
		return new PHPStan\Rules\Comparison\ImpossibleCheckTypeStaticMethodCallRule(
			$this->getService('082'),
			$this->getService('081'),
			true,
			false,
			true
		);
	}


	public function createService0755(): PHPStan\Rules\Comparison\BooleanAndConstantConditionRule
	{
		return new PHPStan\Rules\Comparison\BooleanAndConstantConditionRule(
			$this->getService('080'),
			$this->getService('081'),
			true,
			false,
			true
		);
	}


	public function createService0756(): PHPStan\Rules\Comparison\WhileLoopAlwaysTrueConditionRule
	{
		return new PHPStan\Rules\Comparison\WhileLoopAlwaysTrueConditionRule(
			$this->getService('080'),
			$this->getService('081'),
			true,
			true
		);
	}


	public function createService0757(): PHPStan\Rules\Comparison\UsageOfVoidMatchExpressionRule
	{
		return new PHPStan\Rules\Comparison\UsageOfVoidMatchExpressionRule;
	}


	public function createService0758(): PHPStan\Rules\Comparison\DoWhileLoopConstantConditionRule
	{
		return new PHPStan\Rules\Comparison\DoWhileLoopConstantConditionRule(
			$this->getService('080'),
			$this->getService('081'),
			true,
			true
		);
	}


	public function createService0759(): PHPStan\Rules\Comparison\BooleanOrConstantConditionRule
	{
		return new PHPStan\Rules\Comparison\BooleanOrConstantConditionRule(
			$this->getService('080'),
			$this->getService('081'),
			true,
			false,
			true
		);
	}


	public function createService0760(): PHPStan\Rules\Comparison\IfConstantConditionRule
	{
		return new PHPStan\Rules\Comparison\IfConstantConditionRule($this->getService('080'), $this->getService('081'), true, true);
	}


	public function createService0761(): PHPStan\Rules\Comparison\ElseIfConstantConditionRule
	{
		return new PHPStan\Rules\Comparison\ElseIfConstantConditionRule(
			$this->getService('080'),
			$this->getService('081'),
			true,
			false,
			true
		);
	}


	public function createService0762(): PHPStan\Rules\Comparison\BooleanNotConstantConditionRule
	{
		return new PHPStan\Rules\Comparison\BooleanNotConstantConditionRule(
			$this->getService('080'),
			$this->getService('081'),
			true,
			false,
			true
		);
	}


	public function createService0763(): PHPStan\Rules\Comparison\TernaryOperatorConstantConditionRule
	{
		return new PHPStan\Rules\Comparison\TernaryOperatorConstantConditionRule(
			$this->getService('080'),
			$this->getService('081'),
			true,
			true
		);
	}


	public function createService0764(): PHPStan\Rules\Comparison\WhileLoopAlwaysFalseConditionRule
	{
		return new PHPStan\Rules\Comparison\WhileLoopAlwaysFalseConditionRule(
			$this->getService('080'),
			$this->getService('081'),
			true,
			true
		);
	}


	public function createService0765(): PHPStan\Rules\Comparison\MatchExpressionRule
	{
		return new PHPStan\Rules\Comparison\MatchExpressionRule($this->getService('080'), $this->getService('081'), true);
	}


	public function createService0766(): PHPStan\Rules\Comparison\ConstantLooseComparisonRule
	{
		return new PHPStan\Rules\Comparison\ConstantLooseComparisonRule($this->getService('081'), true, false, true);
	}


	public function createService0767(): PHPStan\Rules\Comparison\LogicalXorConstantConditionRule
	{
		return new PHPStan\Rules\Comparison\LogicalXorConstantConditionRule(
			$this->getService('080'),
			$this->getService('081'),
			true,
			false,
			true
		);
	}


	public function createService0768(): PHPStan\Rules\Comparison\NumberComparisonOperatorsConstantConditionRule
	{
		return new PHPStan\Rules\Comparison\NumberComparisonOperatorsConstantConditionRule($this->getService('081'), true, true);
	}


	public function createService0769(): PHPStan\Rules\Comparison\ImpossibleCheckTypeMethodCallRule
	{
		return new PHPStan\Rules\Comparison\ImpossibleCheckTypeMethodCallRule(
			$this->getService('082'),
			$this->getService('081'),
			true,
			false,
			true
		);
	}


	public function createService0770(): PHPStan\Rules\Traits\TraitUseCollector
	{
		return new PHPStan\Rules\Traits\TraitUseCollector;
	}


	public function createService0771(): PHPStan\Rules\Traits\TraitDeclarationCollector
	{
		return new PHPStan\Rules\Traits\TraitDeclarationCollector;
	}


	public function createService0772(): PHPStan\Rules\DeadCode\PossiblyPureStaticCallCollector
	{
		return new PHPStan\Rules\DeadCode\PossiblyPureStaticCallCollector;
	}


	public function createService0773(): PHPStan\Rules\DeadCode\PossiblyPureMethodCallCollector
	{
		return new PHPStan\Rules\DeadCode\PossiblyPureMethodCallCollector;
	}


	public function createService0774(): PHPStan\Rules\DeadCode\PossiblyPureFuncCallCollector
	{
		return new PHPStan\Rules\DeadCode\PossiblyPureFuncCallCollector($this->getService('reflectionProvider'));
	}


	public function createService0775(): PHPStan\Rules\DeadCode\MethodWithoutImpurePointsCollector
	{
		return new PHPStan\Rules\DeadCode\MethodWithoutImpurePointsCollector;
	}


	public function createService0776(): PHPStan\Rules\DeadCode\FunctionWithoutImpurePointsCollector
	{
		return new PHPStan\Rules\DeadCode\FunctionWithoutImpurePointsCollector;
	}


	public function createService0777(): PHPStan\Rules\DeadCode\ConstructorWithoutImpurePointsCollector
	{
		return new PHPStan\Rules\DeadCode\ConstructorWithoutImpurePointsCollector;
	}


	public function createService0778(): PHPStan\Rules\DeadCode\PossiblyPureNewCollector
	{
		return new PHPStan\Rules\DeadCode\PossiblyPureNewCollector($this->getService('reflectionProvider'));
	}


	public function createService0779(): PHPStan\Rules\Generics\MethodTagTemplateTypeTraitRule
	{
		return new PHPStan\Rules\Generics\MethodTagTemplateTypeTraitRule(
			$this->getService('014'),
			$this->getService('reflectionProvider')
		);
	}


	public function createService0780(): PHPStan\Rules\Generics\EnumTemplateTypeRule
	{
		return new PHPStan\Rules\Generics\EnumTemplateTypeRule;
	}


	public function createService0781(): PHPStan\Rules\Generics\FunctionTemplateTypeRule
	{
		return new PHPStan\Rules\Generics\FunctionTemplateTypeRule($this->getService('0449'), $this->getService('018'));
	}


	public function createService0782(): PHPStan\Rules\Generics\MethodTagTemplateTypeRule
	{
		return new PHPStan\Rules\Generics\MethodTagTemplateTypeRule($this->getService('014'));
	}


	public function createService0783(): PHPStan\Rules\Generics\MethodSignatureVarianceRule
	{
		return new PHPStan\Rules\Generics\MethodSignatureVarianceRule($this->getService('013'));
	}


	public function createService0784(): PHPStan\Rules\Generics\UsedTraitsRule
	{
		return new PHPStan\Rules\Generics\UsedTraitsRule($this->getService('0449'), $this->getService('015'));
	}


	public function createService0785(): PHPStan\Rules\Generics\TraitTemplateTypeRule
	{
		return new PHPStan\Rules\Generics\TraitTemplateTypeRule($this->getService('0449'), $this->getService('018'));
	}


	public function createService0786(): PHPStan\Rules\Generics\MethodTemplateTypeRule
	{
		return new PHPStan\Rules\Generics\MethodTemplateTypeRule($this->getService('0449'), $this->getService('018'));
	}


	public function createService0787(): PHPStan\Rules\Generics\PropertyVarianceRule
	{
		return new PHPStan\Rules\Generics\PropertyVarianceRule($this->getService('013'));
	}


	public function createService0788(): PHPStan\Rules\Generics\EnumAncestorsRule
	{
		return new PHPStan\Rules\Generics\EnumAncestorsRule($this->getService('015'), $this->getService('016'));
	}


	public function createService0789(): PHPStan\Rules\Generics\ClassTemplateTypeRule
	{
		return new PHPStan\Rules\Generics\ClassTemplateTypeRule($this->getService('018'));
	}


	public function createService0790(): PHPStan\Rules\Generics\FunctionSignatureVarianceRule
	{
		return new PHPStan\Rules\Generics\FunctionSignatureVarianceRule($this->getService('013'));
	}


	public function createService0791(): PHPStan\Rules\Generics\ClassAncestorsRule
	{
		return new PHPStan\Rules\Generics\ClassAncestorsRule($this->getService('015'), $this->getService('016'));
	}


	public function createService0792(): PHPStan\Rules\Generics\InterfaceAncestorsRule
	{
		return new PHPStan\Rules\Generics\InterfaceAncestorsRule($this->getService('015'), $this->getService('016'));
	}


	public function createService0793(): PHPStan\Rules\Generics\InterfaceTemplateTypeRule
	{
		return new PHPStan\Rules\Generics\InterfaceTemplateTypeRule($this->getService('018'));
	}


	public function createService0794(): PHPStan\Rules\Classes\MethodTagTraitUseRule
	{
		return new PHPStan\Rules\Classes\MethodTagTraitUseRule($this->getService('032'));
	}


	public function createService0795(): PHPStan\Rules\Classes\PropertyTagTraitRule
	{
		return new PHPStan\Rules\Classes\PropertyTagTraitRule($this->getService('035'), $this->getService('reflectionProvider'));
	}


	public function createService0796(): PHPStan\Rules\Classes\MixinTraitUseRule
	{
		return new PHPStan\Rules\Classes\MixinTraitUseRule($this->getService('034'));
	}


	public function createService0797(): PHPStan\Rules\Classes\PropertyTagRule
	{
		return new PHPStan\Rules\Classes\PropertyTagRule($this->getService('035'));
	}


	public function createService0798(): PHPStan\Rules\Classes\ExistingClassesInInterfaceExtendsRule
	{
		return new PHPStan\Rules\Classes\ExistingClassesInInterfaceExtendsRule(
			$this->getService('060'),
			$this->getService('reflectionProvider'),
			true
		);
	}


	public function createService0799(): PHPStan\Rules\Classes\DuplicateClassDeclarationRule
	{
		return new PHPStan\Rules\Classes\DuplicateClassDeclarationRule(
			$this->getService('betterReflectionReflector'),
			$this->getService('relativePathHelper')
		);
	}


	public function createService0800(): PHPStan\Rules\Classes\MixinTraitRule
	{
		return new PHPStan\Rules\Classes\MixinTraitRule($this->getService('034'), $this->getService('reflectionProvider'));
	}


	public function createService0801(): PHPStan\Rules\Classes\LocalTypeAliasesRule
	{
		return new PHPStan\Rules\Classes\LocalTypeAliasesRule($this->getService('031'));
	}


	public function createService0802(): PHPStan\Rules\Classes\MixinRule
	{
		return new PHPStan\Rules\Classes\MixinRule($this->getService('034'));
	}


	public function createService0803(): PHPStan\Rules\Classes\LocalTypeTraitAliasesRule
	{
		return new PHPStan\Rules\Classes\LocalTypeTraitAliasesRule($this->getService('031'), $this->getService('reflectionProvider'));
	}


	public function createService0804(): PHPStan\Rules\Classes\MethodTagRule
	{
		return new PHPStan\Rules\Classes\MethodTagRule($this->getService('032'));
	}


	public function createService0805(): PHPStan\Rules\Classes\MethodTagTraitRule
	{
		return new PHPStan\Rules\Classes\MethodTagTraitRule($this->getService('032'), $this->getService('reflectionProvider'));
	}


	public function createService0806(): PHPStan\Rules\Classes\ExistingClassesInClassImplementsRule
	{
		return new PHPStan\Rules\Classes\ExistingClassesInClassImplementsRule(
			$this->getService('060'),
			$this->getService('reflectionProvider'),
			true
		);
	}


	public function createService0807(): PHPStan\Rules\Classes\LocalTypeTraitUseAliasesRule
	{
		return new PHPStan\Rules\Classes\LocalTypeTraitUseAliasesRule($this->getService('031'));
	}


	public function createService0808(): PHPStan\Rules\Classes\ExistingClassInClassExtendsRule
	{
		return new PHPStan\Rules\Classes\ExistingClassInClassExtendsRule(
			$this->getService('060'),
			$this->getService('reflectionProvider'),
			true
		);
	}


	public function createService0809(): PHPStan\Rules\Classes\ExistingClassInTraitUseRule
	{
		return new PHPStan\Rules\Classes\ExistingClassInTraitUseRule(
			$this->getService('060'),
			$this->getService('reflectionProvider'),
			true
		);
	}


	public function createService0810(): PHPStan\Rules\Classes\DuplicateDeclarationRule
	{
		return new PHPStan\Rules\Classes\DuplicateDeclarationRule($this->getService('033'));
	}


	public function createService0811(): PHPStan\Rules\Classes\PropertyTagTraitUseRule
	{
		return new PHPStan\Rules\Classes\PropertyTagTraitUseRule($this->getService('035'));
	}


	public function createService0812(): PHPStan\Rules\Functions\ExistingClassesInTypehintsRule
	{
		return new PHPStan\Rules\Functions\ExistingClassesInTypehintsRule($this->getService('011'));
	}


	public function createService0813(): PHPStan\Rules\Functions\MissingFunctionReturnTypehintRule
	{
		return new PHPStan\Rules\Functions\MissingFunctionReturnTypehintRule($this->getService('019'));
	}


	public function createService0814(): PHPStan\Rules\Functions\DuplicateFunctionDeclarationRule
	{
		return new PHPStan\Rules\Functions\DuplicateFunctionDeclarationRule(
			$this->getService('betterReflectionReflector'),
			$this->getService('relativePathHelper')
		);
	}


	public function createService0815(): PHPStan\Rules\Functions\MissingFunctionParameterTypehintRule
	{
		return new PHPStan\Rules\Functions\MissingFunctionParameterTypehintRule($this->getService('019'));
	}


	public function createService0816(): PHPStan\Rules\Properties\MissingPropertyTypehintRule
	{
		return new PHPStan\Rules\Properties\MissingPropertyTypehintRule($this->getService('019'));
	}


	public function createService0817(): PHPStan\Rules\Properties\ExistingClassesInPropertiesRule
	{
		return new PHPStan\Rules\Properties\ExistingClassesInPropertiesRule(
			$this->getService('reflectionProvider'),
			$this->getService('060'),
			$this->getService('070'),
			$this->getService('0453'),
			true,
			false,
			true
		);
	}


	public function createService0818(): PHPStan\Rules\Methods\ExistingClassesInTypehintsRule
	{
		return new PHPStan\Rules\Methods\ExistingClassesInTypehintsRule($this->getService('011'));
	}


	public function createService0819(): PHPStan\Rules\Methods\MissingMethodReturnTypehintRule
	{
		return new PHPStan\Rules\Methods\MissingMethodReturnTypehintRule($this->getService('019'));
	}


	public function createService0820(): PHPStan\Rules\Methods\MissingMethodParameterTypehintRule
	{
		return new PHPStan\Rules\Methods\MissingMethodParameterTypehintRule($this->getService('019'));
	}


	public function createService0821(): PHPStan\Rules\Methods\MissingMethodSelfOutTypeRule
	{
		return new PHPStan\Rules\Methods\MissingMethodSelfOutTypeRule($this->getService('019'));
	}


	public function createService0822(): PHPStan\Rules\Methods\OverridingMethodRule
	{
		return new PHPStan\Rules\Methods\OverridingMethodRule(
			$this->getService('0453'),
			$this->getService('0862'),
			true,
			$this->getService('056'),
			$this->getService('059'),
			$this->getService('058'),
			false
		);
	}


	public function createService0823(): PHPStan\Rules\PhpDoc\InvalidPhpDocTagValueRule
	{
		return new PHPStan\Rules\PhpDoc\InvalidPhpDocTagValueRule($this->getService('0844'), $this->getService('0847'));
	}


	public function createService0824(): PHPStan\Rules\PhpDoc\RequireExtendsDefinitionTraitRule
	{
		return new PHPStan\Rules\PhpDoc\RequireExtendsDefinitionTraitRule(
			$this->getService('reflectionProvider'),
			$this->getService('068')
		);
	}


	public function createService0825(): PHPStan\Rules\PhpDoc\IncompatiblePropertyPhpDocTypeRule
	{
		return new PHPStan\Rules\PhpDoc\IncompatiblePropertyPhpDocTypeRule(
			$this->getService('017'),
			$this->getService('070'),
			$this->getService('069')
		);
	}


	public function createService0826(): PHPStan\Rules\PhpDoc\IncompatiblePhpDocTypeRule
	{
		return new PHPStan\Rules\PhpDoc\IncompatiblePhpDocTypeRule($this->getService('0449'), $this->getService('066'));
	}


	public function createService0827(): PHPStan\Rules\PhpDoc\SealedDefinitionClassRule
	{
		return new PHPStan\Rules\PhpDoc\SealedDefinitionClassRule(
			$this->getService('reflectionProvider'),
			$this->getService('060'),
			true,
			true
		);
	}


	public function createService0828(): PHPStan\Rules\PhpDoc\RequireImplementsDefinitionTraitRule
	{
		return new PHPStan\Rules\PhpDoc\RequireImplementsDefinitionTraitRule(
			$this->getService('reflectionProvider'),
			$this->getService('060'),
			true,
			true
		);
	}


	public function createService0829(): PHPStan\Rules\PhpDoc\InvalidPHPStanDocTagRule
	{
		return new PHPStan\Rules\PhpDoc\InvalidPHPStanDocTagRule($this->getService('0844'), $this->getService('0847'));
	}


	public function createService0830(): PHPStan\Rules\PhpDoc\IncompatibleClassConstantPhpDocTypeRule
	{
		return new PHPStan\Rules\PhpDoc\IncompatibleClassConstantPhpDocTypeRule($this->getService('017'), $this->getService('070'));
	}


	public function createService0831(): PHPStan\Rules\PhpDoc\FunctionConditionalReturnTypeRule
	{
		return new PHPStan\Rules\PhpDoc\FunctionConditionalReturnTypeRule($this->getService('071'));
	}


	public function createService0832(): PHPStan\Rules\PhpDoc\IncompatibleSelfOutTypeRule
	{
		return new PHPStan\Rules\PhpDoc\IncompatibleSelfOutTypeRule($this->getService('070'), $this->getService('017'));
	}


	public function createService0833(): PHPStan\Rules\PhpDoc\InvalidThrowsPhpDocValueRule
	{
		return new PHPStan\Rules\PhpDoc\InvalidThrowsPhpDocValueRule($this->getService('0449'));
	}


	public function createService0834(): PHPStan\Rules\PhpDoc\RequireExtendsDefinitionClassRule
	{
		return new PHPStan\Rules\PhpDoc\RequireExtendsDefinitionClassRule($this->getService('068'));
	}


	public function createService0835(): PHPStan\Rules\PhpDoc\MethodAssertRule
	{
		return new PHPStan\Rules\PhpDoc\MethodAssertRule($this->getService('065'));
	}


	public function createService0836(): PHPStan\Rules\PhpDoc\IncompatibleParamImmediatelyInvokedCallableRule
	{
		return new PHPStan\Rules\PhpDoc\IncompatibleParamImmediatelyInvokedCallableRule($this->getService('0449'));
	}


	public function createService0837(): PHPStan\Rules\PhpDoc\SealedDefinitionTraitRule
	{
		return new PHPStan\Rules\PhpDoc\SealedDefinitionTraitRule($this->getService('reflectionProvider'));
	}


	public function createService0838(): PHPStan\Rules\PhpDoc\MethodConditionalReturnTypeRule
	{
		return new PHPStan\Rules\PhpDoc\MethodConditionalReturnTypeRule($this->getService('071'));
	}


	public function createService0839(): PHPStan\Rules\PhpDoc\RequireImplementsDefinitionClassRule
	{
		return new PHPStan\Rules\PhpDoc\RequireImplementsDefinitionClassRule;
	}


	public function createService0840(): PHPStan\Rules\PhpDoc\FunctionAssertRule
	{
		return new PHPStan\Rules\PhpDoc\FunctionAssertRule($this->getService('065'));
	}


	public function createService0841(): PhpParser\BuilderFactory
	{
		return new PhpParser\BuilderFactory;
	}


	public function createService0842(): PhpParser\NodeVisitor\NameResolver
	{
		return new PhpParser\NodeVisitor\NameResolver(options: ['preserveOriginalNames' => true]);
	}


	public function createService0843(): PHPStan\PhpDocParser\ParserConfig
	{
		return new PHPStan\PhpDocParser\ParserConfig(['lines' => true]);
	}


	public function createService0844(): PHPStan\PhpDocParser\Lexer\Lexer
	{
		return new PHPStan\PhpDocParser\Lexer\Lexer($this->getService('0843'));
	}


	public function createService0845(): PHPStan\PhpDocParser\Parser\TypeParser
	{
		return new PHPStan\PhpDocParser\Parser\TypeParser($this->getService('0843'), $this->getService('0846'));
	}


	public function createService0846(): PHPStan\PhpDocParser\Parser\ConstExprParser
	{
		return new PHPStan\PhpDocParser\Parser\ConstExprParser($this->getService('0843'));
	}


	public function createService0847(): PHPStan\PhpDocParser\Parser\PhpDocParser
	{
		return new PHPStan\PhpDocParser\Parser\PhpDocParser(
			$this->getService('0843'),
			$this->getService('0845'),
			$this->getService('0846')
		);
	}


	public function createService0848(): PHPStan\PhpDocParser\Printer\Printer
	{
		return new PHPStan\PhpDocParser\Printer\Printer;
	}


	public function createService0849(): PHPStan\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber
	{
		return $this->getService('0195')->create();
	}


	public function createService0850(): PHPStan\BetterReflection\SourceLocator\SourceStubber\ReflectionSourceStubber
	{
		return $this->getService('0194')->create();
	}


	public function createService0851(): PHPStan\Dependency\ExportedNodeVisitor
	{
		return new PHPStan\Dependency\ExportedNodeVisitor($this->getService('0177'));
	}


	public function createService0852(): PHPStan\Reflection\BetterReflection\SourceLocator\CachingVisitor
	{
		return new PHPStan\Reflection\BetterReflection\SourceLocator\CachingVisitor;
	}


	public function createService0853(): PHPStan\Reflection\Php\PhpClassReflectionExtension
	{
		return new PHPStan\Reflection\Php\PhpClassReflectionExtension(
			$this->getService('0160'),
			$this->getService('085'),
			$this->getService('0460'),
			$this->getService('0222'),
			$this->getService('0205'),
			$this->getService('0854'),
			$this->getService('0855'),
			$this->getService('0208'),
			$this->getService('defaultAnalysisParser'),
			$this->getService('stubPhpDocProvider'),
			$this->getService('0193'),
			$this->getService('0449'),
			$this->getService('0203'),
			false
		);
	}


	public function createService0854(): PHPStan\Reflection\Annotations\AnnotationsMethodsClassReflectionExtension
	{
		return new PHPStan\Reflection\Annotations\AnnotationsMethodsClassReflectionExtension;
	}


	public function createService0855(): PHPStan\Reflection\Annotations\AnnotationsPropertiesClassReflectionExtension
	{
		return new PHPStan\Reflection\Annotations\AnnotationsPropertiesClassReflectionExtension;
	}


	public function createService0856(): PHPStan\Reflection\Php\UniversalObjectCratesClassReflectionExtension
	{
		return new PHPStan\Reflection\Php\UniversalObjectCratesClassReflectionExtension(
			$this->getService('reflectionProvider'),
			['stdClass'],
			$this->getService('0855')
		);
	}


	public function createService0857(): PHPStan\Reflection\Mixin\MixinMethodsClassReflectionExtension
	{
		return new PHPStan\Reflection\Mixin\MixinMethodsClassReflectionExtension([]);
	}


	public function createService0858(): PHPStan\Reflection\Mixin\MixinPropertiesClassReflectionExtension
	{
		return new PHPStan\Reflection\Mixin\MixinPropertiesClassReflectionExtension([]);
	}


	public function createService0859(): PHPStan\Reflection\Php\Soap\SoapClientMethodsClassReflectionExtension
	{
		return new PHPStan\Reflection\Php\Soap\SoapClientMethodsClassReflectionExtension;
	}


	public function createService0860(): PHPStan\Reflection\RequireExtension\RequireExtendsMethodsClassReflectionExtension
	{
		return new PHPStan\Reflection\RequireExtension\RequireExtendsMethodsClassReflectionExtension;
	}


	public function createService0861(): PHPStan\Reflection\RequireExtension\RequireExtendsPropertiesClassReflectionExtension
	{
		return new PHPStan\Reflection\RequireExtension\RequireExtendsPropertiesClassReflectionExtension;
	}


	public function createService0862(): PHPStan\Rules\Methods\MethodSignatureRule
	{
		return new PHPStan\Rules\Methods\MethodSignatureRule($this->getService('057'), true, true, true);
	}


	public function createService0863(): PHPStan\Type\Php\ReflectionGetAttributesMethodReturnTypeExtension
	{
		return new PHPStan\Type\Php\ReflectionGetAttributesMethodReturnTypeExtension('ReflectionClass');
	}


	public function createService0864(): PHPStan\Type\Php\ReflectionGetAttributesMethodReturnTypeExtension
	{
		return new PHPStan\Type\Php\ReflectionGetAttributesMethodReturnTypeExtension('ReflectionClassConstant');
	}


	public function createService0865(): PHPStan\Type\Php\ReflectionGetAttributesMethodReturnTypeExtension
	{
		return new PHPStan\Type\Php\ReflectionGetAttributesMethodReturnTypeExtension('ReflectionFunctionAbstract');
	}


	public function createService0866(): PHPStan\Type\Php\ReflectionGetAttributesMethodReturnTypeExtension
	{
		return new PHPStan\Type\Php\ReflectionGetAttributesMethodReturnTypeExtension('ReflectionParameter');
	}


	public function createService0867(): PHPStan\Type\Php\ReflectionGetAttributesMethodReturnTypeExtension
	{
		return new PHPStan\Type\Php\ReflectionGetAttributesMethodReturnTypeExtension('ReflectionProperty');
	}


	public function createService0868(): PHPStan\Type\Php\DateTimeModifyReturnTypeExtension
	{
		return new PHPStan\Type\Php\DateTimeModifyReturnTypeExtension($this->getService('0453'), 'DateTime');
	}


	public function createService0869(): PHPStan\Type\Php\DateTimeModifyReturnTypeExtension
	{
		return new PHPStan\Type\Php\DateTimeModifyReturnTypeExtension($this->getService('0453'), 'DateTimeImmutable');
	}


	public function createService0870(): PHPStan\Reflection\PHPStan\NativeReflectionEnumReturnDynamicReturnTypeExtension
	{
		return new PHPStan\Reflection\PHPStan\NativeReflectionEnumReturnDynamicReturnTypeExtension(
			$this->getService('0453'),
			'PHPStan\Reflection\ClassReflection',
			'getNativeReflection'
		);
	}


	public function createService0871(): PHPStan\Reflection\PHPStan\NativeReflectionEnumReturnDynamicReturnTypeExtension
	{
		return new PHPStan\Reflection\PHPStan\NativeReflectionEnumReturnDynamicReturnTypeExtension(
			$this->getService('0453'),
			'PHPStan\Reflection\Php\BuiltinMethodReflection',
			'getDeclaringClass'
		);
	}


	public function createService0872(): PHPStan\Reflection\BetterReflection\Type\AdapterReflectionEnumCaseDynamicReturnTypeExtension
	{
		return new PHPStan\Reflection\BetterReflection\Type\AdapterReflectionEnumCaseDynamicReturnTypeExtension(
			$this->getService('0453'),
			'PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnumBackedCase'
		);
	}


	public function createService0873(): PHPStan\Reflection\BetterReflection\Type\AdapterReflectionEnumCaseDynamicReturnTypeExtension
	{
		return new PHPStan\Reflection\BetterReflection\Type\AdapterReflectionEnumCaseDynamicReturnTypeExtension(
			$this->getService('0453'),
			'PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnumUnitCase'
		);
	}


	public function createService0874(): PHPStan\Reflection\BetterReflection\SourceLocator\SymbolFinderInFiles
	{
		return new PHPStan\Reflection\BetterReflection\SourceLocator\SymbolFinderInFiles($this->getService('0875'));
	}


	public function createService0875(): PHPStan\Reflection\BetterReflection\SourceLocator\PhpFileCleaner
	{
		return new PHPStan\Reflection\BetterReflection\SourceLocator\PhpFileCleaner;
	}


	public function createService0876(): PHPStan\Rules\Exceptions\MissingCheckedExceptionInFunctionThrowsRule
	{
		return new PHPStan\Rules\Exceptions\MissingCheckedExceptionInFunctionThrowsRule($this->getService('077'));
	}


	public function createService0877(): PHPStan\Rules\Exceptions\MissingCheckedExceptionInMethodThrowsRule
	{
		return new PHPStan\Rules\Exceptions\MissingCheckedExceptionInMethodThrowsRule($this->getService('077'));
	}


	public function createService0878(): PHPStan\Rules\Exceptions\MissingCheckedExceptionInPropertyHookThrowsRule
	{
		return new PHPStan\Rules\Exceptions\MissingCheckedExceptionInPropertyHookThrowsRule($this->getService('077'));
	}


	public function createService0879(): PHPStan\Rules\Properties\UninitializedPropertyRule
	{
		return new PHPStan\Rules\Properties\UninitializedPropertyRule($this->getService('0192'));
	}


	public function createService0880(): PHPStan\Rules\Exceptions\MethodThrowTypeCovarianceRule
	{
		return new PHPStan\Rules\Exceptions\MethodThrowTypeCovarianceRule($this->getService('057'), true);
	}


	public function createService0881(): PHPStan\Rules\Classes\NewStaticInAbstractClassStaticMethodRule
	{
		return new PHPStan\Rules\Classes\NewStaticInAbstractClassStaticMethodRule;
	}


	public function createService0882(): PHPStan\Rules\InternalTag\RestrictedInternalClassConstantUsageExtension
	{
		return new PHPStan\Rules\InternalTag\RestrictedInternalClassConstantUsageExtension($this->getService('045'));
	}


	public function createService0883(): PHPStan\Rules\InternalTag\RestrictedInternalClassNameUsageExtension
	{
		return new PHPStan\Rules\InternalTag\RestrictedInternalClassNameUsageExtension($this->getService('045'));
	}


	public function createService0884(): PHPStan\Rules\InternalTag\RestrictedInternalFunctionUsageExtension
	{
		return new PHPStan\Rules\InternalTag\RestrictedInternalFunctionUsageExtension($this->getService('045'));
	}


	public function createService0885(): PHPStan\Rules\Variables\AssignToByRefExprFromForeachRule
	{
		return new PHPStan\Rules\Variables\AssignToByRefExprFromForeachRule($this->getService('0170'));
	}


	public function createService0886(): PHPStan\Rules\InternalTag\RestrictedInternalPropertyUsageExtension
	{
		return new PHPStan\Rules\InternalTag\RestrictedInternalPropertyUsageExtension($this->getService('045'));
	}


	public function createService0887(): PHPStan\Rules\InternalTag\RestrictedInternalMethodUsageExtension
	{
		return new PHPStan\Rules\InternalTag\RestrictedInternalMethodUsageExtension($this->getService('045'));
	}


	public function createService0888(): PHPStan\Rules\Constants\ValueAssignedToDefineRule
	{
		return new PHPStan\Rules\Constants\ValueAssignedToDefineRule($this->getService('0159'));
	}


	public function createService0889(): PHPStan\Rules\Constants\ValueAssignedToGlobalConstantRule
	{
		return new PHPStan\Rules\Constants\ValueAssignedToGlobalConstantRule($this->getService('0159'));
	}


	public function createService0890(): PHPStan\Rules\Exceptions\TooWideFunctionThrowTypeRule
	{
		return new PHPStan\Rules\Exceptions\TooWideFunctionThrowTypeRule($this->getService('076'));
	}


	public function createService0891(): PHPStan\Rules\Exceptions\TooWideMethodThrowTypeRule
	{
		return new PHPStan\Rules\Exceptions\TooWideMethodThrowTypeRule(
			$this->getService('0449'),
			$this->getService('076'),
			false,
			false
		);
	}


	public function createService0892(): PHPStan\Rules\Exceptions\TooWidePropertyHookThrowTypeRule
	{
		return new PHPStan\Rules\Exceptions\TooWidePropertyHookThrowTypeRule($this->getService('076'), false);
	}


	public function createService0893(): PHPStan\Rules\Keywords\UnusedLabelRule
	{
		return new PHPStan\Rules\Keywords\UnusedLabelRule;
	}


	public function createService0894(): PHPStan\Rules\Functions\ParameterCastableToNumberRule
	{
		return new PHPStan\Rules\Functions\ParameterCastableToNumberRule(
			$this->getService('reflectionProvider'),
			$this->getService('012'),
			$this->getService('0453')
		);
	}


	public function createService0895(): PHPStan\Rules\Functions\PrintfParameterTypeRule
	{
		return new PHPStan\Rules\Functions\PrintfParameterTypeRule(
			$this->getService('038'),
			$this->getService('reflectionProvider'),
			$this->getService('020'),
			true
		);
	}


	public function createService0896(): PHPStan\Rules\DateIntervalInstantiationRule
	{
		return new PHPStan\Rules\DateIntervalInstantiationRule;
	}


	public function createService0897(): PHPStan\DependencyInjection\LazyDeprecatedScopeResolverProvider
	{
		return new PHPStan\DependencyInjection\LazyDeprecatedScopeResolverProvider($this->getService('0179'));
	}


	public function createService0898(): PHPStan\Rules\Deprecations\DeprecatedScopeHelper
	{
		return $this->getService('0897')->get();
	}


	public function createService0899(): PHPStan\Rules\Deprecations\DefaultDeprecatedScopeResolver
	{
		return new PHPStan\Rules\Deprecations\DefaultDeprecatedScopeResolver;
	}


	public function createService0900(): PHPStan\Rules\Deprecations\CallWithDeprecatedIniOptionRule
	{
		return new PHPStan\Rules\Deprecations\CallWithDeprecatedIniOptionRule(
			$this->getService('reflectionProvider'),
			$this->getService('0898'),
			$this->getService('0453')
		);
	}


	public function createService0901(): PHPStan\Rules\Deprecations\RestrictedDeprecatedClassConstantUsageExtension
	{
		return new PHPStan\Rules\Deprecations\RestrictedDeprecatedClassConstantUsageExtension($this->getService('0898'));
	}


	public function createService0902(): PHPStan\Rules\Deprecations\RestrictedDeprecatedFunctionUsageExtension
	{
		return new PHPStan\Rules\Deprecations\RestrictedDeprecatedFunctionUsageExtension($this->getService('0898'));
	}


	public function createService0903(): PHPStan\Rules\Deprecations\RestrictedDeprecatedMethodUsageExtension
	{
		return new PHPStan\Rules\Deprecations\RestrictedDeprecatedMethodUsageExtension($this->getService('0898'));
	}


	public function createService0904(): PHPStan\Rules\Deprecations\RestrictedDeprecatedPropertyUsageExtension
	{
		return new PHPStan\Rules\Deprecations\RestrictedDeprecatedPropertyUsageExtension($this->getService('0898'));
	}


	public function createService0905(): PHPStan\Rules\Deprecations\RestrictedDeprecatedClassNameUsageExtension
	{
		return new PHPStan\Rules\Deprecations\RestrictedDeprecatedClassNameUsageExtension(
			$this->getService('0898'),
			$this->getService('reflectionProvider'),
			true
		);
	}


	public function createService0906(): PHPStan\Rule\Nette\RethrowExceptionRule
	{
		return new PHPStan\Rule\Nette\RethrowExceptionRule([
			'Nette\Application\UI\Presenter' => [
				'redirectUrl' => 'Nette\Application\AbortException',
				'sendJson' => 'Nette\Application\AbortException',
				'sendResponse' => 'Nette\Application\AbortException',
				'terminate' => 'Nette\Application\AbortException',
				'forward' => 'Nette\Application\AbortException',
			],
			'Nette\Application\UI\Component' => [
				'redirect' => 'Nette\Application\AbortException',
				'redirectPermanent' => 'Nette\Application\AbortException',
				'error' => 'Nette\Application\BadRequestException',
			],
		]);
	}


	public function createService0907(): PHPStan\PhpDoc\PHPUnit\MockObjectTypeNodeResolverExtension
	{
		return new PHPStan\PhpDoc\PHPUnit\MockObjectTypeNodeResolverExtension;
	}


	public function createService0908(): PHPStan\Type\PHPUnit\Assert\AssertFunctionTypeSpecifyingExtension
	{
		return new PHPStan\Type\PHPUnit\Assert\AssertFunctionTypeSpecifyingExtension;
	}


	public function createService0909(): PHPStan\Type\PHPUnit\Assert\AssertMethodTypeSpecifyingExtension
	{
		return new PHPStan\Type\PHPUnit\Assert\AssertMethodTypeSpecifyingExtension;
	}


	public function createService0910(): PHPStan\Type\PHPUnit\Assert\AssertStaticMethodTypeSpecifyingExtension
	{
		return new PHPStan\Type\PHPUnit\Assert\AssertStaticMethodTypeSpecifyingExtension;
	}


	public function createService0911(): PHPStan\Type\PHPUnit\MockBuilderDynamicReturnTypeExtension
	{
		return new PHPStan\Type\PHPUnit\MockBuilderDynamicReturnTypeExtension;
	}


	public function createService0912(): PHPStan\Type\PHPUnit\MockForIntersectionDynamicReturnTypeExtension
	{
		return new PHPStan\Type\PHPUnit\MockForIntersectionDynamicReturnTypeExtension;
	}


	public function createService0913(): PHPStan\Rules\PHPUnit\CoversHelper
	{
		return new PHPStan\Rules\PHPUnit\CoversHelper($this->getService('reflectionProvider'));
	}


	public function createService0914(): PHPStan\Rules\PHPUnit\AnnotationHelper
	{
		return new PHPStan\Rules\PHPUnit\AnnotationHelper;
	}


	public function createService0915(): PHPStan\Rules\PHPUnit\TestMethodsHelper
	{
		return new PHPStan\Rules\PHPUnit\TestMethodsHelper($this->getService('0449'), $this->getService('0916'));
	}


	public function createService0916(): PHPStan\Rules\PHPUnit\PHPUnitVersion
	{
		return $this->getService('0917')->createPHPUnitVersion();
	}


	public function createService0917(): PHPStan\Rules\PHPUnit\PHPUnitVersionDetector
	{
		return new PHPStan\Rules\PHPUnit\PHPUnitVersionDetector;
	}


	public function createService0918(): PHPStan\Rules\PHPUnit\DataProviderHelper
	{
		return $this->getService('0919')->create();
	}


	public function createService0919(): PHPStan\Rules\PHPUnit\DataProviderHelperFactory
	{
		return new PHPStan\Rules\PHPUnit\DataProviderHelperFactory(
			$this->getService('reflectionProvider'),
			$this->getService('0449'),
			$this->getService('defaultAnalysisParser'),
			$this->getService('0916')
		);
	}


	public function createService0920(): PHPStan\Type\PHPUnit\DataProviderReturnTypeIgnoreExtension
	{
		return new PHPStan\Type\PHPUnit\DataProviderReturnTypeIgnoreExtension($this->getService('0915'), $this->getService('0918'));
	}


	public function createService0921(): PHPStan\Type\PHPUnit\DynamicCallToAssertionIgnoreExtension
	{
		return new PHPStan\Type\PHPUnit\DynamicCallToAssertionIgnoreExtension;
	}


	public function createService0922(): PHPStan\Rules\PHPUnit\DataProviderDeclarationRule
	{
		return new PHPStan\Rules\PHPUnit\DataProviderDeclarationRule($this->getService('0918'), true, true);
	}


	public function createService0923(): PHPStan\Rules\PHPUnit\AttributeRequiresPhpVersionRule
	{
		return new PHPStan\Rules\PHPUnit\AttributeRequiresPhpVersionRule($this->getService('0916'), $this->getService('0915'), true);
	}


	public function createService0924(): PHPStan\Rules\PHPUnit\AssertEqualsIsDiscouragedRule
	{
		return new PHPStan\Rules\PHPUnit\AssertEqualsIsDiscouragedRule;
	}


	public function createService0925(): PHPStan\Rules\PHPUnit\DataProviderDataRule
	{
		return new PHPStan\Rules\PHPUnit\DataProviderDataRule(
			$this->getService('0915'),
			$this->getService('0918'),
			$this->getService('0916')
		);
	}


	public function createService0926(): PHPStan\Rules\BooleansInConditions\BooleanRuleHelper
	{
		return new PHPStan\Rules\BooleansInConditions\BooleanRuleHelper($this->getService('020'));
	}


	public function createService0927(): PHPStan\Rules\Operators\OperatorRuleHelper
	{
		return new PHPStan\Rules\Operators\OperatorRuleHelper($this->getService('020'));
	}


	public function createService0928(): PHPStan\Rules\VariableVariables\VariablePropertyFetchRule
	{
		return new PHPStan\Rules\VariableVariables\VariablePropertyFetchRule($this->getService('reflectionProvider'), ['stdClass']);
	}


	public function createService0929(): PHPStan\Rules\DisallowedConstructs\DisallowedLooseComparisonRule
	{
		return new PHPStan\Rules\DisallowedConstructs\DisallowedLooseComparisonRule(true);
	}


	public function createService0930(): PHPStan\Rules\BooleansInConditions\BooleanInBooleanAndRule
	{
		return new PHPStan\Rules\BooleansInConditions\BooleanInBooleanAndRule($this->getService('0926'));
	}


	public function createService0931(): PHPStan\Rules\BooleansInConditions\BooleanInBooleanNotRule
	{
		return new PHPStan\Rules\BooleansInConditions\BooleanInBooleanNotRule($this->getService('0926'));
	}


	public function createService0932(): PHPStan\Rules\BooleansInConditions\BooleanInBooleanOrRule
	{
		return new PHPStan\Rules\BooleansInConditions\BooleanInBooleanOrRule($this->getService('0926'));
	}


	public function createService0933(): PHPStan\Rules\BooleansInConditions\BooleanInDoWhileConditionRule
	{
		return new PHPStan\Rules\BooleansInConditions\BooleanInDoWhileConditionRule($this->getService('0926'));
	}


	public function createService0934(): PHPStan\Rules\BooleansInConditions\BooleanInElseIfConditionRule
	{
		return new PHPStan\Rules\BooleansInConditions\BooleanInElseIfConditionRule($this->getService('0926'));
	}


	public function createService0935(): PHPStan\Rules\BooleansInConditions\BooleanInIfConditionRule
	{
		return new PHPStan\Rules\BooleansInConditions\BooleanInIfConditionRule($this->getService('0926'));
	}


	public function createService0936(): PHPStan\Rules\BooleansInConditions\BooleanInTernaryOperatorRule
	{
		return new PHPStan\Rules\BooleansInConditions\BooleanInTernaryOperatorRule($this->getService('0926'));
	}


	public function createService0937(): PHPStan\Rules\BooleansInConditions\BooleanInWhileConditionRule
	{
		return new PHPStan\Rules\BooleansInConditions\BooleanInWhileConditionRule($this->getService('0926'));
	}


	public function createService0938(): PHPStan\Rules\Cast\UselessCastRule
	{
		return new PHPStan\Rules\Cast\UselessCastRule(true, true);
	}


	public function createService0939(): PHPStan\Rules\Classes\RequireParentConstructCallRule
	{
		return new PHPStan\Rules\Classes\RequireParentConstructCallRule;
	}


	public function createService0940(): PHPStan\Rules\DisallowedConstructs\DisallowedBacktickRule
	{
		return new PHPStan\Rules\DisallowedConstructs\DisallowedBacktickRule;
	}


	public function createService0941(): PHPStan\Rules\DisallowedConstructs\DisallowedEmptyRule
	{
		return new PHPStan\Rules\DisallowedConstructs\DisallowedEmptyRule;
	}


	public function createService0942(): PHPStan\Rules\DisallowedConstructs\DisallowedImplicitArrayCreationRule
	{
		return new PHPStan\Rules\DisallowedConstructs\DisallowedImplicitArrayCreationRule;
	}


	public function createService0943(): PHPStan\Rules\DisallowedConstructs\DisallowedShortTernaryRule
	{
		return new PHPStan\Rules\DisallowedConstructs\DisallowedShortTernaryRule;
	}


	public function createService0944(): PHPStan\Rules\ForeachLoop\OverwriteVariablesWithForeachRule
	{
		return new PHPStan\Rules\ForeachLoop\OverwriteVariablesWithForeachRule;
	}


	public function createService0945(): PHPStan\Rules\ForLoop\OverwriteVariablesWithForLoopInitRule
	{
		return new PHPStan\Rules\ForLoop\OverwriteVariablesWithForLoopInitRule;
	}


	public function createService0946(): PHPStan\Rules\Functions\ArrayFilterStrictRule
	{
		return new PHPStan\Rules\Functions\ArrayFilterStrictRule($this->getService('reflectionProvider'), true, true, true);
	}


	public function createService0947(): PHPStan\Rules\Functions\ClosureUsesThisRule
	{
		return new PHPStan\Rules\Functions\ClosureUsesThisRule;
	}


	public function createService0948(): PHPStan\Rules\Methods\WrongCaseOfInheritedMethodRule
	{
		return new PHPStan\Rules\Methods\WrongCaseOfInheritedMethodRule;
	}


	public function createService0949(): PHPStan\Rules\Methods\IllegalConstructorMethodCallRule
	{
		return new PHPStan\Rules\Methods\IllegalConstructorMethodCallRule;
	}


	public function createService0950(): PHPStan\Rules\Methods\IllegalConstructorStaticCallRule
	{
		return new PHPStan\Rules\Methods\IllegalConstructorStaticCallRule;
	}


	public function createService0951(): PHPStan\Rules\Operators\OperandInArithmeticPostDecrementRule
	{
		return new PHPStan\Rules\Operators\OperandInArithmeticPostDecrementRule($this->getService('0927'));
	}


	public function createService0952(): PHPStan\Rules\Operators\OperandInArithmeticPostIncrementRule
	{
		return new PHPStan\Rules\Operators\OperandInArithmeticPostIncrementRule($this->getService('0927'));
	}


	public function createService0953(): PHPStan\Rules\Operators\OperandInArithmeticPreDecrementRule
	{
		return new PHPStan\Rules\Operators\OperandInArithmeticPreDecrementRule($this->getService('0927'));
	}


	public function createService0954(): PHPStan\Rules\Operators\OperandInArithmeticPreIncrementRule
	{
		return new PHPStan\Rules\Operators\OperandInArithmeticPreIncrementRule($this->getService('0927'));
	}


	public function createService0955(): PHPStan\Rules\Operators\OperandInArithmeticUnaryMinusRule
	{
		return new PHPStan\Rules\Operators\OperandInArithmeticUnaryMinusRule($this->getService('0927'));
	}


	public function createService0956(): PHPStan\Rules\Operators\OperandInArithmeticUnaryPlusRule
	{
		return new PHPStan\Rules\Operators\OperandInArithmeticUnaryPlusRule($this->getService('0927'));
	}


	public function createService0957(): PHPStan\Rules\Operators\OperandsInArithmeticAdditionRule
	{
		return new PHPStan\Rules\Operators\OperandsInArithmeticAdditionRule($this->getService('0927'));
	}


	public function createService0958(): PHPStan\Rules\Operators\OperandsInArithmeticDivisionRule
	{
		return new PHPStan\Rules\Operators\OperandsInArithmeticDivisionRule($this->getService('0927'));
	}


	public function createService0959(): PHPStan\Rules\Operators\OperandsInArithmeticExponentiationRule
	{
		return new PHPStan\Rules\Operators\OperandsInArithmeticExponentiationRule($this->getService('0927'));
	}


	public function createService0960(): PHPStan\Rules\Operators\OperandsInArithmeticModuloRule
	{
		return new PHPStan\Rules\Operators\OperandsInArithmeticModuloRule($this->getService('0927'));
	}


	public function createService0961(): PHPStan\Rules\Operators\OperandsInArithmeticMultiplicationRule
	{
		return new PHPStan\Rules\Operators\OperandsInArithmeticMultiplicationRule($this->getService('0927'));
	}


	public function createService0962(): PHPStan\Rules\Operators\OperandsInArithmeticSubtractionRule
	{
		return new PHPStan\Rules\Operators\OperandsInArithmeticSubtractionRule($this->getService('0927'));
	}


	public function createService0963(): PHPStan\Rules\StrictCalls\DynamicCallOnStaticMethodsRule
	{
		return new PHPStan\Rules\StrictCalls\DynamicCallOnStaticMethodsRule($this->getService('020'));
	}


	public function createService0964(): PHPStan\Rules\StrictCalls\DynamicCallOnStaticMethodsCallableRule
	{
		return new PHPStan\Rules\StrictCalls\DynamicCallOnStaticMethodsCallableRule($this->getService('020'));
	}


	public function createService0965(): PHPStan\Rules\StrictCalls\StrictFunctionCallsRule
	{
		return new PHPStan\Rules\StrictCalls\StrictFunctionCallsRule($this->getService('reflectionProvider'));
	}


	public function createService0966(): PHPStan\Rules\SwitchConditions\MatchingTypeInSwitchCaseConditionRule
	{
		return new PHPStan\Rules\SwitchConditions\MatchingTypeInSwitchCaseConditionRule($this->getService('0171'));
	}


	public function createService0967(): PHPStan\Rules\VariableVariables\VariableMethodCallRule
	{
		return new PHPStan\Rules\VariableVariables\VariableMethodCallRule;
	}


	public function createService0968(): PHPStan\Rules\VariableVariables\VariableMethodCallableRule
	{
		return new PHPStan\Rules\VariableVariables\VariableMethodCallableRule;
	}


	public function createService0969(): PHPStan\Rules\VariableVariables\VariableStaticMethodCallRule
	{
		return new PHPStan\Rules\VariableVariables\VariableStaticMethodCallRule;
	}


	public function createService0970(): PHPStan\Rules\VariableVariables\VariableStaticMethodCallableRule
	{
		return new PHPStan\Rules\VariableVariables\VariableStaticMethodCallableRule;
	}


	public function createService0971(): PHPStan\Rules\VariableVariables\VariableStaticPropertyFetchRule
	{
		return new PHPStan\Rules\VariableVariables\VariableStaticPropertyFetchRule;
	}


	public function createService0972(): PHPStan\Rules\VariableVariables\VariableVariablesRule
	{
		return new PHPStan\Rules\VariableVariables\VariableVariablesRule;
	}


	public function createService0973(): ShipMonk\PHPStan\DeadCode\Cache\UsageCacheStorage
	{
		return new ShipMonk\PHPStan\DeadCode\Cache\UsageCacheStorage('/home/runner/work/phpstan-src/phpstan-src/tmp', true);
	}


	public function createService0974(): ShipMonk\PHPStan\DeadCode\Hierarchy\ClassHierarchy
	{
		return new ShipMonk\PHPStan\DeadCode\Hierarchy\ClassHierarchy;
	}


	public function createService0975(): ShipMonk\PHPStan\DeadCode\Transformer\FileSystem
	{
		return new ShipMonk\PHPStan\DeadCode\Transformer\FileSystem;
	}


	public function createService0976(): ShipMonk\PHPStan\DeadCode\Output\OutputEnhancer
	{
		return new ShipMonk\PHPStan\DeadCode\Output\OutputEnhancer($this->getService('relativePathHelper'), null);
	}


	public function createService0977(): ShipMonk\PHPStan\DeadCode\Debug\DebugUsagePrinter
	{
		return new ShipMonk\PHPStan\DeadCode\Debug\DebugUsagePrinter(
			$this->getService('0179'),
			$this->getService('0976'),
			$this->getService('reflectionProvider')
		);
	}


	public function createService0978(): ShipMonk\PHPStan\DeadCode\Provider\ApiPhpDocUsageProvider
	{
		return new ShipMonk\PHPStan\DeadCode\Provider\ApiPhpDocUsageProvider(
			$this->getService('reflectionProvider'),
			true,
			$this->getParameter('analysedPaths')
		);
	}


	public function createService0979(): ShipMonk\PHPStan\DeadCode\Provider\EnumUsageProvider
	{
		return new ShipMonk\PHPStan\DeadCode\Provider\EnumUsageProvider(true);
	}


	public function createService0980(): ShipMonk\PHPStan\DeadCode\Provider\VendorUsageProvider
	{
		return new ShipMonk\PHPStan\DeadCode\Provider\VendorUsageProvider(true);
	}


	public function createService0981(): ShipMonk\PHPStan\DeadCode\Provider\BuiltinUsageProvider
	{
		return new ShipMonk\PHPStan\DeadCode\Provider\BuiltinUsageProvider(true);
	}


	public function createService0982(): ShipMonk\PHPStan\DeadCode\Provider\ReflectionUsageProvider
	{
		return new ShipMonk\PHPStan\DeadCode\Provider\ReflectionUsageProvider(true);
	}


	public function createService0983(): ShipMonk\PHPStan\DeadCode\Provider\PhpUnitUsageProvider
	{
		return new ShipMonk\PHPStan\DeadCode\Provider\PhpUnitUsageProvider(null, $this->getService('0847'), $this->getService('0844'));
	}


	public function createService0984(): ShipMonk\PHPStan\DeadCode\Provider\PhpBenchUsageProvider
	{
		return new ShipMonk\PHPStan\DeadCode\Provider\PhpBenchUsageProvider(null, $this->getService('0847'), $this->getService('0844'));
	}


	public function createService0985(): ShipMonk\PHPStan\DeadCode\Provider\BehatUsageProvider
	{
		return new ShipMonk\PHPStan\DeadCode\Provider\BehatUsageProvider(null);
	}


	public function createService0986(): ShipMonk\PHPStan\DeadCode\Provider\SymfonyUsageProvider
	{
		return new ShipMonk\PHPStan\DeadCode\Provider\SymfonyUsageProvider($this->getService('0179'), null, null, []);
	}


	public function createService0987(): ShipMonk\PHPStan\DeadCode\Provider\TwigUsageProvider
	{
		return new ShipMonk\PHPStan\DeadCode\Provider\TwigUsageProvider(
			$this->getService('reflectionProvider'),
			$this->getParameter('analysedPaths'),
			null
		);
	}


	public function createService0988(): ShipMonk\PHPStan\DeadCode\Provider\DoctrineUsageProvider
	{
		return new ShipMonk\PHPStan\DeadCode\Provider\DoctrineUsageProvider(null);
	}


	public function createService0989(): ShipMonk\PHPStan\DeadCode\Provider\PhpStanUsageProvider
	{
		return new ShipMonk\PHPStan\DeadCode\Provider\PhpStanUsageProvider(true, $this->getService('0179'));
	}


	public function createService0990(): ShipMonk\PHPStan\DeadCode\Provider\EloquentUsageProvider
	{
		return new ShipMonk\PHPStan\DeadCode\Provider\EloquentUsageProvider(null);
	}


	public function createService0991(): ShipMonk\PHPStan\DeadCode\Provider\LaravelUsageProvider
	{
		return new ShipMonk\PHPStan\DeadCode\Provider\LaravelUsageProvider($this->getService('reflectionProvider'), null);
	}


	public function createService0992(): ShipMonk\PHPStan\DeadCode\Provider\NetteUsageProvider
	{
		return new ShipMonk\PHPStan\DeadCode\Provider\NetteUsageProvider($this->getService('reflectionProvider'), null);
	}


	public function createService0993(): ShipMonk\PHPStan\DeadCode\Provider\NetteTesterUsageProvider
	{
		return new ShipMonk\PHPStan\DeadCode\Provider\NetteTesterUsageProvider(null);
	}


	public function createService0994(): ShipMonk\PHPStan\DeadCode\Provider\StreamWrapperUsageProvider
	{
		return new ShipMonk\PHPStan\DeadCode\Provider\StreamWrapperUsageProvider(true);
	}


	public function createService0995(): ShipMonk\PHPStan\DeadCode\Excluder\TestsUsageExcluder
	{
		return new ShipMonk\PHPStan\DeadCode\Excluder\TestsUsageExcluder($this->getService('reflectionProvider'), false, null);
	}


	public function createService0996(): ShipMonk\PHPStan\DeadCode\Excluder\MixedUsageExcluder
	{
		return new ShipMonk\PHPStan\DeadCode\Excluder\MixedUsageExcluder(false);
	}


	public function createService0997(): ShipMonk\PHPStan\DeadCode\Collector\MethodCallCollector
	{
		return new ShipMonk\PHPStan\DeadCode\Collector\MethodCallCollector(
			$this->getService('0973'),
			[$this->getService('0995'), $this->getService('0996')]
		);
	}


	public function createService0998(): ShipMonk\PHPStan\DeadCode\Collector\ConstantFetchCollector
	{
		return new ShipMonk\PHPStan\DeadCode\Collector\ConstantFetchCollector(
			$this->getService('0973'),
			$this->getService('reflectionProvider'),
			[$this->getService('0995'), $this->getService('0996')]
		);
	}


	public function createService0999(): ShipMonk\PHPStan\DeadCode\Collector\PropertyAccessCollector
	{
		return new ShipMonk\PHPStan\DeadCode\Collector\PropertyAccessCollector(
			$this->getService('0973'),
			[$this->getService('0995'), $this->getService('0996')]
		);
	}


	public function createService01000(): ShipMonk\PHPStan\DeadCode\Collector\ClassDefinitionCollector
	{
		return new ShipMonk\PHPStan\DeadCode\Collector\ClassDefinitionCollector($this->getService('reflectionProvider'));
	}


	public function createService01001(): ShipMonk\PHPStan\DeadCode\Collector\ProvidedUsagesCollector
	{
		return new ShipMonk\PHPStan\DeadCode\Collector\ProvidedUsagesCollector(
			$this->getService('0973'),
			$this->getService('reflectionProvider'),
			[
				$this->getService('0978'),
				$this->getService('0979'),
				$this->getService('0980'),
				$this->getService('0981'),
				$this->getService('0982'),
				$this->getService('0983'),
				$this->getService('0984'),
				$this->getService('0985'),
				$this->getService('0986'),
				$this->getService('0987'),
				$this->getService('0988'),
				$this->getService('0989'),
				$this->getService('0990'),
				$this->getService('0991'),
				$this->getService('0992'),
				$this->getService('0993'),
				$this->getService('0994'),
			],
			[$this->getService('0995'), $this->getService('0996')]
		);
	}


	public function createService01002(): ShipMonk\PHPStan\DeadCode\Rule\DeadCodeRule
	{
		return new ShipMonk\PHPStan\DeadCode\Rule\DeadCodeRule(
			$this->getService('0977'),
			$this->getService('0973'),
			$this->getService('0974'),
			true,
			true,
			true,
			true,
			true,
			false,
			$this->getService('01003')
		);
	}


	public function createService01003(): ShipMonk\PHPStan\DeadCode\Compatibility\BackwardCompatibilityChecker
	{
		return new ShipMonk\PHPStan\DeadCode\Compatibility\BackwardCompatibilityChecker([], null);
	}


	public function createService01004(): ShipMonk\PHPStan\DeadCode\Visitor\PropertyWriteVisitor
	{
		return new ShipMonk\PHPStan\DeadCode\Visitor\PropertyWriteVisitor;
	}


	public function createService01005(): PHPStan\Build\ServiceLocatorDynamicReturnTypeExtension
	{
		return new PHPStan\Build\ServiceLocatorDynamicReturnTypeExtension;
	}


	public function createService01006(): PHPStan\Build\ContainerDynamicReturnTypeExtension
	{
		return new PHPStan\Build\ContainerDynamicReturnTypeExtension;
	}


	public function createService01007(): PHPStan\PhpDoc\StubSourceLocatorFactory
	{
		return new PHPStan\PhpDoc\StubSourceLocatorFactory(
			$this->getService('php8PhpParser'),
			$this->getService('0849'),
			$this->getService('0197'),
			$this->getService('0458'),
			[
				'/home/runner/work/phpstan-src/phpstan-src/stubs/Memcached.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/Redis.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/ReflectionAttribute.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/ReflectionClassConstant.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/ReflectionFunctionAbstract.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/ReflectionMethod.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/ReflectionParameter.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/ReflectionProperty.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/iterable.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/ArrayObject.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/WeakReference.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/ext-ds.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/ImagickPixel.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/PDOStatement.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/date.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/ibm_db2.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/mysqli.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/zip.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/dom.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/spl.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/SplObjectStorage.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/Exception.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/arrayFunctions.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/core.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/typeCheckingFunctions.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/Countable.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/file.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/stream_socket_client.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/stream_socket_server.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/ctype.stub',
				'/home/runner/work/phpstan-src/phpstan-src/vendor/phpstan/phpstan-phpunit/stubs/Assert.stub',
				'/home/runner/work/phpstan-src/phpstan-src/vendor/phpstan/phpstan-phpunit/stubs/AssertionFailedError.stub',
				'/home/runner/work/phpstan-src/phpstan-src/vendor/phpstan/phpstan-phpunit/stubs/ExpectationFailedException.stub',
				'/home/runner/work/phpstan-src/phpstan-src/vendor/phpstan/phpstan-phpunit/stubs/MockBuilder.stub',
				'/home/runner/work/phpstan-src/phpstan-src/vendor/phpstan/phpstan-phpunit/stubs/MockObject.stub',
				'/home/runner/work/phpstan-src/phpstan-src/vendor/phpstan/phpstan-phpunit/stubs/Stub.stub',
				'/home/runner/work/phpstan-src/phpstan-src/vendor/phpstan/phpstan-phpunit/stubs/TestCase.stub',
				'/home/runner/work/phpstan-src/phpstan-src/build/stubs/ReactChildProcess.stub',
				'/home/runner/work/phpstan-src/phpstan-src/build/stubs/ReactStreams.stub',
				'/home/runner/work/phpstan-src/phpstan-src/build/stubs/NetteDIContainer.stub',
				'/home/runner/work/phpstan-src/phpstan-src/build/stubs/PhpParserName.stub',
				'/home/runner/work/phpstan-src/phpstan-src/build/stubs/PhpParserNode.stub',
				'/home/runner/work/phpstan-src/phpstan-src/build/stubs/PhpParserExpr.stub',
				'/home/runner/work/phpstan-src/phpstan-src/build/stubs/PhpParserStmt.stub',
				'/home/runner/work/phpstan-src/phpstan-src/build/stubs/Identifier.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/bcmath.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/socket_select_php8.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/ReflectionClassWithLazyObjects.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/ReflectionEnumWithLazyObjects.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/json_validate.stub',
			]
		);
	}


	public function createServiceBetterReflectionProvider(): PHPStan\Reflection\BetterReflection\BetterReflectionProvider
	{
		return new PHPStan\Reflection\BetterReflection\BetterReflectionProvider(
			$this->getService('0204'),
			$this->getService('0459'),
			$this->getService('betterReflectionReflector'),
			$this->getService('0449'),
			$this->getService('0205'),
			$this->getService('0453'),
			$this->getService('0206'),
			$this->getService('stubPhpDocProvider'),
			$this->getService('0461'),
			$this->getService('relativePathHelper'),
			$this->getService('0191'),
			$this->getService('0173'),
			$this->getService('0849'),
			$this->getService('0203'),
			['stdClass']
		);
	}


	public function createServiceBetterReflectionReflector(): PHPStan\Reflection\BetterReflection\Reflector\MemoizingReflector
	{
		return new PHPStan\Reflection\BetterReflection\Reflector\MemoizingReflector($this->getService('originalBetterReflectionReflector'));
	}


	public function createServiceBetterReflectionSourceLocator(): PHPStan\BetterReflection\SourceLocator\Type\SourceLocator
	{
		return $this->getService('0196')->create();
	}


	public function createServiceCacheStorage(): PHPStan\Cache\FileCacheStorage
	{
		return new PHPStan\Cache\FileCacheStorage('/home/runner/work/phpstan-src/phpstan-src/tmp/cache/PHPStan');
	}


	public function createServiceContainer(): Container_c66912be09
	{
		return $this;
	}


	public function createServiceCurrentPhpVersionLexer(): PhpParser\Lexer
	{
		return $this->getService('php8Lexer');
	}


	public function createServiceCurrentPhpVersionPhpParser(): PhpParser\Parser\Php8
	{
		return $this->getService('php8PhpParser');
	}


	public function createServiceCurrentPhpVersionPhpParserFactory(): PHPStan\Parser\PhpParserFactory
	{
		return new PHPStan\Parser\PhpParserFactory($this->getService('currentPhpVersionLexer'), $this->getService('0453'));
	}


	public function createServiceCurrentPhpVersionRichParser(): PHPStan\Parser\RichParser
	{
		return new PHPStan\Parser\RichParser(
			$this->getService('currentPhpVersionPhpParser'),
			$this->getService('0842'),
			$this->getService('0179'),
			$this->getService('0169')
		);
	}


	public function createServiceCurrentPhpVersionSimpleDirectParser(): PHPStan\Parser\SimpleParser
	{
		return new PHPStan\Parser\SimpleParser($this->getService('currentPhpVersionPhpParser'), $this->getService('0842'));
	}


	public function createServiceCurrentPhpVersionSimpleParser(): PHPStan\Parser\CleaningParser
	{
		return new PHPStan\Parser\CleaningParser($this->getService('currentPhpVersionSimpleDirectParser'), $this->getService('0453'));
	}


	public function createServiceDefaultAnalysisParser(): PHPStan\Parser\CachedParser
	{
		return $this->getService('stubParser');
	}


	public function createServiceErrorFormatter__checkstyle(): PHPStan\Command\ErrorFormatter\CheckstyleErrorFormatter
	{
		return new PHPStan\Command\ErrorFormatter\CheckstyleErrorFormatter($this->getService('simpleRelativePathHelper'));
	}


	public function createServiceErrorFormatter__filterOutUnmatchedInlineIgnoresDuringPartialAnalysis(): ShipMonk\PHPStan\DeadCode\Formatter\FilterOutUnmatchedInlineIgnoresFormatter
	{
		return new ShipMonk\PHPStan\DeadCode\Formatter\FilterOutUnmatchedInlineIgnoresFormatter(
			$this->getService('0179'),
			'table',
			[
				ShipMonk\PHPStan\DeadCode\Rule\DeadCodeRule::IDENTIFIER_METHOD,
				ShipMonk\PHPStan\DeadCode\Rule\DeadCodeRule::IDENTIFIER_CONSTANT,
				ShipMonk\PHPStan\DeadCode\Rule\DeadCodeRule::IDENTIFIER_ENUM_CASE,
				ShipMonk\PHPStan\DeadCode\Rule\DeadCodeRule::IDENTIFIER_PROPERTY_NEVER_READ,
				ShipMonk\PHPStan\DeadCode\Rule\DeadCodeRule::IDENTIFIER_PROPERTY_NEVER_WRITTEN,
			]
		);
	}


	public function createServiceErrorFormatter__github(): PHPStan\Command\ErrorFormatter\GithubErrorFormatter
	{
		return new PHPStan\Command\ErrorFormatter\GithubErrorFormatter($this->getService('simpleRelativePathHelper'));
	}


	public function createServiceErrorFormatter__gitlab(): PHPStan\Command\ErrorFormatter\GitlabErrorFormatter
	{
		return new PHPStan\Command\ErrorFormatter\GitlabErrorFormatter($this->getService('simpleRelativePathHelper'));
	}


	public function createServiceErrorFormatter__json(): PHPStan\Command\ErrorFormatter\JsonErrorFormatter
	{
		return new PHPStan\Command\ErrorFormatter\JsonErrorFormatter(false);
	}


	public function createServiceErrorFormatter__junit(): PHPStan\Command\ErrorFormatter\JunitErrorFormatter
	{
		return new PHPStan\Command\ErrorFormatter\JunitErrorFormatter($this->getService('simpleRelativePathHelper'));
	}


	public function createServiceErrorFormatter__prettyJson(): PHPStan\Command\ErrorFormatter\JsonErrorFormatter
	{
		return new PHPStan\Command\ErrorFormatter\JsonErrorFormatter(true);
	}


	public function createServiceErrorFormatter__raw(): PHPStan\Command\ErrorFormatter\RawErrorFormatter
	{
		return new PHPStan\Command\ErrorFormatter\RawErrorFormatter;
	}


	public function createServiceErrorFormatter__removeDeadCode(): ShipMonk\PHPStan\DeadCode\Formatter\RemoveDeadCodeFormatter
	{
		return new ShipMonk\PHPStan\DeadCode\Formatter\RemoveDeadCodeFormatter($this->getService('0975'), $this->getService('0976'));
	}


	public function createServiceErrorFormatter__table(): PHPStan\Command\ErrorFormatter\TableErrorFormatter
	{
		return new PHPStan\Command\ErrorFormatter\TableErrorFormatter(
			$this->getService('relativePathHelper'),
			$this->getService('simpleRelativePathHelper'),
			$this->getService('0217'),
			true,
			null,
			null,
			'8'
		);
	}


	public function createServiceErrorFormatter__teamcity(): PHPStan\Command\ErrorFormatter\TeamcityErrorFormatter
	{
		return new PHPStan\Command\ErrorFormatter\TeamcityErrorFormatter($this->getService('simpleRelativePathHelper'));
	}


	public function createServiceExceptionTypeResolver(): PHPStan\Rules\Exceptions\ExceptionTypeResolver
	{
		return $this->getService('075');
	}


	public function createServiceFileExcluderAnalyse(): PHPStan\File\FileExcluder
	{
		return $this->getService('0175')->createAnalyseFileExcluder();
	}


	public function createServiceFileExcluderScan(): PHPStan\File\FileExcluder
	{
		return $this->getService('0175')->createScanFileExcluder();
	}


	public function createServiceFileFinderAnalyse(): PHPStan\File\FileFinder
	{
		return new PHPStan\File\FileFinder($this->getService('fileExcluderAnalyse'), $this->getService('0173'), ['php']);
	}


	public function createServiceFileFinderScan(): PHPStan\File\FileFinder
	{
		return new PHPStan\File\FileFinder($this->getService('fileExcluderScan'), $this->getService('0173'), ['php']);
	}


	public function createServiceFreshStubParser(): PHPStan\Parser\StubParser
	{
		return new PHPStan\Parser\StubParser($this->getService('php8PhpParser'), $this->getService('0842'));
	}


	public function createServiceNodeScopeResolverReflector(): PHPStan\BetterReflection\Reflector\DefaultReflector
	{
		return $this->getService('stubReflector');
	}


	public function createServiceOriginalBetterReflectionReflector(): PHPStan\BetterReflection\Reflector\DefaultReflector
	{
		return $this->getService('stubReflector');
	}


	public function createServiceParentDirectoryRelativePathHelper(): PHPStan\File\ParentDirectoryRelativePathHelper
	{
		return new PHPStan\File\ParentDirectoryRelativePathHelper('/home/runner/work/phpstan-src/phpstan-src');
	}


	public function createServicePathRoutingParser(): PHPStan\Parser\PathRoutingParser
	{
		return new PHPStan\Parser\PathRoutingParser(
			$this->getService('0173'),
			$this->getService('currentPhpVersionRichParser'),
			$this->getService('currentPhpVersionSimpleParser'),
			$this->getService('php8Parser'),
			$this->getParameter('singleReflectionFile')
		);
	}


	public function createServicePhp8Lexer(): PhpParser\Lexer\Emulative
	{
		return $this->getService('0254')->createEmulative();
	}


	public function createServicePhp8Parser(): PHPStan\Parser\SimpleParser
	{
		return new PHPStan\Parser\SimpleParser($this->getService('php8PhpParser'), $this->getService('0842'));
	}


	public function createServicePhp8PhpParser(): PhpParser\Parser\Php8
	{
		return new PhpParser\Parser\Php8($this->getService('php8Lexer'));
	}


	public function createServicePhpParserDecorator(): PHPStan\Parser\PhpParserDecorator
	{
		return new PHPStan\Parser\PhpParserDecorator($this->getService('defaultAnalysisParser'));
	}


	public function createServicePhpstanDiagnoseExtension(): PHPStan\Diagnose\PHPStanDiagnoseExtension
	{
		return new PHPStan\Diagnose\PHPStanDiagnoseExtension(
			$this->getService('0453'),
			80421,
			$this->getService('0173'),
			['/home/runner/work/phpstan-src/phpstan-src'],
			[
				'/home/runner/work/phpstan-src/phpstan-src/conf/parametersSchema.neon',
				'/home/runner/work/phpstan-src/phpstan-src/conf/config.level8.neon',
				'/home/runner/work/phpstan-src/phpstan-src/conf/config.level7.neon',
				'/home/runner/work/phpstan-src/phpstan-src/conf/config.level6.neon',
				'/home/runner/work/phpstan-src/phpstan-src/conf/config.level5.neon',
				'/home/runner/work/phpstan-src/phpstan-src/conf/config.level4.neon',
				'/home/runner/work/phpstan-src/phpstan-src/conf/config.level3.neon',
				'/home/runner/work/phpstan-src/phpstan-src/conf/config.level2.neon',
				'/home/runner/work/phpstan-src/phpstan-src/conf/config.level1.neon',
				'/home/runner/work/phpstan-src/phpstan-src/conf/config.level0.neon',
				'/home/runner/work/phpstan-src/phpstan-src/phpstan.neon.dist',
				'/home/runner/work/phpstan-src/phpstan-src/build/phpstan.neon',
				'/home/runner/work/phpstan-src/phpstan-src/vendor/phpstan/phpstan-deprecation-rules/rules.neon',
				'/home/runner/work/phpstan-src/phpstan-src/vendor/phpstan/phpstan-nette/rules.neon',
				'/home/runner/work/phpstan-src/phpstan-src/vendor/phpstan/phpstan-phpunit/extension.neon',
				'/home/runner/work/phpstan-src/phpstan-src/vendor/phpstan/phpstan-phpunit/rules.neon',
				'/home/runner/work/phpstan-src/phpstan-src/vendor/phpstan/phpstan-strict-rules/rules.neon',
				'/home/runner/work/phpstan-src/phpstan-src/conf/bleedingEdge.neon',
				'/home/runner/work/phpstan-src/phpstan-src/phpstan-baseline.neon',
				'/home/runner/work/phpstan-src/phpstan-src/phpstan-baseline.php',
				'/home/runner/work/phpstan-src/phpstan-src/build/ignore-by-php-version.neon.php',
				'/home/runner/work/phpstan-src/phpstan-src/build/baseline-8.0.neon',
				'/home/runner/work/phpstan-src/phpstan-src/build/baseline-8.1.neon',
				'/home/runner/work/phpstan-src/phpstan-src/vendor/shipmonk/dead-code-detector/rules.neon',
				'/home/runner/work/phpstan-src/phpstan-src/build/ignore-gte-php7.4-errors.neon',
				'/home/runner/work/phpstan-src/phpstan-src/build/baseline-7.4.neon',
				'/home/runner/work/phpstan-src/phpstan-src/build/spl-autoload-functions-php-8.neon',
				'/home/runner/work/phpstan-src/phpstan-src/build/deprecated-8.4.neon',
				'/home/runner/work/phpstan-src/phpstan-src/build/new-phpunit.neon',
				'/home/runner/work/phpstan-src/phpstan-src/build/pre-php-85.neon',
				'/home/runner/work/phpstan-src/phpstan-src/build/ignore-by-architecture.neon.php',
				'/home/runner/work/phpstan-src/phpstan-src/conf/config.stubValidator.neon',
			],
			$this->getService('0452'),
			$this->getService('simpleRelativePathHelper')
		);
	}


	public function createServiceReflectionProvider(): PHPStan\Reflection\BetterReflection\BetterReflectionProvider
	{
		return $this->getService('stubBetterReflectionProvider');
	}


	public function createServiceReflectionProviderFactory(): PHPStan\Reflection\ReflectionProvider\ReflectionProviderFactory
	{
		return new PHPStan\Reflection\ReflectionProvider\ReflectionProviderFactory($this->getService('betterReflectionProvider'));
	}


	public function createServiceRegistry(): PHPStan\Rules\LazyRegistry
	{
		return new PHPStan\Rules\LazyRegistry($this->getService('0179'));
	}


	public function createServiceRelativePathHelper(): PHPStan\File\FuzzyRelativePathHelper
	{
		return new PHPStan\File\FuzzyRelativePathHelper(
			$this->getService('parentDirectoryRelativePathHelper'),
			'/home/runner/work/phpstan-src/phpstan-src',
			$this->getParameter('analysedPaths')
		);
	}


	public function createServiceRules__0(): PHPStan\Rules\Deprecations\FetchingDeprecatedConstRule
	{
		return new PHPStan\Rules\Deprecations\FetchingDeprecatedConstRule(
			$this->getService('reflectionProvider'),
			$this->getService('0898')
		);
	}


	public function createServiceRules__1(): PHPStan\Rule\Nette\DoNotExtendNetteObjectRule
	{
		return new PHPStan\Rule\Nette\DoNotExtendNetteObjectRule;
	}


	public function createServiceRules__10(): PHPStan\Rules\PHPUnit\NoMissingSpaceInMethodAnnotationRule
	{
		return new PHPStan\Rules\PHPUnit\NoMissingSpaceInMethodAnnotationRule($this->getService('0914'));
	}


	public function createServiceRules__11(): PHPStan\Rules\PHPUnit\ShouldCallParentMethodsRule
	{
		return new PHPStan\Rules\PHPUnit\ShouldCallParentMethodsRule;
	}


	public function createServiceRules__12(): PHPStan\Build\FinalClassRule
	{
		return new PHPStan\Build\FinalClassRule($this->getService('0173'));
	}


	public function createServiceRules__13(): PHPStan\Build\AttributeNamedArgumentsRule
	{
		return new PHPStan\Build\AttributeNamedArgumentsRule($this->getService('reflectionProvider'));
	}


	public function createServiceRules__14(): PHPStan\Build\NamedArgumentsRule
	{
		return new PHPStan\Build\NamedArgumentsRule($this->getService('reflectionProvider'), $this->getService('0453'));
	}


	public function createServiceRules__15(): PHPStan\Build\OverrideAttributeThirdPartyMethodRule
	{
		return new PHPStan\Build\OverrideAttributeThirdPartyMethodRule($this->getService('0453'), $this->getService('058'));
	}


	public function createServiceRules__16(): PHPStan\Build\SkipTestsWithRequiresPhpAttributeRule
	{
		return new PHPStan\Build\SkipTestsWithRequiresPhpAttributeRule;
	}


	public function createServiceRules__17(): PHPStan\Build\MemoizationPropertyRule
	{
		return new PHPStan\Build\MemoizationPropertyRule($this->getService('0173'));
	}


	public function createServiceRules__18(): PHPStan\Build\OrChainIdenticalComparisonToInArrayRule
	{
		return new PHPStan\Build\OrChainIdenticalComparisonToInArrayRule($this->getService('0170'), $this->getService('0173'));
	}


	public function createServiceRules__2(): PHPStan\Rule\Nette\RegularExpressionPatternRule
	{
		return new PHPStan\Rule\Nette\RegularExpressionPatternRule;
	}


	public function createServiceRules__3(): PHPStan\Rules\PHPUnit\AssertSameBooleanExpectedRule
	{
		return new PHPStan\Rules\PHPUnit\AssertSameBooleanExpectedRule;
	}


	public function createServiceRules__4(): PHPStan\Rules\PHPUnit\AssertSameNullExpectedRule
	{
		return new PHPStan\Rules\PHPUnit\AssertSameNullExpectedRule;
	}


	public function createServiceRules__5(): PHPStan\Rules\PHPUnit\AssertSameWithCountRule
	{
		return new PHPStan\Rules\PHPUnit\AssertSameWithCountRule;
	}


	public function createServiceRules__6(): PHPStan\Rules\PHPUnit\ClassCoversExistsRule
	{
		return new PHPStan\Rules\PHPUnit\ClassCoversExistsRule($this->getService('0913'), $this->getService('reflectionProvider'));
	}


	public function createServiceRules__7(): PHPStan\Rules\PHPUnit\ClassMethodCoversExistsRule
	{
		return new PHPStan\Rules\PHPUnit\ClassMethodCoversExistsRule($this->getService('0913'), $this->getService('0449'));
	}


	public function createServiceRules__8(): PHPStan\Rules\PHPUnit\MockMethodCallRule
	{
		return new PHPStan\Rules\PHPUnit\MockMethodCallRule;
	}


	public function createServiceRules__9(): PHPStan\Rules\PHPUnit\NoMissingSpaceInClassAnnotationRule
	{
		return new PHPStan\Rules\PHPUnit\NoMissingSpaceInClassAnnotationRule($this->getService('0914'));
	}


	public function createServiceSimpleRelativePathHelper(): PHPStan\File\SimpleRelativePathHelper
	{
		return new PHPStan\File\SimpleRelativePathHelper('/home/runner/work/phpstan-src/phpstan-src');
	}


	public function createServiceStubBetterReflectionProvider(): PHPStan\Reflection\BetterReflection\BetterReflectionProvider
	{
		return new PHPStan\Reflection\BetterReflection\BetterReflectionProvider(
			$this->getService('0204'),
			$this->getService('0459'),
			$this->getService('stubReflector'),
			$this->getService('0449'),
			$this->getService('0205'),
			$this->getService('0453'),
			$this->getService('0206'),
			$this->getService('stubPhpDocProvider'),
			$this->getService('0461'),
			$this->getService('relativePathHelper'),
			$this->getService('0191'),
			$this->getService('0173'),
			$this->getService('0849'),
			$this->getService('0203'),
			['stdClass']
		);
	}


	public function createServiceStubFileTypeMapper(): PHPStan\Type\FileTypeMapper
	{
		return new PHPStan\Type\FileTypeMapper(
			$this->getService('0193'),
			$this->getService('stubParser'),
			$this->getService('0234'),
			$this->getService('0232'),
			$this->getService('0191'),
			$this->getService('0173'),
			$this->getService('01'),
			2048,
			2048
		);
	}


	public function createServiceStubParser(): PHPStan\Parser\CachedParser
	{
		return new PHPStan\Parser\CachedParser($this->getService('freshStubParser'), 128);
	}


	public function createServiceStubPhpDocProvider(): PHPStan\PhpDoc\StubPhpDocProvider
	{
		return new PHPStan\PhpDoc\StubPhpDocProvider(
			$this->getService('stubParser'),
			$this->getService('stubFileTypeMapper'),
			$this->getService('0228')
		);
	}


	public function createServiceStubReflector(): PHPStan\BetterReflection\Reflector\DefaultReflector
	{
		return new PHPStan\BetterReflection\Reflector\DefaultReflector($this->getService('stubSourceLocator'));
	}


	public function createServiceStubSourceLocator(): PHPStan\BetterReflection\SourceLocator\Type\SourceLocator
	{
		return $this->getService('01007')->create();
	}


	public function createServiceTypeSpecifier(): PHPStan\Analyser\TypeSpecifier
	{
		return $this->getService('typeSpecifierFactory')->create();
	}


	public function createServiceTypeSpecifierFactory(): PHPStan\Analyser\TypeSpecifierFactory
	{
		return new PHPStan\Analyser\TypeSpecifierFactory($this->getService('0179'));
	}


	public function initialize(): void
	{
	}


	protected function getStaticParameters(): array
	{
		return [
			'bootstrapFiles' => [
				'/home/runner/work/phpstan-src/phpstan-src/stubs/runtime/ReflectionUnionType.php',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/runtime/ReflectionAttribute.php',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/runtime/Attribute85.php',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/runtime/ReflectionIntersectionType.php',
				'/home/runner/work/phpstan-src/phpstan-src/tests/phpstan-bootstrap.php',
			],
			'excludePaths' => [
				'analyseAndScan' => [
					'/home/runner/work/phpstan-src/phpstan-src/src/Rules/Constants/ConstantAttributesRule.php',
					'/home/runner/work/phpstan-src/phpstan-src/tests/*/data/*',
					'/home/runner/work/phpstan-src/phpstan-src/tests/tmp/*',
					'/home/runner/work/phpstan-src/phpstan-src/tests/vendor/*',
					'/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Analyser/nsrt/*',
					'/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Analyser/traits/*',
					'/home/runner/work/phpstan-src/phpstan-src/tests/notAutoloaded/*',
					'/home/runner/work/phpstan-src/phpstan-src/tests/bench/*',
					'/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Reflection/UnionTypesTest.php',
					'/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Reflection/MixedTypeTest.php',
					'/home/runner/work/phpstan-src/phpstan-src/tests/e2e/magic-setter/*',
					'/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Rules/Properties/UninitializedPropertyRuleTest.php',
					'/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Command/IgnoredRegexValidatorTest.php',
					'/home/runner/work/phpstan-src/phpstan-src/src/Command/IgnoredRegexValidator.php',
				],
				'analyse' => [],
			],
			'level' => 8,
			'paths' => [
				'/home/runner/work/phpstan-src/phpstan-src/build/PHPStan',
				'/home/runner/work/phpstan-src/phpstan-src/src',
				'/home/runner/work/phpstan-src/phpstan-src/tests',
			],
			'exceptions' => [
				'implicitThrows' => true,
				'reportUncheckedExceptionDeadCatch' => true,
				'uncheckedExceptionRegexes' => ['#^PHPUnit\\\#', '#^SebastianBergmann\\\#'],
				'uncheckedExceptionClasses' => [
					'PHPStan\ShouldNotHappenException',
					'Symfony\Component\Console\Exception\InvalidArgumentException',
					'PHPStan\BetterReflection\SourceLocator\Exception\InvalidFileLocation',
					'PHPStan\BetterReflection\SourceLocator\Exception\InvalidArgumentException',
					'Symfony\Component\Finder\Exception\DirectoryNotFoundException',
					'InvalidArgumentException',
					'PHPStan\DependencyInjection\ParameterNotFoundException',
					'PHPStan\DependencyInjection\DuplicateIncludedFilesException',
					'PHPStan\Analyser\UndefinedVariableException',
					'RuntimeException',
					'Nette\Neon\Exception',
					'Nette\Utils\JsonException',
					'PHPStan\File\CouldNotReadFileException',
					'PHPStan\File\CouldNotWriteFileException',
					'PHPStan\Parser\ParserErrorsException',
					'ReflectionException',
					'Nette\Utils\AssertionException',
					'PHPStan\File\PathNotFoundException',
					'PHPStan\Broker\ClassNotFoundException',
					'PHPStan\Broker\FunctionNotFoundException',
					'PHPStan\Broker\ConstantNotFoundException',
					'PHPStan\DependencyInjection\MissingServiceException',
					'PHPStan\Reflection\MissingMethodFromReflectionException',
					'PHPStan\Reflection\MissingPropertyFromReflectionException',
					'PHPStan\Reflection\MissingConstantFromReflectionException',
					'PHPStan\Type\CircularTypeAliasDefinitionException',
					'PHPStan\Reflection\MissingStaticAccessorInstanceException',
					'LogicException',
					'Error',
					'PHPStan\Analyser\Generator\TrampolineException',
				],
				'checkedExceptionRegexes' => [],
				'checkedExceptionClasses' => [],
				'check' => [
					'missingCheckedExceptionInThrows' => true,
					'tooWideThrowType' => true,
					'tooWideImplicitThrowType' => false,
					'throwTypeCovariance' => true,
				],
			],
			'featureToggles' => [
				'bleedingEdge' => true,
				'checkNonStringableDynamicAccess' => true,
				'checkParameterCastableToNumberFunctions' => true,
				'skipCheckGenericClasses' => [],
				'stricterFunctionMap' => true,
				'reportPreciseLineForUnusedFunctionParameter' => true,
				'checkPrintfParameterTypes' => true,
				'internalTag' => true,
				'newStaticInAbstractClassStaticMethod' => true,
				'checkExtensionsForComparisonOperators' => true,
				'checkGenericIterableClasses' => true,
				'reportTooWideBool' => true,
				'rawMessageInBaseline' => true,
				'reportNestedTooWideType' => false,
				'assignToByRefForeachExpr' => true,
				'curlSetOptArrayTypes' => true,
				'checkDateIntervalConstructor' => true,
				'reportMethodPurityOverride' => true,
				'checkDynamicConstantNameValues' => true,
				'unusedLabel' => true,
			],
			'fileExtensions' => ['php'],
			'checkAdvancedIsset' => true,
			'reportAlwaysTrueInLastCondition' => false,
			'checkClassCaseSensitivity' => true,
			'checkExplicitMixed' => false,
			'checkImplicitMixed' => false,
			'checkFunctionArgumentTypes' => true,
			'checkFunctionNameCase' => true,
			'checkInternalClassCaseSensitivity' => true,
			'checkMissingCallableSignature' => false,
			'checkMissingVarTagTypehint' => true,
			'checkArgumentsPassedByReference' => true,
			'checkMaybeUndefinedVariables' => true,
			'checkNullables' => true,
			'checkThisOnly' => false,
			'checkUnionTypes' => true,
			'checkBenevolentUnionTypes' => false,
			'checkExplicitMixedMissingReturn' => true,
			'checkPhpDocMissingReturn' => true,
			'checkPhpDocMethodSignatures' => true,
			'checkExtraArguments' => true,
			'checkMissingTypehints' => true,
			'checkTooWideParameterOutInProtectedAndPublicMethods' => false,
			'checkTooWideReturnTypesInProtectedAndPublicMethods' => false,
			'checkTooWideThrowTypesInProtectedAndPublicMethods' => false,
			'checkUninitializedProperties' => true,
			'checkDynamicProperties' => true,
			'strictRulesInstalled' => true,
			'deprecationRulesInstalled' => true,
			'inferPrivatePropertyTypeFromConstructor' => false,
			'checkStrictPrintfPlaceholderTypes' => true,
			'reportMaybes' => true,
			'reportMaybesInMethodSignatures' => true,
			'reportMaybesInPropertyPhpDocTypes' => true,
			'reportStaticMethodSignatures' => true,
			'reportWrongPhpDocTypeInVarTag' => true,
			'reportAnyTypeWideningInVarTag' => false,
			'reportNonIntStringArrayKey' => true,
			'reportPossiblyNonexistentGeneralArrayOffset' => false,
			'reportPossiblyNonexistentConstantArrayOffset' => true,
			'checkMissingOverrideMethodAttribute' => false,
			'checkMissingOverridePropertyAttribute' => null,
			'mixinExcludeClasses' => [],
			'scanFiles' => [],
			'scanDirectories' => [],
			'parallel' => [
				'jobSize' => 20,
				'processTimeout' => 600.0,
				'maximumNumberOfProcesses' => 8,
				'minimumNumberOfJobsPerProcess' => 2,
				'buffer' => 134217728,
				'loadLimit' => 1.0,
			],
			'phpVersion' => 80421,
			'polluteScopeWithLoopInitialAssignments' => false,
			'polluteScopeWithAlwaysIterableForeach' => false,
			'polluteScopeWithBlock' => false,
			'propertyAlwaysWrittenTags' => [],
			'propertyAlwaysReadTags' => [],
			'additionalConstructors' => ['PHPUnit\Framework\TestCase::setUp'],
			'treatPhpDocTypesAsCertain' => true,
			'usePathConstantsAsConstantString' => false,
			'rememberPossiblyImpureFunctionValues' => true,
			'tips' => ['discoveringSymbols' => true, 'treatPhpDocTypesAsCertain' => true, 'possiblyImpure' => true],
			'tipsOfTheDay' => true,
			'reportMagicMethods' => true,
			'reportMagicProperties' => true,
			'ignoreErrors' => [
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/build/PHPStan/Build/ContainerDynamicReturnTypeExtension.php',
				],
				[
					'rawMessage' => 'Method PHPStan\Analyser\AnalyserResultFinalizer::finalize() throws checked exception Throwable but it\'s missing from the PHPDoc @throws tag.',
					'identifier' => 'missingType.checkedException',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Analyser/AnalyserResultFinalizer.php',
				],
				[
					'rawMessage' => 'PHPDoc tag @var with type int|string is not subtype of type string.',
					'identifier' => 'varTag.type',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Analyser/ArgumentsNormalizer.php',
				],
				[
					'rawMessage' => 'Casting to string something that\'s already string.',
					'identifier' => 'cast.useless',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Analyser/ExprHandler/AssignHandler.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Analyser/ExprHandler/AssignHandler.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantBooleanType is error-prone and deprecated. Use Type::isTrue() or Type::isFalse() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Analyser/ExprHandler/BinaryOpHandler.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantBooleanType is error-prone and deprecated. Use Type::isTrue() or Type::isFalse() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Analyser/ExprHandler/BooleanNotHandler.php',
				],
				[
					'rawMessage' => 'Only numeric types are allowed in pre-increment, float|int|string|null given.',
					'identifier' => 'preInc.nonNumeric',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Analyser/ExprHandler/PreIncHandler.php',
				],
				[
					'rawMessage' => 'Cannot assign offset \'realCount\' to array<mixed>|string.',
					'identifier' => 'offsetAssign.dimType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Analyser/Ignore/IgnoredErrorHelperResult.php',
				],
				[
					'rawMessage' => 'Casting to string something that\'s already string.',
					'identifier' => 'cast.useless',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Analyser/MutatingScope.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantBooleanType is error-prone and deprecated. Use Type::isTrue() or Type::isFalse() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Analyser/MutatingScope.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Analyser/MutatingScope.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Analyser/MutatingScope.php',
				],
				[
					'rawMessage' => 'Parameter #2 $node of method PHPStan\BetterReflection\SourceLocator\Ast\Strategy\NodeToReflection::__invoke() expects PhpParser\Node\Expr\ArrowFunction|PhpParser\Node\Expr\Closure|PhpParser\Node\Expr\FuncCall|PhpParser\Node\Stmt\Class_|PhpParser\Node\Stmt\Const_|PhpParser\Node\Stmt\Enum_|PhpParser\Node\Stmt\Function_|PhpParser\Node\Stmt\Interface_|PhpParser\Node\Stmt\Trait_, PhpParser\Node\Stmt\ClassLike given.',
					'identifier' => 'argument.type',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Analyser/NodeScopeResolver.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantBooleanType is error-prone and deprecated. Use Type::isTrue() or Type::isFalse() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Analyser/RicherScopeGetTypeHelper.php',
				],
				[
					'rawMessage' => 'Call to method __construct() of internal class PhpParser\Internal\TokenStream from outside its root namespace PhpParser.',
					'identifier' => 'method.internalClass',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Analyser/RuleErrorTransformer.php',
				],
				[
					'rawMessage' => 'Instantiation of internal class PhpParser\Internal\TokenStream.',
					'identifier' => 'new.internalClass',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Analyser/RuleErrorTransformer.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ConstantScalarType is error-prone and deprecated. Use Type::isConstantScalarValue() or Type::getConstantScalarTypes() or Type::getConstantScalarValues() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Analyser/TypeSpecifier.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantBooleanType is error-prone and deprecated. Use Type::isTrue() or Type::isFalse() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 5,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Analyser/TypeSpecifier.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 5,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Analyser/TypeSpecifier.php',
				],
				[
					'rawMessage' => 'Template type TNodeType is declared as covariant, but occurs in contravariant position in parameter node of method PHPStan\Collectors\Collector::processNode().',
					'identifier' => 'generics.variance',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Collectors/Collector.php',
				],
				[
					'rawMessage' => 'Method PHPStan\Collectors\Registry::__construct() has parameter $collectors with generic interface PHPStan\Collectors\Collector but does not specify its types: TNodeType, TValue',
					'identifier' => 'missingType.generics',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Collectors/Registry.php',
				],
				[
					'rawMessage' => 'Property PHPStan\Collectors\Registry::$cache with generic interface PHPStan\Collectors\Collector does not specify its types: TNodeType, TValue',
					'identifier' => 'missingType.generics',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Collectors/Registry.php',
				],
				[
					'rawMessage' => 'Property PHPStan\Collectors\Registry::$collectors with generic interface PHPStan\Collectors\Collector does not specify its types: TNodeType, TValue',
					'identifier' => 'missingType.generics',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Collectors/Registry.php',
				],
				[
					'rawMessage' => 'Anonymous function has an unused use $container.',
					'identifier' => 'closure.unusedUse',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Command/CommandHelper.php',
				],
				[
					'rawMessage' => 'Call to static method expand() of internal class Nette\DI\Helpers from outside its root namespace Nette.',
					'identifier' => 'staticMethod.internalClass',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Command/CommandHelper.php',
				],
				[
					'rawMessage' => 'Parameter #1 $path of function dirname expects string, string|false given.',
					'identifier' => 'argument.type',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Command/CommandHelper.php',
				],
				[
					'rawMessage' => 'Static property PHPStan\Command\CommandHelper::$reservedMemory is never read, only written.',
					'identifier' => 'property.onlyWritten',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Command/CommandHelper.php',
				],
				[
					'rawMessage' => 'Call to static method escape() of internal class Nette\DI\Helpers from outside its root namespace Nette.',
					'identifier' => 'staticMethod.internalClass',
					'count' => 4,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Command/ErrorFormatter/BaselineNeonErrorFormatter.php',
				],
				[
					'rawMessage' => 'Call to static method escape() of internal class Nette\DI\Helpers from outside its root namespace Nette.',
					'identifier' => 'staticMethod.internalClass',
					'count' => 5,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Command/ErrorFormatter/BaselinePhpErrorFormatter.php',
				],
				[
					'rawMessage' => 'Parameter #1 $headers (array<string>) of method PHPStan\Command\ErrorsConsoleStyle::table() should be contravariant with parameter $headers (array) of method Symfony\Component\Console\Style\StyleInterface::table()',
					'identifier' => 'method.childParameterType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Command/ErrorsConsoleStyle.php',
				],
				[
					'rawMessage' => 'Parameter #1 $headers (array<string>) of method PHPStan\Command\ErrorsConsoleStyle::table() should be contravariant with parameter $headers (array) of method Symfony\Component\Console\Style\SymfonyStyle::table()',
					'identifier' => 'method.childParameterType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Command/ErrorsConsoleStyle.php',
				],
				[
					'rawMessage' => 'Parameter #2 $rows (array<array<string>>) of method PHPStan\Command\ErrorsConsoleStyle::table() should be contravariant with parameter $rows (array) of method Symfony\Component\Console\Style\StyleInterface::table()',
					'identifier' => 'method.childParameterType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Command/ErrorsConsoleStyle.php',
				],
				[
					'rawMessage' => 'Parameter #2 $rows (array<array<string>>) of method PHPStan\Command\ErrorsConsoleStyle::table() should be contravariant with parameter $rows (array) of method Symfony\Component\Console\Style\SymfonyStyle::table()',
					'identifier' => 'method.childParameterType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Command/ErrorsConsoleStyle.php',
				],
				[
					'rawMessage' => 'Call to static method escape() of internal class Nette\DI\Helpers from outside its root namespace Nette.',
					'identifier' => 'staticMethod.internalClass',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/DependencyInjection/AutowiredAttributeServicesExtension.php',
				],
				[
					'rawMessage' => 'Call to static method expand() of internal class Nette\DI\Helpers from outside its root namespace Nette.',
					'identifier' => 'staticMethod.internalClass',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/DependencyInjection/AutowiredAttributeServicesExtension.php',
				],
				[
					'rawMessage' => 'Call to static method expand() of internal class Nette\DI\Helpers from outside its root namespace Nette.',
					'identifier' => 'staticMethod.internalClass',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/DependencyInjection/ContainerFactory.php',
				],
				[
					'rawMessage' => 'Call to static method merge() of internal class Nette\Schema\Helpers from outside its root namespace Nette.',
					'identifier' => 'staticMethod.internalClass',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/DependencyInjection/ContainerFactory.php',
				],
				[
					'rawMessage' => 'Variable method call on Nette\Schema\Elements\AnyOf|Nette\Schema\Elements\Structure|Nette\Schema\Elements\Type.',
					'identifier' => 'method.dynamicName',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/DependencyInjection/ContainerFactory.php',
				],
				[
					'rawMessage' => 'Variable static method call on Nette\Schema\Expect.',
					'identifier' => 'staticMethod.dynamicName',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/DependencyInjection/ContainerFactory.php',
				],
				[
					'rawMessage' => 'Fetching class constant PREVENT_MERGING of deprecated class Nette\DI\Config\Helpers.',
					'identifier' => 'classConstant.deprecatedClass',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/DependencyInjection/NeonAdapter.php',
				],
				[
					'rawMessage' => 'Parameter #1 $path of function dirname expects string, string|false given.',
					'identifier' => 'argument.type',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Diagnose/PHPStanDiagnoseExtension.php',
				],
				[
					'rawMessage' => 'Call to method getContent() of internal class PhpMerge\internal\Line from outside its root namespace PhpMerge.',
					'identifier' => 'method.internalClass',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Fixable/Patcher.php',
				],
				[
					'rawMessage' => 'Call to static method createArray() of internal class PhpMerge\internal\Hunk from outside its root namespace PhpMerge.',
					'identifier' => 'staticMethod.internalClass',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Fixable/Patcher.php',
				],
				[
					'rawMessage' => 'Call to static method createArray() of internal class PhpMerge\internal\Line from outside its root namespace PhpMerge.',
					'identifier' => 'staticMethod.internalClass',
					'count' => 5,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Fixable/Patcher.php',
				],
				[
					'rawMessage' => 'Call to method getTokenCode() of internal class PhpParser\Internal\TokenStream from outside its root namespace PhpParser.',
					'identifier' => 'method.internalClass',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Fixable/PhpPrinterIndentationDetectorVisitor.php',
				],
				[
					'rawMessage' => 'Parameter $origTokens of method PHPStan\Fixable\PhpPrinterIndentationDetectorVisitor::__construct() has typehint with internal class PhpParser\Internal\TokenStream.',
					'identifier' => 'parameter.internalClass',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Fixable/PhpPrinterIndentationDetectorVisitor.php',
				],
				[
					'rawMessage' => 'Property $origTokens references internal class PhpParser\Internal\TokenStream in its type.',
					'identifier' => 'property.internalClass',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Fixable/PhpPrinterIndentationDetectorVisitor.php',
				],
				[
					'rawMessage' => 'Call to function method_exists() with PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode and \'getParamOutTypeTagV…\' will always evaluate to true.',
					'identifier' => 'function.alreadyNarrowedType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/PhpDoc/PhpDocNodeResolver.php',
				],
				[
					'rawMessage' => 'Call to function method_exists() with PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode and \'getSelfOutTypeTagVa…\' will always evaluate to true.',
					'identifier' => 'function.alreadyNarrowedType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/PhpDoc/PhpDocNodeResolver.php',
				],
				[
					'rawMessage' => 'Method PHPStan\PhpDoc\ResolvedPhpDocBlock::getNameScope() should return PHPStan\Analyser\NameScope but returns PHPStan\Analyser\NameScope|null.',
					'identifier' => 'return.type',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/PhpDoc/ResolvedPhpDocBlock.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ArrayType is error-prone and deprecated. Use Type::isArray() or Type::getArrays() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/PhpDoc/TypeNodeResolver.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\CallableType is error-prone and deprecated. Use Type::isCallable() and Type::getCallableParametersAcceptors() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/PhpDoc/TypeNodeResolver.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantArrayType is error-prone and deprecated. Use Type::getConstantArrays() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/PhpDoc/TypeNodeResolver.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/PhpDoc/TypeNodeResolver.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Generic\GenericObjectType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/PhpDoc/TypeNodeResolver.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IterableType is error-prone and deprecated. Use Type::isIterable() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/PhpDoc/TypeNodeResolver.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ObjectType is error-prone and deprecated. Use Type::isObject() or Type::getObjectClassNames() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/PhpDoc/TypeNodeResolver.php',
				],
				[
					'rawMessage' => 'Dead catch - PHPStan\BetterReflection\Identifier\Exception\InvalidIdentifierName is never thrown in the try block.',
					'identifier' => 'catch.neverThrown',
					'count' => 4,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/BetterReflection/BetterReflectionProvider.php',
				],
				[
					'rawMessage' => 'Dead catch - PHPStan\BetterReflection\NodeCompiler\Exception\UnableToCompileNode is never thrown in the try block.',
					'identifier' => 'catch.neverThrown',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/BetterReflection/BetterReflectionProvider.php',
				],
				[
					'rawMessage' => 'Creating new ReflectionClass is a runtime reflection concept that might not work in PHPStan because it uses fully static reflection engine. Use objects retrieved from ReflectionProvider instead.',
					'identifier' => 'phpstanApi.runtimeReflection',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/BetterReflection/SourceLocator/AutoloadSourceLocator.php',
				],
				[
					'rawMessage' => 'Creating new ReflectionFunction is a runtime reflection concept that might not work in PHPStan because it uses fully static reflection engine. Use objects retrieved from ReflectionProvider instead.',
					'identifier' => 'phpstanApi.runtimeReflection',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/BetterReflection/SourceLocator/AutoloadSourceLocator.php',
				],
				[
					'rawMessage' => 'Parameter #2 $node of method PHPStan\BetterReflection\SourceLocator\Ast\Strategy\NodeToReflection::__invoke() expects PhpParser\Node\Expr\ArrowFunction|PhpParser\Node\Expr\Closure|PhpParser\Node\Expr\FuncCall|PhpParser\Node\Stmt\Class_|PhpParser\Node\Stmt\Const_|PhpParser\Node\Stmt\Enum_|PhpParser\Node\Stmt\Function_|PhpParser\Node\Stmt\Interface_|PhpParser\Node\Stmt\Trait_, PhpParser\Node\Stmt\ClassLike given.',
					'identifier' => 'argument.type',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/BetterReflection/SourceLocator/AutoloadSourceLocator.php',
				],
				[
					'rawMessage' => 'Method PHPStan\Reflection\BetterReflection\SourceLocator\FileReadTrapStreamWrapper::invokeWithRealFileStreamWrapper() has parameter $cb with no signature specified for callable.',
					'identifier' => 'missingType.callable',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/BetterReflection/SourceLocator/FileReadTrapStreamWrapper.php',
				],
				[
					'rawMessage' => 'Parameter #2 $node of method PHPStan\BetterReflection\SourceLocator\Ast\Strategy\NodeToReflection::__invoke() expects PhpParser\Node\Expr\ArrowFunction|PhpParser\Node\Expr\Closure|PhpParser\Node\Expr\FuncCall|PhpParser\Node\Stmt\Class_|PhpParser\Node\Stmt\Const_|PhpParser\Node\Stmt\Enum_|PhpParser\Node\Stmt\Function_|PhpParser\Node\Stmt\Interface_|PhpParser\Node\Stmt\Trait_, PhpParser\Node\Expr\FuncCall|PhpParser\Node\Stmt\ClassLike|PhpParser\Node\Stmt\Const_|PhpParser\Node\Stmt\Function_ given.',
					'identifier' => 'argument.type',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/BetterReflection/SourceLocator/OptimizedDirectorySourceLocator.php',
				],
				[
					'rawMessage' => 'Parameter #2 $node of method PHPStan\BetterReflection\SourceLocator\Ast\Strategy\NodeToReflection::__invoke() expects PhpParser\Node\Expr\ArrowFunction|PhpParser\Node\Expr\Closure|PhpParser\Node\Expr\FuncCall|PhpParser\Node\Stmt\Class_|PhpParser\Node\Stmt\Const_|PhpParser\Node\Stmt\Enum_|PhpParser\Node\Stmt\Function_|PhpParser\Node\Stmt\Interface_|PhpParser\Node\Stmt\Trait_, PhpParser\Node\Stmt\ClassLike given.',
					'identifier' => 'argument.type',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/BetterReflection/SourceLocator/OptimizedSingleFileSourceLocator.php',
				],
				[
					'rawMessage' => 'Creating new ReflectionClass is a runtime reflection concept that might not work in PHPStan because it uses fully static reflection engine. Use objects retrieved from ReflectionProvider instead.',
					'identifier' => 'phpstanApi.runtimeReflection',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/BetterReflection/SourceLocator/ReflectionClassSourceLocator.php',
				],
				[
					'rawMessage' => 'Creating new ReflectionClass is a runtime reflection concept that might not work in PHPStan because it uses fully static reflection engine. Use objects retrieved from ReflectionProvider instead.',
					'identifier' => 'phpstanApi.runtimeReflection',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/BetterReflection/SourceLocator/RewriteClassAliasSourceLocator.php',
				],
				[
					'rawMessage' => 'Creating new ReflectionClass is a runtime reflection concept that might not work in PHPStan because it uses fully static reflection engine. Use objects retrieved from ReflectionProvider instead.',
					'identifier' => 'phpstanApi.runtimeReflection',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/BetterReflection/SourceLocator/SkipClassAliasSourceLocator.php',
				],
				[
					'rawMessage' => "Call to deprecated method isSubclassOf() of class PHPStan\\Reflection\\ClassReflection:\nUse isSubclassOfClass instead.",
					'identifier' => 'method.deprecated',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/ClassReflection.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Generic\GenericObjectType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/ClassReflection.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ObjectType is error-prone and deprecated. Use Type::isObject() or Type::getObjectClassNames() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/ClassReflection.php',
				],
				[
					'rawMessage' => 'Binary operation "&" between bool|float|int|string|null and bool|float|int|string|null results in an error.',
					'identifier' => 'binaryOp.invalid',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/InitializerExprTypeResolver.php',
				],
				[
					'rawMessage' => 'Binary operation "*" between bool|float|int|string|null and bool|float|int|string|null results in an error.',
					'identifier' => 'binaryOp.invalid',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/InitializerExprTypeResolver.php',
				],
				[
					'rawMessage' => 'Binary operation "+" between bool|float|int|string|null and bool|float|int|string|null results in an error.',
					'identifier' => 'binaryOp.invalid',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/InitializerExprTypeResolver.php',
				],
				[
					'rawMessage' => 'Binary operation "-" between bool|float|int|string|null and bool|float|int|string|null results in an error.',
					'identifier' => 'binaryOp.invalid',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/InitializerExprTypeResolver.php',
				],
				[
					'rawMessage' => 'Binary operation "^" between bool|float|int|string|null and bool|float|int|string|null results in an error.',
					'identifier' => 'binaryOp.invalid',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/InitializerExprTypeResolver.php',
				],
				[
					'rawMessage' => 'Binary operation "|" between bool|float|int|string|null and bool|float|int|string|null results in an error.',
					'identifier' => 'binaryOp.invalid',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/InitializerExprTypeResolver.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ConstantScalarType is error-prone and deprecated. Use Type::isConstantScalarValue() or Type::getConstantScalarTypes() or Type::getConstantScalarValues() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 18,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/InitializerExprTypeResolver.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantArrayType is error-prone and deprecated. Use Type::getConstantArrays() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 4,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/InitializerExprTypeResolver.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantBooleanType is error-prone and deprecated. Use Type::isTrue() or Type::isFalse() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/InitializerExprTypeResolver.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 6,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/InitializerExprTypeResolver.php',
				],
				[
					'rawMessage' => 'PHPDoc tag @var with type float|int is not subtype of native type int.',
					'identifier' => 'varTag.nativeType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/InitializerExprTypeResolver.php',
				],
				[
					'rawMessage' => 'PHPDoc tag @var with type float|int is not subtype of type int.',
					'identifier' => 'varTag.type',
					'count' => 4,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/InitializerExprTypeResolver.php',
				],
				[
					'rawMessage' => 'PHPDoc tag @var with type float|int|null is not subtype of type int|null.',
					'identifier' => 'varTag.type',
					'count' => 6,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/InitializerExprTypeResolver.php',
				],
				[
					'rawMessage' => 'Creating new PHPStan\Php8StubsMap is not covered by backward compatibility promise. The class might change in a minor PHPStan version.',
					'identifier' => 'phpstanApi.constructor',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/SignatureMap/Php8SignatureMapProvider.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantBooleanType is error-prone and deprecated. Use Type::isTrue() or Type::isFalse() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/Classes/ImpossibleInstanceOfRule.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ObjectType is error-prone and deprecated. Use Type::isObject() or Type::getObjectClassNames() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/Classes/RequireImplementsRule.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantBooleanType is error-prone and deprecated. Use Type::isTrue() or Type::isFalse() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 6,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/Comparison/BooleanAndConstantConditionRule.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantBooleanType is error-prone and deprecated. Use Type::isTrue() or Type::isFalse() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/Comparison/BooleanNotConstantConditionRule.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantBooleanType is error-prone and deprecated. Use Type::isTrue() or Type::isFalse() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 6,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/Comparison/BooleanOrConstantConditionRule.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantBooleanType is error-prone and deprecated. Use Type::isTrue() or Type::isFalse() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/Comparison/DoWhileLoopConstantConditionRule.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantBooleanType is error-prone and deprecated. Use Type::isTrue() or Type::isFalse() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/Comparison/ElseIfConstantConditionRule.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantBooleanType is error-prone and deprecated. Use Type::isTrue() or Type::isFalse() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/Comparison/IfConstantConditionRule.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantArrayType is error-prone and deprecated. Use Type::getConstantArrays() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/Comparison/ImpossibleCheckTypeHelper.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantBooleanType is error-prone and deprecated. Use Type::isTrue() or Type::isFalse() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/Comparison/ImpossibleCheckTypeHelper.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/Comparison/ImpossibleCheckTypeHelper.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\TypeWithClassName is error-prone and deprecated. Use Type::getObjectClassNames() or Type::getObjectClassReflections() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/Comparison/ImpossibleCheckTypeHelper.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantBooleanType is error-prone and deprecated. Use Type::isTrue() or Type::isFalse() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 4,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/Comparison/LogicalXorConstantConditionRule.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantBooleanType is error-prone and deprecated. Use Type::isTrue() or Type::isFalse() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 4,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/Comparison/MatchExpressionRule.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantBooleanType is error-prone and deprecated. Use Type::isTrue() or Type::isFalse() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/Comparison/NumberComparisonOperatorsConstantConditionRule.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantBooleanType is error-prone and deprecated. Use Type::isTrue() or Type::isFalse() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/Comparison/StrictComparisonOfDifferentTypesRule.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantBooleanType is error-prone and deprecated. Use Type::isTrue() or Type::isFalse() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/Comparison/TernaryOperatorConstantConditionRule.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantBooleanType is error-prone and deprecated. Use Type::isTrue() or Type::isFalse() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/Comparison/WhileLoopAlwaysFalseConditionRule.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantBooleanType is error-prone and deprecated. Use Type::isTrue() or Type::isFalse() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/Comparison/WhileLoopAlwaysTrueConditionRule.php',
				],
				[
					'rawMessage' => 'Function class_implements() is a runtime reflection concept that might not work in PHPStan because it uses fully static reflection engine. Use objects retrieved from ReflectionProvider instead.',
					'identifier' => 'phpstanApi.runtimeReflection',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/DirectRegistry.php',
				],
				[
					'rawMessage' => 'Function class_parents() is a runtime reflection concept that might not work in PHPStan because it uses fully static reflection engine. Use objects retrieved from ReflectionProvider instead.',
					'identifier' => 'phpstanApi.runtimeReflection',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/DirectRegistry.php',
				],
				[
					'rawMessage' => 'Method PHPStan\Rules\DirectRegistry::__construct() has parameter $rules with generic interface PHPStan\Rules\Rule but does not specify its types: TNodeType',
					'identifier' => 'missingType.generics',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/DirectRegistry.php',
				],
				[
					'rawMessage' => 'Property PHPStan\Rules\DirectRegistry::$cache with generic interface PHPStan\Rules\Rule does not specify its types: TNodeType',
					'identifier' => 'missingType.generics',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/DirectRegistry.php',
				],
				[
					'rawMessage' => 'Property PHPStan\Rules\DirectRegistry::$rules with generic interface PHPStan\Rules\Rule does not specify its types: TNodeType',
					'identifier' => 'missingType.generics',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/DirectRegistry.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Generic\GenericObjectType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/Generics/GenericAncestorsCheck.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/Generics/TemplateTypeCheck.php',
				],
				[
					'rawMessage' => 'Function class_implements() is a runtime reflection concept that might not work in PHPStan because it uses fully static reflection engine. Use objects retrieved from ReflectionProvider instead.',
					'identifier' => 'phpstanApi.runtimeReflection',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/LazyRegistry.php',
				],
				[
					'rawMessage' => 'Function class_parents() is a runtime reflection concept that might not work in PHPStan because it uses fully static reflection engine. Use objects retrieved from ReflectionProvider instead.',
					'identifier' => 'phpstanApi.runtimeReflection',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/LazyRegistry.php',
				],
				[
					'rawMessage' => 'Method PHPStan\Rules\LazyRegistry::getRulesFromContainer() return type with generic interface PHPStan\Rules\Rule does not specify its types: TNodeType',
					'identifier' => 'missingType.generics',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/LazyRegistry.php',
				],
				[
					'rawMessage' => 'Property PHPStan\Rules\LazyRegistry::$cache with generic interface PHPStan\Rules\Rule does not specify its types: TNodeType',
					'identifier' => 'missingType.generics',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/LazyRegistry.php',
				],
				[
					'rawMessage' => 'Property PHPStan\Rules\LazyRegistry::$rules with generic interface PHPStan\Rules\Rule does not specify its types: TNodeType',
					'identifier' => 'missingType.generics',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/LazyRegistry.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ArrayType is error-prone and deprecated. Use Type::isArray() or Type::getArrays() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/Methods/MethodParameterComparisonHelper.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantArrayType is error-prone and deprecated. Use Type::getConstantArrays() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/Methods/MethodParameterComparisonHelper.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IterableType is error-prone and deprecated. Use Type::isIterable() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/Methods/MethodParameterComparisonHelper.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Generic\GenericClassStringType is error-prone and deprecated. Use Type::isClassStringType() and Type::getClassStringObjectType() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/Methods/StaticMethodCallCheck.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Generic\GenericObjectType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/PhpDoc/VarTagTypeRuleHelper.php',
				],
				[
					'rawMessage' => 'Access to an undefined property T of PHPStan\Rules\RuleError::$tip.',
					'identifier' => 'property.notFound',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/RuleErrorBuilder.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/RuleLevelHelper.php',
				],
				[
					'rawMessage' => 'Call to function method_exists() with \'PHPUnit\\\Framework\\\TestCase\' and \'assertFileDoesNotEx…\' will always evaluate to true.',
					'identifier' => 'function.alreadyNarrowedType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Testing/LevelsTestCase.php',
				],
				[
					'rawMessage' => 'Catching internal class PHPUnit\Framework\AssertionFailedError.',
					'identifier' => 'catch.internalClass',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Testing/LevelsTestCase.php',
				],
				[
					'rawMessage' => 'Return type of method PHPStan\Testing\LevelsTestCase::compareFiles() has typehint with internal class PHPUnit\Framework\AssertionFailedError.',
					'identifier' => 'return.internalClass',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Testing/LevelsTestCase.php',
				],
				[
					'rawMessage' => 'Anonymous function has an unused use $container.',
					'identifier' => 'closure.unusedUse',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Testing/PHPStanTestCase.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ConstantScalarType is error-prone and deprecated. Use Type::isConstantScalarValue() or Type::getConstantScalarTypes() or Type::getConstantScalarValues() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Testing/TypeInferenceTestCase.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Accessory/AccessoryArrayListType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Accessory/AccessoryLiteralStringType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Accessory/AccessoryLowercaseStringType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Accessory/AccessoryNonEmptyStringType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Accessory/AccessoryNonEmptyStringType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Accessory/AccessoryNonFalsyStringType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Accessory/AccessoryNumericStringType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Accessory/AccessoryNumericStringType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Accessory/AccessoryUppercaseStringType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Accessory/HasMethodType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Accessory/HasOffsetType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Accessory/HasOffsetType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ConstantScalarType is error-prone and deprecated. Use Type::isConstantScalarValue() or Type::getConstantScalarTypes() or Type::getConstantScalarValues() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Accessory/HasOffsetValueType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Accessory/HasOffsetValueType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Accessory/HasOffsetValueType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Accessory/HasPropertyType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Accessory/NonEmptyArrayType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Accessory/OversizedArrayType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ArrayType is error-prone and deprecated. Use Type::isArray() or Type::getArrays() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/ArrayType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantArrayType is error-prone and deprecated. Use Type::getConstantArrays() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/ArrayType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/ArrayType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/ArrayType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\BooleanType is error-prone and deprecated. Use Type::isBoolean() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/BooleanType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantBooleanType is error-prone and deprecated. Use Type::isTrue() or Type::isFalse() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/BooleanType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\CallableType is error-prone and deprecated. Use Type::isCallable() and Type::getCallableParametersAcceptors() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 4,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/CallableType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/CallableType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/ClosureType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ArrayType is error-prone and deprecated. Use Type::isArray() or Type::getArrays() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Constant/ConstantArrayType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ConstantScalarType is error-prone and deprecated. Use Type::isConstantScalarValue() or Type::getConstantScalarTypes() or Type::getConstantScalarValues() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Constant/ConstantArrayType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantArrayType is error-prone and deprecated. Use Type::getConstantArrays() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 6,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Constant/ConstantArrayType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Constant/ConstantArrayType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Constant/ConstantArrayType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Constant/ConstantArrayTypeBuilder.php',
				],
				[
					'rawMessage' => 'PHPDoc tag @var assumes the expression with type PHPStan\Type\Type is always PHPStan\Type\Constant\ConstantIntegerType|PHPStan\Type\Constant\ConstantStringType but it\'s error-prone and dangerous.',
					'identifier' => 'phpstanApi.varTagAssumption',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Constant/ConstantArrayTypeBuilder.php',
				],
				[
					'rawMessage' => 'PHPDoc tag @var with type float|int is not subtype of native type int.',
					'identifier' => 'varTag.nativeType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Constant/ConstantArrayTypeBuilder.php',
				],
				[
					'rawMessage' => 'PHPDoc tag @var with type float|int is not subtype of type int.',
					'identifier' => 'varTag.type',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Constant/ConstantArrayTypeBuilder.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\BooleanType is error-prone and deprecated. Use Type::isBoolean() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Constant/ConstantBooleanType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ConstantScalarType is error-prone and deprecated. Use Type::isConstantScalarValue() or Type::getConstantScalarTypes() or Type::getConstantScalarValues() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 4,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Constant/ConstantBooleanType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantBooleanType is error-prone and deprecated. Use Type::isTrue() or Type::isFalse() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Constant/ConstantBooleanType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ConstantScalarType is error-prone and deprecated. Use Type::isConstantScalarValue() or Type::getConstantScalarTypes() or Type::getConstantScalarValues() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 4,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Constant/ConstantFloatType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\FloatType is error-prone and deprecated. Use Type::isFloat() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Constant/ConstantFloatType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ConstantScalarType is error-prone and deprecated. Use Type::isConstantScalarValue() or Type::getConstantScalarTypes() or Type::getConstantScalarValues() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 4,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Constant/ConstantIntegerType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntegerType is error-prone and deprecated. Use Type::isInteger() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Constant/ConstantIntegerType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ClassStringType is error-prone and deprecated. Use Type::isClassStringType() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Constant/ConstantStringType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ConstantScalarType is error-prone and deprecated. Use Type::isConstantScalarValue() or Type::getConstantScalarTypes() or Type::getConstantScalarValues() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 4,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Constant/ConstantStringType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 4,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Constant/ConstantStringType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Generic\GenericClassStringType is error-prone and deprecated. Use Type::isClassStringType() and Type::getClassStringObjectType() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Constant/ConstantStringType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\StringType is error-prone and deprecated. Use Type::isString() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Constant/ConstantStringType.php',
				],
				[
					'rawMessage' => 'PHPDoc tag @var with type int|string is not subtype of type string.',
					'identifier' => 'varTag.type',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Constant/ConstantStringType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantArrayType is error-prone and deprecated. Use Type::getConstantArrays() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Constant/OversizedArrayBuilder.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Enum\EnumCaseObjectType is error-prone and deprecated. Use Type::getEnumCases() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Enum/EnumCaseObjectType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ConstantScalarType is error-prone and deprecated. Use Type::isConstantScalarValue() or Type::getConstantScalarTypes() or Type::getConstantScalarValues() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/ExponentiateHelper.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Generic\GenericObjectType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/FileTypeMapper.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\FloatType is error-prone and deprecated. Use Type::isFloat() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/FloatType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ClassStringType is error-prone and deprecated. Use Type::isClassStringType() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/GenericClassStringType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 4,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/GenericClassStringType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Generic\GenericClassStringType is error-prone and deprecated. Use Type::isClassStringType() and Type::getClassStringObjectType() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 4,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/GenericClassStringType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/GenericClassStringType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\StringType is error-prone and deprecated. Use Type::isString() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/GenericClassStringType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Generic\GenericObjectType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/GenericObjectType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/GenericObjectType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ObjectType is error-prone and deprecated. Use Type::isObject() or Type::getObjectClassNames() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/GenericObjectType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\TypeWithClassName is error-prone and deprecated. Use Type::getObjectClassNames() or Type::getObjectClassReflections() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/GenericObjectType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ObjectType is error-prone and deprecated. Use Type::isObject() or Type::getObjectClassNames() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/GenericStaticType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\TypeWithClassName is error-prone and deprecated. Use Type::getObjectClassNames() or Type::getObjectClassReflections() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/GenericStaticType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateArrayType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateBenevolentUnionType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateBooleanType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateConstantArrayType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateConstantIntegerType.php',
				],
				[
					'rawMessage' => 'Method PHPStan\Type\Generic\TemplateConstantIntegerType::toPhpDocNode() should return PHPStan\PhpDocParser\Ast\Type\ConstTypeNode but returns PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode.',
					'identifier' => 'return.type',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateConstantIntegerType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateConstantStringType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateFloatType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateGenericObjectType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateIntegerType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateIntersectionType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateIterableType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateKeyOfType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateMixedType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateNullType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateObjectShapeType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateObjectType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateObjectWithoutClassType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateStrictMixedType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateStringType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ArrayType is error-prone and deprecated. Use Type::isArray() or Type::getArrays() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateTypeFactory.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\BooleanType is error-prone and deprecated. Use Type::isBoolean() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateTypeFactory.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantArrayType is error-prone and deprecated. Use Type::getConstantArrays() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateTypeFactory.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateTypeFactory.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\FloatType is error-prone and deprecated. Use Type::isFloat() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateTypeFactory.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Generic\GenericObjectType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateTypeFactory.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntegerType is error-prone and deprecated. Use Type::isInteger() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateTypeFactory.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateTypeFactory.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IterableType is error-prone and deprecated. Use Type::isIterable() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateTypeFactory.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\NullType is error-prone and deprecated. Use Type::isNull() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateTypeFactory.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ObjectShapeType is error-prone and deprecated. Use Type::isObject() and Type::hasProperty() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateTypeFactory.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ObjectType is error-prone and deprecated. Use Type::isObject() or Type::getObjectClassNames() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateTypeFactory.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ObjectWithoutClassType is error-prone and deprecated. Use Type::isObject() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateTypeFactory.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\StringType is error-prone and deprecated. Use Type::isString() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateTypeFactory.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateUnionType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ConstantScalarType is error-prone and deprecated. Use Type::isConstantScalarValue() or Type::getConstantScalarTypes() or Type::getConstantScalarValues() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/IntegerRangeType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntegerType is error-prone and deprecated. Use Type::isInteger() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/IntegerRangeType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/IntegerRangeType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntegerType is error-prone and deprecated. Use Type::isInteger() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/IntegerType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ArrayType is error-prone and deprecated. Use Type::isArray() or Type::getArrays() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 4,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/IntersectionType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\BooleanType is error-prone and deprecated. Use Type::isBoolean() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/IntersectionType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\CallableType is error-prone and deprecated. Use Type::isCallable() and Type::getCallableParametersAcceptors() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/IntersectionType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantArrayType is error-prone and deprecated. Use Type::getConstantArrays() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 4,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/IntersectionType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/IntersectionType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Enum\EnumCaseObjectType is error-prone and deprecated. Use Type::getEnumCases() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/IntersectionType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/IntersectionType.php',
				],
				[
					'rawMessage' => 'Method PHPStan\Type\IntersectionType::getConstantArrays() should return list<PHPStan\Type\Constant\ConstantArrayType> but returns array{PHPStan\Type\Type}.',
					'identifier' => 'return.type',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/IntersectionType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/IterableType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IterableType is error-prone and deprecated. Use Type::isIterable() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/IterableType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ConstantScalarType is error-prone and deprecated. Use Type::isConstantScalarValue() or Type::getConstantScalarTypes() or Type::getConstantScalarValues() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/NullType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\NullType is error-prone and deprecated. Use Type::isNull() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/NullType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/ObjectShapeType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ObjectShapeType is error-prone and deprecated. Use Type::isObject() and Type::hasProperty() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/ObjectShapeType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ObjectWithoutClassType is error-prone and deprecated. Use Type::isObject() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/ObjectShapeType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Generic\GenericObjectType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/ObjectType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ObjectType is error-prone and deprecated. Use Type::isObject() or Type::getObjectClassNames() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/ObjectType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ObjectWithoutClassType is error-prone and deprecated. Use Type::isObject() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/ObjectType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ObjectShapeType is error-prone and deprecated. Use Type::isObject() and Type::hasProperty() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/ObjectWithoutClassType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ObjectWithoutClassType is error-prone and deprecated. Use Type::isObject() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 4,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/ObjectWithoutClassType.php',
				],
				[
					'rawMessage' => 'Creating new ReflectionClass is a runtime reflection concept that might not work in PHPStan because it uses fully static reflection engine. Use objects retrieved from ReflectionProvider instead.',
					'identifier' => 'phpstanApi.runtimeReflection',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/PHPStan/ClassNameUsageLocationCreateIdentifierDynamicReturnTypeExtension.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/ArrayKeyExistsFunctionTypeSpecifyingExtension.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ConstantScalarType is error-prone and deprecated. Use Type::isConstantScalarValue() or Type::getConstantScalarTypes() or Type::getConstantScalarValues() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 16,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/BcMathStringOrNullReturnTypeExtension.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/ClassExistsFunctionTypeSpecifyingExtension.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantArrayType is error-prone and deprecated. Use Type::getConstantArrays() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/CompactFunctionReturnTypeExtension.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/CompactFunctionReturnTypeExtension.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/DefineConstantTypeSpecifyingExtension.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/DefinedConstantTypeSpecifyingExtension.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\TypeWithClassName is error-prone and deprecated. Use Type::getObjectClassNames() or Type::getObjectClassReflections() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/DsMapDynamicReturnTypeExtension.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ConstantScalarType is error-prone and deprecated. Use Type::isConstantScalarValue() or Type::getConstantScalarTypes() or Type::getConstantScalarValues() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/FilterFunctionReturnTypeHelper.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/FilterFunctionReturnTypeHelper.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/FunctionExistsFunctionTypeSpecifyingExtension.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantArrayType is error-prone and deprecated. Use Type::getConstantArrays() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/InArrayFunctionTypeSpecifyingExtension.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/IsAFunctionTypeSpecifyingExtension.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/MbSubstituteCharacterDynamicReturnTypeExtension.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/MethodExistsTypeSpecifyingExtension.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ConstantScalarType is error-prone and deprecated. Use Type::isConstantScalarValue() or Type::getConstantScalarTypes() or Type::getConstantScalarValues() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/MinMaxFunctionReturnTypeExtension.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantArrayType is error-prone and deprecated. Use Type::getConstantArrays() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/MinMaxFunctionReturnTypeExtension.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ConstantScalarType is error-prone and deprecated. Use Type::isConstantScalarValue() or Type::getConstantScalarTypes() or Type::getConstantScalarValues() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/NumberFormatFunctionDynamicReturnTypeExtension.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/NumberFormatFunctionDynamicReturnTypeExtension.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/PropertyExistsTypeSpecifyingExtension.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/RangeFunctionReturnTypeExtension.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/ReflectionMethodConstructorThrowTypeExtension.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Generic\GenericClassStringType is error-prone and deprecated. Use Type::isClassStringType() and Type::getClassStringObjectType() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/ReflectionMethodConstructorThrowTypeExtension.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/SscanfFunctionDynamicReturnTypeExtension.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/StrRepeatFunctionReturnTypeExtension.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ObjectType is error-prone and deprecated. Use Type::isObject() or Type::getObjectClassNames() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/StaticType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ObjectWithoutClassType is error-prone and deprecated. Use Type::isObject() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/StaticType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/StringType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\StringType is error-prone and deprecated. Use Type::isString() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/StringType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ArrayType is error-prone and deprecated. Use Type::isArray() or Type::getArrays() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 5,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/TypeCombinator.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\CallableType is error-prone and deprecated. Use Type::isCallable() and Type::getCallableParametersAcceptors() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/TypeCombinator.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantArrayType is error-prone and deprecated. Use Type::getConstantArrays() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 19,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/TypeCombinator.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/TypeCombinator.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Generic\GenericClassStringType is error-prone and deprecated. Use Type::isClassStringType() and Type::getClassStringObjectType() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/TypeCombinator.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 5,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/TypeCombinator.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IterableType is error-prone and deprecated. Use Type::isIterable() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 8,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/TypeCombinator.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\NullType is error-prone and deprecated. Use Type::isNull() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/TypeCombinator.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ObjectShapeType is error-prone and deprecated. Use Type::isObject() and Type::hasProperty() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/TypeCombinator.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/TypeUtils.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ArrayType is error-prone and deprecated. Use Type::isArray() or Type::getArrays() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/TypehintHelper.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantArrayType is error-prone and deprecated. Use Type::getConstantArrays() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/TypehintHelper.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IterableType is error-prone and deprecated. Use Type::isIterable() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/TypehintHelper.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\CallableType is error-prone and deprecated. Use Type::isCallable() and Type::getCallableParametersAcceptors() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/UnionType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Generic\GenericClassStringType is error-prone and deprecated. Use Type::isClassStringType() and Type::getClassStringObjectType() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/UnionType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntersectionType is error-prone and deprecated.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/UnionType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IterableType is error-prone and deprecated. Use Type::isIterable() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 3,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/UnionType.php',
				],
				[
					'rawMessage' => 'PHPDoc tag @var assumes the expression with type PHPStan\Type\Type is always PHPStan\Type\BooleanType but it\'s error-prone and dangerous.',
					'identifier' => 'phpstanApi.varTagAssumption',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/UnionType.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\CallableType is error-prone and deprecated. Use Type::isCallable() and Type::getCallableParametersAcceptors() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/UnionTypeHelper.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\ConstantScalarType is error-prone and deprecated. Use Type::isConstantScalarValue() or Type::getConstantScalarTypes() or Type::getConstantScalarValues() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 4,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/UnionTypeHelper.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantBooleanType is error-prone and deprecated. Use Type::isTrue() or Type::isFalse() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/UnionTypeHelper.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\Constant\ConstantStringType is error-prone and deprecated. Use Type::getConstantStrings() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/UnionTypeHelper.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\IntegerType is error-prone and deprecated. Use Type::isInteger() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/UnionTypeHelper.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\NullType is error-prone and deprecated. Use Type::isNull() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/UnionTypeHelper.php',
				],
				[
					'rawMessage' => 'Doing instanceof PHPStan\Type\VoidType is error-prone and deprecated. Use Type::isVoid() instead.',
					'identifier' => 'phpstanApi.instanceofType',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/VoidType.php',
				],
				[
					'rawMessage' => 'Class PHPStan\Analyser\AnonymousClassNameRuleTest extends generic class PHPStan\Testing\RuleTestCase but does not specify its types: TRule',
					'identifier' => 'missingType.generics',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Analyser/AnonymousClassNameRuleTest.php',
				],
				[
					'rawMessage' => 'Method PHPStan\Analyser\AnonymousClassNameRuleTest::getRule() return type with generic interface PHPStan\Rules\Rule does not specify its types: TNodeType',
					'identifier' => 'missingType.generics',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Analyser/AnonymousClassNameRuleTest.php',
				],
				[
					'rawMessage' => 'Class PHPStan\Analyser\EvaluationOrderTest extends generic class PHPStan\Testing\RuleTestCase but does not specify its types: TRule',
					'identifier' => 'missingType.generics',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Analyser/EvaluationOrderTest.php',
				],
				[
					'rawMessage' => 'Method PHPStan\Analyser\EvaluationOrderTest::getRule() return type with generic interface PHPStan\Rules\Rule does not specify its types: TNodeType',
					'identifier' => 'missingType.generics',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Analyser/EvaluationOrderTest.php',
				],
				[
					'rawMessage' => 'Constant SOME_CONSTANT_IN_AUTOLOAD_FILE not found.',
					'identifier' => 'constant.notFound',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Command/AnalyseCommandTest.php',
				],
				[
					'rawMessage' => 'Class PHPStan\Node\FileNodeTest extends generic class PHPStan\Testing\RuleTestCase but does not specify its types: TRule',
					'identifier' => 'missingType.generics',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Node/FileNodeTest.php',
				],
				[
					'rawMessage' => 'Method PHPStan\Node\FileNodeTest::getRule() return type with generic interface PHPStan\Rules\Rule does not specify its types: TNodeType',
					'identifier' => 'missingType.generics',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Node/FileNodeTest.php',
				],
				[
					'rawMessage' => 'Access to constant on internal class InternalAnnotations\InternalFoo.',
					'identifier' => 'classConstant.internalClass',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Reflection/Annotations/InternalAnnotationsTest.php',
				],
				[
					'rawMessage' => 'Access to constant on internal interface InternalAnnotations\InternalFooInterface.',
					'identifier' => 'classConstant.internalInterface',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Reflection/Annotations/InternalAnnotationsTest.php',
				],
				[
					'rawMessage' => 'Access to constant on internal trait InternalAnnotations\InternalFooTrait.',
					'identifier' => 'classConstant.internalTrait',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Reflection/Annotations/InternalAnnotationsTest.php',
				],
				[
					'rawMessage' => 'PHPDoc tag @var with type string is not subtype of type class-string.',
					'identifier' => 'varTag.type',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Reflection/BetterReflection/SourceLocator/OptimizedDirectorySourceLocatorTest.php',
				],
				[
					'rawMessage' => 'Creating new PHPStan\Php8StubsMap is not covered by backward compatibility promise. The class might change in a minor PHPStan version.',
					'identifier' => 'phpstanApi.constructor',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Reflection/ReflectionProviderGoldenTest.php',
				],
				[
					'rawMessage' => 'Creating new PHPStan\Php8StubsMap is not covered by backward compatibility promise. The class might change in a minor PHPStan version.',
					'identifier' => 'phpstanApi.constructor',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Reflection/SignatureMap/Php8SignatureMapProviderTest.php',
				],
				[
					'rawMessage' => 'Access to constant on internal class PHPUnit\Framework\AssertionFailedError.',
					'identifier' => 'classConstant.internalClass',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Testing/TypeInferenceTestCaseTest.php',
				],
				[
					'rawMessage' => 'Catching internal class PHPUnit\Framework\AssertionFailedError.',
					'identifier' => 'catch.internalClass',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Testing/TypeInferenceTestCaseTest.php',
				],
				[
					'rawMessage' => 'PHPDoc tag @var assumes the expression with type PHPStan\Type\Generic\TemplateType is always PHPStan\Type\Generic\TemplateMixedType but it\'s error-prone and dangerous.',
					'identifier' => 'phpstanApi.varTagAssumption',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Type/IterableTypeTest.php',
				],
				[
					'message' => '#^Strict comparison using \=\=\= between list\<callable\(string\)\: void\> and false will always evaluate to false\.$#',
					'identifier' => 'identical.alwaysFalse',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/BetterReflection/SourceLocator/AutoloadSourceLocator.php',
				],
				[
					'message' => '#^Call to function method_exists\(\) with ReflectionFunction and \'getClosureCalledCla…\' will always evaluate to true\.$#',
					'identifier' => 'function.alreadyNarrowedType',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/ClosureTypeFactory.php',
				],
				[
					'message' => '#^Strict comparison using \=\=\= between list\<non\-falsy\-string\> and false will always evaluate to false\.$#',
					'identifier' => 'identical.alwaysFalse',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/MbFunctionsReturnTypeExtension.php',
				],
				[
					'message' => '#^Strict comparison using \=\=\= between int\<0, max\> and false will always evaluate to false\.$#',
					'identifier' => 'identical.alwaysFalse',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/MbStrlenFunctionReturnTypeExtension.php',
				],
				[
					'message' => '#^Strict comparison using \=\=\= between list\<non\-falsy\-string\> and false will always evaluate to false\.$#',
					'identifier' => 'identical.alwaysFalse',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/MbStrlenFunctionReturnTypeExtension.php',
				],
				[
					'message' => '#^Strict comparison using \=\=\= between list\<non\-falsy\-string\> and false will always evaluate to false\.$#',
					'identifier' => 'identical.alwaysFalse',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/StrSplitFunctionReturnTypeExtension.php',
				],
				[
					'message' => '#^Class PHPStan\\\Command\\\ErrorsConsoleStyle has an uninitialized property \$progressBar\. Give it default value or assign it in the constructor\.$#',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Command/ErrorsConsoleStyle.php',
				],
				[
					'message' => '#^Class PHPStan\\\Parallel\\\ParallelAnalyser has an uninitialized property \$processPool\. Give it default value or assign it in the constructor\.$#',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Parallel/ParallelAnalyser.php',
				],
				[
					'message' => '#^Class PHPStan\\\Parallel\\\SpawnedProcess has an uninitialized property \$process\. Give it default value or assign it in the constructor\.$#',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Parallel/SpawnedProcess.php',
				],
				[
					'message' => '#^Class PHPStan\\\PhpDoc\\\ResolvedPhpDocBlock has an uninitialized property \$phpDocNodes\. Give it default value or assign it in the constructor\.$#',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/PhpDoc/ResolvedPhpDocBlock.php',
				],
				[
					'message' => '#^Class PHPStan\\\PhpDoc\\\ResolvedPhpDocBlock has an uninitialized property \$phpDocNode\. Give it default value or assign it in the constructor\.$#',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/PhpDoc/ResolvedPhpDocBlock.php',
				],
				[
					'message' => '#^Class PHPStan\\\PhpDoc\\\ResolvedPhpDocBlock has an uninitialized property \$phpDocString\. Give it default value or assign it in the constructor\.$#',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/PhpDoc/ResolvedPhpDocBlock.php',
				],
				[
					'message' => '#^Class PHPStan\\\PhpDoc\\\ResolvedPhpDocBlock has an uninitialized property \$filename\. Give it default value or assign it in the constructor\.$#',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/PhpDoc/ResolvedPhpDocBlock.php',
				],
				[
					'message' => '#^Class PHPStan\\\PhpDoc\\\ResolvedPhpDocBlock has an uninitialized property \$templateTypeMap\. Give it default value or assign it in the constructor\.$#',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/PhpDoc/ResolvedPhpDocBlock.php',
				],
				[
					'message' => '#^Class PHPStan\\\PhpDoc\\\ResolvedPhpDocBlock has an uninitialized property \$templateTags\. Give it default value or assign it in the constructor\.$#',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/PhpDoc/ResolvedPhpDocBlock.php',
				],
				[
					'message' => '#^Class PHPStan\\\PhpDoc\\\ResolvedPhpDocBlock has an uninitialized property \$phpDocNodeResolver\. Give it default value or assign it in the constructor\.$#',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/PhpDoc/ResolvedPhpDocBlock.php',
				],
				[
					'message' => '#^Class PHPStan\\\PhpDoc\\\ResolvedPhpDocBlock has an uninitialized property \$reflectionProvider\. Give it default value or assign it in the constructor\.$#',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/PhpDoc/ResolvedPhpDocBlock.php',
				],
				[
					'message' => '#^Class PHPStan\\\Reflection\\\BetterReflection\\\SourceLocator\\\CachingVisitor has an uninitialized property \$fileName\. Give it default value or assign it in the constructor\.$#',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/BetterReflection/SourceLocator/CachingVisitor.php',
				],
				[
					'message' => '#^Class PHPStan\\\Reflection\\\BetterReflection\\\SourceLocator\\\CachingVisitor has an uninitialized property \$contents\. Give it default value or assign it in the constructor\.$#',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/BetterReflection/SourceLocator/CachingVisitor.php',
				],
				[
					'message' => '#^Class PHPStan\\\Reflection\\\BetterReflection\\\SourceLocator\\\CachingVisitor has an uninitialized property \$classNodes\. Give it default value or assign it in the constructor\.$#',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/BetterReflection/SourceLocator/CachingVisitor.php',
				],
				[
					'message' => '#^Class PHPStan\\\Reflection\\\BetterReflection\\\SourceLocator\\\CachingVisitor has an uninitialized property \$functionNodes\. Give it default value or assign it in the constructor\.$#',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/BetterReflection/SourceLocator/CachingVisitor.php',
				],
				[
					'message' => '#^Class PHPStan\\\Reflection\\\BetterReflection\\\SourceLocator\\\CachingVisitor has an uninitialized property \$constantNodes\. Give it default value or assign it in the constructor\.$#',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/BetterReflection/SourceLocator/CachingVisitor.php',
				],
				'#^Class PHPStan\\\Rules\\\RuleErrors\\\RuleError(?:\d+) has an uninitialized property (?:\$message|\$line|\$identifier|\$tip|\$file|\$metadata|\$originalNode)#',
				'#Extension has an uninitialized property (?:\$typeSpecifier|\$broker)#',
				['message' => '#has an uninitialized property#', 'path' => '/home/runner/work/phpstan-src/phpstan-src/tests'],
				[
					'message' => '#^PHPDoc tag @var with type list\<callable\(string\): void\>\|false is not subtype of native type list\<callable\(string\): void\>\.$#',
					'count' => 2,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Command/CommandHelper.php',
				],
				[
					'message' => '#^Use of constant E_STRICT is deprecated\.$#',
					'identifier' => 'constant.deprecated',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Analyser/FileAnalyser.php',
				],
				[
					'message' => '#^Call to an undefined static method PHPUnit\\\Framework\\\TestCase\:\:assertFileNotExists\(\)\.$#',
					'identifier' => 'staticMethod.notFound',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Testing/LevelsTestCase.php',
				],
				'#^Dynamic call to static method PHPUnit\\\Framework\\\\\S+\(\)\.$#',
				'#should be contravariant with parameter \$node \(PhpParser\\\Node\) of method PHPStan\\\Rules\\\Rule<PhpParser\\\Node>::processNode\(\)$#',
				'#Variable property access on PhpParser\\\Node#',
				[
					'identifier' => 'shipmonk.deadMethod',
					'message' => '#^Unused .*?Factory::create#',
					'reportUnmatched' => false,
				],
				[
					'identifier' => 'shipmonk.deadMethod',
					'message' => '#^Unused PHPStan\\\DependencyInjection\\\BleedingEdgeToggle::isBleedingEdge#',
					'reportUnmatched' => false,
				],
				[
					'identifier' => 'shipmonk.deadMethod',
					'paths' => [
						'/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Tests',
						'/home/runner/work/phpstan-src/phpstan-src/tests/e2e',
					],
					'reportUnmatched' => false,
				],
				[
					'identifier' => 'shipmonk.deadConstant',
					'paths' => ['/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Fixture'],
					'reportUnmatched' => false,
				],
				[
					'identifier' => 'shipmonk.deadEnumCase',
					'paths' => ['/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Fixture'],
					'reportUnmatched' => false,
				],
				[
					'message' => "#^Access to constant on deprecated class DeprecatedAnnotations\\\\DeprecatedFoo\\:\nin 1\\.0\\.0\\.\$#",
					'path' => '/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Reflection/Annotations/DeprecatedAnnotationsTest.php',
				],
				[
					'message' => "#^Access to constant on deprecated class DeprecatedAnnotations\\\\DeprecatedWithMultipleTags\\:\nin Foo 1\\.1\\.0 and will be removed in 1\\.5\\.0, use\n  \\\\Foo\\\\Bar\\\\NotDeprecated instead\\.\$#",
					'path' => '/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Reflection/Annotations/DeprecatedAnnotationsTest.php',
				],
				[
					'message' => '#^Variable property access on T of PHPStan\\\Rules\\\RuleError\.$#',
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/RuleErrorBuilder.php',
				],
				[
					'message' => '#^Parameter \#1 (?:\$argument|\$objectOrClass) of class ReflectionClass constructor expects class\-string\<PHPStan\\\ExtensionInstaller\\\GeneratedConfig\>\|PHPStan\\\ExtensionInstaller\\\GeneratedConfig, string given\.$#',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Command/CommandHelper.php',
				],
				[
					'message' => '#^Parameter \#1 (?:\$argument|\$objectOrClass) of class ReflectionClass constructor expects class\-string\<PHPStan\\\ExtensionInstaller\\\GeneratedConfig\>\|PHPStan\\\ExtensionInstaller\\\GeneratedConfig, string given\.$#',
					'count' => 1,
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Diagnose/PHPStanDiagnoseExtension.php',
				],
				['identifier' => 'ternary.shortNotAllowed'],
				[
					'identifier' => 'shipmonk.deadMethod',
					'path' => '/home/runner/work/phpstan-src/phpstan-src/src/Internal/CombinationsHelper.php',
					'reportUnmatched' => false,
				],
				[
					'rawMessage' => 'Property PHPStan\Command\CommandHelper::$reservedMemory is never read',
					'reportUnmatched' => false,
				],
			],
			'internalErrorsCountLimit' => 50,
			'cache' => [
				'nodesByStringCountMax' => 128,
				'resolvedPhpDocBlockCacheCountMax' => 2048,
				'nameScopeMapMemoryCacheCountMax' => 2048,
			],
			'reportUnmatchedIgnoredErrors' => true,
			'reportIgnoresWithoutComments' => false,
			'typeAliases' => [],
			'universalObjectCratesClasses' => ['stdClass'],
			'stubFiles' => [
				'/home/runner/work/phpstan-src/phpstan-src/stubs/Memcached.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/Redis.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/ReflectionAttribute.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/ReflectionClassConstant.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/ReflectionFunctionAbstract.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/ReflectionMethod.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/ReflectionParameter.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/ReflectionProperty.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/iterable.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/ArrayObject.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/WeakReference.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/ext-ds.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/ImagickPixel.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/PDOStatement.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/date.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/ibm_db2.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/mysqli.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/zip.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/dom.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/spl.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/SplObjectStorage.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/Exception.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/arrayFunctions.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/core.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/typeCheckingFunctions.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/Countable.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/file.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/stream_socket_client.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/stream_socket_server.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/ctype.stub',
				'/home/runner/work/phpstan-src/phpstan-src/vendor/phpstan/phpstan-phpunit/stubs/Assert.stub',
				'/home/runner/work/phpstan-src/phpstan-src/vendor/phpstan/phpstan-phpunit/stubs/AssertionFailedError.stub',
				'/home/runner/work/phpstan-src/phpstan-src/vendor/phpstan/phpstan-phpunit/stubs/ExpectationFailedException.stub',
				'/home/runner/work/phpstan-src/phpstan-src/vendor/phpstan/phpstan-phpunit/stubs/MockBuilder.stub',
				'/home/runner/work/phpstan-src/phpstan-src/vendor/phpstan/phpstan-phpunit/stubs/MockObject.stub',
				'/home/runner/work/phpstan-src/phpstan-src/vendor/phpstan/phpstan-phpunit/stubs/Stub.stub',
				'/home/runner/work/phpstan-src/phpstan-src/vendor/phpstan/phpstan-phpunit/stubs/TestCase.stub',
				'/home/runner/work/phpstan-src/phpstan-src/build/stubs/ReactChildProcess.stub',
				'/home/runner/work/phpstan-src/phpstan-src/build/stubs/ReactStreams.stub',
				'/home/runner/work/phpstan-src/phpstan-src/build/stubs/NetteDIContainer.stub',
				'/home/runner/work/phpstan-src/phpstan-src/build/stubs/PhpParserName.stub',
				'/home/runner/work/phpstan-src/phpstan-src/build/stubs/PhpParserNode.stub',
				'/home/runner/work/phpstan-src/phpstan-src/build/stubs/PhpParserExpr.stub',
				'/home/runner/work/phpstan-src/phpstan-src/build/stubs/PhpParserStmt.stub',
				'/home/runner/work/phpstan-src/phpstan-src/build/stubs/Identifier.stub',
			],
			'earlyTerminatingMethodCalls' => ['PHPUnit\Framework\Assert' => ['fail', 'markTestIncomplete', 'markTestSkipped']],
			'earlyTerminatingFunctionCalls' => [],
			'resultCachePath' => '/home/runner/work/phpstan-src/phpstan-src/tmp/resultCache.php',
			'resultCacheSkipIfOlderThanDays' => 7,
			'resultCacheChecksProjectExtensionFilesDependencies' => false,
			'dynamicConstantNames' => [
				'ICONV_IMPL',
				'LIBXML_VERSION',
				'LIBXML_DOTTED_VERSION',
				'Memcached::HAVE_ENCODING',
				'Memcached::HAVE_IGBINARY',
				'Memcached::HAVE_JSON',
				'Memcached::HAVE_MSGPACK',
				'Memcached::HAVE_SASL',
				'Memcached::HAVE_SESSION',
				'PHP_VERSION',
				'PHP_MAJOR_VERSION',
				'PHP_MINOR_VERSION',
				'PHP_RELEASE_VERSION',
				'PHP_VERSION_ID',
				'PHP_EXTRA_VERSION',
				'PHP_WINDOWS_VERSION_MAJOR',
				'PHP_WINDOWS_VERSION_MINOR',
				'PHP_WINDOWS_VERSION_BUILD',
				'PHP_ZTS',
				'PHP_DEBUG',
				'PHP_MAXPATHLEN',
				'PHP_OS',
				'PHP_OS_FAMILY',
				'PHP_SAPI',
				'PHP_EOL',
				'PHP_INT_MAX',
				'PHP_INT_MIN',
				'PHP_INT_SIZE',
				'PHP_FLOAT_DIG',
				'PHP_FLOAT_EPSILON',
				'PHP_FLOAT_MIN',
				'PHP_FLOAT_MAX',
				'DEFAULT_INCLUDE_PATH',
				'PEAR_INSTALL_DIR',
				'PEAR_EXTENSION_DIR',
				'PHP_EXTENSION_DIR',
				'PHP_PREFIX',
				'PHP_BINDIR',
				'PHP_BINARY',
				'PHP_MANDIR',
				'PHP_LIBDIR',
				'PHP_DATADIR',
				'PHP_SYSCONFDIR',
				'PHP_LOCALSTATEDIR',
				'PHP_CONFIG_FILE_PATH',
				'PHP_CONFIG_FILE_SCAN_DIR',
				'PHP_SHLIB_SUFFIX',
				'PHP_FD_SETSIZE',
				'OPENSSL_VERSION_NUMBER',
				'ZEND_DEBUG_BUILD',
				'ZEND_THREAD_SAFE',
				'E_ALL',
			],
			'customRulesetUsed' => false,
			'editorUrl' => null,
			'editorUrlTitle' => null,
			'errorFormat' => null,
			'sourceLocatorPlaygroundMode' => false,
			'__validate' => false,
			'parametersNotInvalidatingCache' => [
				['parameters', 'editorUrl'],
				['parameters', 'editorUrlTitle'],
				['parameters', 'errorFormat'],
				['parameters', 'ignoreErrors'],
				['parameters', 'reportUnmatchedIgnoredErrors'],
				['parameters', 'tipsOfTheDay'],
				['parameters', 'parallel'],
				['parameters', 'internalErrorsCountLimit'],
				['parameters', 'cache'],
				['parameters', 'memoryLimitFile'],
				['parameters', 'pro'],
				'parametersSchema',
				'parameters.shipmonkDeadCode.debug.usagesOf',
				'parameters.shipmonkDeadCode.reportTransitivelyDeadMethodAsSeparateError',
			],
			'methodsThrowingExceptions' => [
				'Nette\Application\UI\Presenter' => [
					'redirectUrl' => 'Nette\Application\AbortException',
					'sendJson' => 'Nette\Application\AbortException',
					'sendResponse' => 'Nette\Application\AbortException',
					'terminate' => 'Nette\Application\AbortException',
					'forward' => 'Nette\Application\AbortException',
				],
				'Nette\Application\UI\Component' => [
					'redirect' => 'Nette\Application\AbortException',
					'redirectPermanent' => 'Nette\Application\AbortException',
					'error' => 'Nette\Application\BadRequestException',
				],
			],
			'phpunit' => ['convertUnionToIntersectionType' => true, 'reportMissingDataProviderReturnType' => false],
			'strictRules' => [
				'allRules' => true,
				'disallowedLooseComparison' => true,
				'booleansInConditions' => true,
				'booleansInLoopConditions' => [true, true],
				'uselessCast' => true,
				'requireParentConstructorCall' => true,
				'disallowedBacktick' => true,
				'disallowedEmpty' => true,
				'disallowedImplicitArrayCreation' => true,
				'disallowedShortTernary' => true,
				'overwriteVariablesWithLoop' => true,
				'closureUsesThis' => true,
				'matchingInheritedMethodNames' => true,
				'numericOperandsInArithmeticOperators' => true,
				'strictFunctionCalls' => true,
				'dynamicCallOnStaticMethod' => true,
				'switchConditionsMatchingType' => true,
				'noVariableVariables' => true,
				'strictArrayFilter' => true,
				'illegalConstructorMethodCall' => true,
			],
			'shipmonkDeadCode' => [
				'trackMixedAccess' => null,
				'reportTransitivelyDeadMethodAsSeparateError' => false,
				'cache' => ['offloadCollectorData' => true],
				'detect' => [
					'deadMethods' => true,
					'deadConstants' => true,
					'deadEnumCases' => true,
					'deadProperties' => ['neverRead' => true, 'neverWritten' => true],
				],
				'usageProviders' => [
					'apiPhpDoc' => ['enabled' => true],
					'enum' => ['enabled' => true],
					'vendor' => ['enabled' => true],
					'builtin' => ['enabled' => true],
					'reflection' => ['enabled' => true],
					'phpstan' => ['enabled' => true],
					'phpunit' => ['enabled' => null],
					'phpbench' => ['enabled' => null],
					'behat' => ['enabled' => null],
					'symfony' => ['enabled' => null, 'configDir' => null, 'containerXmlPaths' => []],
					'twig' => ['enabled' => null],
					'doctrine' => ['enabled' => null],
					'eloquent' => ['enabled' => null],
					'laravel' => ['enabled' => null],
					'nette' => ['enabled' => null],
					'netteTester' => ['enabled' => null],
					'streamWrapper' => ['enabled' => true],
				],
				'usageExcluders' => [
					'tests' => ['enabled' => false, 'devPaths' => null],
					'usageOverMixed' => ['enabled' => false],
				],
				'debug' => ['usagesOf' => []],
				'filterOutUnmatchedInlineIgnoresDuringPartialAnalysis' => [
					'wrappedErrorFormatter' => 'table',
					'identifiers' => [
						'ShipMonk\PHPStan\DeadCode\Rule\DeadCodeRule::IDENTIFIER_METHOD',
						'ShipMonk\PHPStan\DeadCode\Rule\DeadCodeRule::IDENTIFIER_CONSTANT',
						'ShipMonk\PHPStan\DeadCode\Rule\DeadCodeRule::IDENTIFIER_ENUM_CASE',
						'ShipMonk\PHPStan\DeadCode\Rule\DeadCodeRule::IDENTIFIER_PROPERTY_NEVER_READ',
						'ShipMonk\PHPStan\DeadCode\Rule\DeadCodeRule::IDENTIFIER_PROPERTY_NEVER_WRITTEN',
					],
				],
			],
			'tmpDir' => '/home/runner/work/phpstan-src/phpstan-src/tmp',
			'reportStatic' => true,
			'debugMode' => true,
			'productionMode' => false,
			'tempDir' => '/home/runner/work/phpstan-src/phpstan-src/tmp',
			'rootDir' => '/home/runner/work/phpstan-src/phpstan-src',
			'currentWorkingDirectory' => '/home/runner/work/phpstan-src/phpstan-src',
			'cliArgumentsVariablesRegistered' => true,
			'additionalConfigFiles' => [
				'/home/runner/work/phpstan-src/phpstan-src/conf/config.level8.neon',
				'/home/runner/work/phpstan-src/phpstan-src/phpstan.neon.dist',
				'/home/runner/work/phpstan-src/phpstan-src/src/PhpDoc/../../conf/config.stubValidator.neon',
			],
			'allConfigFiles' => [
				'/home/runner/work/phpstan-src/phpstan-src/conf/parametersSchema.neon',
				'/home/runner/work/phpstan-src/phpstan-src/conf/config.level8.neon',
				'/home/runner/work/phpstan-src/phpstan-src/conf/config.level7.neon',
				'/home/runner/work/phpstan-src/phpstan-src/conf/config.level6.neon',
				'/home/runner/work/phpstan-src/phpstan-src/conf/config.level5.neon',
				'/home/runner/work/phpstan-src/phpstan-src/conf/config.level4.neon',
				'/home/runner/work/phpstan-src/phpstan-src/conf/config.level3.neon',
				'/home/runner/work/phpstan-src/phpstan-src/conf/config.level2.neon',
				'/home/runner/work/phpstan-src/phpstan-src/conf/config.level1.neon',
				'/home/runner/work/phpstan-src/phpstan-src/conf/config.level0.neon',
				'/home/runner/work/phpstan-src/phpstan-src/phpstan.neon.dist',
				'/home/runner/work/phpstan-src/phpstan-src/build/phpstan.neon',
				'/home/runner/work/phpstan-src/phpstan-src/vendor/phpstan/phpstan-deprecation-rules/rules.neon',
				'/home/runner/work/phpstan-src/phpstan-src/vendor/phpstan/phpstan-nette/rules.neon',
				'/home/runner/work/phpstan-src/phpstan-src/vendor/phpstan/phpstan-phpunit/extension.neon',
				'/home/runner/work/phpstan-src/phpstan-src/vendor/phpstan/phpstan-phpunit/rules.neon',
				'/home/runner/work/phpstan-src/phpstan-src/vendor/phpstan/phpstan-strict-rules/rules.neon',
				'/home/runner/work/phpstan-src/phpstan-src/conf/bleedingEdge.neon',
				'/home/runner/work/phpstan-src/phpstan-src/phpstan-baseline.neon',
				'/home/runner/work/phpstan-src/phpstan-src/phpstan-baseline.php',
				'/home/runner/work/phpstan-src/phpstan-src/build/ignore-by-php-version.neon.php',
				'/home/runner/work/phpstan-src/phpstan-src/build/baseline-8.0.neon',
				'/home/runner/work/phpstan-src/phpstan-src/build/baseline-8.1.neon',
				'/home/runner/work/phpstan-src/phpstan-src/vendor/shipmonk/dead-code-detector/rules.neon',
				'/home/runner/work/phpstan-src/phpstan-src/build/ignore-gte-php7.4-errors.neon',
				'/home/runner/work/phpstan-src/phpstan-src/build/baseline-7.4.neon',
				'/home/runner/work/phpstan-src/phpstan-src/build/spl-autoload-functions-php-8.neon',
				'/home/runner/work/phpstan-src/phpstan-src/build/deprecated-8.4.neon',
				'/home/runner/work/phpstan-src/phpstan-src/build/new-phpunit.neon',
				'/home/runner/work/phpstan-src/phpstan-src/build/pre-php-85.neon',
				'/home/runner/work/phpstan-src/phpstan-src/build/ignore-by-architecture.neon.php',
				'/home/runner/work/phpstan-src/phpstan-src/conf/config.stubValidator.neon',
			],
			'composerAutoloaderProjectPaths' => ['/home/runner/work/phpstan-src/phpstan-src'],
			'generateBaselineFile' => null,
			'usedLevel' => '8',
			'cliAutoloadFile' => null,
			'env' => [
				'GITHUB_STATE' => '/home/runner/work/_temp/_runner_file_commands/save_state_5a1b6771-49e1-4998-acca-cce28920e7bd',
				'DOTNET_NOLOGO' => '1',
				'CLAUDE_CODE_ENTRYPOINT' => 'sdk-cli',
				'USER' => 'runner',
				'AI_AGENT' => 'claude-code_2-1-144_agent',
				'CI' => 'true',
				'GIT_EDITOR' => 'true',
				'GITHUB_ENV' => '/home/runner/work/_temp/_runner_file_commands/set_env_5a1b6771-49e1-4998-acca-cce28920e7bd',
				'CACHE_RESTORE_KEY' => 'Linux-php-8.4.21-composer-locked-',
				'PIPX_HOME' => '/opt/pipx',
				'GH_TOKEN' => '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',
				'USE_BAZEL_FALLBACK_VERSION' => 'silent:',
				'RUNNER_ENVIRONMENT' => 'github-hosted',
				'JAVA_HOME_8_X64' => '/usr/lib/jvm/temurin-8-jdk-amd64',
				'SHLVL' => '2',
				'HOME' => '/home/runner',
				'RUNNER_TEMP' => '/home/runner/work/_temp',
				'GITHUB_EVENT_PATH' => '/home/runner/work/_temp/_github_workflow/event.json',
				'GITHUB_REPOSITORY_OWNER' => 'phpstan-bot',
				'JAVA_HOME_11_X64' => '/usr/lib/jvm/temurin-11-jdk-amd64',
				'PIPX_BIN_DIR' => '/opt/pipx_bin',
				'COMPOSER_PROCESS_TIMEOUT' => '0',
				'ANDROID_NDK_LATEST_HOME' => '/usr/local/lib/android/sdk/ndk/29.0.14206865',
				'GRADLE_HOME' => '/usr/share/gradle-9.5.1',
				'GITHUB_RETENTION_DAYS' => '90',
				'JAVA_HOME_21_X64' => '/usr/lib/jvm/temurin-21-jdk-amd64',
				'POWERSHELL_DISTRIBUTION_CHANNEL' => 'GitHub-Actions-Linux',
				'GITHUB_HEAD_REF' => '',
				'GITHUB_REPOSITORY_OWNER_ID' => '79867460',
				'AZURE_EXTENSION_DIR' => '/opt/az/azcliextensions',
				'ACTIONS_ORCHESTRATION_ID' => 'e22fa465-98ec-40ad-8617-5bcc74aba40a.fix.__default',
				'MAKEFLAGS' => '',
				'SYSTEMD_EXEC_PID' => '2073',
				'GITHUB_GRAPHQL_URL' => 'https://api.github.com/graphql',
				'NVM_DIR' => '/home/runner/.nvm',
				'JAVA_HOME_25_X64' => '/usr/lib/jvm/temurin-25-jdk-amd64',
				'DOTNET_SKIP_FIRST_TIME_EXPERIENCE' => '1',
				'JAVA_HOME_17_X64' => '/usr/lib/jvm/temurin-17-jdk-amd64',
				'ImageVersion' => '20260513.135.3',
				'RUNNER_OS' => 'Linux',
				'GITHUB_API_URL' => 'https://api.github.com',
				'LOGNAME' => 'runner',
				'SWIFT_PATH' => '/usr/share/swift/usr/bin',
				'GOROOT_1_22_X64' => '/opt/hostedtoolcache/go/1.22.12/x64',
				'GOROOT_1_23_X64' => '/opt/hostedtoolcache/go/1.23.12/x64',
				'CHROMEWEBDRIVER' => '/usr/local/share/chromedriver-linux64',
				'_' => '/usr/bin/make',
				'JOURNAL_STREAM' => '9:14649',
				'GITHUB_WORKFLOW' => 'Claude Fix Issue',
				'MEMORY_PRESSURE_WATCH' => '/sys/fs/cgroup/system.slice/hosted-compute-agent.service/memory.pressure',
				'GOROOT_1_24_X64' => '/opt/hostedtoolcache/go/1.24.13/x64',
				'GITHUB_RUN_ID' => '26104205344',
				'ACTIONS_RUNNER_ACTION_ARCHIVE_CACHE' => '/opt/actionarchivecache',
				'GOROOT_1_25_X64' => '/opt/hostedtoolcache/go/1.25.10/x64',
				'GITHUB_WORKFLOW_SHA' => '94ca5ea0d60ae4968896770550a2caa9a414da24',
				'BOOTSTRAP_HASKELL_NONINTERACTIVE' => '1',
				'GITHUB_REF_TYPE' => 'branch',
				'ImageOS' => 'ubuntu24',
				'GITHUB_BASE_REF' => '',
				'GITHUB_ACTION_REPOSITORY' => '',
				'ENABLE_RUNNER_TRACING' => 'true',
				'GITHUB_WORKFLOW_REF' => 'phpstan-bot/phpstan-src/.github/workflows/claude-fix-issue.yml@refs/heads/2.2.x',
				'COMPOSER_NO_INTERACTION' => '1',
				'PATH' => '/home/runner/.composer/vendor/bin:/snap/bin:/home/runner/.local/bin:/opt/pipx_bin:/home/runner/.cargo/bin:/home/runner/.config/composer/vendor/bin:/usr/local/.ghcup/bin:/home/runner/.dotnet/tools:/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/games:/usr/local/games:/snap/bin',
				'RUNNER_TRACKING_ID' => 'github_d9413001-b8b2-4e0c-ba4f-3ec131e2c696',
				'DOTNET_MULTILEVEL_LOOKUP' => '0',
				'INVOCATION_ID' => 'c4b8479d4487421aa7b63b341c4f1099',
				'RUNNER_TOOL_CACHE' => '/opt/hostedtoolcache',
				'ANT_HOME' => '/usr/share/ant',
				'GITHUB_TRIGGERING_ACTOR' => 'phpstan-bot',
				'GITHUB_RUN_NUMBER' => '337',
				'COREPACK_ENABLE_AUTO_PIN' => '0',
				'RUNNER_ARCH' => 'X64',
				'XDG_RUNTIME_DIR' => '/run/user/1001',
				'AGENT_TOOLSDIRECTORY' => '/opt/hostedtoolcache',
				'GITHUB_ACTION' => '__run_2',
				'MAKELEVEL' => '1',
				'CLAUDE_EFFORT' => 'high',
				'LANG' => 'C.UTF-8',
				'NoDefaultCurrentDirectoryInExePath' => '1',
				'VCPKG_INSTALLATION_ROOT' => '/usr/local/share/vcpkg',
				'RUNNER_NAME' => 'GitHub Actions 1000004308',
				'GITHUB_REF_NAME' => '2.2.x',
				'XDG_CONFIG_HOME' => '/home/runner/.config',
				'CONDA' => '/usr/share/miniconda',
				'GITHUB_REPOSITORY' => 'phpstan-bot/phpstan-src',
				'GITHUB_ACTION_REF' => '',
				'ANDROID_NDK_ROOT' => '/usr/local/lib/android/sdk/ndk/27.3.13750724',
				'DEBIAN_FRONTEND' => 'noninteractive',
				'GITHUB_REPOSITORY_ID' => '1166836639',
				'SHELL' => '/bin/bash',
				'GITHUB_ACTIONS' => 'true',
				'GITHUB_REF_PROTECTED' => 'false',
				'ACCEPT_EULA' => 'Y',
				'CLAUDE_CODE_SESSION_ID' => 'ecd33fec-7819-490a-bbd0-2e9b33ee2684',
				'GITHUB_JOB' => 'fix',
				'GITHUB_WORKSPACE' => '/home/runner/work/phpstan-src/phpstan-src',
				'CLAUDECODE' => '1',
				'GITHUB_SHA' => '94ca5ea0d60ae4968896770550a2caa9a414da24',
				'GITHUB_RUN_ATTEMPT' => '1',
				'GITHUB_REF' => 'refs/heads/2.2.x',
				'ANDROID_SDK_ROOT' => '/usr/local/lib/android/sdk',
				'COMPOSER_NO_AUDIT' => '1',
				'GITHUB_ACTOR' => 'phpstan-bot',
				'JAVA_HOME' => '/usr/lib/jvm/temurin-17-jdk-amd64',
				'PWD' => '/home/runner/work/phpstan-src/phpstan-src',
				'RUNNER_WORKSPACE' => '/home/runner/work/phpstan-src',
				'GITHUB_ACTOR_ID' => '79867460',
				'GITHUB_PATH' => '/home/runner/work/_temp/_runner_file_commands/add_path_5a1b6771-49e1-4998-acca-cce28920e7bd',
				'CLAUDE_CODE_EXECPATH' => '/usr/local/lib/node_modules/@anthropic-ai/claude-code/bin/claude.exe',
				'GHCUP_INSTALL_BASE_PREFIX' => '/usr/local',
				'GITHUB_EVENT_NAME' => 'workflow_dispatch',
				'GITHUB_SERVER_URL' => 'https://github.com',
				'ANDROID_HOME' => '/usr/local/lib/android/sdk',
				'GECKOWEBDRIVER' => '/usr/local/share/gecko_driver',
				'HOMEBREW_CLEANUP_PERIODIC_FULL_DAYS' => '3650',
				'GITHUB_OUTPUT' => '/home/runner/work/_temp/_runner_file_commands/set_output_5a1b6771-49e1-4998-acca-cce28920e7bd',
				'HOMEBREW_NO_AUTO_UPDATE' => '1',
				'EDGEWEBDRIVER' => '/usr/local/share/edge_driver',
				'SGX_AESM_ADDR' => '1',
				'CHROME_BIN' => '/usr/bin/google-chrome',
				'MFLAGS' => '',
				'PSModulePath' => '/root/.local/share/powershell/Modules:/usr/local/share/powershell/Modules:/opt/microsoft/powershell/7/Modules:/usr/share/az_14.6.0',
				'ANDROID_NDK' => '/usr/local/lib/android/sdk/ndk/27.3.13750724',
				'MEMORY_PRESSURE_WRITE' => 'c29tZSAyMDAwMDAgMjAwMDAwMAA=',
				'SELENIUM_JAR_PATH' => '/usr/share/java/selenium-server.jar',
				'ANDROID_NDK_HOME' => '/usr/local/lib/android/sdk/ndk/27.3.13750724',
				'GITHUB_STEP_SUMMARY' => '/home/runner/work/_temp/_runner_file_commands/step_summary_5a1b6771-49e1-4998-acca-cce28920e7bd',
				'LINES' => '50',
				'COLUMNS' => '80',
				'SHELL_VERBOSITY' => '1',
			],
			'allStubFiles' => [
				'/home/runner/work/phpstan-src/phpstan-src/stubs/Memcached.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/Redis.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/ReflectionAttribute.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/ReflectionClassConstant.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/ReflectionFunctionAbstract.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/ReflectionMethod.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/ReflectionParameter.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/ReflectionProperty.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/iterable.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/ArrayObject.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/WeakReference.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/ext-ds.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/ImagickPixel.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/PDOStatement.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/date.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/ibm_db2.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/mysqli.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/zip.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/dom.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/spl.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/SplObjectStorage.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/Exception.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/arrayFunctions.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/core.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/typeCheckingFunctions.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/Countable.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/file.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/stream_socket_client.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/stream_socket_server.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/ctype.stub',
				'/home/runner/work/phpstan-src/phpstan-src/vendor/phpstan/phpstan-phpunit/stubs/Assert.stub',
				'/home/runner/work/phpstan-src/phpstan-src/vendor/phpstan/phpstan-phpunit/stubs/AssertionFailedError.stub',
				'/home/runner/work/phpstan-src/phpstan-src/vendor/phpstan/phpstan-phpunit/stubs/ExpectationFailedException.stub',
				'/home/runner/work/phpstan-src/phpstan-src/vendor/phpstan/phpstan-phpunit/stubs/MockBuilder.stub',
				'/home/runner/work/phpstan-src/phpstan-src/vendor/phpstan/phpstan-phpunit/stubs/MockObject.stub',
				'/home/runner/work/phpstan-src/phpstan-src/vendor/phpstan/phpstan-phpunit/stubs/Stub.stub',
				'/home/runner/work/phpstan-src/phpstan-src/vendor/phpstan/phpstan-phpunit/stubs/TestCase.stub',
				'/home/runner/work/phpstan-src/phpstan-src/build/stubs/ReactChildProcess.stub',
				'/home/runner/work/phpstan-src/phpstan-src/build/stubs/ReactStreams.stub',
				'/home/runner/work/phpstan-src/phpstan-src/build/stubs/NetteDIContainer.stub',
				'/home/runner/work/phpstan-src/phpstan-src/build/stubs/PhpParserName.stub',
				'/home/runner/work/phpstan-src/phpstan-src/build/stubs/PhpParserNode.stub',
				'/home/runner/work/phpstan-src/phpstan-src/build/stubs/PhpParserExpr.stub',
				'/home/runner/work/phpstan-src/phpstan-src/build/stubs/PhpParserStmt.stub',
				'/home/runner/work/phpstan-src/phpstan-src/build/stubs/Identifier.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/bcmath.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/socket_select_php8.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/ReflectionClassWithLazyObjects.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/ReflectionEnumWithLazyObjects.stub',
				'/home/runner/work/phpstan-src/phpstan-src/stubs/json_validate.stub',
			],
		];
	}


	protected function getDynamicParameter($key)
	{
		switch (true) {
			case $key === 'singleReflectionFile': return null;
			case $key === 'singleReflectionInsteadOfFile': return null;
			case $key === 'analysedPaths': return null;
			case $key === 'analysedPathsFromConfig': return null;
			case $key === 'sysGetTempDir': return sys_get_temp_dir();
			case $key === 'pro': return [
			'dnsServers' => ['1.1.1.2'],
			'tmpDir' => ($this->getParameter('sysGetTempDir')) . '/phpstan-fixer',
		];
			default: return parent::getDynamicParameter($key);
		};
	}


	public function getParameters(): array
	{
		array_map(function ($key) { $this->getParameter($key); }, [
			'singleReflectionFile',
			'singleReflectionInsteadOfFile',
			'analysedPaths',
			'analysedPathsFromConfig',
			'sysGetTempDir',
			'pro',
		]);
		return parent::getParameters();
	}
}
