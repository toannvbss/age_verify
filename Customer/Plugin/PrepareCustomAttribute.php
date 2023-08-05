<?php

namespace Smartosc\Customer\Plugin;

use Magento\Customer\Model\CustomerExtractor;
use Magento\Framework\App\RequestInterface;
use Smartosc\Customer\Block\Widget\Telephone;

class PrepareCustomAttribute
{
    /**
     * @var Telephone
     */
    protected $telephoneBlock;

    /**
     * @param Telephone $telephoneBlock
     */
    public function __construct(
        Telephone $telephoneBlock
    ) {
        $this->telephoneBlock = $telephoneBlock;
    }

    /**
     * @param CustomerExtractor $customerExtractor
     * @param $customer
     * @param $formCode
     * @param RequestInterface $request
     * @return mixed
     */
    public function afterExtract(
        CustomerExtractor $customerExtractor,
        $customer,
        $formCode,
        RequestInterface $request
    ) {
        foreach ($this->customAttributeCode() as $customAttribute) {
            if ($attributeValue = $request->getParam($customAttribute)) {
                $customer->setCustomAttribute($customAttribute, $attributeValue);
            }
        }
        $prefix = $request->getParam('telephone_prefix');
        if ($prefix && $tel = $request->getParam('telephone')) {
            $telephone = $tel;
            $customer->setTelephone($telephone);
            $customer->setCustomAttribute('crm_telephone', $telephone);
            $customer->setCustomAttribute('customer_telephone', $telephone);
        }
        if ($beverages = $request->getParam('beverages_preference')) {
            $beverageValue = '';
            foreach ($beverages as $beverage) {
                if ($beverageValue) {
                    $beverageValue = $beverageValue . ',' . $beverage;
                } else {
                    $beverageValue = $beverage;
                }
            }
            $customer->setCustomAttribute('beverages_preference', $beverageValue);
        }
        if ($contacts = $request->getParam('contact_interest')) {
            $contactValue = '';
            foreach ($contacts as $contact) {
                if ($contactValue) {
                    $contactValue = $contactValue . ',' . $contact;
                } else {
                    $contactValue = $contact;
                }
            }
            $customer->setCustomAttribute('contact_interest', $contactValue);
        }
        $customer->setCustomAttribute('temporaries_password', $request->getParam('password'));
        $customer->setCustomAttribute('form_code', $formCode);
        $customer->setCustomAttribute('custom_gender', $request->getParam('gender'));
        return $customer;
    }

    /**
     * @return string[]
     */
    private function customAttributeCode(): array
    {
        return [
            'salutation',
            'telephone_prefix',
            'referral_code'
        ];
    }
}
