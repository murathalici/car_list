<?php

namespace Drupal\cars_list;

/**
 * Wrapper class for fetching car makes and models.
 *
 * Provides utility methods to interface with the
 * CarMakesModelsSelectList service.
 */
class CarMakesModelsSelectListWrapper {

  /**
   * Fetches a list of car makes.
   *
   * @return array
   *   An array of car makes.
   */
  public static function carMakes(): array {
    $carMakesSelectList = \Drupal::service('cars_list.car_makes_models_select_list');

    return $carMakesSelectList->fetchCarMakes();
  }

  /**
   * Fetches a list of car models for a given make.
   *
   * @param string $make
   *   The car make for which to fetch models.
   *
   * @return array
   *   An array of car models for the specified make.
   */
  public static function carModels($make): array {
    $carMakesSelectList = \Drupal::service('cars_list.car_makes_models_select_list');
    return $carMakesSelectList->fetchCarModels($make);
  }

}
