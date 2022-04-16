<?php declare(strict_types=1);

namespace MateuszMesek\DocumentDataCatalogProductMview\SubscriptionProvider;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Eav\Model\Config;
use Magento\Framework\EntityManager\MetadataPool;
use MateuszMesek\DocumentDataCatalogProductMview\SubscriptionProvider\Attribute\Generator;
use MateuszMesek\DocumentDataEavApi\AttributeValidatorInterface;
use MateuszMesek\DocumentDataIndexMviewApi\SubscriptionProviderInterface;
use Traversable;

class Attribute implements SubscriptionProviderInterface
{
    private MetadataPool $metadataPool;
    private Config $config;
    private AttributeValidatorInterface $attributeValidator;

    public function __construct(
        MetadataPool $metadataPool,
        Config $config,
        AttributeValidatorInterface $attributeValidator
    )
    {
        $this->metadataPool = $metadataPool;
        $this->config = $config;
        $this->attributeValidator = $attributeValidator;
    }

    public function get(array $context): Traversable
    {
        $metadata = $this->metadataPool->getMetadata(ProductInterface::class);

        /** @var AttributeInterface[] $attributes */
        $attributes = $this->config->getEntityAttributes($metadata->getEavEntityType());

        foreach ($attributes as $attribute) {
            if (!$this->attributeValidator->validate($attribute)) {
                continue;
            }

            $id = "attribute_{$metadata->getEavEntityType()}_{$attribute->getAttributeCode()}";

            yield $attribute->getAttributeCode() => [
                $id => [
                    'id' => $id,
                    'type' => Generator::class,
                    'arguments' => [
                        $attribute->getAttributeCode(),
                        'both'
                    ]
                ]
            ];
        }
    }
}
