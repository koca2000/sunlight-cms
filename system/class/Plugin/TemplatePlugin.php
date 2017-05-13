<?php

namespace Sunlight\Plugin;

use Sunlight\Database\Database as DB;
use Sunlight\LangPack;

class TemplatePlugin extends Plugin
{
    const DEFAULT_LAYOUT = 'default';

    protected static $typeDefinition = array(
        'dir' => 'plugins/templates',
        'class' => __CLASS__,
        'default_base_namespace' => 'SunlightTemplate',
        'options' => array(
            'css' => array('type' => 'array', 'required' => false, 'default' => array('template_style' => 'style.css'), 'normalizer' => array('Sunlight\Plugin\PluginOptionNormalizer', 'normalizeWebPathArray')),
            'js' => array('type' => 'array', 'required' => false, 'default' => array(), 'normalizer' => array('Sunlight\Plugin\PluginOptionNormalizer', 'normalizeWebPathArray')),
            'dark' => array('type' => 'boolean', 'required' => false, 'default' => false),
            'smiley.count' => array('type' => 'integer', 'required' => false, 'default' => 10),
            'smiley.format' => array('type' => 'string', 'required' => false, 'default' => 'gif'),
            'bbcode.buttons' => array('type' => 'boolean', 'required' => false, 'default' => true),
            'box.parent' => array('type' => 'string', 'required' => false, 'default' => ''),
            'box.item' => array('type' => 'string', 'required' => false, 'default' => 'div'),
            'box.title' => array('type' => 'string', 'required' => false, 'default' => 'h3'),
            'box.title.inside' => array('type' => 'boolean', 'required' => false, 'default' => false),
            'layouts' => array('type' => 'array', 'required' => true, 'normalizer' => array('Sunlight\Plugin\PluginOptionNormalizer', 'normalizeTemplateLayouts')),
            'layout.labels' => array('type' => 'string', 'required' => false, 'normalizer' => array('Sunlight\Plugin\PluginOptionNormalizer', 'normalizePath')),
        ),
    );

    /** @var bool */
    protected $layoutLabelsLoaded = false;

    public function canBeDisabled()
    {
        return !$this->isDefault() && parent::canBeDisabled();
    }

    public function canBeRemoved()
    {
        return !$this->isDefault() && parent::canBeRemoved();
    }

    /**
     * See if this is the default template
     *
     * @return bool
     */
    public function isDefault()
    {
        return $this->name === _default_template;
    }
    
    /**
     * Get template file path for the given layout
     *
     * @param string $layout
     * @return string
     */
    public function getTemplate($layout = self::DEFAULT_LAYOUT)
    {
        if (!isset($this->options['layouts'][$layout])) {
            $layout = static::DEFAULT_LAYOUT;
        }

        return $this->options['layouts'][$layout]['template'];
    }

    /**
     * See if the given layout exists
     *
     * @param string $layout layout name
     * @return bool
     */
    public function hasLayout($layout)
    {
        return isset($this->options['layouts'][$layout]);
    }

    /**
     * Get list of template layout identifiers
     *
     * @return string[]
     */
    public function getLayouts()
    {
        return array_keys($this->options['layouts']);
    }

    /**
     * Get label for the given layout
     *
     * @param string $layout layout name
     * @return string
     */
    public function getLayoutLabel($layout)
    {
        if (isset($this->options['layouts'][$layout])) {
            return sprintf(
                '%s - %s',
                $this->options['name'],
                $this->loadLayoutLabels()
                    ? $GLOBALS['_lang'][$this->getLayoutLabelsKey()][$layout]['label']
                    : $layout
            );
        } else {
            return $layout;
        }
    }

    /**
     * Get list of slot identifiers for the given layout
     *
     * string $layout layout name
     * @return string[]
     */
    public function getSlots($layout)
    {
        if (isset($this->options['layouts'][$layout])) {
            return $this->options['layouts'][$layout]['slots'];

        }

        return array();
    }

    /**
     * Get label for the given layout and slot
     *
     * @param string $layout
     * @param string $slot
     * @return string
     */
    public function getSlotLabel($layout, $slot)
    {
        $this->loadLayoutLabels();
        $labelsKey = $this->getLayoutLabelsKey();

        return sprintf(
            '%s - %s - %s',
            $this->options['name'],
            $this->layoutLabelsLoaded ? $GLOBALS['_lang'][$labelsKey][$layout]['label'] : $layout,
            $this->layoutLabelsLoaded ? $GLOBALS['_lang'][$labelsKey][$layout]['slots'][$slot] : $slot
        );
    }

    /**
     * Get boxes for the given layout
     *
     * @param string $layout
     * @return array
     */
    public function getBoxes($layout = self::DEFAULT_LAYOUT)
    {
        if (!isset($this->options['layouts'][$layout])) {
            $layout = static::DEFAULT_LAYOUT;
        }

        $boxes = array();
        $query = DB::query('SELECT id,title,content,slot,page_ids,page_children,class FROM ' . _boxes_table . ' WHERE template=' . DB::val($this->name) . ' AND layout=' . DB::val($layout) . ' AND visible=1' . (!_login ? ' AND public=1' : '') . ' AND level <= ' . _priv_level . ' ORDER BY ord');

        while ($box = DB::row($query)) {
            $boxes[$box['slot']][$box['id']] = $box;
        }

        DB::free($query);

        return $boxes;
    }

    /**
     * Attempt to load layout labels (if they exist and haven't been loaded yet)
     *
     * @return bool whether layout labels are loaded
     */
    protected function loadLayoutLabels()
    {
        if (null !== $this->options['layout.labels'] && !$this->layoutLabelsLoaded) {
            LangPack::register($this->getLayoutLabelsKey(), $this->options['layout.labels']);
            $this->layoutLabelsLoaded = true;
        }

        return $this->layoutLabelsLoaded;
    }

    /**
     * Get layout labels key ($_lang dictionary key)
     *
     * @return string
     */
    protected function getLayoutLabelsKey()
    {
        return $this->type . '.' . $this->name . '.layout_labels';
    }
}
