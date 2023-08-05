<?php /** @noinspection PhpUndefinedMethodInspection */

namespace Miu\AgeVerification\Block;

use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\View\Element\Template;
use Miu\AgeVerification\Helper\Config;
use Magento\Framework\Stdlib\CookieManagerInterface;

class View extends Template
{
    /**
     * @var Config
     */
    protected $helper;

    /**
     * @var SessionManagerInterface
     */
    protected $_coreSession;

    /**
     * @var \Magento\Framework\Stdlib\CookieManagerInterface
     */
    protected $_cookieManager;

    /**
     * View constructor.
     * @param Template\Context $context
     * @param Config $helper
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Config $helper,
        SessionManagerInterface $coreSession,
        CookieManagerInterface $cookieManager,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->_coreSession = $coreSession;
        $this->_cookieManager = $cookieManager;
        parent::__construct($context, $data);
    }

    /**
     * @return Template
     */
    protected function _prepareLayout()
    {
        return parent::_prepareLayout();
    }

    /**
     * @return Config
     */
    public function initHelper()
    {
        return $this->helper;
    }

    /**
     * @return string
     */
    public function getAjaxUrl()
    {
        return $this->getUrl('ageverification/index/save', ['_secure' => true]);
    }

    /**
     * @return mixed
     */
    public function getSecretValue()
    {        
        return $this->_cookieManager->getCookie('ageverification');
    }
}