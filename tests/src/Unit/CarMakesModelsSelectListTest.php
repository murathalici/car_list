<?php

namespace Drupal\Tests\cars_list\Unit;

use Drupal\cars_list\CarMakesModelsSelectList;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

/**
 * Tests for CarMakesModelsSelectList.
 *
 * @coversDefaultClass \Drupal\cars_list\CarMakesModelsSelectList
 * @group cars_list
 */
class CarMakesModelsSelectListTest extends TestCase {

  /**
   * The mocked HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The mocked logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The mocked logger channel factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The mocked cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The mocked config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The mocked immutable config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $immutableConfig;

  /**
   * The service under test.
   *
   * @var \Drupal\cars_list\CarMakesModelsSelectList
   */
  protected $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->httpClient = $this->createMock(ClientInterface::class);
    $this->logger = $this->createMock(LoggerChannelInterface::class);
    $this->loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $this->loggerFactory->method('get')->willReturn($this->logger);
    $this->cache = $this->createMock(CacheBackendInterface::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->immutableConfig = $this->createMock(ImmutableConfig::class);
    $this->configFactory->method('get')->willReturn($this->immutableConfig);

    $this->service = new CarMakesModelsSelectList(
      $this->httpClient,
      $this->loggerFactory,
      $this->cache,
      $this->configFactory
    );
  }

  /**
   * Tests fetching car makes.
   */
  public function testFetchCarMakes() {
    $jsonResponse = json_encode(
      [
        'Results' => [
          ['Make_Name' => 'Ford'],
          ['Make_Name' => 'Toyota'],
        ],
      ]
      );
    $this->httpClient->method('request')->willReturn(new Response(200, [], $jsonResponse));

    $makes = $this->service->fetchCarMakes();

    $this->assertEquals(['Ford', 'Toyota'], $makes);
  }

  /**
   * Tests fetching car makes with an API error.
   */
  public function testFetchCarMakesWithApiError() {
    $this->httpClient->method('request')->willThrowException(new RequestException('API Error', new Request('GET', 'test')));
    $this->logger->expects($this->once())->method('error');

    $makes = $this->service->fetchCarMakes();

    $this->assertEquals([], $makes);
  }

  /**
   * Tests fetching car models.
   */
  public function testFetchCarModels() {
    $jsonResponse = json_encode(
      [
        'Results' => [
        ['Model_Name' => 'Bronco'],
        ['Model_Name' => 'Mustang'],
        ],
      ]);
    $this->httpClient->method('request')->willReturn(new Response(200, [], $jsonResponse));

    $models = $this->service->fetchCarModels('Ford');

    $this->assertEquals(['Bronco', 'Mustang'], $models);
  }

  /**
   * Tests fetching car makes with an empty API response.
   */
  public function testFetchCarMakesWithEmptyApiResponse() {
    $emptyResponse = new Response(200, [], json_encode(['Results' => []]));

    $this->httpClient->expects($this->once())
      ->method('request')
      ->with('GET', 'https://vpic.nhtsa.dot.gov/api/vehicles/getallmakes?format=json')
      ->willReturn($emptyResponse);

    $makes = $this->service->fetchCarMakes();

    $this->assertEmpty($makes);
  }

}
