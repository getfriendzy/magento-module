<?php

declare(strict_types=1);

namespace Friendzy\Widget\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UninstallInterface;

/**
 * bin/magento module:uninstall: leaves none of the module's settings behind.
 */
class Uninstall implements UninstallInterface
{
    /**
     * @inheritdoc
     */
    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context): void
    {
        $setup->startSetup();
        $setup->getConnection()->delete($setup->getTable('core_config_data'), ['path LIKE ?' => 'friendzy/%']);
        $setup->endSetup();
    }
}
