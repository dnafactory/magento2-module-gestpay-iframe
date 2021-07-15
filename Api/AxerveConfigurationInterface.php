<?php

namespace DNAFactory\BancaSellaProIframe\Api;

use Magento\Store\Model\ScopeInterface;

interface AxerveConfigurationInterface
{
    public function isActive($scopeConfig = ScopeInterface::SCOPE_STORE, $scopeCode = null): bool;
    public function isTest($scopeConfig = ScopeInterface::SCOPE_STORE, $scopeCode = null): bool;
    public function getMerchantId($scopeConfig = ScopeInterface::SCOPE_STORE, $scopeCode = null): string;
    public function getApiKey($scopeConfig = ScopeInterface::SCOPE_STORE, $scopeCode = null): string;
    public function getCurrency($scopeConfig = ScopeInterface::SCOPE_STORE, $scopeCode = null): string;
    public function getUrlLiveWsdl($scopeConfig = ScopeInterface::SCOPE_STORE, $scopeCode = null): string;
    public function getUrlTestWsdl($scopeConfig = ScopeInterface::SCOPE_STORE, $scopeCode = null): string;
    public function getUrlWsdl($scopeConfig = ScopeInterface::SCOPE_STORE, $scopeCode = null): string;
}
