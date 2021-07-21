<?php

namespace DNAFactory\BancaSellaProIframe\Exception;

class AxerveInactiveException extends \Magento\Framework\Exception\LocalizedException
{
    public function __construct(
        \Exception $cause = null,
        $code = 0
    ) {
        $phrase = __('Iframe payment is not enabled.');
        parent::__construct($phrase, $cause, $code);
    }
}
