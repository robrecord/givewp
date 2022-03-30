<?php

namespace Give\Framework;

/**
 * This class use to register script.
 * This class internally uses asset information to set script dependencies and version generated by @wordpress/dependency-extraction-webpack-plugin package.
 * It also handles script translation registration.
 *
 * @unreleased
 */
class EnqueueScript
{
    /**
     * @var string
     */
    private $scriptId;

    /**
     * @var string
     */
    private $relativeScriptPath;

    /**
     * @var string
     */
    private $absoluteScriptPath;

    /**
     * @var array
     */
    private $scriptDependencies = [];

    /**
     * @var string
     */
    private $version = '';

    /**
     * @var bool
     */
    private $loadScriptInFooter = false;

    /**
     * @var bool
     */
    private $registerTranslations = false;

    /**
     * @var string
     */
    private $localizeScriptParamName;

    /**
     * @var mixed
     */
    private $localizeScriptParamData;

    /**
     * @var string
     */
    private $pluginDirPath;

    /**
     * @var string
     */
    private $pluginDirUrl;

    /**
     * @var string
     */
    private $textDomain;

    /**
     * @unreleased
     *
     * @param string $scriptId
     * @param string $scriptPath
     * @param string $pluginDirPath
     * @param string $pluginDirUrl
     * @param string $textDomain
     */
    public function __construct($scriptId, $scriptPath, $pluginDirPath, $pluginDirUrl, $textDomain)
    {
        $this->pluginDirPath = trailingslashit($pluginDirPath);
        $this->pluginDirUrl = trailingslashit($pluginDirUrl);
        $this->textDomain = $textDomain;
        $this->scriptId = $scriptId;
        $this->relativeScriptPath = $scriptPath;
        $this->absoluteScriptPath = $this->pluginDirPath . $this->relativeScriptPath;
    }

    /**
     * @unreleased
     *
     * @param string $version
     *
     * @return $this
     */
    public function version($version)
    {
        $this->version = $version;
        return $this;
    }

    /**
     * @unreleased
     * @return $this
     */
    public function loadInFooter()
    {
        $this->loadScriptInFooter = true;
        return $this;
    }

    /**
     * @unreleased
     *
     * @param array $scriptDependencies
     *
     * @return $this
     */
    public function dependencies(array $scriptDependencies)
    {
        $this->scriptDependencies = $scriptDependencies;
        return $this;
    }

    /**
     * @unreleased
     * @return $this
     */
    public function register()
    {
        $scriptUrl = $this->pluginDirUrl . $this->relativeScriptPath;
        $scriptAsset = $this->getAssetFileData();

        wp_register_script(
            $this->scriptId,
            $scriptUrl,
            $scriptAsset['dependencies'],
            $scriptAsset['version'],
            $this->loadScriptInFooter
        );

        if ($this->registerTranslations) {
            wp_set_script_translations(
                $this->scriptId,
                $this->textDomain,
                $this->pluginDirPath . 'languages'
            );
        }

        if ($this->localizeScriptParamData) {
            wp_localize_script(
                $this->scriptId,
                $this->localizeScriptParamName,
                $this->localizeScriptParamData
            );
        }

        return $this;
    }

    /**
     * This function should be called after enqueue or register function.
     *
     * @unreleased
     * @return $this
     */
    public function registerTranslations()
    {
        $this->registerTranslations = true;

        return $this;
    }

    /**
     * This function should be called after enqueue or register function.
     *
     * @param string $jsVariableName
     * @param mixed $data
     *
     * @return $this
     */
    public function registerLocalizeData($jsVariableName, $data)
    {
        $this->localizeScriptParamName = $jsVariableName;
        $this->localizeScriptParamData = $data;

        return $this;
    }

    /**
     * @unreleased
     * @return $this
     */
    public function enqueue()
    {
        if (!wp_script_is($this->scriptId, 'registered')) {
            $this->register();
        }
        wp_enqueue_script($this->scriptId);

        return $this;
    }

    /**
     * @unreleased
     * @return string
     */
    public function getScriptId()
    {
        return $this->scriptId;
    }

    /**
     * @unreleased
     *
     * @return array
     */
    public function getAssetFileData()
    {
        $scriptAssetPath = trailingslashit(dirname($this->absoluteScriptPath))
            . basename($this->absoluteScriptPath, '.js')
            . '.asset.php';
        $scriptAsset = file_exists($scriptAssetPath)
            ? require($scriptAssetPath)
            : ['dependencies' => [], 'version' => $this->version ?: filemtime($this->absoluteScriptPath)];

        if ($this->scriptDependencies) {
            $scriptAsset['dependencies'] = array_merge($this->scriptDependencies, $scriptAsset['dependencies']);
        }

        return $scriptAsset;
    }
}
