<?php

namespace DNAFactory\BancaSellaProIframe\Helper;

use DNAFactory\BancaSellaProIframe\Api\AxerveConfigurationInterface;
use DNAFactory\BancaSellaProIframe\Exception\AxerveInactiveException;
use DNAFactory\BancaSellaProIframe\Exception\OrderAlreadyPaidException;
use DNAFactory\BancaSellaProIframe\Exception\OrderWrongPaymentMethodException;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Url;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;

class Data extends AbstractHelper
{
    const ORDER_ID_FIELD = 'o_key';

    const IFRAME_PAYMENT_METHOD = 'easynolo_bancasellapro';

    const IFRAME_PAYMENT_PATH = 'bancasellaproiframe/iframe/payment';
    const IFRAME_PATH = 'bancasellaproiframe/iframe/index';

    const ORDER_STATUS_PENDING = 'pending';

    /**
     * @var string
     */
    public string $errorPath = 'checkout/cart';
    /**
     * @var string
     */
    public string $successPath = 'checkout/onepage/success';
    /**
     * @var RequestInterface
     */
    protected RequestInterface $request;
    /**
     * @var EncryptorInterface
     */
    protected EncryptorInterface $encryptor;
    /**
     * @var CookieManagerInterface
     */
    protected CookieManagerInterface $cookieManager;
    /**
     * @var ManagerInterface
     */
    protected ManagerInterface $messageManager;
    /**
     * @var Url
     */
    protected Url $url;
    /**
     * @var ResultFactory
     */
    protected ResultFactory $resultFactory;
    /**
     * @var AxerveConfigurationInterface
     */
    protected AxerveConfigurationInterface $axerveConfiguration;

    public function __construct(
        AxerveConfigurationInterface $axerveConfiguration,
        ResultFactory $resultFactory,
        Url $url,
        ManagerInterface $messageManager,
        CookieManagerInterface $cookieManager,
        EncryptorInterface $encryptor,
        RequestInterface $request,
        Context $context
    ) {

        parent::__construct($context);
        $this->request = $request;
        $this->encryptor = $encryptor;
        $this->cookieManager = $cookieManager;
        $this->messageManager = $messageManager;
        $this->url = $url;
        $this->resultFactory = $resultFactory;
        $this->axerveConfiguration = $axerveConfiguration;
    }

    /**
     * @param bool $decrypted
     * @return mixed|string
     */
    public function getCurrentOKey($decrypted = true)
    {
        $oKey = $this->request->getParam('o_key');

        if (!$decrypted) {
            return $oKey;
        }

        return $this->encryptor->decrypt($oKey);
    }

    /**
     * @param $data
     * @return string
     */
    public function encrypt($data)
    {
        return $this->encryptor->encrypt($data);
    }

    /**
     * @param $data
     * @return string
     */
    public function decrypt($data)
    {
        return $this->encryptor->decrypt($data);
    }

    /**
     * @param OrderInterface $order
     * @return bool
     */
    public function validateOrder(OrderInterface $order)
    {
        // Only pending and gestpay orders

        if (!$order->getPayment() || $order->getPayment()->getMethod() !== self::IFRAME_PAYMENT_METHOD) {
            throw new OrderWrongPaymentMethodException();
        }

        if (!($order->getState() == Order::STATE_NEW && $order->getStatus() == self::ORDER_STATUS_PENDING)) {
            throw new OrderAlreadyPaidException();
        }

        if ($order->getPayment()->getBaseAmountPaid() > 0) {
            throw new OrderAlreadyPaidException();
        }

        return true;
    }

    public function isIframeActive()
    {
        if (!$this->axerveConfiguration->isActive()) {
            throw new AxerveInactiveException();
        }

        return true;
    }

    /**
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Stdlib\Cookie\FailureToSendException
     */
    public function cleanCookie()
    {
        $this->cookieManager->deleteCookie('encString');
        $this->cookieManager->deleteCookie('TransKey');
        $this->cookieManager->deleteCookie('shopLogin');
    }

    /**
     * @return CookieManagerInterface
     */
    public function getCookieManager()
    {
        return $this->cookieManager;
    }

    /**
     * @param string $path
     * @param array $data
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function redirect(string $path, $data = [])
    {
        $redirect = $this->prepareRedirect();
        $redirect->setUrl($this->url->getUrl($path, $data));

        return $redirect;
    }

    /**
     * @param array $data
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function redirectToPayment($data = [])
    {
        return $this->redirect(self::IFRAME_PAYMENT_PATH, $data);
    }

    /**
     * @param null $errorMessage
     * @return \Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Stdlib\Cookie\FailureToSendException
     */
    public function redirectWithError($errorMessage = null)
    {
        $this->cleanCookie();
        $this->messageManager->addErrorMessage($errorMessage);

        $redirect = $this->prepareRedirect();
        $redirect->setUrl($this->url->getUrl($this->errorPath));
        return $redirect;
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    protected function prepareRedirect()
    {
        $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $redirect->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0', true);
        $redirect->setHttpResponseCode(302); // Prevent Browser Caching, don't remove
        return $redirect;
    }

    /**
     * @return string
     */
    public function getMerchantId()
    {
        return $this->axerveConfiguration->getMerchantId();
    }

    /**
     * @return string
     */
    public function getUrlWsdl()
    {
        return $this->axerveConfiguration->getUrlWsdl();
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->axerveConfiguration->getCurrency();
    }

    /**
     * @return bool
     */
    public function isTest()
    {
        return $this->axerveConfiguration->isTest();
    }

    public function getApiKey()
    {
        return $this->axerveConfiguration->getApiKey();
    }

    public function generateUrlToIframe($orderId)
    {
        return $this->url->getUrl(self::IFRAME_PATH, [
            self::ORDER_ID_FIELD => $this->encrypt($orderId)
        ]);
    }
}
