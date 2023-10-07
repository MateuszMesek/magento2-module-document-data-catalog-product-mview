<?php declare(strict_types=1);

namespace MateuszMesek\DocumentDataCatalogProductMview\Model\SubscriptionProvider;

use MateuszMesek\DocumentDataCatalogProductMview\Model\SubscriptionProvider\OnWebsiteChange\Generator;
use MateuszMesek\DocumentDataIndexMviewApi\Model\SubscriptionProviderInterface;
use Traversable;

class OnWebsiteChange implements SubscriptionProviderInterface
{
    public function get(array $context): Traversable
    {
        yield '*' => [
            'onWebsiteChange' => [
                'id' => 'onWebsiteChange',
                'type' => Generator::class,
                'arguments' => []
            ]
        ];
    }
}
