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
 * Connect, both steps. The challenge waits in the admin session; the password is never kept.
 */
class Post extends Action implements HttpPostActionInterface
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
        $session = $this->_getSession();
        $request = $this->getRequest();
        // The body only: a password or code in an address would land in the access log.
        $code = trim((string) $request->getPostValue('code'));
        $challenge = (string) $session->getData('friendzy_challenge');
        if ($code !== '' && $challenge !== '') {
            $storeId = (int) $session->getData('friendzy_store');
            $result = $this->connection->answer($storeId, $challenge, $code);
        } else {
            $storeId = (int) $request->getPostValue('store');
            $email = trim((string) $request->getPostValue('email'));
            $result = $this->connection->connect($storeId, $email, (string) $request->getPostValue('password'));
        }

        if (isset($result['challenge'])) {
            $session->setData('friendzy_challenge', $result['challenge']);
            $session->setData('friendzy_store', $storeId);
        } elseif (isset($result['site'])) {
            $this->forget();
            $this->messageManager->addSuccessMessage(__('Connected. Friendzy is on this store view, but your shoppers do not see it yet. Once Friendzy has checked this store view, turn on Live in your Friendzy admin and the button appears.'));
        } else {
            $this->forget();
            $this->messageManager->addErrorMessage($result['error'] ?? __('That did not work. Try again.'));
        }

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('friendzy/connect/index');
    }

    /**
     * Connect is over, one way or the other.
     *
     * @return void
     */
    private function forget(): void
    {
        $this->_getSession()->unsetData('friendzy_challenge');
        $this->_getSession()->unsetData('friendzy_store');
    }
}
