<?php

namespace Miu\AgeVerification\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Config extends AbstractHelper
{
    const XML_PATH_MODULE_ENABLE = 'age_verification_tab/general/active';
    const XML_PATH_POPUP_TITLE = 'age_verification_tab/general/title';
    const XML_PATH_POPUP_AGREE_BUTTON_TEXT = 'age_verification_tab/general/agree_button_text';
    const XML_PATH_POPUP_DISAGREE_BUTTON_TEXT = 'age_verification_tab/general/disagree_button_text';
    const XML_PATH_POPUP_CONTENT = 'age_verification_tab/general/content';
    const XML_PATH_POPUP_REDIRECT_URL = 'age_verification_tab/general/redirect';
    const XML_PATH_POPUP_COOKIE_INTERVAL = 'age_verification_tab/general/cookie_interval';
    const XML_PATH_SECURE_CODE = 'age_verification_tab/general/session_code';

    /**
     * @param $config
     * @return mixed
     */
    public function getConfig($config)
    {
        return $this->scopeConfig->getValue($config, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return bool
     */
    public function isEnable()
    {
        return (bool) $this->getConfig(self::XML_PATH_MODULE_ENABLE);
    }

    /**
     * @return mixed
     */
    public function getPopupTitle()
    {
        return $this->getConfig(self::XML_PATH_POPUP_TITLE);
    }

    /**
     * @return mixed
     */
    public function getPopupAgreeButtonText()
    {
        return $this->getConfig(self::XML_PATH_POPUP_AGREE_BUTTON_TEXT);
    }

    /**
     * @return mixed
     */
    public function getPopupDisAgreeButtonText()
    {
        return $this->getConfig(self::XML_PATH_POPUP_DISAGREE_BUTTON_TEXT);
    }

    /**
     * @return mixed
     */
    public function getPopupContent()
    {
        return $this->getConfig(self::XML_PATH_POPUP_CONTENT);
    }

    /**
     * @return mixed
     */
    public function getSecureCode()
    {
        return $this->getConfig(self::XML_PATH_SECURE_CODE);
    }

    /**
     * @return mixed
     */
    public function getCookieInterval()
    {
        $interval = $this->getConfig(self::XML_PATH_POPUP_COOKIE_INTERVAL) ? : 24;
        $cookieHours = $interval/24;
        return floor($cookieHours)*3600;
    }

    /**
     * @return mixed
     */
    public function getRedirectUrl()
    {
        return $this->getConfig(self::XML_PATH_POPUP_REDIRECT_URL);
    }
}