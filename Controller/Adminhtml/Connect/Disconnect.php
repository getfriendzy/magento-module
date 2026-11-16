<?php

declare(strict_types=1);

namespace Friendzy\Widget\Controller\Adminhtml\Connect;

use Friendzy\Widget\Model\Connection;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;

/**
 * Takes a store view off Friendzy.
 */
class Disconnect extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Friendzy_Widget::config';

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @param Context $context
     * @param Connection $connection
     */
    public function __construct(Context $context, Connection $connection)
    {
        parent::__construct($context);
        $this->connection = $connection;
    }

    /**
     * @inheritdoc
     */
    public function execute(): ResultInterface
    {
        $this->connection->disconnect((int) $this->getRequest()->getPostValue('store'));
        $this->messageManager->addSuccessMessage(__('Disconnected. Your Friendzy account and site are still there.'));

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('friendzy/connect/index');
    }
}
