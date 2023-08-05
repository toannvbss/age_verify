<?php

namespace Smartosc\Customer\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Store\Model\ScopeInterface;

class CustomerConfigProvider extends DataObject
{
    const CRM_FIELD_REGISTER = 'acc_crm/crm_field_register';
    const CRM_FIELD_INFO = 'acc_crm/crm_field_info';
    const ENABLE_CHANGE_EMAIL = 'enable_change_email';
    const PERSONAL_DATA_LABEL = 'personal_data_label';
    const PERSONAL_DATA_LINK= 'personal_data_link';
    const TERM_CONDITIONS_LABEL = 'term_conditions_label';
    const TERM_CONDITIONS_LINK = 'term_conditions_link';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * ConfigProvider constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param array $data
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        array $data = []
    ) {
        parent::__construct($data);
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param int|null $scopeCode
     * @param string $scopeConfig
     * @return mixed
     */
    public function getFieldPersonalDataLabel(int $scopeCode = null, string $scopeConfig = ScopeInterface::SCOPE_STORE)
    {
        return $this->scopeConfig->getValue(
            self::CRM_FIELD_REGISTER . '/' . self::PERSONAL_DATA_LABEL,
            $scopeConfig,
            $scopeCode
        );
    }
    /**
     * @param int|null $scopeCode
     * @param string $scopeConfig
     * @return mixed
     */
    public function getFieldPersonalDataLink(int $scopeCode = null, string $scopeConfig = ScopeInterface::SCOPE_STORE)
    {
        return $this->scopeConfig->getValue(
            self::CRM_FIELD_REGISTER . '/' . self::PERSONAL_DATA_LINK,
            $scopeConfig,
            $scopeCode
        );
    }

    /**
     * @param int|null $scopeCode
     * @param string $scopeConfig
     * @return mixed
     */
    public function getTermConditionsLabel(int $scopeCode = null, string $scopeConfig = ScopeInterface::SCOPE_STORE)
    {
        return $this->scopeConfig->getValue(
            self::CRM_FIELD_REGISTER . '/' . self::TERM_CONDITIONS_LABEL,
            $scopeConfig,
            $scopeCode
        );
    }
    /**
     * @param int|null $scopeCode
     * @param string $scopeConfig
     * @return mixed
     */
    public function getTermConditionsLink(int $scopeCode = null, string $scopeConfig = ScopeInterface::SCOPE_STORE)
    {
        return $this->scopeConfig->getValue(
            self::CRM_FIELD_REGISTER . '/' . self::TERM_CONDITIONS_LINK,
            $scopeConfig,
            $scopeCode
        );
    }

    /**
     * @param int|null $scopeCode
     * @param string $scopeConfig
     * @return mixed
     */
    public function isChangeEmailCustomer(int $scopeCode = null, string $scopeConfig = ScopeInterface::SCOPE_STORE)
    {
        return $this->scopeConfig->getValue(
            self::CRM_FIELD_INFO . '/' . self::ENABLE_CHANGE_EMAIL,
            $scopeConfig,
            $scopeCode
        );
    }
}
