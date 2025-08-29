<?php

use EosUptrade\TICKeos\Bundle\EtsPaymentFacadeClientBundle\Domain\Enum;
use EosUptrade\TICKeos\Bundle\EtsPaymentFacadeClientBundle\Helper\BrowserType\BrowserType;
use EosUptrade\TICKeos\Bundle\EtsPaymentFacadeClientBundle\Helper\BrowserType\BrowserTypeConfiguration;
use EosUptrade\TICKeos\Bundle\OrderProcessBundle\Domain\ProcessStep\ProcessStep;
use EosUptrade\TICKeos\Bundle\OrderProcessBundle\Domain\ProcessSteps;
use EosUptrade\TICKeos\Bundle\TICKeosCoreBundle\Domain\AnonymousCustomerSessionHandler;
use EosUptrade\TICKeos\Bundle\TICKeosCoreBundle\Event\PreProductBuyEvent;
use EosUptrade\TICKeos\Bundle\TICKeosMobileBundle\Domain\Certificate\LegacyCertificateProvider;
use EosUptrade\TICKeos\Bundle\TICKeosMobileBundle\Event\CustomerToKvpEvent;
use EosUptrade\TICKeos\Bundle\TICKeosMobileBundle\Event\V2019_06\FilterBuyRequestEvent;
use EosUptrade\TICKeos\Bundle\TICKeosMobileBundle\Exception\MobileServiceException;
use EosUptrade\TICKeos\Core\Customer\CustomerAccountData;
use EosUptrade\TICKeos\Core\Domain\Log\Order\LogEntryTypeProcessError;
use EosUptrade\TICKeos\Core\License\License;
use EosUptrade\TICKeos\Core\Payment\Exception\PaymentMethodRemovalException;
use EosUptrade\TICKeos\Core\Payment\External\ParameterizedPaymentMethod as ExternalParameterizedPaymentMethod;
use EosUptrade\TICKeos\Library\OrderProcessContracts\Order\OptionProductInterface;
use EosUptrade\TICKeos\Library\TickeosContracts\Repository\CustomerRepository;
use EosUptrade\TICKeos\Library\TickeosContracts\Session\AnonymousCustomerSessionHandlerInterface;
use EosUptrade\TICKeos\Shop\Domain\Customer\CustomerDisabledByChecker;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;

class ServiceApi {}

class MobileServiceApi implements ServiceApi
{
    public const string VERSION = '3.7';
    private const string CACHE_VERSION = '3.7';

    public function __construct(
        protected sfUser $user,
        protected AuthorizationCheckerInterface $authorizationChecker,
        protected readonly LegacyCertificateProvider $certificateProvider,
        protected MobileServiceClient $client,
        protected AnonymousCustomerSessionHandler $anonymousCustomerSessionHandler,
        protected CustomerRepository $customerRepository,
        protected CustomerDisabledByChecker $customerDisabledByChecker,
        protected LoggerInterface $logger,
        protected ?BrowserTypeConfiguration $browserTypeConfiguration,
    ) {
        $this->eventDispatcher = sfContext::getInstance()->getEventDispatcher();
    }

    /**
     * Calls methods defined via sfEventDispatcher.
     *
     * If a method cannot be found via sfEventDispatcher, the method name will
     * be parsed to magically handle getMyFactory() and setMyFactory() methods.
     *
     * @param string $method    The method name
     * @param array  $arguments The method arguments
     *
     * @return mixed The returned value of the called method
     *
     * @throws sfException if call fails
     */
    public function __call($method, $arguments)
    {
        $event = $this->dispatcher->notifyUntil(new sfEvent($this, 'context.method_not_found', ['method' => $method, 'arguments' => $arguments]));
        if (!$event->isProcessed()) {
            $verb = substr($method, 0, 3); // get | set
            $factory = strtolower(substr($method, 3)); // factory name

            if ('get' == $verb && $this->has($factory)) {
                return $this->factories[$factory];
            }
            if ('set' == $verb && isset($arguments[0])) {
                return $this->set($factory, $arguments[0]);
            }

            throw new sfException(sprintf('Call to undefined method %s::%s.', get_class($this), $method));
        }

        return $event->getReturnValue();
    }

    /**
     * Creates a new context instance.
     *
     * @param sfApplicationConfiguration $configuration An sfApplicationConfiguration instance
     * @param string                     $name          A name for this context (application name by default)
     * @param string                     $class         The context class to use (sfContext by default)
     *
     * @return sfContext An sfContext instance
     *
     * @throws sfFactoryException
     */
    public static function createInstance(sfApplicationConfiguration $configuration, $name = null, $class = __CLASS__)
    {
        if (null === $name) {
            $name = $configuration->getApplication();
        }

        self::$current = $name;

        self::$instances[$name] = new $class();

        if (!self::$instances[$name] instanceof sfContext) {
            throw new sfFactoryException(sprintf('Class "%s" is not of the type sfContext.', $class));
        }

        self::$instances[$name]->initialize($configuration);

        return self::$instances[$name];
    }

    /**
     * Initializes the current sfContext instance.
     *
     * @param sfApplicationConfiguration $configuration An sfApplicationConfiguration instance
     */
    public function initialize(sfApplicationConfiguration $configuration)
    {
        $this->configuration = $configuration;
        $this->dispatcher = $configuration->getEventDispatcher();

        try {
            $this->loadFactories();
        } catch (sfException $e) {
            $e->printStackTrace();
        } catch (Exception $e) {
            sfException::createFromException($e)->printStackTrace();
        }

        $this->dispatcher->connect('template.filter_parameters', [$this, 'filterTemplateParameters']);
        $this->dispatcher->connect('response.fastcgi_finish_request', [$this, 'shutdownUserAndStorage']);

        // register our shutdown function
        register_shutdown_function([$this, 'shutdown']);
    }

    /**
     * Retrieves the singleton instance of this class.
     *
     * @param string $name  the name of the sfContext to retrieve
     * @param string $class The context class to use (sfContext by default)
     *
     * @return sfContext an sfContext implementation instance
     *
     * @throws sfException
     */
    public static function getInstance($name = null, $class = __CLASS__)
    {
        if (null === $name) {
            $name = self::$current;
        }

        if (!isset(self::$instances[$name])) {
            throw new sfException(sprintf('The "%s" context does not exist.', $name));
        }

        return self::$instances[$name];
    }

    /**
     * Checks to see if there has been a context created.
     *
     * @param string $name The name of the sfContext to check for
     *
     * @return bool true is instanced, otherwise false
     */
    public static function hasInstance($name = null)
    {
        if (null === $name) {
            $name = self::$current;
        }

        return isset(self::$instances[$name]);
    }

    /**
     * Loads the symfony factories.
     */
    public function loadFactories()
    {
        if (sfConfig::get('sf_use_database')) {
            // setup our database connections
            $this->factories['databaseManager'] = new sfDatabaseManager($this->configuration, ['auto_shutdown' => false]);
        }

        // create a new action stack
        $this->factories['actionStack'] = new sfActionStack();

        if (sfConfig::get('sf_debug') && sfConfig::get('sf_logging_enabled')) {
            $timer = sfTimerManager::getTimer('Factories');
        }

        // include the factories configuration
        require $this->configuration->getConfigCache()->checkConfig('config/factories.yml');

        $this->dispatcher->notify(new sfEvent($this, 'context.load_factories'));

        if (sfConfig::get('sf_debug') && sfConfig::get('sf_logging_enabled')) {
            // @var $timer sfTimer
            $timer->addTime();
        }
    }

    /**
     * Dispatches the current request.
     */
    public function dispatch()
    {
        $this->getController()->dispatch();
    }

    /**
     * Sets the current context to something else.
     *
     * @param string $name The name of the context to switch to
     */
    public static function switchTo($name)
    {
        if (!isset(self::$instances[$name])) {
            $currentConfiguration = sfContext::getInstance()->getConfiguration();
            sfContext::createInstance(ProjectConfiguration::getApplicationConfiguration($name, $currentConfiguration->getEnvironment(), $currentConfiguration->isDebug()));
        }

        self::$current = $name;

        sfContext::getInstance()->getConfiguration()->activate();
    }

    /**
     * Returns the configuration instance.
     *
     * @return sfApplicationConfiguration The current application configuration instance
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * Retrieves the current event dispatcher.
     *
     * @return sfEventDispatcher An sfEventDispatcher instance
     */
    public function getEventDispatcher()
    {
        return $this->dispatcher;
    }

    /**
     * Retrieve the action name for this context.
     *
     * @return string|null the currently executing action name if one is set, null otherwise
     */
    public function getActionName()
    {
        // get the last action stack entry
        if ($this->factories['actionStack'] && $lastEntry = $this->factories['actionStack']->getLastEntry()) {
            // @var $lastEntry sfActionStackEntry
            return $lastEntry->getActionName();
        }

        return null;
    }

    /**
     * Retrieve the ActionStack.
     *
     * @return sfActionStack the sfActionStack instance
     */
    public function getActionStack()
    {
        return $this->factories['actionStack'];
    }

    /**
     * Retrieve the controller.
     *
     * @return sfFrontWebController the current sfController implementation instance
     */
    public function getController()
    {
        return isset($this->factories['controller']) ? $this->factories['controller'] : null;
    }

    /**
     * Retrieves the mailer.
     *
     * @return sfMailer the current sfMailer implementation instance
     */
    public function getMailer()
    {
        if (!isset($this->factories['mailer'])) {
            $this->factories['mailer'] = new $this->mailerConfiguration['class']($this->dispatcher, $this->mailerConfiguration);
        }

        return $this->factories['mailer'];
    }

    /**
     * Set mailer configuration.
     *
     * @param array $configuration
     */
    public function setMailerConfiguration($configuration)
    {
        $this->mailerConfiguration = $configuration;
    }

    /**
     * Retrieve the logger.
     *
     * @return sfLogger the current sfLogger implementation instance
     */
    public function getLogger()
    {
        if (!isset($this->factories['logger'])) {
            $this->factories['logger'] = new sfNoLogger($this->dispatcher);
        }

        return $this->factories['logger'];
    }

    /**
     * Retrieve a database connection from the database manager.
     *
     * This is a shortcut to manually getting a connection from an existing
     * database implementation instance.
     *
     * If the [sf_use_database] setting is off, this will return null.
     *
     * @param string $name a database name
     *
     * @return mixed a database instance
     *
     * @throws sfDatabaseException if the requested database name does not exist
     */
    public function getDatabaseConnection($name = 'default')
    {
        if (null !== $this->factories['databaseManager']) {
            return $this->factories['databaseManager']->getDatabase($name)->getConnection();
        }

        return null;
    }

    /**
     * Retrieve the database manager.
     *
     * @return sfDatabaseManager the current sfDatabaseManager instance
     */
    public function getDatabaseManager()
    {
        return isset($this->factories['databaseManager']) ? $this->factories['databaseManager'] : null;
    }

    /**
     * Retrieve the module directory for this context.
     *
     * @return string|null an absolute filesystem path to the directory of the currently executing module if one is set, null otherwise
     */
    public function getModuleDirectory()
    {
        // get the last action stack entry
        if (isset($this->factories['actionStack']) && $lastEntry = $this->factories['actionStack']->getLastEntry()) {
            // @var $lastEntry sfActionStackEntry
            return sfConfig::get('sf_app_module_dir').'/'.$lastEntry->getModuleName();
        }

        return null;
    }

    /**
     * Retrieve the module name for this context.
     *
     * @return string|null the currently executing module name if one is set, null otherwise
     */
    public function getModuleName()
    {
        // get the last action stack entry
        if (isset($this->factories['actionStack']) && $lastEntry = $this->factories['actionStack']->getLastEntry()) {
            // @var $lastEntry sfActionStackEntry
            return $lastEntry->getModuleName();
        }

        return null;
    }

    /**
     * Retrieve the request.
     *
     * @return sfRequest the current sfRequest implementation instance
     */
    public function getRequest()
    {
        return isset($this->factories['request']) ? $this->factories['request'] : null;
    }

    /**
     * Retrieve the response.
     *
     * @return sfResponse the current sfResponse implementation instance
     */
    public function getResponse()
    {
        return isset($this->factories['response']) ? $this->factories['response'] : null;
    }

    /**
     * Set the response object.
     *
     * @param sfResponse $response an sfResponse instance
     */
    public function setResponse($response)
    {
        $this->factories['response'] = $response;
    }

    /**
     * Retrieve the storage.
     *
     * @return sfStorage the current sfStorage implementation instance
     */
    public function getStorage()
    {
        return isset($this->factories['storage']) ? $this->factories['storage'] : null;
    }

    /**
     * Retrieve the view cache manager.
     *
     * @return sfViewCacheManager the current sfViewCacheManager implementation instance
     */
    public function getViewCacheManager()
    {
        return isset($this->factories['viewCacheManager']) ? $this->factories['viewCacheManager'] : null;
    }

    /**
     * Retrieve the i18n instance.
     *
     * @return sfI18N the current sfI18N implementation instance
     *
     * @throws sfConfigurationException
     */
    public function getI18N()
    {
        if (!sfConfig::get('sf_i18n')) {
            throw new sfConfigurationException('You must enable i18n support in your settings.yml configuration file.');
        }

        return $this->factories['i18n'];
    }

    /**
     * Retrieve the routing instance.
     *
     * @return sfRouting the current sfRouting implementation instance
     */
    public function getRouting()
    {
        return isset($this->factories['routing']) ? $this->factories['routing'] : null;
    }

    /**
     * Retrieve the user.
     *
     * @return sfUser the current sfUser implementation instance
     */
    public function getUser()
    {
        return isset($this->factories['user']) ? $this->factories['user'] : null;
    }

    /**
     * Retrieves the service container.
     *
     * @return sfServiceContainer the current sfServiceContainer implementation instance
     */
    public function getServiceContainer()
    {
        if (!isset($this->factories['serviceContainer'])) {
            $this->factories['serviceContainer'] = new $this->serviceContainerConfiguration['class']();
            $this->factories['serviceContainer']->setService('sf_event_dispatcher', $this->configuration->getEventDispatcher());
            $this->factories['serviceContainer']->setService('sf_formatter', new sfFormatter());
            $this->factories['serviceContainer']->setService('sf_user', $this->getUser());
            $this->factories['serviceContainer']->setService('sf_routing', $this->getRouting());
        }

        return $this->factories['serviceContainer'];
    }

    /**
     * Set service ontainer configuration.
     */
    public function setServiceContainerConfiguration(array $config)
    {
        $this->serviceContainerConfiguration = $config;
    }

    /**
     * Retrieves a service from the service container.
     *
     * @param string $id The service identifier
     *
     * @return object The service instance
     */
    public function getService($id)
    {
        return $this->getServiceContainer()->getService($id);
    }

    /**
     * Returns the configuration cache.
     *
     * @return sfConfigCache A sfConfigCache instance
     */
    public function getConfigCache()
    {
        return $this->configuration->getConfigCache();
    }

    /**
     * Returns true if the context object exists (implements the ArrayAccess interface).
     *
     * @param string $name The name of the context object
     *
     * @return bool true if the context object exists, false otherwise
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($name)
    {
        return $this->has($name);
    }

    /**
     * Returns the context object associated with the name (implements the ArrayAccess interface).
     *
     * @param string $name The offset of the value to get
     *
     * @return mixed The context object if exists, null otherwise
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($name)
    {
        return $this->get($name);
    }

    /**
     * Sets the context object associated with the offset (implements the ArrayAccess interface).
     *
     * @param string $offset Service name
     * @param mixed  $value  Service
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * Unsets the context object associated with the offset (implements the ArrayAccess interface).
     *
     * @param string $offset The parameter name
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        unset($this->factories[$offset]);
    }

    /**
     * Gets an object from the current context.
     *
     * @param string $name The name of the object to retrieve
     *
     * @return object The object associated with the given name
     */
    public function get($name)
    {
        if (!$this->has($name)) {
            throw new sfException(sprintf('The "%s" object does not exist in the current context.', $name));
        }

        return $this->factories[$name];
    }

    /**
     * Puts an object in the current context.
     *
     * @param string $name   The name of the object to store
     * @param mixed  $object The object to store
     */
    public function set($name, $object)
    {
        $this->factories[$name] = $object;
    }

    /**
     * Returns true if an object is currently stored in the current context with the given name, false otherwise.
     *
     * @param string $name The object name
     *
     * @return bool true if the object is not null, false otherwise
     */
    public function has($name)
    {
        return isset($this->factories[$name]);
    }

    /**
     * Listens to the template.filter_parameters event.
     *
     * @param sfEvent $event      An sfEvent instance
     * @param array   $parameters An array of template parameters to filter
     *
     * @return array The filtered parameters array
     */
    public function filterTemplateParameters(sfEvent $event, $parameters)
    {
        $parameters['sf_context'] = $this;
        $parameters['sf_request'] = $this->factories['request'];
        $parameters['sf_params'] = $this->factories['request']->getParameterHolder();
        $parameters['sf_response'] = $this->factories['response'];
        $parameters['sf_user'] = $this->factories['user'];

        return $parameters;
    }

    /**
     * Shuts the user/storage down.
     *
     * @internal Should be called only via invoking "response.fastcgi_finish_request" or context shutting down.
     */
    public function shutdownUserAndStorage()
    {
        if (!$this->hasShutdownUserAndStorage && $this->has('user')) {
            $this->getUser()->shutdown();
            $this->getStorage()->shutdown();

            $this->hasShutdownUserAndStorage = true;
        }
    }

    /**
     * Execute the shutdown procedure.
     */
    public function shutdown()
    {
        $this->shutdownUserAndStorage();

        if ($this->has('routing')) {
            $this->getRouting()->shutdown();
        }

        if (sfConfig::get('sf_use_database')) {
            $this->getDatabaseManager()->shutdown();
        }

        if (sfConfig::get('sf_logging_enabled')) {
            $this->getLogger()->shutdown();
        }
    }

    /**
     * Extract the class or interface name from filename.
     *
     * @param string $filename a filename
     *
     * @return string a class or interface name, if one can be extracted, otherwise null
     */
    public static function extractClassName($filename)
    {
        $retval = null;

        if (self::isPathAbsolute($filename)) {
            $filename = basename($filename);
        }

        $pattern = '/(.*?)\.(class|interface)\.php/i';

        if (preg_match($pattern, $filename, $match)) {
            $retval = $match[1];
        }

        return $retval;
    }

    /**
     * Clear all files in a given directory.
     *
     * @param string $directory an absolute filesystem path to a directory
     */
    public static function clearDirectory($directory)
    {
        if (!is_dir($directory)) {
            return;
        }

        // open a file point to the cache dir
        $fp = opendir($directory);

        // ignore names
        $ignore = ['.', '..', 'CVS', '.svn'];

        while (($file = readdir($fp)) !== false) {
            if (!in_array($file, $ignore)) {
                if (is_link($directory.'/'.$file)) {
                    // delete symlink
                    unlink($directory.'/'.$file);
                } elseif (is_dir($directory.'/'.$file)) {
                    // recurse through directory
                    self::clearDirectory($directory.'/'.$file);

                    // delete the directory
                    rmdir($directory.'/'.$file);
                } else {
                    // delete the file
                    unlink($directory.'/'.$file);
                }
            }
        }

        // close file pointer
        closedir($fp);
    }

    /**
     * Clear all files and directories corresponding to a glob pattern.
     *
     * @param string $pattern an absolute filesystem pattern
     */
    public static function clearGlob($pattern)
    {
        if (false === $files = glob($pattern)) {
            return;
        }

        // order is important when removing directories
        sort($files);

        foreach ($files as $file) {
            if (is_dir($file)) {
                // delete directory
                self::clearDirectory($file);
            } else {
                // delete file
                unlink($file);
            }
        }
    }

    /**
     * Determine if a filesystem path is absolute.
     *
     * @param string $path a filesystem path
     *
     * @return bool true, if the path is absolute, otherwise false
     */
    public static function isPathAbsolute($path)
    {
        if ('/' == $path[0] || '\\' == $path[0]
            || (
                strlen($path) > 3 && ctype_alpha($path[0])
                && ':' == $path[1]
                && ('\\' == $path[2] || '/' == $path[2])
            )
        ) {
            return true;
        }

        return false;
    }

    /**
     * Strips comments from php source code.
     *
     * @param string $source PHP source code
     *
     * @return string comment free source code
     */
    public static function stripComments($source)
    {
        if (!function_exists('token_get_all')) {
            return $source;
        }

        $ignore = [T_COMMENT => true, T_DOC_COMMENT => true];
        $output = '';

        foreach (token_get_all($source) as $token) {
            // array
            if (isset($token[1])) {
                // no action on comments
                if (!isset($ignore[$token[0]])) {
                    // anything else -> output "as is"
                    $output .= $token[1];
                }
            } else {
                // simple 1-character token
                $output .= $token;
            }
        }

        return $output;
    }

    /**
     * Strip slashes recursively from array.
     *
     * @param array $value the value to strip
     *
     * @return array clean value with slashes stripped
     */
    public static function stripslashesDeep($value)
    {
        return is_array($value) ? array_map(['sfToolkit', 'stripslashesDeep'], $value) : stripslashes($value);
    }

    // code from php at moechofe dot com (array_merge comment on php.net)
    /*
     * array arrayDeepMerge ( array array1 [, array array2 [, array ...]] )
     *
     * Like array_merge
     *
     *  arrayDeepMerge() merges the elements of one or more arrays together so
     * that the values of one are appended to the end of the previous one. It
     * returns the resulting array.
     *  If the input arrays have the same string keys, then the later value for
     * that key will overwrite the previous one. If, however, the arrays contain
     * numeric keys, the later value will not overwrite the original value, but
     * will be appended.
     *  If only one array is given and the array is numerically indexed, the keys
     * get reindexed in a continuous way.
     *
     * Different from array_merge
     *  If string keys have arrays for values, these arrays will merge recursively.
     */
    public static function arrayDeepMerge()
    {
        switch (func_num_args()) {
            case 0:
                return false;

            case 1:
                return func_get_arg(0);

            case 2:
                $args = func_get_args();
                $args[2] = [];
                if (is_array($args[0]) && is_array($args[1])) {
                    foreach (array_unique(array_merge(array_keys($args[0]), array_keys($args[1]))) as $key) {
                        $isKey0 = array_key_exists($key, $args[0]);
                        $isKey1 = array_key_exists($key, $args[1]);
                        if ($isKey0 && $isKey1 && is_array($args[0][$key]) && is_array($args[1][$key])) {
                            $args[2][$key] = self::arrayDeepMerge($args[0][$key], $args[1][$key]);
                        } elseif ($isKey0 && $isKey1) {
                            $args[2][$key] = $args[1][$key];
                        } elseif (!$isKey1) {
                            $args[2][$key] = $args[0][$key];
                        } elseif (!$isKey0) {
                            $args[2][$key] = $args[1][$key];
                        }
                    }

                    return $args[2];
                }

                return $args[1];

            default:
                $args = func_get_args();
                $args[1] = sfToolkit::arrayDeepMerge($args[0], $args[1]);
                array_shift($args);

                return call_user_func_array(['sfToolkit', 'arrayDeepMerge'], $args);

                break;
        }
    }

    /**
     * Converts string to array.
     *
     * @param string $string the value to convert to array
     *
     * @return array
     */
    public static function stringToArray($string)
    {
        preg_match_all('/
      \s*((?:\w+-)*\w+)     # key                               \\1
      \s*=\s*               # =
      (\'|")?               # values may be included in \' or " \\2
      (.*?)                 # value                             \\3
      (?(2) \\2)            # matching \' or " if needed        \\4
      \s*(?:
        (?=\w+\s*=) | \s*$  # followed by another key= or the end of the string
      )
    /x', (string) $string, $matches, PREG_SET_ORDER);

        $attributes = [];
        foreach ($matches as $val) {
            $attributes[$val[1]] = self::literalize($val[3]);
        }

        return $attributes;
    }

    /**
     * Finds the type of the passed value, returns the value as the new type.
     *
     * @param string $value
     * @param bool   $quoted Quote?
     */
    public static function literalize($value, $quoted = false)
    {
        // lowercase our value for comparison
        $value = trim($value);
        $lvalue = strtolower($value);

        if (in_array($lvalue, ['null', '~', ''])) {
            $value = null;
        } elseif (in_array($lvalue, ['true', 'on', '+', 'yes'])) {
            $value = true;
        } elseif (in_array($lvalue, ['false', 'off', '-', 'no'])) {
            $value = false;
        } elseif (ctype_digit($value)) {
            $value = (int) $value;
        } elseif (is_numeric($value)) {
            $value = (float) $value;
        } else {
            $value = self::replaceConstants($value);
            if ($quoted) {
                $value = '\''.str_replace('\'', '\\\'', $value).'\'';
            }
        }

        return $value;
    }

    /**
     * Replaces constant identifiers in a scalar value.
     *
     * @param string $value the value to perform the replacement on
     *
     * @return string the value with substitutions made
     */
    public static function replaceConstants($value)
    {
        if (!is_string($value)) {
            return $value;
        }

        return preg_replace_callback('/%(.+?)%/', function ($v) {
            return sfConfig::has(strtolower($v[1])) ? sfConfig::get(strtolower($v[1])) : '%'.$v[1].'%';
        }, $value);
    }

    /**
     * Returns subject replaced with regular expression matchs.
     *
     * @param mixed $search       subject to search
     * @param array $replacePairs array of search => replace pairs
     */
    public static function pregtr($search, $replacePairs)
    {
        return preg_replace(array_keys($replacePairs), array_values($replacePairs), (string) $search);
    }

    /**
     * Checks if array values are empty.
     *
     * @param array $array the array to check
     *
     * @return bool true if empty, otherwise false
     */
    public static function isArrayValuesEmpty($array)
    {
        static $isEmpty = true;
        foreach ($array as $value) {
            $isEmpty = is_array($value) ? self::isArrayValuesEmpty($value) : '' === (string) $value;
            if (!$isEmpty) {
                break;
            }
        }

        return $isEmpty;
    }

    /**
     * Checks if a string is an utf8.
     *
     * Yi Stone Li<yili@yahoo-inc.com>
     * Copyright (c) 2007 Yahoo! Inc. All rights reserved.
     * Licensed under the BSD open source license
     *
     * @param string
     *
     * @return bool true if $string is valid UTF-8 and false otherwise
     */
    public static function isUTF8($string)
    {
        for ($idx = 0, $strlen = strlen($string); $idx < $strlen; ++$idx) {
            $byte = ord($string[$idx]);

            if ($byte & 0x80) {
                if (($byte & 0xE0) == 0xC0) {
                    // 2 byte char
                    $bytes_remaining = 1;
                } elseif (($byte & 0xF0) == 0xE0) {
                    // 3 byte char
                    $bytes_remaining = 2;
                } elseif (($byte & 0xF8) == 0xF0) {
                    // 4 byte char
                    $bytes_remaining = 3;
                } else {
                    return false;
                }

                if ($idx + $bytes_remaining >= $strlen) {
                    return false;
                }

                while ($bytes_remaining--) {
                    if ((ord($string[++$idx]) & 0xC0) != 0x80) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * Returns an array value for a path.
     *
     * @param array  $values  The values to search
     * @param string $name    The token name
     * @param array  $default Default if not found
     *
     * @return array
     */
    public static function getArrayValueForPath($values, $name, $default = null)
    {
        if (false === $offset = strpos($name, '[')) {
            return isset($values[$name]) ? $values[$name] : $default;
        }

        if (!isset($values[substr($name, 0, $offset)])) {
            return $default;
        }

        $array = $values[substr($name, 0, $offset)];

        while (false !== $pos = strpos($name, '[', $offset)) {
            $end = strpos($name, ']', $pos);
            if ($end == $pos + 1) {
                // reached a []
                if (!is_array($array)) {
                    return $default;
                }

                break;
            }
            if (!isset($array[substr($name, $pos + 1, $end - $pos - 1)])) {
                return $default;
            }
            if (is_array($array)) {
                $array = $array[substr($name, $pos + 1, $end - $pos - 1)];
                $offset = $end;
            } else {
                return $default;
            }
        }

        return $array;
    }

    /**
     * Get path to php cli.
     *
     * @return string
     *
     * @throws sfException If no php cli found
     */
    public static function getPhpCli()
    {
        $path = getenv('PATH') ?: getenv('Path');
        $suffixes = DIRECTORY_SEPARATOR == '\\' ? (getenv('PATHEXT') ? explode(PATH_SEPARATOR, getenv('PATHEXT')) : ['.exe', '.bat', '.cmd', '.com']) : [''];
        foreach (['php5', 'php'] as $phpCli) {
            foreach ($suffixes as $suffix) {
                foreach (explode(PATH_SEPARATOR, $path) as $dir) {
                    if (is_file($file = $dir.DIRECTORY_SEPARATOR.$phpCli.$suffix) && is_executable($file)) {
                        return $file;
                    }
                }
            }
        }

        throw new sfException('Unable to find PHP executable.');
    }

    /**
     * Converts strings to UTF-8 via iconv. NB, the result may not by UTF-8 if the conversion failed.
     *
     * This file comes from Prado (BSD License)
     *
     * @param string $string string to convert to UTF-8
     * @param string $from   current encoding
     *
     * @return string UTF-8 encoded string, original string if iconv failed
     */
    public static function I18N_toUTF8($string, $from)
    {
        $from = strtoupper($from);
        if ('UTF-8' != $from) {
            $s = iconv($from, 'UTF-8', $string);  // to UTF-8

            return false !== $s ? $s : $string; // it could return false
        }

        return $string;
    }

    /**
     * Converts UTF-8 strings to a different encoding. NB. The result may not have been encoded if iconv fails.
     *
     * This file comes from Prado (BSD License)
     *
     * @param string $string the UTF-8 string for conversion
     * @param string $to     new encoding
     *
     * @return string encoded string
     */
    public static function I18N_toEncoding($string, $to)
    {
        $to = strtoupper($to);
        if ('UTF-8' != $to) {
            $s = iconv('UTF-8', $to, $string);

            return false !== $s ? $s : $string;
        }

        return $string;
    }

    /**
     * Adds a path to the PHP include_path setting.
     *
     * @param mixed  $path     Single string path or an array of paths
     * @param string $position Either 'front' or 'back'
     *
     * @return string The old include path
     */
    public static function addIncludePath($path, $position = 'front')
    {
        if (is_array($path)) {
            foreach ('front' == $position ? array_reverse($path) : $path as $p) {
                self::addIncludePath($p, $position);
            }

            return;
        }

        $paths = explode(PATH_SEPARATOR, get_include_path());

        // remove what's already in the include_path
        if (false !== $key = array_search(realpath($path), array_map('realpath', $paths))) {
            unset($paths[$key]);
        }

        switch ($position) {
            case 'front':
                array_unshift($paths, $path);

                break;

            case 'back':
                $paths[] = $path;

                break;

            default:
                throw new InvalidArgumentException(sprintf('Unrecognized position: "%s"', $position));
        }

        return set_include_path(implode(PATH_SEPARATOR, $paths));
    }

    protected $type = 'file';
    protected $names = [];
    protected $prunes = [];
    protected $discards = [];
    protected $execs = [];
    protected $mindepth = 0;
    protected $sizes = [];
    protected $maxdepth = 1000000;
    protected $relative = false;
    protected $follow_link = false;
    protected $sort = false;
    protected $ignore_version_control = true;

    /**
     * Sets maximum directory depth.
     *
     * Finder will descend at most $level levels of directories below the starting point.
     *
     * @param int $level
     *
     * @return sfFinder current sfFinder object
     */
    public function maxdepth($level)
    {
        $this->maxdepth = $level;

        return $this;
    }

    /**
     * Sets minimum directory depth.
     *
     * Finder will start applying tests at level $level.
     *
     * @param int $level
     *
     * @return sfFinder current sfFinder object
     */
    public function mindepth($level)
    {
        $this->mindepth = $level;

        return $this;
    }

    public function get_type()
    {
        return $this->type;
    }

    /**
     * Sets the type of elements to returns.
     *
     * @param string $name directory or file or any (for both file and directory)
     *
     * @return sfFinder new sfFinder object
     */
    public static function type($name)
    {
        $finder = new self();

        return $finder->setType($name);
    }

    /**
     * Sets the type of elements to returns.
     *
     * @param string $name directory or file or any (for both file and directory)
     *
     * @return sfFinder Current object
     */
    public function setType($name)
    {
        $name = strtolower($name);

        if ('dir' === substr($name, 0, 3)) {
            $this->type = 'directory';

            return $this;
        }
        if ('any' === $name) {
            $this->type = 'any';

            return $this;
        }

        $this->type = 'file';

        return $this;
    }

    /**
     * Adds rules that files must match.
     *
     * You can use patterns (delimited with / sign), globs or simple strings.
     *
     * $finder->name('*.php')
     * $finder->name('/\.php$/') // same as above
     * $finder->name('test.php')
     *
     * @param  list   a list of patterns, globs or strings
     *
     * @return sfFinder Current object
     */
    public function name()
    {
        $args = func_get_args();
        $this->names = array_merge($this->names, $this->args_to_array($args));

        return $this;
    }

    /**
     * Adds rules that files must not match.
     *
     * @see    ->name()
     *
     * @param  list   a list of patterns, globs or strings
     *
     * @return sfFinder Current object
     */
    public function not_name()
    {
        $args = func_get_args();
        $this->names = array_merge($this->names, $this->args_to_array($args, true));

        return $this;
    }

    /**
     * Adds tests for file sizes.
     *
     * $finder->size('> 10K');
     * $finder->size('<= 1Ki');
     * $finder->size(4);
     *
     * @param  list   a list of comparison strings
     *
     * @return sfFinder Current object
     */
    public function size()
    {
        $args = func_get_args();
        $numargs = count($args);
        for ($i = 0; $i < $numargs; ++$i) {
            $this->sizes[] = new sfNumberCompare($args[$i]);
        }

        return $this;
    }

    /**
     * Traverses no further.
     *
     * @param  list   a list of patterns, globs to match
     *
     * @return sfFinder Current object
     */
    public function prune()
    {
        $args = func_get_args();
        $this->prunes = array_merge($this->prunes, $this->args_to_array($args));

        return $this;
    }

    /**
     * Discards elements that matches.
     *
     * @param  list   a list of patterns, globs to match
     *
     * @return sfFinder Current object
     */
    public function discard()
    {
        $args = func_get_args();
        $this->discards = array_merge($this->discards, $this->args_to_array($args));

        return $this;
    }

    /**
     * Ignores version control directories.
     *
     * Currently supports Subversion, CVS, DARCS, Gnu Arch, Monotone, Bazaar-NG, GIT, Mercurial
     *
     * @param bool $ignore falase when version control directories shall be included (default is true)
     *
     * @return sfFinder Current object
     */
    public function ignore_version_control($ignore = true)
    {
        $this->ignore_version_control = $ignore;

        return $this;
    }

    /**
     * Returns files and directories ordered by name.
     *
     * @return sfFinder Current object
     */
    public function sort_by_name()
    {
        $this->sort = 'name';

        return $this;
    }

    /**
     * Returns files and directories ordered by type (directories before files), then by name.
     *
     * @return sfFinder Current object
     */
    public function sort_by_type()
    {
        $this->sort = 'type';

        return $this;
    }

    /**
     * Executes function or method for each element.
     *
     * Element match if functino or method returns true.
     *
     * $finder->exec('myfunction');
     * $finder->exec(array($object, 'mymethod'));
     *
     * @param  mixed  function or method to call
     *
     * @return sfFinder Current object
     */
    public function exec()
    {
        $args = func_get_args();
        $numargs = count($args);
        for ($i = 0; $i < $numargs; ++$i) {
            if (is_array($args[$i]) && !method_exists($args[$i][0], $args[$i][1])) {
                throw new sfException(sprintf('method "%s" does not exist for object "%s".', $args[$i][1], $args[$i][0]));
            }
            if (!is_array($args[$i]) && !function_exists($args[$i])) {
                throw new sfException(sprintf('function "%s" does not exist.', $args[$i]));
            }

            $this->execs[] = $args[$i];
        }

        return $this;
    }

    /**
     * Returns relative paths for all files and directories.
     *
     * @return sfFinder Current object
     */
    public function relative()
    {
        $this->relative = true;

        return $this;
    }

    /**
     * Symlink following.
     *
     * @return sfFinder Current object
     */
    public function follow_link()
    {
        $this->follow_link = true;

        return $this;
    }

    /**
     * Searches files and directories which match defined rules.
     *
     * @return array list of files and directories
     */
    public function in()
    {
        $files = [];
        $here_dir = getcwd();

        $finder = clone $this;

        if ($this->ignore_version_control) {
            $ignores = ['.svn', '_svn', 'CVS', '_darcs', '.arch-params', '.monotone', '.bzr', '.git', '.hg'];

            $finder->discard($ignores)->prune($ignores);
        }

        // first argument is an array?
        $numargs = func_num_args();
        $arg_list = func_get_args();
        if (1 === $numargs && is_array($arg_list[0])) {
            $arg_list = $arg_list[0];
            $numargs = count($arg_list);
        }

        for ($i = 0; $i < $numargs; ++$i) {
            $dir = realpath($arg_list[$i]);

            if (!is_dir($dir)) {
                continue;
            }

            $dir = str_replace('\\', '/', $dir);

            // absolute path?
            if (!self::isPathAbsolute($dir)) {
                $dir = $here_dir.'/'.$dir;
            }

            $new_files = str_replace('\\', '/', $finder->search_in($dir));

            if ($this->relative) {
                $new_files = preg_replace('#^'.preg_quote(rtrim($dir, '/'), '#').'/#', '', $new_files);
            }

            $files = array_merge($files, $new_files);
        }

        if ('name' === $this->sort) {
            sort($files);
        }

        return array_unique($files);
    }

    public static function isPathAbsolute($path)
    {
        if ('/' === $path[0] || '\\' === $path[0]
            || (
                strlen($path) > 3 && ctype_alpha($path[0])
                && ':' === $path[1]
                && ('\\' === $path[2] || '/' === $path[2])
            )
        ) {
            return true;
        }

        return false;
    }

    // glob, patterns (must be //) or strings
    protected function to_regex($str)
    {
        if (preg_match('/^(!)?([^a-zA-Z0-9\\\\]).+?\\2[ims]?$/', $str)) {
            return $str;
        }

        return sfGlobToRegex::glob_to_regex($str);
    }

    protected function args_to_array($arg_list, $not = false)
    {
        $list = [];
        $nbArgList = count($arg_list);
        for ($i = 0; $i < $nbArgList; ++$i) {
            if (is_array($arg_list[$i])) {
                foreach ($arg_list[$i] as $arg) {
                    $list[] = [$not, $this->to_regex($arg)];
                }
            } else {
                $list[] = [$not, $this->to_regex($arg_list[$i])];
            }
        }

        return $list;
    }

    protected function search_in($dir, $depth = 0)
    {
        if ($depth > $this->maxdepth) {
            return [];
        }

        $dir = realpath($dir);

        if ((!$this->follow_link) && is_link($dir)) {
            return [];
        }

        $files = [];
        $temp_files = [];
        $temp_folders = [];
        if (is_dir($dir) && is_readable($dir)) {
            $current_dir = opendir($dir);
            while (false !== $entryname = readdir($current_dir)) {
                if ('.' == $entryname || '..' == $entryname) {
                    continue;
                }

                $current_entry = $dir.DIRECTORY_SEPARATOR.$entryname;
                if ((!$this->follow_link) && is_link($current_entry)) {
                    continue;
                }

                if (is_dir($current_entry)) {
                    if ('type' === $this->sort) {
                        $temp_folders[$entryname] = $current_entry;
                    } else {
                        if (('directory' === $this->type || 'any' === $this->type) && ($depth >= $this->mindepth) && !$this->is_discarded($dir, $entryname) && $this->match_names($dir, $entryname) && $this->exec_ok($dir, $entryname)) {
                            $files[] = $current_entry;
                        }

                        if (!$this->is_pruned($dir, $entryname)) {
                            $files = array_merge($files, $this->search_in($current_entry, $depth + 1));
                        }
                    }
                } else {
                    if (('directory' !== $this->type || 'any' === $this->type) && ($depth >= $this->mindepth) && !$this->is_discarded($dir, $entryname) && $this->match_names($dir, $entryname) && $this->size_ok($dir, $entryname) && $this->exec_ok($dir, $entryname)) {
                        if ('type' === $this->sort) {
                            $temp_files[] = $current_entry;
                        } else {
                            $files[] = $current_entry;
                        }
                    }
                }
            }

            if ('type' === $this->sort) {
                ksort($temp_folders);
                foreach ($temp_folders as $entryname => $current_entry) {
                    if (('directory' === $this->type || 'any' === $this->type) && ($depth >= $this->mindepth) && !$this->is_discarded($dir, $entryname) && $this->match_names($dir, $entryname) && $this->exec_ok($dir, $entryname)) {
                        $files[] = $current_entry;
                    }

                    if (!$this->is_pruned($dir, $entryname)) {
                        $files = array_merge($files, $this->search_in($current_entry, $depth + 1));
                    }
                }

                sort($temp_files);
                $files = array_merge($files, $temp_files);
            }

            closedir($current_dir);
        }

        return $files;
    }

    protected function match_names($dir, $entry)
    {
        if (!count($this->names)) {
            return true;
        }

        // Flags indicating that there was attempts to match
        // at least one "not_name" or "name" rule respectively
        // to following variables:
        $one_not_name_rule = false;
        $one_name_rule = false;

        foreach ($this->names as $args) {
            list($not, $regex) = $args;
            $not ? $one_not_name_rule = true : $one_name_rule = true;
            if (preg_match($regex, $entry)) {
                // We must match ONLY ONE "not_name" or "name" rule:
                // if "not_name" rule matched then we return "false"
                // if "name" rule matched then we return "true"
                return $not ? false : true;
            }
        }

        if ($one_not_name_rule && $one_name_rule) {
            return false;
        }
        if ($one_not_name_rule) {
            return true;
        }
        if ($one_name_rule) {
            return false;
        }

        return true;
    }

    protected function size_ok($dir, $entry)
    {
        if (0 === count($this->sizes)) {
            return true;
        }

        if (!is_file($dir.DIRECTORY_SEPARATOR.$entry)) {
            return true;
        }

        $filesize = filesize($dir.DIRECTORY_SEPARATOR.$entry);
        foreach ($this->sizes as $number_compare) {
            if (!$number_compare->test($filesize)) {
                return false;
            }
        }

        return true;
    }

    protected function is_pruned($dir, $entry)
    {
        if (0 === count($this->prunes)) {
            return false;
        }

        foreach ($this->prunes as $args) {
            $regex = $args[1];
            if (preg_match($regex, $entry)) {
                return true;
            }
        }

        return false;
    }

    protected function is_discarded($dir, $entry)
    {
        if (0 === count($this->discards)) {
            return false;
        }

        foreach ($this->discards as $args) {
            $regex = $args[1];
            if (preg_match($regex, $entry)) {
                return true;
            }
        }

        return false;
    }

    protected function exec_ok($dir, $entry)
    {
        if (0 === count($this->execs)) {
            return true;
        }

        foreach ($this->execs as $exec) {
            if (!call_user_func_array($exec, [$dir, $entry])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Executes an application defined process prior to execution of this sfAction object.
     *
     * By default, this method is empty.
     */
    public function preExecute()
    {
    }

    /**
     * Execute an application defined process immediately after execution of this sfAction object.
     *
     * By default, this method is empty.
     */
    public function postExecute()
    {
    }

    /**
     * Forwards current action to the default 404 error action.
     *
     * @param string $message Message of the generated exception
     *
     * @throws sfError404Exception
     */
    public function forward404($message = null)
    {
        throw new sfError404Exception($this->get404Message($message));
    }

    /**
     * Forwards current action to the default 404 error action unless the specified condition is true.
     *
     * @param bool   $condition A condition that evaluates to true or false
     * @param string $message   Message of the generated exception
     *
     * @throws sfError404Exception
     */
    public function forward404Unless($condition, $message = null)
    {
        if (!$condition) {
            throw new sfError404Exception($this->get404Message($message));
        }
    }

    /**
     * Forwards current action to the default 404 error action if the specified condition is true.
     *
     * @param bool   $condition A condition that evaluates to true or false
     * @param string $message   Message of the generated exception
     *
     * @throws sfError404Exception
     */
    public function forward404If($condition, $message = null)
    {
        if ($condition) {
            throw new sfError404Exception($this->get404Message($message));
        }
    }

    /**
     * Redirects current action to the default 404 error action (with browser redirection).
     *
     * This method stops the current code flow.
     */
    public function redirect404()
    {
        return $this->redirect('/'.sfConfig::get('sf_error_404_module').'/'.sfConfig::get('sf_error_404_action'));
    }

    /**
     * Forwards current action to a new one (without browser redirection).
     *
     * This method stops the action. So, no code is executed after a call to this method.
     *
     * @param string $module A module name
     * @param string $action An action name
     *
     * @throws sfStopException
     */
    public function forward($module, $action)
    {
        if (sfConfig::get('sf_logging_enabled')) {
            $this->dispatcher->notify(new sfEvent($this, 'application.log', [sprintf('Forward to action "%s/%s"', $module, $action)]));
        }

        $this->getController()->forward($module, $action);

        throw new sfStopException();
    }

    /**
     * If the condition is true, forwards current action to a new one (without browser redirection).
     *
     * This method stops the action. So, no code is executed after a call to this method.
     *
     * @param bool   $condition A condition that evaluates to true or false
     * @param string $module    A module name
     * @param string $action    An action name
     *
     * @throws sfStopException
     */
    public function forwardIf($condition, $module, $action)
    {
        if ($condition) {
            $this->forward($module, $action);
        }
    }

    /**
     * Unless the condition is true, forwards current action to a new one (without browser redirection).
     *
     * This method stops the action. So, no code is executed after a call to this method.
     *
     * @param bool   $condition A condition that evaluates to true or false
     * @param string $module    A module name
     * @param string $action    An action name
     *
     * @throws sfStopException
     */
    public function forwardUnless($condition, $module, $action)
    {
        if (!$condition) {
            $this->forward($module, $action);
        }
    }

    /**
     * Redirects current request to a new URL.
     *
     * 2 URL formats are accepted :
     *  - a full URL: http://www.google.com/
     *  - an internal URL (url_for() format): module/action
     *
     * This method stops the action. So, no code is executed after a call to this method.
     *
     * @param string $url        Url
     * @param int    $statusCode Status code (default to 302)
     *
     * @throws sfStopException
     */
    public function redirect($url, $statusCode = 302)
    {
        // compatibility with url_for2() style signature
        if (is_object($statusCode) || is_array($statusCode)) {
            $url = array_merge(['sf_route' => $url], is_object($statusCode) ? ['sf_subject' => $statusCode] : $statusCode);
            $statusCode = func_num_args() >= 3 ? func_get_arg(2) : 302;
        }

        $this->getController()->redirect($url, 0, $statusCode);

        throw new sfStopException();
    }

    /**
     * Redirects current request to a new URL, only if specified condition is true.
     *
     * This method stops the action. So, no code is executed after a call to this method.
     *
     * @param bool   $condition  A condition that evaluates to true or false
     * @param string $url        Url
     * @param int    $statusCode Status code (default to 302)
     *
     * @throws sfStopException
     *
     * @see redirect
     */
    public function redirectIf($condition, $url, $statusCode = 302)
    {
        if ($condition) {
            // compatibility with url_for2() style signature
            $arguments = func_get_args();
            call_user_func_array([$this, 'redirect'], array_slice($arguments, 1));
        }
    }

    /**
     * Redirects current request to a new URL, unless specified condition is true.
     *
     * This method stops the action. So, no code is executed after a call to this method.
     *
     * @param bool   $condition  A condition that evaluates to true or false
     * @param string $url        Url
     * @param int    $statusCode Status code (default to 302)
     *
     * @throws sfStopException
     *
     * @see redirect
     */
    public function redirectUnless($condition, $url, $statusCode = 302)
    {
        if (!$condition) {
            // compatibility with url_for2() style signature
            $arguments = func_get_args();
            call_user_func_array([$this, 'redirect'], array_slice($arguments, 1));
        }
    }

    /**
     * Appends the given text to the response content and bypasses the built-in view system.
     *
     * This method must be called as with a return:
     *
     * <code>return $this->renderText('some text')</code>
     *
     * @param string $text Text to append to the response
     *
     * @return string sfView::NONE
     */
    public function renderText($text)
    {
        $this->getResponse()->setContent($this->getResponse()->getContent().$text);

        return sfView::NONE;
    }

    /**
     * Convert the given data into a JSON response.
     *
     * <code>return $this->renderJson(array('username' => 'john'))</code>
     *
     * @param mixed $data Data to encode as JSON
     *
     * @return string sfView::NONE
     */
    public function renderJson($data)
    {
        $this->getResponse()->setContentType('application/json');
        $this->getResponse()->setContent(json_encode($data));

        return sfView::NONE;
    }

    /**
     * Returns the partial rendered content.
     *
     * If the vars parameter is omitted, the action's internal variables
     * will be passed, just as it would to a normal template.
     *
     * If the vars parameter is set then only those values are
     * available in the partial.
     *
     * @param string $templateName partial name
     * @param array  $vars         vars
     *
     * @return string The partial content
     */
    public function getPartial($templateName, $vars = null)
    {
        $this->getContext()->getConfiguration()->loadHelpers('Partial');

        $vars = null !== $vars ? $vars : $this->varHolder->getAll();

        return get_partial($templateName, $vars);
    }

    /**
     * Appends the result of the given partial execution to the response content.
     *
     * This method must be called as with a return:
     *
     * <code>return $this->renderPartial('foo/bar')</code>
     *
     * @param string $templateName partial name
     * @param array  $vars         vars
     *
     * @return string sfView::NONE
     *
     * @see    getPartial
     */
    public function renderPartial($templateName, $vars = null)
    {
        return $this->renderText($this->getPartial($templateName, $vars));
    }

    /**
     * Returns the component rendered content.
     *
     * If the vars parameter is omitted, the action's internal variables
     * will be passed, just as it would to a normal template.
     *
     * If the vars parameter is set then only those values are
     * available in the component.
     *
     * @param string $moduleName    module name
     * @param string $componentName component name
     * @param array  $vars          vars
     *
     * @return string The component rendered content
     */
    public function getComponent($moduleName, $componentName, $vars = null)
    {
        $this->getContext()->getConfiguration()->loadHelpers('Partial');

        $vars = null !== $vars ? $vars : $this->varHolder->getAll();

        return get_component($moduleName, $componentName, $vars);
    }

    /**
     * Appends the result of the given component execution to the response content.
     *
     * This method must be called as with a return:
     *
     * <code>return $this->renderComponent('foo', 'bar')</code>
     *
     * @param string $moduleName    module name
     * @param string $componentName component name
     * @param array  $vars          vars
     *
     * @return string sfView::NONE
     *
     * @see    getComponent
     */
    public function renderComponent($moduleName, $componentName, $vars = null)
    {
        return $this->renderText($this->getComponent($moduleName, $componentName, $vars));
    }

    /**
     * Returns the security configuration for this module.
     *
     * @return string Current security configuration as an array
     */
    public function getSecurityConfiguration()
    {
        return $this->security;
    }

    /**
     * Overrides the current security configuration for this module.
     *
     * @param array $security The new security configuration
     */
    public function setSecurityConfiguration($security)
    {
        $this->security = $security;
    }

    /**
     * Returns a value from security.yml.
     *
     * @param string $name    The name of the value to pull from security.yml
     * @param mixed  $default The default value to return if none is found in security.yml
     */
    public function getSecurityValue($name, $default = null)
    {
        $actionName = strtolower($this->getActionName());

        if (isset($this->security[$actionName][$name])) {
            return $this->security[$actionName][$name];
        }

        if (isset($this->security['all'][$name])) {
            return $this->security['all'][$name];
        }

        return $default;
    }

    /**
     * Indicates that this action requires security.
     *
     * @return bool true, if this action requires security, otherwise false
     */
    public function isSecure()
    {
        return $this->getSecurityValue('is_secure', false);
    }

    /**
     * Gets credentials the user must have to access this action.
     *
     * @return mixed An array or a string describing the credentials the user must have to access this action
     */
    public function getCredential()
    {
        return $this->getSecurityValue('credentials');
    }

    /**
     * Sets an alternate template for this sfAction.
     *
     * See 'Naming Conventions' in the 'Symfony View' documentation.
     *
     * @param string $name   Template name
     * @param string $module The module (current if null)
     */
    public function setTemplate($name, $module = null)
    {
        if (sfConfig::get('sf_logging_enabled')) {
            $this->dispatcher->notify(new sfEvent($this, 'application.log', [sprintf('Change template to "%s/%s"', null === $module ? 'CURRENT' : $module, $name)]));
        }

        if (null !== $module) {
            $dir = $this->context->getConfiguration()->getTemplateDir($module, $name.sfView::SUCCESS.'.php');
            $name = $dir.'/'.$name;
        }

        sfConfig::set('symfony.view.'.$this->getModuleName().'_'.$this->getActionName().'_template', $name);
    }

    /**
     * Gets the name of the alternate template for this sfAction.
     *
     * WARNING: It only returns the template you set with the setTemplate() method,
     *          and does not return the template that you configured in your view.yml.
     *
     * See 'Naming Conventions' in the 'Symfony View' documentation.
     *
     * @return string Template name. Returns null if no template has been set within the action
     */
    public function getTemplate()
    {
        return sfConfig::get('symfony.view.'.$this->getModuleName().'_'.$this->getActionName().'_template');
    }

    /**
     * Sets an alternate layout for this sfAction.
     *
     * To de-activate the layout, set the layout name to false.
     *
     * To revert the layout to the one configured in the view.yml, set the template name to null.
     *
     * @param mixed $name Layout name or false to de-activate the layout
     */
    public function setLayout($name)
    {
        if (sfConfig::get('sf_logging_enabled')) {
            $this->dispatcher->notify(new sfEvent($this, 'application.log', [sprintf('Change layout to "%s"', $name)]));
        }

        sfConfig::set('symfony.view.'.$this->getModuleName().'_'.$this->getActionName().'_layout', $name);
    }

    /**
     * Gets the name of the alternate layout for this sfAction.
     *
     * WARNING: It only returns the layout you set with the setLayout() method,
     *          and does not return the layout that you configured in your view.yml.
     *
     * @return mixed Layout name. Returns null if no layout has been set within the action
     */
    public function getLayout()
    {
        return sfConfig::get('symfony.view.'.$this->getModuleName().'_'.$this->getActionName().'_layout');
    }

    /**
     * Changes the default view class used for rendering the template associated with the current action.
     *
     * @param string $class View class name
     */
    public function setViewClass($class)
    {
        sfConfig::set('mod_'.strtolower($this->getModuleName()).'_view_class', $class);
    }

    /**
     * Returns the current route for this request.
     *
     * @return sfRoute The route for the request
     */
    public function getRoute()
    {
        return $this->getRequest()->getAttribute('sf_route');
    }

    /**
     * Returns a formatted message for a 404 error.
     *
     * @param string $message An error message (null by default)
     *
     * @return string The error message or a default one if null
     */
    protected function get404Message($message = null)
    {
        return null === $message ? sprintf('This request has been forwarded to a 404 error page by the action "%s/%s".', $this->getModuleName(), $this->getActionName()) : $message;
    }
}
