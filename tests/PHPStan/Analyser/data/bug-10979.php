<?php

declare(strict_types=1);

namespace Bug10979;

enum LibraryRelationTypeEnum: int {
	case LanguageTool = 1;
	case Framework = 2;
}

enum FieldMasterEnum: int
{
	case ServerSide = 1;
	case FrontEnd = 2;
	case Database = 3;
}

enum LanguageToolMasterEnum: int
{
	case PHP = 1;
	case Ruby = 2;
	case Java = 3;
	case Python = 4;
	case Go = 5;
	case NodeJS = 6;
	case JavaScript = 7;
	case TypeScript = 8;
	case SQL = 9;
	case MySQL = 10;
	case PostgreSQL = 11;
	case SQLite = 12;
	case OracleDatabase = 13;
	case Db2 = 14;
	case SQLServer = 15;
	case FirebirdSQL = 16;
	case MongoDB = 17;
	case ApacheCassandra = 18;
	case Redis = 19;
	case BigQuery = 20;
	case AmazonDynamoDB = 21;
	case MariaDB = 22;
	case CockroachDB = 23;
	case CPlusPlus = 24;
}

enum FrameworkMasterEnum: int
{
	case Laravel = 1;
	case RubyOnRails = 2;
	case React = 3;
	case Vue = 4;
	case Svelte = 5;
	case TailwindCSS = 6;
	case ReactNative = 7;
}

// ライブラリのenum(そのままDBに反映している)
// NOTE 名前はライブラリだが、パッケージ系も含めていく予定
enum LibraryMasterEnum: int
{
	/**
	 * !! ここではカテゴリごとに整理せずそのまま追加していく(下のgetFieldIdsでカテゴリ別に整理してます) !!
	 * !! 重複はStanで検出できる。変更・追加の際は、各メソッドも含めて全て同様に変更・追加 !!
	 * !! もし削除したい値がある場合は手動でDBからも削除する必要あり !!
	 */
	case Guzzle = 1000001;
	case PHPUnit = 1000002;
	case Monolog = 1000003;
	case PChart = 1000004;
	case PHPStan = 1000005;
	case PHPMailer = 1000006;
	case RespectValidation = 1000007;
	case Stripe = 1000008;
	case Ratchet = 100009;
	case Sentinel = 1000010;
	case LaravelScout = 1000011;
	case LaravelCashier = 1000012;
	case LaravelJetstream = 1000013;
	case LaravelSanctum = 1000014;
	case JQuery = 1000015;
	case D3js = 1000016;
	case Lodash = 1000017;
	case Underscorejs = 1000018;
	case Animejs = 1000019;
	case AnimateOnScroll = 1000020;
	case Videojs = 1000021;
	case Chartjs = 1000022;
	case Cleavejs = 1000023;
	case FullPagejs = 1000024;
	case Leaflet = 1000025;
	case Threejs = 1000026;
	case Screenfulljs = 1000027;
	case Axios = 1000028;
	case TypeORM = 1000029;
	case VueChartjs = 1000030;
	case VeeValidate = 1000031;
	case VueDraggable = 1000032;
	case Vuelidate = 1000033;
	case VueMultiselect = 1000034;
	case Vuex = 1000035;
	case Vuetify = 1000036;
	case ElementUI = 1000037;
	case VueMaterial = 1000038;
	case BootstrapVue = 1000039;
	case SocketIO = 1000040;
	case Matplotlib = 1000041;
	case Seaborn = 1000042;
	case Selenium = 1000043;
	case OpenCV = 1000044;
	case Keras = 1000045;
	case PyTorch = 1000046;
	case NumPy = 1000047;
	case Pandas = 1000048;
	case Plotly = 1000049;
	case TanStackQuery = 1000050;
	case Redux = 1000051;
	case Zod = 1000052;
	case Htmx = 1000053;
	case Cmake = 1000054;
	case Tldraw = 1000055;
	case SLF4J = 1000056;
	case Mockito = 1000057;
	case OpenCSV = 1000058;
	case DBeaver = 1000059;
	case SequelPro = 1000060;
	case SequelAce = 1000061;
	case TablePlus = 1000062;
	case Navicat = 1000063;
	case MySQLWorkbench = 1000064;
	case PHPMyAdmin = 1000065;
	case Devise = 1000066;
	case Capybara = 1000067;
	case ShadcnUi = 1000068;
	case MUI = 1000069;
	case ChakraUI = 1000070;
	case Recoil = 1000071;
	case Jotai = 1000072;
	case Zustand = 1000073;
	case SWR = 1000074;
	case ReactHookForm = 1000075;
	case RadixUI = 1000076;
	case GSAP = 1000077;
	case Swiper = 1000078;
	case EmblaCarousel = 1000079;
	case Husky = 1000080;
	case DrizzleORM = 1000081;
	case MilionJs = 1000082;
	case Biome = 1000083;
	case Prettier = 1000084;
	case ESLint = 1000085;
	case DaisyUI = 1000086;
	case Playwright = 1000087;
	case Pinia = 1000088;
	case SolidJS = 1000089;
	case NextAuth = 1000090;
	case InertiaJS = 1000091;

	/**
	 * フロントで使用できる文字列に変換
	 *
	 * @return string
	 */
	public function toString(): string
	{
		return match ($this) {
			/**
			 * サーバーサイド(言語・ツール)
			 */
			// PHP
			self::Guzzle => 'Guzzle',
			self::PHPUnit => 'PHPUnit',
			self::Monolog => 'Monolog',
			self::PChart => 'pChart',
			self::PHPStan => 'PHPStan',
			self::PHPMailer => 'PHPMailer',
			self::RespectValidation => 'RespectValidation',
			self::Stripe => 'Stripe',
			self::Ratchet => 'Ratchet',
			self::Sentinel => 'Sentinel',
			// Python
			self::Matplotlib => 'Matplotlib',
			self::Seaborn => 'Seaborn',
			self::Selenium => 'Selenium',
			self::OpenCV => 'OpenCV',
			self::Keras => 'Keras',
			self::PyTorch => 'PyTorch',
			self::NumPy => 'NumPy',
			self::Pandas => 'Pandas',
			self::Plotly => 'Plotly',
			// C++
			self::Cmake => 'CMake',
			// Node.js
			self::Playwright => 'Playwright',

			/**
			 * サーバーサイド(フレームワーク)
			 */
			// Laravel
			self::LaravelScout => 'Laravel Scout',
			self::LaravelCashier => 'Laravel Cashier',
			self::LaravelJetstream => 'Laravel Jetstream',
			self::LaravelSanctum => 'Laravel Sanctum',
			// Java
			self::SLF4J => 'SLF4J',
			self::Mockito => 'Mockito',
			self::OpenCSV => 'OpenCSV',
			// Rails
			self::Devise => 'Devise',
			self::Capybara => 'Capybara',

			/**
			 * フロントエンド
			 */
			// JavaScript・TypeScript
			self::JQuery => 'jQuery',
			self::D3js => 'D3.js',
			self::Lodash => 'Lodash',
			self::Underscorejs => 'Underscore.js',
			self::Animejs => 'Anime.js',
			self::AnimateOnScroll => 'Animate On Scroll',
			self::Videojs => 'Video.js',
			self::Chartjs => 'Chart.js',
			self::Cleavejs => 'Cleave.js',
			self::FullPagejs => 'FullPage.js',
			self::Leaflet => 'Leaflet',
			self::Threejs => 'Three.js',
			self::Screenfulljs => 'Screenfull.js',
			self::Axios => 'Axios',
			self::SocketIO => 'Socket.io',
			self::TanStackQuery => 'TanStack Query',
			self::Htmx => 'htmx',
			self::GSAP => 'GSAP',
			self::Swiper => 'Swiper',
			self::EmblaCarousel => 'Embla Carousel',
			self::Husky => 'husky',
			self::MilionJs => 'Milion.js',
			self::Biome => 'Biome',
			self::Prettier => 'Prettier',
			self::ESLint => 'ESLint',
			self::SolidJS => 'SolidJS',
			self::NextAuth => 'NextAuth',
			self::InertiaJS => 'Inertia.js',
			self::DrizzleORM => 'Drizzle ORM', // TS専用
			self::Zod => 'Zod', // TS専用
			self::TypeORM => 'TypeORM', // TS専用
			// Vue
			self::VueChartjs => 'Vue Chart.js',
			self::VeeValidate => 'VeeValidate',
			self::VueDraggable => 'Vue Draggable',
			self::Vuelidate => 'Vuelidate',
			self::VueMultiselect => 'Vue Multiselect',
			self::Vuex => 'Vuex',
			self::Vuetify => 'Vuetify',
			self::ElementUI => 'Element UI',
			self::VueMaterial => 'Vue Material',
			self::BootstrapVue => 'Bootstrap Vue',
			self::Pinia => 'Pinia',
			// React
			self::Redux => 'Redux',
			self::Tldraw => 'tldraw',
			self::ShadcnUi => 'shadcn/ui',
			self::MUI => 'MUI',
			self::ChakraUI => 'Chakra UI',
			self::Recoil => 'Recoil',
			self::Jotai => 'Jotai',
			self::Zustand => 'Zustand',
			self::SWR => 'SWR',
			self::ReactHookForm => 'React Hook Form',
			self::RadixUI => 'Radix UI',
			// Tailwind CSS
			self::DaisyUI => 'DaisyUI',

			/**
			 * インフラ
			 */

			/**
			 * モバイルアプリ
			 */

			/**
			 * データベース
			 */
			self::DBeaver => 'DBeaver',
			self::SequelPro => 'Sequel Pro',
			self::SequelAce => 'Sequel Ace',
			self::TablePlus => 'TablePlus',
			self::Navicat => 'Navicat',
			self::MySQLWorkbench => 'MySQL Workbench',
			self::PHPMyAdmin => 'phpMyAdmin',
		};
	}

	/**
	 * 関連する分野のIDを配列で返す
	 * ※複数の分野に関連する場合は複数のIDを返す
	 *
	 * @return array<int>
	 */
	public function getFieldIds(): array
	{
		return match ($this) {
			/**
			 * 複数に関連するライブラリ
			 */
			self::InertiaJS => [
				FieldMasterEnum::FrontEnd->value,
				FieldMasterEnum::ServerSide->value,
			],

			/**
			 * サーバーサイド
			 */
			self::Guzzle,
			self::PHPUnit,
			self::Monolog,
			self::PChart,
			self::PHPStan,
			self::PHPMailer,
			self::RespectValidation,
			self::Stripe,
			self::Ratchet,
			self::Sentinel,
			self::Matplotlib,
			self::Seaborn,
			self::Selenium,
			self::OpenCV,
			self::Keras,
			self::PyTorch,
			self::NumPy,
			self::Pandas,
			self::Plotly,
			self::Cmake,
			self::LaravelScout,
			self::LaravelCashier,
			self::LaravelJetstream,
			self::LaravelSanctum,
			self::SLF4J,
			self::Mockito,
			self::OpenCSV,
			self::Devise,
			self::Capybara
			=> [
				FieldMasterEnum::ServerSide->value,
			],

			/**
			 * フロントエンド
			 */
			self::JQuery,
			self::D3js,
			self::Lodash,
			self::Underscorejs,
			self::Animejs,
			self::AnimateOnScroll,
			self::Videojs,
			self::Chartjs,
			self::Cleavejs,
			self::FullPagejs,
			self::Leaflet,
			self::Threejs,
			self::Screenfulljs,
			self::Axios,
			self::TypeORM,
			self::VueChartjs,
			self::VeeValidate,
			self::VueDraggable,
			self::Vuelidate,
			self::VueMultiselect,
			self::Vuex,
			self::Vuetify,
			self::ElementUI,
			self::VueMaterial,
			self::BootstrapVue,
			self::SocketIO,
			self::TanStackQuery,
			self::Htmx,
			self::Zod,
			self::Redux,
			self::Tldraw,
			self::ShadcnUi,
			self::MUI,
			self::ChakraUI,
			self::Recoil,
			self::Jotai,
			self::Zustand,
			self::SWR,
			self::ReactHookForm,
			self::RadixUI,
			self::GSAP,
			self::Swiper,
			self::EmblaCarousel,
			self::Husky,
			self::DrizzleORM,
			self::MilionJs,
			self::Biome,
			self::Prettier,
			self::ESLint,
			self::DaisyUI,
			self::Playwright,
			self::Pinia,
			self::SolidJS,
			self::NextAuth
			=> [
				FieldMasterEnum::FrontEnd->value,
			],

			/**
			 * インフラ
			 */

			/**
			 * モバイルアプリ
			 */

			/**
			 * データベース
			 */
			self::DBeaver,
			self::SequelPro,
			self::SequelAce,
			self::TablePlus,
			self::Navicat,
			self::MySQLWorkbench,
			self::PHPMyAdmin
			=> [
				FieldMasterEnum::Database->value,
			],
		};
	}

	/**
	 * 関連する言語・ツール、もしくはフレームワークのIDを配列で返す
	 * ※複数に関連する場合は複数のIDを返す
	 *
	 * @return array<int>
	 */
	public function getMasterIds(): array
	{
		return match ($this) {
			/**
			 * 複数に関連するライブラリ
			 */
			self::Stripe => [
				LanguageToolMasterEnum::PHP->value,
				LanguageToolMasterEnum::Ruby->value,
				LanguageToolMasterEnum::Java->value,
				LanguageToolMasterEnum::Python->value,
				LanguageToolMasterEnum::Go->value,
				LanguageToolMasterEnum::NodeJS->value,
				LanguageToolMasterEnum::JavaScript->value,
				LanguageToolMasterEnum::TypeScript->value,
				FrameworkMasterEnum::ReactNative->value,
			],
			self::PyTorch => [
				LanguageToolMasterEnum::Python->value,
				LanguageToolMasterEnum::CPlusPlus->value,
			],
			self::OpenCV => [
				LanguageToolMasterEnum::Python->value,
				LanguageToolMasterEnum::CPlusPlus->value,
				LanguageToolMasterEnum::Java->value,
			],
			self::InertiaJS => [
				FrameworkMasterEnum::Vue->value,
				FrameworkMasterEnum::React->value,
				FrameworkMasterEnum::Svelte->value,
				FrameworkMasterEnum::Laravel->value,
				FrameworkMasterEnum::RubyOnRails->value,
			],

			/**
			 * サーバーサイド(言語・ツール)
			 */
			// PHP
			self::Guzzle,
			self::PHPUnit,
			self::Monolog,
			self::PChart,
			self::PHPStan,
			self::PHPMailer,
			self::RespectValidation,
			self::Ratchet,
			self::Sentinel => [
				LanguageToolMasterEnum::PHP->value,
			],
			// C++
			self::Cmake => [
				LanguageToolMasterEnum::CPlusPlus->value,
			],
			// Python
			self::Matplotlib,
			self::Seaborn,
			self::Selenium,
			self::Keras,
			self::NumPy,
			self::Pandas,
			self::Plotly => [
				LanguageToolMasterEnum::Python->value,
			],
			// Java
			self::SLF4J,
			self::Mockito,
			self::OpenCSV => [
				LanguageToolMasterEnum::Java->value,
			],
			// Node.js
			self::Playwright => [
				LanguageToolMasterEnum::NodeJS->value,
			],

			/**
			 * サーバーサイド(フレームワーク)
			 */
			// Laravel
			self::LaravelScout,
			self::LaravelCashier,
			self::LaravelJetstream,
			self::LaravelSanctum => [
				FrameworkMasterEnum::Laravel->value,
			],
			// Rails
			self::Devise,
			self::Capybara => [
				FrameworkMasterEnum::RubyOnRails->value,
			],

			/**
			 * フロントエンド
			 */
			// JavaScript・TypeScript
			self::JQuery,
			self::D3js,
			self::Lodash,
			self::Underscorejs,
			self::Animejs,
			self::AnimateOnScroll,
			self::Videojs,
			self::Chartjs,
			self::Cleavejs,
			self::FullPagejs,
			self::Leaflet,
			self::Threejs,
			self::Screenfulljs,
			self::Axios,
			self::SocketIO,
			self::TanStackQuery,
			self::Htmx,
			self::GSAP,
			self::Swiper,
			self::EmblaCarousel,
			self::Husky,
			self::Biome,
			self::Prettier,
			self::ESLint,
			self::SolidJS,
			self::NextAuth
			=> [
				LanguageToolMasterEnum::JavaScript->value,
				LanguageToolMasterEnum::TypeScript->value,
			],
			// TypeScript
			self::Zod,
			self::TypeORM,
			self::DrizzleORM
			=> [
				LanguageToolMasterEnum::TypeScript->value,
			],
			// Vue
			self::VueChartjs,
			self::VeeValidate,
			self::VueDraggable,
			self::Vuelidate,
			self::VueMultiselect,
			self::Vuex,
			self::Vuetify,
			self::ElementUI,
			self::VueMaterial,
			self::BootstrapVue,
			self::Pinia
			=> [
				FrameworkMasterEnum::Vue->value,
			],
			// React
			self::Redux,
			self::Tldraw,
			self::ShadcnUi,
			self::MUI,
			self::ChakraUI,
			self::Recoil,
			self::Jotai,
			self::Zustand,
			self::SWR,
			self::ReactHookForm,
			self::RadixUI,
			self::MilionJs
			=> [
				FrameworkMasterEnum::React->value,
			],
			// Tailwind CSS
			self::DaisyUI => [
				FrameworkMasterEnum::TailwindCSS->value,
			],

			/**
			 * インフラ
			 */

			/**
			 * モバイルアプリ
			 */

			/**
			 * データベース
			 */
			self::DBeaver => [
				LanguageToolMasterEnum::SQL->value,
				LanguageToolMasterEnum::MySQL->value,
				LanguageToolMasterEnum::PostgreSQL->value,
				LanguageToolMasterEnum::SQLite->value,
				LanguageToolMasterEnum::OracleDatabase->value,
				LanguageToolMasterEnum::Db2->value,
				LanguageToolMasterEnum::SQLServer->value,
				LanguageToolMasterEnum::FirebirdSQL->value,
				LanguageToolMasterEnum::MongoDB->value,
				LanguageToolMasterEnum::ApacheCassandra->value,
				LanguageToolMasterEnum::Redis->value,
				LanguageToolMasterEnum::BigQuery->value,
				LanguageToolMasterEnum::AmazonDynamoDB->value,
			],
			self::SequelPro,
			self::SequelAce => [
				LanguageToolMasterEnum::SQL->value,
				LanguageToolMasterEnum::MySQL->value,
			],
			self::TablePlus => [
				LanguageToolMasterEnum::SQL->value,
				LanguageToolMasterEnum::MySQL->value,
				LanguageToolMasterEnum::PostgreSQL->value,
				LanguageToolMasterEnum::SQLite->value,
				LanguageToolMasterEnum::OracleDatabase->value,
				LanguageToolMasterEnum::Redis->value,
				LanguageToolMasterEnum::ApacheCassandra->value,
				LanguageToolMasterEnum::MongoDB->value,
				LanguageToolMasterEnum::MariaDB->value,
				LanguageToolMasterEnum::SQLServer->value,
				LanguageToolMasterEnum::BigQuery->value,
				LanguageToolMasterEnum::CockroachDB->value,
			],
			self::Navicat => [
				LanguageToolMasterEnum::SQL->value,
				LanguageToolMasterEnum::MySQL->value,
				LanguageToolMasterEnum::PostgreSQL->value,
				LanguageToolMasterEnum::SQLite->value,
				LanguageToolMasterEnum::OracleDatabase->value,
				LanguageToolMasterEnum::SQLServer->value,
				LanguageToolMasterEnum::MariaDB->value,
			],
			self::MySQLWorkbench => [
				LanguageToolMasterEnum::SQL->value,
				LanguageToolMasterEnum::MySQL->value,
			],
			self::PHPMyAdmin => [
				LanguageToolMasterEnum::SQL->value,
				LanguageToolMasterEnum::MySQL->value,
				LanguageToolMasterEnum::MariaDB->value,
			],
		};
	}

	/**
	 * `language_tool` or `framework`いずれかのtypeを返す
	 *
	 * @return LibraryRelationTypeEnum
	 */
	public function getType(): LibraryRelationTypeEnum
	{
		return match ($this) {
			/**
			 * 言語・ツール&フレームワークどちらにも関連するライブラリ
			 */

			/**
			 * 言語・ツール
			 */
			self::Guzzle,
			self::PHPUnit,
			self::Monolog,
			self::PChart,
			self::PHPStan,
			self::PHPMailer,
			self::RespectValidation,
			self::Stripe,
			self::Ratchet,
			self::Sentinel,
			self::Matplotlib,
			self::Seaborn,
			self::Selenium,
			self::OpenCV,
			self::Keras,
			self::PyTorch,
			self::NumPy,
			self::Pandas,
			self::Plotly,
			self::Cmake,
			self::SLF4J,
			self::Mockito,
			self::OpenCSV,
			self::JQuery,
			self::D3js,
			self::Lodash,
			self::Underscorejs,
			self::Animejs,
			self::AnimateOnScroll,
			self::Videojs,
			self::Chartjs,
			self::Cleavejs,
			self::FullPagejs,
			self::Leaflet,
			self::Threejs,
			self::Screenfulljs,
			self::Axios,
			self::SocketIO,
			self::Htmx,
			self::TanStackQuery,
			self::Zod,
			self::TypeORM,
			self::DBeaver,
			self::SequelPro,
			self::SequelAce,
			self::TablePlus,
			self::Navicat,
			self::MySQLWorkbench,
			self::PHPMyAdmin,
			self::GSAP,
			self::Swiper,
			self::EmblaCarousel,
			self::Husky,
			self::DrizzleORM,
			self::MilionJs,
			self::Biome,
			self::Prettier,
			self::ESLint,
			self::DaisyUI,
			self::Playwright,
			self::SolidJS,
			self::NextAuth
			=> LibraryRelationTypeEnum::LanguageTool,

			/**
			 * フレームワーク
			 */
			self::LaravelScout,
			self::LaravelCashier,
			self::LaravelJetstream,
			self::LaravelSanctum,
			self::VueChartjs,
			self::VeeValidate,
			self::VueDraggable,
			self::Vuelidate,
			self::VueMultiselect,
			self::Vuex,
			self::Vuetify,
			self::ElementUI,
			self::VueMaterial,
			self::BootstrapVue,
			self::Redux,
			self::Tldraw,
			self::Devise,
			self::Capybara,
			self::ShadcnUi,
			self::MUI,
			self::ChakraUI,
			self::Recoil,
			self::Jotai,
			self::Zustand,
			self::SWR,
			self::ReactHookForm,
			self::RadixUI,
			self::Pinia,
			self::InertiaJS
			=> LibraryRelationTypeEnum::Framework,
		};
	}

	/**
	 * カテゴリIDがライブラリのIDかどうか判定
	 *
	 * @param int $categoryId
	 * @return bool
	 */
	public static function isLibraryCategoryId(int $categoryId): bool
	{
		foreach (self::cases() as $case) {
			if ($categoryId === $case->value) {
				return true;
			}
		}
		return false;
	}
}
