<?php

declare(strict_types=1);

namespace Friendzy\Widget\ViewModel;

use Friendzy\Widget\Model\Config;
use Magento\Backend\Model\Session;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * The store views for the Connect page, and the store view waiting on a two-factor code.
 */
class ConnectPage implements ArgumentInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Session
     */
    private $session;

    /**
     * @param Config $config
     * @param StoreManagerInterface $storeManager
     * @param Session $session
     */
    public function __construct(Config $config, StoreManagerInterface $storeManager, Session $session)
    {
        $this->config = $config;
        $this->storeManager = $storeManager;
        $this->session = $session;
    }

    /**
     * Every store view with the site it is on.
     *
     * @return array<int, array{id: int, name: string, site: string}>
     */
    public function stores(): array
    {
        $rows = [];
        foreach ($this->storeManager->getStores() as $store) {
            $id = (int) $store->getId();
            $rows[] = ['id' => $id, 'name' => (string) $store->getName(), 'site' => $this->config->siteId($id)];
        }

        return $rows;
    }

    /**
     * The store view whose Connect waits for a code, or 0.
     *
     * @return int
     */
    public function waitingStore(): int
    {
        return (int) $this->session->getData('friendzy_store');
    }

    /**
     * Where this store opens the merchant area.
     *
     * @return string
     */
    public function adminUrl(): string
    {
        return $this->config->adminUrl();
    }
}
