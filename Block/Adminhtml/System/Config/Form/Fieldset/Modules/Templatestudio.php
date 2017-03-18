<?php
/**
 * Copyright Â© 2016 Templatestudio UK. All rights reserved.
 */

namespace Templatestudio\Core\Block\Adminhtml\System\Config\Form\Fieldset\Modules;

class Templatestudio extends \Magento\Config\Block\System\Config\Form\Fieldset
{

    /**
     * Vendor config
     * 
     * @var \Templatestudio\Core\Model\Vendor\Config
     */
    protected $vendorConfig;

    /**
     * Templatestudio module list
     * 
     * @var \Templatestudio\Core\App\Module\ModuleList
     */
    protected $moduleList;

    /**
     * Extension model factory
     * 
     * @var \Templatestudio\Core\Model\ExtensionFactory
     */
    protected $extensionFactory;

    /**
     * Field factory
     * 
     * @var \Magento\Config\Block\System\Config\Form\Field\Factory
     */
    protected $fieldFactory;

    /**
     * Magento metadata
     * 
     * @var \Magento\Framework\App\ProductMetadataInterface
     */
    protected $productMetadata;

    /**
     * Module conflict checker
     * 
     * @var \Magento\Framework\Module\ConflictChecker $conflictChecker
     */
    protected $conflictChecker;

    /**
     * Constructor
     * 
     * @param \Magento\Backend\Block\Context $context
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\Framework\View\Helper\Js $jsHelper
     * @param \Templatestudio\Core\Model\Vendor\Config $vendorConfig
     * @param \Templatestudio\Core\App\Module\ModuleList $moduleList
     * @param \Templatestudio\Core\Model\ExtensionFactory $extensionFactory
     * @param \Magento\Config\Block\System\Config\Form\Field\Factory $fieldFactory
     * @param \Magento\Framework\App\ProductMetadataInterface $productMetadata
     * @param \Magento\Framework\Module\ConflictChecker $conflictChecker
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\View\Helper\Js $jsHelper,
        \Templatestudio\Core\Model\Vendor\Config $vendorConfig,
        \Templatestudio\Core\App\Module\ModuleList $moduleList,
        \Templatestudio\Core\Model\ExtensionFactory $extensionFactory,
        \Magento\Config\Block\System\Config\Form\Field\Factory $fieldFactory,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Framework\Module\ConflictChecker $conflictChecker,
        array $data = []
    ) {
        parent::__construct($context, $authSession, $jsHelper, $data);
        $this->vendorConfig = $vendorConfig;
        $this->moduleList = $moduleList;
        $this->extensionFactory = $extensionFactory;
        $this->fieldFactory = $fieldFactory;
        $this->productMetadata = $productMetadata;
        $this->conflictChecker = $conflictChecker;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $extensionModel = $this->extensionFactory->create();
        $extensionModel->checkUpdate();

        $modules = $this->moduleList->getAll();
        $modulesData = $extensionModel->getModulesData();

        $fieldRenderer = $this->fieldFactory->create();
        $fieldRenderer->setForm($element->getForm());
        $fieldRenderer->setConfigData($element->getConfigData());

        $coreModule = $this->getModuleName();
        $noModules = true;

        if (! empty($modules)) {
            $conflicts = $this->conflictChecker->checkConflictsWhenEnableModules($this->moduleList->getNames());

            foreach ($modules as $moduleConfig) {
                $moduleName = $moduleConfig['name'];

                if ($coreModule === $moduleName) {
                    continue;
                }

                $label = ltrim(strstr($moduleName, '_'), '_');
                $version = ! empty($moduleConfig['setup_version']) ? $moduleConfig['setup_version'] : '&mdash;';
                $class = 'module-success';
                $url = $this->getVendorUrl();
                $tooltip = null;

                if (is_array($modulesData) and array_key_exists($moduleName, $modulesData)) {
                    $module = $modulesData[$moduleName];

                    if (! empty($module['version']) and version_compare($version, $module['version'], 'lt')) {
                        $class = 'module-notice';
                        $tooltip = __('Update available');
                    }

                    if (! empty($module['display_name'])) {
                        $label = $module['display_name'];
                    } elseif (! empty($module['name'])) {
                        $label = $module['name'];
                    }

                    if (! empty($module['url'])) {
                        $url = $module['url'];
                    }

                    unset($module);
                }

                $label = '<a href="' . $url . '" '.
                    'title="'. $this->escapeHtml($this->stripTags($label)) . '" onclick="this.target=\'_blank\'">' . $label . '</a>';

                if (array_key_exists($moduleName, $conflicts)) {
                    $class = 'module-warning';
                    $tooltip = implode("\n", $conflicts[$moduleName]);
                }

                $element->addField($moduleName, 'note', [
                    'label' => '<i class="module ' . $class . '"></i>' . $label,
                    'text' => $version,
                    'tooltip' => $tooltip
                ])->setRenderer($fieldRenderer);

                $noModules = false;
            }
        }

        if (true === $noModules) {
            $element->addField('no-extensions', 'note', [
                'label' => '',
                'text' => __('There are no Template Studio extensions installed.')
            ])->setRenderer($fieldRenderer);
        }

        return parent::render($element);
    }

    /**
     * {@inheritdoc}
     */
    protected function _getHeaderHtml($element)
    {
        $html = parent::_getHeaderHtml($element);

        $html .= '
        <a id="templatestudio-core-quote" href="'. $this->getQuoteUrl() . '"'.
            ' title="' . __('Get in touch') . '" target="_blank">
            <img src="' . $this->getViewFileUrl('Templatestudio_Core::images/templatestudio-quote.jpg') . '" '.
                'alt="' . __('Get in touch') . '" />
        </a>';

        return $html;
    }

    /**
     * Retrieve quote URL
     * 
     * @return string
     */
    protected function getQuoteUrl()
    {
        return $this->vendorConfig->getQuoteUrl();
    }

    /**
     * Retrieve vendor/developer URL
     * 
     * @return string
     */
    protected function getVendorUrl()
    {
        return $this->vendorConfig->getVendorUrl();
    }
}