<?php

namespace DNAFactory\BancaSellaProIframe\Configuration;

use DNAFactory\BancaSellaProIframe\Api\AxerveConfigurationInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class AxerveConfiguration extends AbstractHelper implements AxerveConfigurationInterface
{
    const AXERVE_IS_ACTIVE = 'payment/easynolo_bancasellapro/active';
    const AXERVE_MERCHANT_ID = 'payment/easynolo_bancasellapro/merchant_id';
    const AXERVE_API_KEY = 'payment/easynolo_bancasellapro/api_key';
    const AXERVE_IS_TEST = 'payment/easynolo_bancasellapro/debug';
    const AXERVE_CURRENCY = 'payment/easynolo_bancasellapro/currency';
    const AXERVE_URL_LIVE_WSDL = 'payment/easynolo_bancasellapro/url_live_wsdl';
    const AXERVE_URL_TEST_WSDL = 'payment/easynolo_bancasellapro/url_test_wsdl';

    protected StoreManagerInterface $storeManager;
    protected WriterInterface $configWriter;

    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        WriterInterface $configWriter
    ) {
        parent::__construct($context);
        $this->storeManager = $storeManager;
        $this->configWriter = $configWriter;
    }

    public function isActive($scopeConfig = ScopeInterface::SCOPE_STORE, $scopeCode = null): bool
    {
        return (bool) $this->scopeConfig->getValue(self::AXERVE_IS_ACTIVE, $scopeConfig, $scopeCode);
    }

    public function isTest($scopeConfig = ScopeInterface::SCOPE_STORE, $scopeCode = null): bool
    {
        return (bool) $this->scopeConfig->getValue(self::AXERVE_IS_TEST, $scopeConfig, $scopeCode);
    }

    public function getMerchantId($scopeConfig = ScopeInterface::SCOPE_STORE, $scopeCode = null): string
    {
        return (string) $this->scopeConfig->getValue(self::AXERVE_MERCHANT_ID, $scopeConfig, $scopeCode);
    }

    public function getApiKey($scopeConfig = ScopeInterface::SCOPE_STORE, $scopeCode = null): string
    {
        return (string) $this->scopeConfig->getValue(self::AXERVE_API_KEY, $scopeConfig, $scopeCode);
    }

    public function getCurrency($scopeConfig = ScopeInterface::SCOPE_STORE, $scopeCode = null): string
    {
        return (string) $this->scopeConfig->getValue(self::AXERVE_CURRENCY, $scopeConfig, $scopeCode);
    }

    public function getUrlLiveWsdl($scopeConfig = ScopeInterface::SCOPE_STORE, $scopeCode = null): string
    {
        return (string) $this->scopeConfig->getValue(self::AXERVE_URL_LIVE_WSDL, $scopeConfig, $scopeCode);
    }

    public function getUrlTestWsdl($scopeConfig = ScopeInterface::SCOPE_STORE, $scopeCode = null): string
    {
        return (string) $this->scopeConfig->getValue(self::AXERVE_URL_TEST_WSDL, $scopeConfig, $scopeCode);
    }

    public function getUrlWsdl($scopeConfig = ScopeInterface::SCOPE_STORE, $scopeCode = null): string
    {
        if ($this->isTest($scopeConfig, $scopeCode)) {
            return $this->getUrlTestWsdl($scopeConfig, $scopeCode);
        }

        return $this->getUrlLiveWsdl($scopeConfig, $scopeCode);
    }
}
