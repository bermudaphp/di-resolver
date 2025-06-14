<?php

namespace Bermuda\ParameterResolver\Tests;

use Bermuda\Di\Attribute\Config;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ConfigAttributeTest extends TestCase
{
    #[Test]
    public function getEntryFromConfigReturnsValue(): void
    {
        $config = new Config('debug', null, false);
        $data = ['debug' => true];

        $result = $config->getEntryFromConfig($data);

        $this->assertTrue($result);
    }

    #[Test]
    public function getEntryFromConfigWithDottedPath(): void
    {
        $config = new Config('database.host');
        $data = [
            'database' => [
                'host' => 'localhost',
                'port' => 3306
            ]
        ];

        $result = $config->getEntryFromConfig($data);

        $this->assertEquals('localhost', $result);
    }

    #[Test]
    public function throwsOutOfBoundsForMissingKey(): void
    {
        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('Undefined configuration key: database → missing');

        $config = new Config('database.missing');
        $data = ['database' => ['host' => 'localhost']];

        $config->getEntryFromConfig($data);
    }

    #[Test]
    public function throwsInvalidArgumentForScalarValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("The configuration value at path 'database' is not accessible");

        $config = new Config('database.host');
        $data = ['database' => 'scalar_value'];

        $config->getEntryFromConfig($data);
    }

    #[Test]
    public function handlesNullValues(): void
    {
        $config = new Config('nullable.value');
        $data = [
            'nullable' => [
                'value' => null
            ]
        ];

        $result = $config->getEntryFromConfig($data);

        $this->assertNull($result);
    }

    #[Test]
    public function handlesArrayAccessObjects(): void
    {
        $config = new Config('database.host');
        $data = new \ArrayObject([
            'database' => new \ArrayObject([
                'host' => 'localhost'
            ])
        ]);

        $result = $config->getEntryFromConfig($data);

        $this->assertEquals('localhost', $result);
    }

    #[Test]
    public function handlesExplodeDotsDisabled(): void
    {
        $config = new Config('app.debug', null, false);
        $data = [
            'app.debug' => true,
            'app' => ['debug' => false]
        ];

        $result = $config->getEntryFromConfig($data);

        $this->assertTrue($result);
    }

    #[Test]
    public function throwsTypeErrorForInvalidRootType(): void
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('must be of type ArrayAccess|array, string given');

        $config = new Config('key');
        $invalidData = 'not_an_array';

        $config->getEntryFromConfig($invalidData);
    }

    #[Test]
    public function handlesDeepNesting(): void
    {
        $config = new Config('level1.level2.level3.level4');
        $data = [
            'level1' => [
                'level2' => [
                    'level3' => [
                        'level4' => 'deep_value'
                    ]
                ]
            ]
        ];

        $result = $config->getEntryFromConfig($data);

        $this->assertEquals('deep_value', $result);
    }

    #[Test]
    public function preservesDataTypes(): void
    {
        $config = new Config('data.number');
        $data = [
            'data' => [
                'number' => 42,
                'float' => 3.14,
                'bool' => false,
                'string' => 'text'
            ]
        ];

        $result = $config->getEntryFromConfig($data);

        $this->assertSame(42, $result);
        $this->assertIsInt($result);
    }

    #[Test]
    public function handlesFalsyValues(): void
    {
        $config = new Config('test.zero');
        $data = [
            'test' => [
                'zero' => 0,
                'false' => false,
                'empty' => '',
                'null' => null
            ]
        ];

        $result = $config->getEntryFromConfig($data);

        $this->assertSame(0, $result);
    }

    #[Test]
    public function throwsOutOfBoundsForMissingRootKey(): void
    {
        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('Undefined configuration key: missing');

        $config = new Config('missing');
        $data = ['existing' => 'value'];

        $config->getEntryFromConfig($data);
    }

    #[Test]
    public function throwsOutOfBoundsForDeepMissingKey(): void
    {
        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('Undefined configuration key: app → database → missing');

        $config = new Config('app.database.missing.key');
        $data = [
            'app' => [
                'database' => [
                    'existing' => 'value'
                ]
            ]
        ];

        $config->getEntryFromConfig($data);
    }

    #[Test]
    public function throwsInvalidArgumentForNestedScalar(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("The configuration value at path 'database → port' is not accessible");

        $config = new Config('database.port.details');
        $data = [
            'database' => [
                'port' => 3306
            ]
        ];

        $config->getEntryFromConfig($data);
    }
}