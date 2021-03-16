<?php

namespace Sunlight\Plugin;

use Kuria\Cache\CacheInterface;
use Kuria\ClassLoader\ClassLoader;
use Sunlight\Core;

class PluginManager
{
    /** Plugin type - language */
    const LANGUAGE = 'language';
    /** Plugin type - template */
    const TEMPLATE = 'template';
    /** Plugin type - extend */
    const EXTEND = 'extend';

    /**
     * Plugin list
     *
     * @var Plugin[] class => instance
     */
    private $plugins;

    /**
     * Plugin map
     *
     * array(
     *      type => array(name1 => instance1, ...),
     *      ...
     * )
     *
     * @var Plugin[][]
     */
    private $pluginMap;

    /**
     * Array of inactive plugins
     *
     * array(
     *      type => array(name1 => instance1, ...),
     *      ...
     * )
     *
     * @var InactivePlugin[][]
     */
    private $inactivePlugins;

    /** @var array[] */
    private $types;

    /** @var CacheInterface */
    private $cache;

    /** @var bool */
    private $initialized = false;

    function __construct(CacheInterface $pluginCache)
    {
        $this->cache = $pluginCache;
        $this->types = self::getTypeDefinitions();
    }

    /**
     * @return array
     */
    static function getTypeDefinitions(): array
    {
        return [
            self::LANGUAGE => LanguagePlugin::TYPE_DEFINITION,
            self::TEMPLATE => TemplatePlugin::TYPE_DEFINITION,
            self::EXTEND => ExtendPlugin::TYPE_DEFINITION,
        ];
    }

    /**
     * Purge plugin cache
     *
     * @return bool
     */
    function purgeCache(): bool
    {
        return $this->cache->clear();
    }

    /**
     * See if the given type is valid
     *
     * @param string $type
     * @return bool
     */
    function isValidType(string $type): bool
    {
        return isset($this->types[$type]);
    }

    /**
     * Get all valid types
     *
     * @return string[]
     */
    function getTypes(): array
    {
        return array_keys($this->types);
    }

    /**
     * Get definition of the given type
     *
     * @param string $type
     * @throws \InvalidArgumentException if the plugin type is not valid
     * @return array
     */
    function getTypeDefinition(string $type): array
    {
        if (!isset($this->types[$type])) {
            throw new \InvalidArgumentException(sprintf('Invalid plugin type "%s"', $type));
        }

        return $this->types[$type];
    }

    /**
     * See if the given plugin class is active
     *
     * @param string $class
     * @return bool
     */
    function hasInstance(string $class): bool
    {
        if (!$this->initialized) {
            $this->initialize();
        }

        return isset($this->plugins[$class]);
    }

    /**
     * Get plugin instance
     *
     * @param string $class
     * @throws \OutOfBoundsException if the plugin does not exist
     * @return Plugin|ExtendPlugin|TemplatePlugin|LanguagePlugin
     */
    function getInstance(string $class)
    {
        if (!$this->initialized) {
            $this->initialize();
        }

        if (isset($this->plugins[$class])) {
            return $this->plugins[$class];
        } else {
            throw new \OutOfBoundsException(sprintf('Plugin instance of class "%s/%s" does not exist', $class));
        }
    }

    /**
     * Get all plugin instances
     *
     * @return Plugin[]|ExtendPlugin[]|TemplatePlugin[]|LanguagePlugin[]
     */
    function getInstances(): array
    {
        if (!$this->initialized) {
            $this->initialize();
        }

        return $this->plugins;
    }

    /**
     * See if the given plugin exists and is active
     *
     * @param string $type
     * @param string $name
     * @return bool
     */
    function has(string $type, string $name): bool
    {
        if (!$this->initialized) {
            $this->initialize();
        }

        return isset($this->pluginMap[$type][$name]);
    }

    /**
     * See if the given plugin exists (either active or inactive)
     *
     * @param string $type
     * @param string $name
     * @return bool
     */
    function exists(string $type, string $name): bool
    {
        return $this->has($type, $name) || $this->hasInactive($type, $name);
    }

    /**
     * Get single plugin
     *
     * @param string $type
     * @param string $name
     * @throws \OutOfBoundsException if the plugin does not exist
     * @return Plugin|ExtendPlugin|TemplatePlugin|LanguagePlugin
     */
    function get(string $type, string $name)
    {
        if (!$this->initialized) {
            $this->initialize();
        }

        if (isset($this->pluginMap[$type][$name])) {
            return $this->pluginMap[$type][$name];
        } else {
            throw new \OutOfBoundsException(sprintf('Plugin "%s/%s" does not exist', $type, $name));
        }
    }

    /**
     * Get a template plugin
     *
     * @param string $name
     * @throws \OutOfBoundsException if the plugin does not exist
     * @return TemplatePlugin
     */
    function getTemplate(string $name): TemplatePlugin
    {
        /** @var TemplatePlugin $plugin */
        $plugin = $this->get(self::TEMPLATE, $name);

        return $plugin;
    }

    /**
     * Get an extend plugin
     *
     * @param string $name
     * @throws \OutOfBoundsException if the plugin does not exist
     * @return ExtendPlugin
     */
    function getExtend(string $name): ExtendPlugin
    {
        /** @var ExtendPlugin $plugin */
        $plugin = $this->get(self::EXTEND, $name);

        return $plugin;
    }

    /**
     * Get a language plugin
     *
     * @param string $name
     * @throws \OutOfBoundsException if the plugin does not exist
     * @return LanguagePlugin
     */
    function getLanguage(string $name): LanguagePlugin
    {
        /** @var LanguagePlugin $plugin */
        $plugin = $this->get(self::LANGUAGE, $name);

        return $plugin;
    }

    /**
     * Get all plugins, optionally for only single type
     *
     * @param string|null $type specific type or null (all)
     * @throws \InvalidArgumentException if the plugin type is not valid
     * @return Plugin[]|Plugin[][] name indexed (if type is specified) or type and name indexed array of Plugin instances
     */
    function all(?string $type = null): array
    {
        if (!$this->initialized) {
            $this->initialize();
        }

        if ($type !== null) {
            if (!isset($this->types[$type])) {
                throw new \InvalidArgumentException(sprintf('Invalid plugin type "%s"', $type));
            }

            return $this->pluginMap[$type];
        } else {
            return $this->pluginMap;
        }
    }

    /**
     * @return LanguagePlugin[]
     */
    function getAllLanguages(): array
    {
        /** @var LanguagePlugin[] $languages */
        $languages = $this->all(self::LANGUAGE);

        return $languages;
    }

    /**
     * @return TemplatePlugin[]
     */
    function getAllTemplates(): array
    {
        /** @var TemplatePlugin[] $templates */
        $templates = $this->all(self::TEMPLATE);

        return $templates;
    }

    /**
     * @return ExtendPlugin[]
     */
    function getAllExtends(): array
    {
        /** @var ExtendPlugin[] $extends */
        $extends = $this->all(self::EXTEND);

        return $extends;
    }

    /**
     * See if the given inactive plugin exists
     *
     * @param string $type
     * @param string $name
     * @return bool
     */
    function hasInactive(string $type, string $name): bool
    {
        if (!$this->initialized) {
            $this->initialize();
        }

        return isset($this->inactivePlugins[$type][$name]);
    }

    /**
     * Get single inactive plugin
     *
     * @param string $type
     * @param string $name
     * @throws \OutOfBoundsException if the plugin does not exist
     * @return InactivePlugin
     */
    function getInactive(string $type, string $name): InactivePlugin
    {
        if (!$this->initialized) {
            $this->initialize();
        }

        if (isset($this->inactivePlugins[$type][$name])) {
            return $this->inactivePlugins[$type][$name];
        } else {
            throw new \OutOfBoundsException(sprintf('Inactive plugin "%s/%s" does not exist', $type, $name));
        }
    }

    /**
     * Get all inactive plugins
     *
     * @param string|null $type specific type or null (all)
     * @throws \InvalidArgumentException if the plugin type is not valid
     * @return InactivePlugin[]|InactivePlugin[][] name indexed (if type is specified) or type and name indexed array of plugin arrays
     */
    function getAllInactive(?string $type = null): array
    {
        if (!$this->initialized) {
            $this->initialize();
        }

        if ($type !== null) {
            if (!isset($this->types[$type])) {
                throw new \InvalidArgumentException(sprintf('Invalid plugin type "%s"', $type));
            }

            return $this->inactivePlugins[$type];
        } else {
            return $this->inactivePlugins;
        }
    }

    /**
     * Find a plugin (active or inactive)
     *
     * @param string $type
     * @param string $name
     * @param bool   $exceptionOnFailure
     * @throws \InvalidArgumentException if the plugin type is not valid
     * @throws \OutOfBoundsException     if the plugin does not exist
     * @return Plugin|null
     */
    function find(string $type, string $name, bool $exceptionOnFailure = true): ?Plugin
    {
        if (!isset($this->types[$type])) {
            throw new \InvalidArgumentException(sprintf('Invalid plugin type "%s"', $type));
        }

        if (isset($this->pluginMap[$type][$name])) {
            $plugin = $this->pluginMap[$type][$name];
        } elseif (isset($this->inactivePlugins[$type][$name])) {
            $plugin = $this->inactivePlugins[$type][$name];
        } else {
            if ($exceptionOnFailure) {
                throw new \OutOfBoundsException(sprintf('Could not find plugin "%s/%s"', $type, $name));
            } else {
                $plugin = null;
            }
        }

        return $plugin;
    }

    /**
     * Get name => label pairs for given plugin type
     *
     * @param string $type
     * @throws \InvalidArgumentException if the plugin type is not valid
     * @return array
     */
    function choices(string $type): array
    {
        if (!isset($this->types[$type])) {
            throw new \InvalidArgumentException(sprintf('Invalid plugin type "%s"', $type));
        }

        $choices = [];
        foreach ($this->pluginMap[$type] as $name => $instance) {
            $choices[$name] = $instance->getOption('name');
        }

        return $choices;
    }

    /**
     * Get HTML select for given plugin type
     *
     * @param string      $pluginType
     * @param string|null $active     active plugin name
     * @param string|null $inputName  input name (null = no <select> tag, only options)
     * @throws \InvalidArgumentException if the plugin type is not valid
     * @return string
     */
    function select(string $pluginType, ?string $active = null, ?string $inputName = null): string
    {
        if (!isset($this->types[$pluginType])) {
            throw new \InvalidArgumentException(sprintf('Invalid plugin type "%s"', $pluginType));
        }

        $output = "";
        if ($inputName) {
            $output .= "<select name=\"{$inputName}\">\n";
        }

        foreach ($this->choices($pluginType) as $name => $label) {
            $output .=
                '<option value="' . _e($name) . '"' . ($active === $name ? ' selected' : '') . '>'
                . _e($label)
                . "</option>\n";
        }
        if ($inputName) {
            $output .= "</select>\n";
        }

        return $output;
    }

    /**
     * Initialize the manager if not done yet
     */
    private function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

        // load data
        $data = $this->cache->get($this->getCacheKey());

        // invalidate stale data
        if ($data !== null && !$this->validateCachedData($data)) {
            $data = null;
        }

        // if data could not be loaded from cache, use plugin loader
        if ($data === null) {
            $data = $this->loadPlugins();
        }

        // setup autoload
        Core::$classLoader->addPrefixes($data['autoload']['psr-0'], ClassLoader::PSR0);
        Core::$classLoader->addPrefixes($data['autoload']['psr-4']);
        Core::$classLoader->addClassMap($data['autoload']['classmap']);

        foreach ($data['autoload']['files'] as $path) {
            require $path;
        }

        // initialize plugins
        $this->pluginMap = [];
        $this->inactivePlugins = [];

        foreach ($data['plugins'] as $type => $plugins) {
            $this->pluginMap[$type] = [];
            $this->inactivePlugins[$type] = [];

            foreach ($plugins as $name => $plugin) {
                if (Plugin::STATUS_OK === $plugin['status']) {
                    $pluginInstance = new $plugin['options']['class']($plugin, $this);

                    if (!is_a($pluginInstance, $this->types[$type]['class'])) {
                        throw new \LogicException(sprintf('Plugin class "%s" of plugin type "%s" must extend "%s"', get_class($pluginInstance), $type, $this->types[$type]['class']));
                    }

                    $this->plugins[$plugin['options']['class']] = $pluginInstance;
                    $this->pluginMap[$type][$name] = $pluginInstance;
                } else {
                    $this->inactivePlugins[$type][$name] = new InactivePlugin(
                        $plugin,
                        $this
                    );
                }
            }
        }

        // set variables
        $this->initialized = true;
    }

    /**
     * @return array
     */
    private function loadPlugins(): array
    {
        $pluginLoader = new PluginLoader($this->types);
        $result = $pluginLoader->load();
        $data = [
            'plugins' => $result['plugins'],
            'autoload' => $result['autoload'],
            'bound_files' => $this->mapBoundFiles($result['bound_files']),
            'system_hash' => $this->getSystemHash(),
        ];

        $this->cache->set($this->getCacheKey(), $data);

        return $data;
    }

    /**
     * @return string
     */
    private function getCacheKey(): string
    {
        return _debug ? 'plugins_debug' : 'plugins';
    }

    /**
     * @return string
     */
    private function getSystemHash(): string
    {
        return sha1(Core::VERSION . '$' . realpath(_root));
    }

    /**
     * @param array $boundFiles
     * @return array
     */
    private function mapBoundFiles(array $boundFiles): array
    {
        $map = [];

        foreach ($boundFiles as $boundFile) {
            $map[realpath($boundFile)] = filemtime($boundFile);
        }

        return $map;
    }

    /**
     * @param array $data
     * @return bool
     */
    private function validateCachedData(array $data): bool
    {
        if ($data['system_hash'] !== $this->getSystemHash()) {
            return false;
        }

        foreach ($data['bound_files'] as $path => $mtime) {
            if (@filemtime($path) !== $mtime) {
                return false;
            }
        }

        return true;
    }
}
