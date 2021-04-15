<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_ElasticSearch
 */


namespace Amasty\ElasticSearch\Setup\Operation;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Class RemoveStemmedWordsScheme
 */
class RemoveStemmedWordsScheme
{
    /**
     * @param SchemaSetupInterface $installer
     * @throws \Exception
     */
    public function execute(SchemaSetupInterface $installer)
    {
        $stemmedTableName = $installer->getTable('amasty_elastic_stemmed_word');
        if ($installer->getConnection()->isTableExists($stemmedTableName)) {
            $select = $installer->getConnection()->select()->from($stemmedTableName);
            $stemmedData = $installer->getConnection()->fetchAll($select);
            $insertData = [];
            foreach ($stemmedData as $data) {
                $insertData[] = [
                    'synonym_id' => null,
                    'store_id' => $data['store_id'],
                    'term' => $data['stemmed_word'] . ', ' . $data['words']
                ];
            }

            if ($insertData) {
                $installer->getConnection()->insertOnDuplicate(
                    $installer->getTable('amasty_elastic_synonym'),
                    $insertData
                );
                $installer->getConnection()->dropTable($installer->getTable('amasty_elastic_stemmed_word'));
            }
        }

    }
}
