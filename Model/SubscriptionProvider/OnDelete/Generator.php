<?php declare(strict_types=1);

namespace MateuszMesek\DocumentDataCatalogProductMview\Model\SubscriptionProvider\OnDelete;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\DB\Ddl\Trigger;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Store\Model\ResourceModel\Store as StoreResource;
use Magento\Store\Model\StoreDimensionProvider;
use MateuszMesek\DocumentDataIndexMview\Model\Data\SubscriptionFactory;
use Traversable;

class Generator
{
    public function __construct(
        private readonly MetadataPool        $metadataPool,
        private readonly StoreResource       $storeResource,
        private readonly SubscriptionFactory $subscriptionFactory
    )
    {
    }

    public function generate(): Traversable
    {
        $metadata = $this->metadataPool->getMetadata(ProductInterface::class);

        $storeTable = $this->storeResource->getMainTable();
        $storeDimensionName = StoreDimensionProvider::DIMENSION_NAME;

        foreach (Trigger::getListOfEvents() as $event) {
            if ($event !== Trigger::EVENT_DELETE) {
                continue;
            }

            yield $this->subscriptionFactory->create([
                'tableName' => $metadata->getEntityTable(),
                'triggerEvent' => $event,
                'rows' => <<<SQL
                    SELECT OLD.{$metadata->getIdentifierField()} AS document_id,
                           NULL AS node_path,
                           JSON_SET('{}', '$.$storeDimensionName', store.store_id) AS dimensions
                    FROM $storeTable AS store
                    WHERE store.store_id != 0
                SQL
            ]);
        }
    }
}
