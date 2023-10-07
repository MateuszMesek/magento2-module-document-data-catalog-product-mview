<?php declare(strict_types=1);

namespace MateuszMesek\DocumentDataCatalogProductMview\Model\SubscriptionProvider\OnWebsiteChange;

use InvalidArgumentException;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Framework\DB\Ddl\Trigger;
use Magento\Store\Model\ResourceModel\Store as StoreResource;
use Magento\Store\Model\StoreDimensionProvider;
use MateuszMesek\DocumentDataIndexMview\Model\Data\SubscriptionFactory;
use Traversable;

class Generator
{
    public function __construct(
        private readonly StoreResource       $storeResource,
        private readonly ProductResource     $productResource,
        private readonly SubscriptionFactory $subscriptionFactory
    )
    {
    }

    public function generate(): Traversable
    {
        $productWebsiteTable = $this->productResource->getProductWebsiteTable();
        $storeTable = $this->storeResource->getMainTable();
        $storeDimensionName = StoreDimensionProvider::DIMENSION_NAME;

        foreach (Trigger::getListOfEvents() as $event) {
            switch ($event) {
                case Trigger::EVENT_INSERT:
                case Trigger::EVENT_UPDATE:
                    $prefix = 'NEW';
                    break;

                case Trigger::EVENT_DELETE:
                    $prefix = 'OLD';
                    break;

                default:
                    throw new InvalidArgumentException("Trigger event '$event' is unsupported");
            }

            yield $this->subscriptionFactory->create([
                'tableName' => $productWebsiteTable,
                'triggerEvent' => $event,
                'rows' => <<<SQL
                    SELECT $prefix.product_id AS document_id,
                           NULL AS node_path,
                           JSON_SET('{}', '$.$storeDimensionName', store.store_id) AS dimensions
                    FROM $storeTable AS store
                    WHERE store.website_id = $prefix.website_id
                SQL
            ]);
        }
    }
}
