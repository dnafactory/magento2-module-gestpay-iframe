<?php

namespace DNAFactory\BancaSellaProIframe\Block;

use DNAFactory\BancaSellaProIframe\Api\AxerveConfigurationInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\View\Element\Template;

class Iframe extends Template
{
    protected AxerveConfigurationInterface $axerveConfiguration;
    protected RequestInterface $request;
    protected CookieManagerInterface $cookieManager;

    public function __construct(
        AxerveConfigurationInterface $axerveConfiguration,
        RequestInterface $request,
        CookieManagerInterface $cookieManager,
        Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->axerveConfiguration = $axerveConfiguration;
        $this->request = $request;
        $this->cookieManager = $cookieManager;
    }

    public function getTansKey()
    {
        return $this->cookieManager->getCookie('TransKey', null);
    }

    public function getPaRes()
    {
        return $this->getRequest()->getParam('PaRes', null);
    }

    public function getJsScript()
    {
        if ($this->axerveConfiguration->isTest()) {
            return "https://sandbox.gestpay.net/Pagam/JavaScript/js_GestPay.js";
        }

        return "https://ecomm.sella.it/Pagam/JavaScript/js_GestPay.js";
    }

    public function getAuthUrl()
    {
        if ($this->axerveConfiguration->isTest()) {
            return "https://sandbox.gestpay.net/pagam/pagam3d.aspx";
        }

        return "https://ecomm.sella.it/pagam/pagam3d.aspx";
    }

    public function getShopLogin()
    {
        return $this->axerveConfiguration->getMerchantId();
    }

    public function getEncodingString()
    {
        return $this->cookieManager->getCookie('encString', null);
    }

    public function getCurrency()
    {
        return $this->axerveConfiguration->getCurrency();
    }

    public function cleanCookie()
    {
        $this->cookieManager->deleteCookie('encString');
        $this->cookieManager->deleteCookie('TransKey');
        $this->cookieManager->deleteCookie('shopLogin');
    }

    public function getResponseUrl()
    {
        return $this->getUrl('bancasellaproiframe/iframe/response');
    }
}
