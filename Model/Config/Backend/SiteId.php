<?php

declare(strict_types=1);

namespace Friendzy\Widget\Model\Config\Backend;

use Friendzy\Widget\Model\SiteId as CleanSiteId;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;

/**
 * Keeps only the id of what is pasted into the setting, and refuses anything that holds none.
 */
class SiteId extends Value
{
    /**
     * @var CleanSiteId
     */
    private $siteId;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param CleanSiteId $siteId
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        CleanSiteId $siteId,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->siteId = $siteId;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * @inheritdoc
     */
    public function beforeSave(): self
    {
        $value = trim((string) $this->getValue());
        $clean = $this->siteId->clean($value);
        if ($value !== '' && $clean === '') {
            throw new LocalizedException(__('That is not a Friendzy site ID. It looks like site-abcdefghijkl.'));
        }
        $this->setValue($clean);

        return parent::beforeSave();
    }
}
