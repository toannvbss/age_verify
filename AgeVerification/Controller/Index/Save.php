<?php /** @noinspection PhpUndefinedMethodInspection */

namespace Miu\AgeVerification\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Session\SessionManagerInterface;
use Miu\AgeVerification\Helper\Config;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\PublicCookieMetadata;
use Magento\Framework\Stdlib\CookieManagerInterface;

class Save extends Action
{
    /**
     * @var JsonFactory
     */
    protected $_resultJsonFactory;

    /**
     * @var SessionManagerInterface
     */
    protected $_coreSession;

    /**
     * @var Config
     */
    protected $helper;

    /**
     * @var \Magento\Framework\Stdlib\CookieManagerInterface
     */
    protected $_cookieManager;

    /**
     * @var \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory
     */
    protected $_cookieMetadataFactory;

    /**
     * Save constructor.
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param SessionManagerInterface $coreSession
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        SessionManagerInterface $coreSession,
        Config $helper,
        CookieManagerInterface $cookieManager,
        CookieMetadataFactory $cookieMetadataFactory        
    ) {
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_coreSession = $coreSession;
        $this->helper = $helper;
        $this->_cookieManager = $cookieManager;
        $this->_cookieMetadataFactory = $cookieMetadataFactory;        
        parent::__construct($context);
    }

    /**
     * Get data from cookie set in remote address
     *
     * @return value
     */
    public function get()
    {
        return $this->_cookieManager->getCookie('ageverification');
    }

    /**
     * Set data to cookie in remote address
     *
     * @param [string] $value    [value of cookie]
     * @param integer  $duration [duration for cookie]
     *
     * @return void
     */
    public function set($value, $duration = 86400)
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

    /**
     * delete cookie remote address
     *
     * @return void
     */
    public function delete()
    {
        $this->_cookieManager->deleteCookie(
            'ageverification',
            $this->_cookieMetadataFactory
                ->createCookieMetadata()
                ->setPath($this->_coreSession->getCookiePath())
                ->setDomain($this->_coreSession->getCookieDomain())
        );
    }    

    /**
     * @return Json
     */
    public function execute()
    {
        $data = $this->getRequest()->getParams();
        $result = $this->_resultJsonFactory->create();
        if($data['type'] == 'true') {
            $this->set(1,$this->helper->getCookieInterval());
            $result->setData(['success' => 200]);
        } else {
            $this->delete();
            $result->setData(['error' => 202]);
        }
        return $result;
    }

    /**
     * Set value in session
     */
    public function setSecretValue()
    {
        $this->_coreSession->start();
        $this->_coreSession->setAgeVerificationCode(
            $this->helper->getSecureCode()
        );
    }

    /**
     * @return mixed
     */
    public function getSecretValue()
    {
        $this->_coreSession->start();
        return $this->_coreSession->getAgeVerificationCode();
    }

    /**
     * @return mixed
     */
    public function unSecretValue()
    {
        $this->_coreSession->start();
        return $this->_coreSession->unsAgeVerificationCode();
    }
}
