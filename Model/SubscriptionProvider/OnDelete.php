<?php declare(strict_types=1);

namespace MateuszMesek\DocumentDataCatalogProductMview\Model\SubscriptionProvider;

use MateuszMesek\DocumentDataCatalogProductMview\Model\SubscriptionProvider\OnDelete\Generator;
use MateuszMesek\DocumentDataIndexMviewApi\Model\SubscriptionProviderInterface;
use Traversable;

class OnDelete implements SubscriptionProviderInterface
{
    public function get(array $context): Traversable
    {
        yield '*' => [
            'onDelete' => [
                'id' => 'onDelete',
                'type' => Generator::class,
                'arguments' => []
            ]
        ];
    }
}
