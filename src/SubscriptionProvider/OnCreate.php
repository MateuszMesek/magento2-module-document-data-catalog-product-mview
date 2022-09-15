<?php declare(strict_types=1);

namespace MateuszMesek\DocumentDataCatalogProductMview\SubscriptionProvider;

use MateuszMesek\DocumentDataCatalogProductMview\SubscriptionProvider\OnCreate\Generator;
use MateuszMesek\DocumentDataIndexMviewApi\SubscriptionProviderInterface;
use Traversable;

class OnCreate implements SubscriptionProviderInterface
{
    public function get(array $context): Traversable
    {
        yield '*' => [
            'onDelete' => [
                'id' => 'onCreate',
                'type' => Generator::class,
                'arguments' => []
            ]
        ];
    }
}
