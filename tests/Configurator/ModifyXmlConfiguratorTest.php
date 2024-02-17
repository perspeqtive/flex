<?php

declare(strict_types=1);

namespace Symfony\Flex\Tests\Configurator;

use Composer\Composer;
use Composer\IO\IOInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Flex\Configurator\ModifyXmlConfigurator;
use Symfony\Flex\Lock;
use Symfony\Flex\Options;
use Symfony\Flex\Recipe;

class ModifyXmlConfiguratorTest extends TestCase
{

    private $testFilePathNs = FLEX_TEST_DIR.'/config/templates/pages/homepage.xml';

    private $testFilePath = FLEX_TEST_DIR.'/config/custom/some-file.xml';
    protected function setUp(): void
    {
        @mkdir(FLEX_TEST_DIR .  '/config/templates/pages', 0777, true);
        copy(__DIR__ . '/../Fixtures/config/templates/pages/homepage.xml', $this->testFilePathNs);
        @mkdir(FLEX_TEST_DIR .  '/config/custom', 0777, true);
        copy(__DIR__ . '/../Fixtures/config/custom/some-file.xml', $this->testFilePath);
    }

    protected function tearDown(): void
    {
        @unlink($this->testFilePath);
        @unlink($this->testFilePathNs);
        @rmdir(FLEX_TEST_DIR . '/config/templates/pages');
        @rmdir(FLEX_TEST_DIR . '/config/templates');
        @rmdir(FLEX_TEST_DIR . '/config/custom');
        @rmdir(FLEX_TEST_DIR . '/config');
    }

    public function testConfigure(): void {

        $expectedTitle = 'Headline';

        $configurator = new ModifyXmlConfigurator(
            $this->createMock(Composer::class),
            $this->createMock(IOInterface::class),
            new Options(['root-dir' => FLEX_TEST_DIR]));

        $recipe = $this->getMockBuilder(Recipe::class)->disableOriginalConstructor()->getMock();
        $lock = $this->getMockBuilder(Lock::class)->disableOriginalConstructor()->getMock();

        $configurator->configure($recipe, [
            [
                'file' => 'config/custom/some-file.xml',
                'xpath' => '//title[@lang="en"]',
                'action' => ModifyXmlConfigurator::ACTION_REPLACE_TEXT_VALUE,
                'value' => $expectedTitle
            ]
        ], $lock);
        $resultString = file_get_contents($this->testFilePath);
        self::assertStringContainsString('<title lang="en">' . $expectedTitle . '</title>', $resultString);
        self::assertSame(3, substr_count($resultString, '<title lang="en">' . $expectedTitle . '</title>'));
    }
    public function testConfigureWithDefaultNamespaceUnconfigured(): void {
        
        $expectedTitle = 'Welcome page';
        
        $configurator = new ModifyXmlConfigurator(
            $this->createMock(Composer::class),
            $this->createMock(IOInterface::class),
            new Options(['root-dir' => FLEX_TEST_DIR]));

        $recipe = $this->getMockBuilder(Recipe::class)->disableOriginalConstructor()->getMock();
        $lock = $this->getMockBuilder(Lock::class)->disableOriginalConstructor()->getMock();
        
        $configurator->configure($recipe, [
                [
                    'file' => 'config/templates/pages/homepage.xml',
                    'xpath' => '/defaultNS:template/defaultNS:meta/defaultNS:title[@lang="en"]',
                    'action' => ModifyXmlConfigurator::ACTION_REPLACE_TEXT_VALUE,
                    'value' => 'Welcome page'
                ]
            ], $lock);
        
        self::assertStringContainsString('<title lang="en">' . $expectedTitle . '</title>', file_get_contents($this->testFilePathNs));
    }

    public function testConfigureWithDefaultNamespaceConfigured(): void {

        $expectedTitle = 'Welcome page';

        $configurator = new ModifyXmlConfigurator(
            $this->createMock(Composer::class),
            $this->createMock(IOInterface::class),
            new Options(['root-dir' => FLEX_TEST_DIR]));

        $recipe = $this->getMockBuilder(Recipe::class)->disableOriginalConstructor()->getMock();
        $lock = $this->getMockBuilder(Lock::class)->disableOriginalConstructor()->getMock();

        $configurator->configure($recipe, [
            [
                'file' => 'config/templates/pages/homepage.xml',
                'default-namespace' => 'x',
                'xpath' => '/x:template/x:meta/x:title[@lang="en"]',
                'action' => ModifyXmlConfigurator::ACTION_REPLACE_TEXT_VALUE,
                'value' => 'Welcome page'
            ]
        ], $lock);

        self::assertStringContainsString('<title lang="en">' . $expectedTitle . '</title>', file_get_contents($this->testFilePathNs));
    }

    public function testConfigureAddAttribute(): void {

        $configurator = new ModifyXmlConfigurator(
            $this->createMock(Composer::class),
            $this->createMock(IOInterface::class),
            new Options(['root-dir' => FLEX_TEST_DIR]));

        $recipe = $this->getMockBuilder(Recipe::class)->disableOriginalConstructor()->getMock();
        $lock = $this->getMockBuilder(Lock::class)->disableOriginalConstructor()->getMock();

        $configurator->configure($recipe, [
            [
                'file' => 'config/custom/some-file.xml',
                'xpath' => '//title[@lang="en"]',
                'action' => ModifyXmlConfigurator::ACTION_UPSERT_ATTRIBUTE_VALUE,
                'value' => 'true',
                'attribute' => 'default'
            ]
        ], $lock);
        self::assertStringContainsString('<title lang="en" default="true">', file_get_contents($this->testFilePath));
        self::assertSame(3, substr_count(file_get_contents($this->testFilePath), '<title lang="en" default="true">'));
    }
    

    
    
}