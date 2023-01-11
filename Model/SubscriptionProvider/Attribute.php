<?php declare(strict_types=1);

namespace MateuszMesek\DocumentDataCatalogProductMview\Model\SubscriptionProvider;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Eav\Model\Config;
use Magento\Framework\EntityManager\MetadataPool;
use MateuszMesek\DocumentDataCatalogProductMview\Model\SubscriptionProvider\Attribute\Generator;
use MateuszMesek\DocumentDataEavApi\Model\AttributeValidatorInterface;
use MateuszMesek\DocumentDataIndexMviewApi\Model\SubscriptionProviderInterface;
use Traversable;

class Attribute implements SubscriptionProviderInterface
{
    public function __construct(
        private readonly MetadataPool                $metadataPool,
        private readonly Config                      $config,
        private readonly AttributeValidatorInterface $attributeValidator
    )
    {
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
