<?php declare(strict_types=1);

namespace MateuszMesek\DocumentDataCatalogProductMview\NodeSubscription\Attribute;

use InvalidArgumentException;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Eav\Model\Config;
use Magento\Framework\DB\Ddl\Trigger;
use Magento\Framework\EntityManager\MetadataPool;
use MateuszMesek\DocumentDataIndexMview\Data\SubscriptionFactory;
use Traversable;

class SubscriptionGenerator
{
    private MetadataPool $metadataPool;
    private Config $config;
    private SubscriptionFactory $subscriptionFactory;
    private ProductResource $productResource;

    public function __construct(
        MetadataPool $metadataPool,
        Config $config,
        SubscriptionFactory $subscriptionFactory,
        ProductResource $productResource
    )
    {
        $this->metadataPool = $metadataPool;
        $this->config = $config;
        $this->subscriptionFactory = $subscriptionFactory;
        $this->productResource = $productResource;
    }

    public function generate(string $code, $relation): Traversable
    {
        $metadata = $this->metadataPool->getMetadata(ProductInterface::class);

        $attribute = $this->config->getAttribute($metadata->getEavEntityType(), $code);

        if (!$attribute) {
            throw new InvalidArgumentException("Attribute '$code' not found");
        }

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

            $condition = '';
            $dimensions = "JSON_SET('{}', '$.store_id', 0)";

            if (!$attribute->isStatic()) {
                $condition = "$prefix.attribute_id = {$attribute->getAttributeId()}";
                $dimensions = "JSON_SET('{}', '$.store_id', $prefix.store_id)";
            }

            yield $this->subscriptionFactory->create([
                'tableName' => $attribute->getBackendTable(),
                'triggerEvent' => $event,
                'condition' => $condition,
                'documentId' => "(SELECT {$metadata->getIdentifierField()} FROM {$metadata->getEntityTable()} WHERE {$metadata->getLinkField()} = $prefix.{$metadata->getLinkField()})",
                'dimensions' => $dimensions,
            ]);

            $relationTable = $this->productResource->getTable('catalog_product_relation');

            if (in_array($relation, ['both', 'parent'])) {
                yield $this->subscriptionFactory->create([
                    'tableName' => $attribute->getBackendTable(),
                    'triggerEvent' => $event,
                    'condition' => $condition,
                    'documentId' => <<<SQL
                        (SELECT parent.{$metadata->getIdentifierField()}
                         FROM {$metadata->getEntityTable()} AS child
                         INNER JOIN $relationTable AS relation
                            ON relation.child_id = child.{$metadata->getIdentifierField()}
                         INNER JOIN {$metadata->getEntityTable()} AS parent
                            ON parent.{$metadata->getLinkField()} = relation.parent_id
                         WHERE child.{$metadata->getLinkField()} = $prefix.{$metadata->getLinkField()})
                    SQL,
                    'dimensions' => $dimensions,
                ]);
            }

            if (in_array($relation, ['both', 'child'])) {
                yield $this->subscriptionFactory->create([
                    'tableName' => $attribute->getBackendTable(),
                    'triggerEvent' => $event,
                    'condition' => $condition,
                    'documentId' => <<<SQL
                        (SELECT child.{$metadata->getIdentifierField()}
                         FROM {$metadata->getEntityTable()} AS parent
                         INNER JOIN $relationTable AS relation
                            ON relation.parent_id = parent.{$metadata->getLinkField()}
                         INNER JOIN {$metadata->getEntityTable()} AS child
                            ON child.{$metadata->getIdentifierField()} = relation.child_id
                         WHERE parent.{$metadata->getLinkField()} = $prefix.{$metadata->getLinkField()})
                    SQL,
                    'dimensions' => $dimensions,
                ]);
            }
        }
    }
}
