<?php

namespace Smartosc\Customer\Block\Account\Dashboard;

use Magento\Customer\Model\AttributeMetadataDataProvider;

class Info extends \Magento\Customer\Block\Account\Dashboard\Info
{
    /**
     * @var \Magento\Framework\View\Element\Template\Context
     */
    protected $context;
    /**
     * @var AttributeMetadataDataProvider
     */
    protected $metadataDataProvider;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer
     * @param \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory
     * @param \Magento\Customer\Helper\View $helperView
     * @param AttributeMetadataDataProvider $metadataDataProvider
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        \Magento\Customer\Helper\View $helperView,
        AttributeMetadataDataProvider $metadataDataProvider,
        array $data = []
    ) {
        parent::__construct($context, $currentCustomer, $subscriberFactory, $helperView, $data);
        $this->metadataDataProvider = $metadataDataProvider;
    }

    public function getAttributeLabel($attributeCode, $attributeValue)
    {
        $attribute = $this->metadataDataProvider->getAttribute(
            \Magento\Customer\Model\Customer::ENTITY,
            $attributeCode
        );
        $attribute->getFrontendLabels();
        foreach ($attribute->getOptions() as $option) {
            if ($option->getValue() == $attributeValue) {
                return $option->getLabel();
            }
        }
        return '';
    }
}
