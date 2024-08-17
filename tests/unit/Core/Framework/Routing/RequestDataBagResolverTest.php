<?php

declare(strict_types=1);

namespace Shopware\Tests\Unit\Core\Framework\Routing;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Routing\RequestDataBagResolver;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

#[CoversClass(RequestDataBagResolver::class)]
class RequestDataBagResolverTest extends TestCase
{
    private RequestDataBagResolver $requestDataBagResolver;

    protected function setUp(): void
    {
        $this->requestDataBagResolver = new RequestDataBagResolver();
    }

    public static function jsonBodyDataProvider(): \Generator
    {
        yield 'invalid json body' => [
            'requestBody' => '{invalid',
            'expectJsonException' => true,
            'expectedData' => null,
        ];
        $data = [
            'key1' => 'value1',
            'key2' => 'value1',
            'key3' => 'value1',
        ];
        yield 'valid json body' => [
            'requestBody' => json_encode($data, \JSON_BIGINT_AS_STRING),
            'expectJsonException' => false,
            'expectedData' => $data,
        ];
    }

    #[DataProvider('jsonBodyDataProvider')]
    public function testJsonBody(string $requestBody, bool $expectJsonException, ?array $expectedData): void
    {
        if ($expectJsonException) {
            self::expectException(JsonException::class);
        }

        $request = new Request(content: $requestBody);
        $request->headers->set('CONTENT_TYPE', 'application/json');
        $request->setMethod(Request::METHOD_POST);

        $argument = new ArgumentMetadata('', RequestDataBag::class, false, false, '');
        $dataBag = $this->requestDataBagResolver->resolve($request, $argument)->current();

        self::assertInstanceOf(RequestDataBag::class, $dataBag);
        self::assertEquals($expectedData, $dataBag->all());
        dump($dataBag->all());
    }
}
