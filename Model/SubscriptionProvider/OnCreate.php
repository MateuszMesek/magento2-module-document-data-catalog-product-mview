<?php declare(strict_types=1);

namespace MateuszMesek\DocumentDataCatalogProductMview\Model\SubscriptionProvider;

use MateuszMesek\DocumentDataCatalogProductMview\Model\SubscriptionProvider\OnCreate\Generator;
use MateuszMesek\DocumentDataIndexMviewApi\Model\SubscriptionProviderInterface;
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
