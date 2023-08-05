<?php

namespace Smartosc\Customer\Block\Html\Header;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;

class Logo extends \Magento\Theme\Block\Html\Header\Logo
{
    const LOGO_DIR = 'customer_logo/';
    const LOGO_PATH_CONFIG = 'smartosc_customer/login/logo';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\MediaStorage\Helper\File\Storage\Database $fileStorageHelper,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        parent::__construct($context, $fileStorageHelper, $data);
    }

    /**
     * @return bool
     */
    public function isLoginPage()
    {
        return $this->getRequest()->getFullActionName() == "customer_account_login";
    }

    /**
     * @return bool
     */
    public function isForgotPasswordPage()
    {
        return $this->getRequest()->getFullActionName() == "customer_account_forgotpassword";
    }

    /**
     * @return mixed
     */
    public function getCustomerLogoConfig()
    {
        return $this->scopeConfig->getValue(self::LOGO_PATH_CONFIG);
    }

    /**
     * @return false|string
     */
    public function getCustomerLogo()
    {
        $logoUrl = false;
        if ($file = trim($this->getCustomerLogoConfig())) {
            $fileUrl = self::LOGO_DIR . $file;
            $mediaUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
            $logoUrl = $mediaUrl . $fileUrl;
        }
        return $logoUrl;
    }
}
