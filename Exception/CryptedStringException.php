<?php

namespace DNAFactory\BancaSellaProIframe\Exception;

class CryptedStringException extends \Magento\Framework\Exception\LocalizedException
{
    public function __construct(
        \Exception $cause = null,
        $code = 0
    ) {
        $phrase = __('Invalid crypted string.');
        parent::__construct($phrase, $cause, $code);
    }
}
