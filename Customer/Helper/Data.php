<?php

namespace Smartosc\Customer\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;

/**
 * Class Data
 *
 * @package Smartosc\Customer\Helper
 */
class Data extends AbstractHelper
{
    /**
     * @var CookieManagerInterface
     */
    protected $_cookieManager;

    /**
     * @var CookieMetadataFactory
     */
    protected $_cookieMetadataFactory;

    /**
     * @var SessionManagerInterface
     */
    protected $_coreSession;

    /**
     * Data constructor.
     *
     * @param Context                 $context
     * @param SessionManagerInterface $coreSession
     * @param CookieManagerInterface  $cookieManager
     * @param CookieMetadataFactory   $cookieMetadataFactory
     */
    public function __construct(
        Context $context,
        SessionManagerInterface $coreSession,
        CookieManagerInterface $cookieManager,
        CookieMetadataFactory $cookieMetadataFactory
    ) {
        $this->_coreSession = $coreSession;
        $this->_cookieManager = $cookieManager;
        $this->_cookieMetadataFactory = $cookieMetadataFactory;
        parent::__construct($context);
    }

    public function setAgeCookie($value, $duration = 86400)
    {
        $metadata = $this->_cookieMetadataFactory
            ->createPublicCookieMetadata()
            ->setDuration($duration)
            ->setPath($this->_coreSession->getCookiePath())
            ->setDomain($this->_coreSession->getCookieDomain());

        $this->_cookieManager->setPublicCookie(
            'ageverification',
            $value,
            $metadata
        );
    }
}
