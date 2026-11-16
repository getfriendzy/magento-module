<?php

declare(strict_types=1);

namespace Friendzy\Widget\Controller\Adminhtml\Connect;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;

/**
 * The Connect page.
 */
class Index extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Friendzy_Widget::config';

    /**
     * @inheritdoc
     */
    public function execute(): ResultInterface
    {
        $page = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $page->setActiveMenu('Friendzy_Widget::connect');
        $page->getConfig()->getTitle()->prepend(__('Friendzy'));

        return $page;
    }
}
