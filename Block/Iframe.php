<?php

namespace DNAFactory\BancaSellaProIframe\Block;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\Http;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Pricing\Helper\Data;
use Magento\Framework\View\Element\Template;
use Magento\Sales\Api\OrderRepositoryInterface;

class Iframe extends Template
{
    const RESPONSE_URL = 'bancasellaproiframe/iframe/response';

    /**
     * @var RequestInterface
     */
    protected RequestInterface $request;
    /**
     * @var FormKey
     */
    protected FormKey $formKey;
    /**
     * @var OrderRepositoryInterface
     */
    protected OrderRepositoryInterface $orderRepository;
    /**
     * @var Data
     */
    protected Data $currency;
    /**
     * @var Http
     */
    protected Http $redirect;
    /**
     * @var ManagerInterface
     */
    protected ManagerInterface $messageManager;
    /**
     * @var \DNAFactory\BancaSellaProIframe\Helper\Data
     */
    protected \DNAFactory\BancaSellaProIframe\Helper\Data $helper;

    public function __construct(
        \DNAFactory\BancaSellaProIframe\Helper\Data $helper,
        Http $redirect,
        ManagerInterface $messageManager,
        Data $currency,
        OrderRepositoryInterface $orderRepository,
        FormKey $formKey,
        RequestInterface $request,
        Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->request = $request;
        $this->formKey = $formKey;
        $this->orderRepository = $orderRepository;
        $this->currency = $currency;
        $this->redirect = $redirect;
        $this->messageManager = $messageManager;
        $this->helper = $helper;
    }

    /**
     * @return string|null
     */
    public function getTansKey()
    {
        return $this->helper->getCookieManager()->getCookie('TransKey', null);
    }

    /**
     * @return mixed
     */
    public function getPaRes()
    {
        return $this->getRequest()->getParam('PaRes', null);
    }

    /**
     * @return string
     */
    public function getJsScript()
    {
        if ($this->helper->isTest()) {
            return "https://sandbox.gestpay.net/Pagam/JavaScript/js_GestPay.js";
        }

        return "https://ecomm.sella.it/Pagam/JavaScript/js_GestPay.js";
    }

    /**
     * @return string
     */
    public function getAuthUrl()
    {
        if ($this->helper->isTest()) {
            return "https://sandbox.gestpay.net/pagam/pagam3d.aspx";
        }

        return "https://ecomm.sella.it/pagam/pagam3d.aspx";
    }

    /**
     * @return string
     */
    public function getShopLogin()
    {
        return $this->helper->getMerchantId();
    }

    /**
     * @return string|null
     */
    public function getEncodingString()
    {
        return $this->helper->getCookieManager()->getCookie('encString', null);
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->helper->getCurrency();
    }

    /**
     * @return string
     */
    public function getResponseUrl()
    {
        return $this->getUrl(self::RESPONSE_URL);
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getFormKey()
    {
        return $this->formKey->getFormKey();
    }

    /**
     * @return int|null
     */
    public function getOrderId()
    {
        return $this->helper->getCurrentOKey();
    }

    /**
     * @return float|string
     */
    public function getAmountToPay()
    {
        try {
            $order = $this->getOrder();
            return $this->currency->currency($order->getGrandTotal());
        } catch (\Exception $exception) {
            return $this->currency->currency(0);
        }
    }

    /**
     * @return bool
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Stdlib\Cookie\FailureToSendException
     */
    public function validateIframe()
    {
        try {
            $order = $this->getOrder();
            return $this->helper->validateOrder($order);
        } catch (\Exception $exception) {
            $this->redirectError($exception->getMessage());
        }

        return true;
    }

    /**
     * @return \Magento\Sales\Api\Data\OrderInterface
     */
    public function getOrder()
    {
        return $this->orderRepository->get($this->getOrderId());
    }

    /**
     * @return mixed|string
     */
    public function getOKey()
    {
        return $this->helper->getCurrentOKey(false);
    }

    /**
     * @param null $errorMessage
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Stdlib\Cookie\FailureToSendException
     */
    protected function redirectError($errorMessage = null)
    {
        $this->helper->cleanCookie();

        $this->messageManager->addErrorMessage($errorMessage);

        $this->redirect->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0', true);
        $this->redirect->setHttpResponseCode(302); // Prevent Browser Caching, don't remove
        $this->redirect->setRedirect($this->_urlBuilder->getUrl($this->helper->errorPath));
    }
}
