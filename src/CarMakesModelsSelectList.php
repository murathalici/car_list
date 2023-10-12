<?php

namespace Drupal\cars_list;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Provides functionality to fetch car makes and models.
 */
class CarMakesModelsSelectList {

  /**
   * The HTTP client for making API requests.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected ClientInterface $httpClient;

  /**
   * The logger channel for the module.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * CarMakesModelsSelectList constructor.
   */
  public function __construct(
    ClientInterface $httpClient,
    LoggerChannelFactoryInterface $loggerFactory,
    CacheBackendInterface $cache,
    ConfigFactoryInterface $configFactory
  ) {
    $this->httpClient = $httpClient;
    $this->logger = $loggerFactory->get('cars_list');
    $this->cache = $cache;
    $this->configFactory = $configFactory;
  }

  /**
   * Fetch car makes and return them as an array.
   */
  public function fetchCarMakes(): array {
    // Get endpoint from config, or default to a hardcoded value.
    $endpoint = $this->configFactory->get('cars_list.settings')->get('base_api_endpoint') ?? 'getallmakes?format=json';
    return $this->apiRequest($endpoint, 'Make_Name');
  }

  /**
   * Fetch car models for a given make and return them as an array.
   */
  public function fetchCarModels(string $make): array {
    // Construct the endpoint dynamically based on the given car make.
    $endpointFormat = $this->configFactory->get('cars_list.settings')->get('model_api_endpoint_format') ?? 'getmodelsformake/%s?format=json';
    $endpoint = sprintf($endpointFormat, urlencode($make));

    $models = $this->apiRequest($endpoint, 'Model_Name');

    // Sort models alphabetically before returning.
    asort($models);
    return $models;
  }

  /**
   * Helper method for the API requests.
   *
   * @param string $endpoint
   *   Specific endpoint to be used.
   * @param string $key
   *   Key to extract from the API response.
   *
   * @return array
   *   The extracted data from the API or an empty array in case of failures.
   */
  private function apiRequest(string $endpoint, string $key): array {
    $baseApiUrl = $this->configFactory->get('cars_list.settings')->get('base_api_url') ?? 'https://vpic.nhtsa.dot.gov/api/vehicles/';
    $url = $baseApiUrl . $endpoint;

    // Generate a cache ID from the URL.
    $cid = 'cars_list:' . md5($url);

    // Check for cached data.
    if ($cache = $this->cache->get($cid)) {
      $this->logger->info('API request to %url returned cached data', ['%url' => $url]);
      return $cache->data;
    }

    try {
      $response = $this->httpClient->request('GET', $url);

      // On successful response, process the data.
      if ($response->getStatusCode() === 200) {
        $data = json_decode($response->getBody(), TRUE);

        // Check for valid JSON.
        if (json_last_error() !== JSON_ERROR_NONE) {
          $this->logger->error('Invalid JSON response from %url', ['%url' => $url]);
          return [];
        }

        // Extract the desired data using the given key.
        $result = array_column($data['Results'] ?? [], $key);

        // Get cache duration from config.
        $cache_duration = $this->configFactory->get('cars_list.settings')->get('cache_duration') ?? 3600;

        // Cache the results for the specified duration.
        $this->cache->set($cid, $result, time() + $cache_duration);

        // Log the successful API request.
        $this->logger->info('API request to %url returned status code %code', [
          '%url' => $url,
          '%code' => $response->getStatusCode(),
        ]);

        return $result;
      }
    }
    catch (RequestException $e) {
      // Log any errors.
      $this->logger->error('API request to %url failed: %error', [
        '%url' => $url,
        '%error' => $e->getMessage(),
      ]);
    }

    return [];
  }

}
