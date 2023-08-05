<?php

namespace Smartosc\Customer\Block\Form;

use Magento\Newsletter\Model\Config;
use Smartosc\Customer\Model\CustomerConfigProvider;

class Register extends \Magento\Customer\Block\Form\Register
{
    /**
     * @var CustomerConfigProvider
     */
    protected $customerConfigProvider;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Directory\Helper\Data $directoryHelper
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Framework\App\Cache\Type\Config $configCacheType
     * @param \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollectionFactory
     * @param \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Customer\Model\Url $customerUrl
     * @param CustomerConfigProvider $customerConfigProvider
     * @param array $data
     * @param Config|null $newsLetterConfig
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Directory\Helper\Data $directoryHelper,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Framework\App\Cache\Type\Config $configCacheType,
        \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollectionFactory,
        \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Model\Url $customerUrl,
        CustomerConfigProvider $customerConfigProvider,
        array $data = [],
        Config $newsLetterConfig = null
    ) {
        parent::__construct(
            $context,
            $directoryHelper,
            $jsonEncoder,
            $configCacheType,
            $regionCollectionFactory,
            $countryCollectionFactory,
            $moduleManager,
            $customerSession,
            $customerUrl,
            $data,
            $newsLetterConfig
        );
        $this->customerConfigProvider = $customerConfigProvider;
    }

    /**
     * @param $path
     * @return string|null
     */
    public function prepareLabelAdditionalField($path): ?string
    {
        $labelConfig = $this->getConfig('acc_crm/crm_field_register/'. $path . '_label');
        $linkConfig = $this->getConfig('acc_crm/crm_field_register/'. $path . '_link');
        if (!str_contains($linkConfig, 'http')) {
            $linkConfig = $this->getBaseUrl() . $linkConfig;
        }
        $labelConfig = str_replace('{', '<a href="'. $linkConfig .'">', $labelConfig);
        return str_replace('}', '</a>', $labelConfig);
    }

    /**
     * @param $path
     * @return string|null
     */
    public function prepareLabelAdditionalLink($path): ?string
    {
        $linkConfig = $this->getConfig('acc_crm/crm_field_register/'. $path . '_link');
        if (!str_contains($linkConfig, 'http')) {
            $linkConfig = $this->getBaseUrl() . $linkConfig;
        }
        return $linkConfig;
    }
}
