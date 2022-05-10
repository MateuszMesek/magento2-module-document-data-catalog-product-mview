<?php declare(strict_types=1);

namespace MateuszMesek\DocumentDataCatalogProductMview\SubscriptionProvider\Attribute;

use InvalidArgumentException;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\DB\Ddl\Trigger;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Store\Model\ResourceModel\Store as StoreResource;
use Magento\Store\Model\StoreDimensionProvider;
use MateuszMesek\DocumentDataIndexMview\Data\SubscriptionFactory;
use Traversable;

class Generator
{
    private MetadataPool $metadataPool;
    private EavConfig $eavConfig;
    private StoreResource $storeResource;
    private SubscriptionFactory $subscriptionFactory;
    private ProductResource $productResource;

    public function __construct(
        MetadataPool        $metadataPool,
        EavConfig           $eavConfig,
        StoreResource       $storeResource,
        SubscriptionFactory $subscriptionFactory,
        ProductResource     $productResource
    )
    {
        $this->metadataPool = $metadataPool;
        $this->eavConfig = $eavConfig;
        $this->storeResource = $storeResource;
        $this->subscriptionFactory = $subscriptionFactory;
        $this->productResource = $productResource;
    }

    public function generate(string $code, $relation): Traversable
    {
        $metadata = $this->metadataPool->getMetadata(ProductInterface::class);

        $attribute = $this->eavConfig->getAttribute($metadata->getEavEntityType(), $code);

        if (!$attribute) {
            throw new InvalidArgumentException("Attribute '$code' not found");
        }

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

            $condition = null;
            $rows = <<<SQL
                SELECT {$metadata->getIdentifierField()} AS document_id,
                       NULL AS node_path,
                       JSON_SET('{}', '$.$storeDimensionName', store.store_id) AS dimensions
                FROM {$metadata->getEntityTable()}
                CROSS JOIN $storeTable AS store
                    ON store.store_id != 0
                WHERE {$metadata->getLinkField()} = $prefix.{$metadata->getLinkField()}
            SQL;

            if (!$attribute->isStatic()) {
                $condition = "$prefix.attribute_id = {$attribute->getAttributeId()}";
                $rows .= <<<SQL
                    AND IF($prefix.store_id = 0, 1, store.store_id = $prefix.store_id)
                SQL;
            }

            yield $this->subscriptionFactory->create([
                'tableName' => $attribute->getBackendTable(),
                'triggerEvent' => $event,
                'condition' => $condition,
                'rows' => $rows,
            ]);

            $relationTable = $this->productResource->getTable('catalog_product_relation');

            if (in_array($relation, ['both', 'parent'])) {
                $rows = <<<SQL
                    SELECT parent.{$metadata->getIdentifierField()}  AS document_id,
                       NULL AS node_path,
                       JSON_SET('{}', '$.$storeDimensionName', store.store_id) AS dimensions
                    FROM {$metadata->getEntityTable()} AS child
                    INNER JOIN $relationTable AS relation
                        ON relation.child_id = child.{$metadata->getIdentifierField()}
                    INNER JOIN {$metadata->getEntityTable()} AS parent
                        ON parent.{$metadata->getLinkField()} = relation.parent_id
                    CROSS JOIN $storeTable AS store
                        ON store.store_id != 0
                    WHERE child.{$metadata->getLinkField()} = $prefix.{$metadata->getLinkField()}
                SQL;

                if (!$attribute->isStatic()) {
                    $rows .= <<<SQL
                        AND IF($prefix.store_id = 0, 1, store.store_id = $prefix.store_id)
                    SQL;
                }

                yield $this->subscriptionFactory->create([
                    'tableName' => $attribute->getBackendTable(),
                    'triggerEvent' => $event,
                    'condition' => $condition,
                    'rows' => $rows
                ]);
            }

            if (in_array($relation, ['both', 'child'])) {
                $rows = <<<SQL
                    SELECT child.{$metadata->getIdentifierField()} AS document_id,
                       NULL AS node_path,
                       JSON_SET('{}', '$.$storeDimensionName', store.store_id) AS dimensions
                    FROM {$metadata->getEntityTable()} AS parent
                    INNER JOIN $relationTable AS relation
                        ON relation.parent_id = parent.{$metadata->getLinkField()}
                    INNER JOIN {$metadata->getEntityTable()} AS child
                        ON child.{$metadata->getIdentifierField()} = relation.child_id
                    CROSS JOIN $storeTable AS store
                        ON store.store_id != 0
                    WHERE parent.{$metadata->getLinkField()} = $prefix.{$metadata->getLinkField()}
                SQL;

                if (!$attribute->isStatic()) {
                    $rows .= <<<SQL
                        AND IF($prefix.store_id = 0, 1, store.store_id = $prefix.store_id)
                    SQL;
                }

                yield $this->subscriptionFactory->create([
                    'tableName' => $attribute->getBackendTable(),
                    'triggerEvent' => $event,
                    'condition' => $condition,
                    'rows' => $rows
                ]);
            }
        }
    }
}
