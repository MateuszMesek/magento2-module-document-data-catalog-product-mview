<?php declare(strict_types=1);

namespace MateuszMesek\DocumentDataCatalogProductMview\SubscriptionProvider;

use MateuszMesek\DocumentDataCatalogProductMview\SubscriptionProvider\OnDelete\Generator;
use MateuszMesek\DocumentDataIndexMviewApi\SubscriptionProviderInterface;
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
