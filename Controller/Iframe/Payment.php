<?php

namespace DNAFactory\BancaSellaProIframe\Controller\Iframe;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;

class Payment extends Action implements HttpGetActionInterface, HttpPostActionInterface
{
    /**
     * @inheritdoc
     */
    public function execute()
    {
        return $this->resultFactory
                    ->create(ResultFactory::TYPE_PAGE)
                    ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0', true);
    }
}
