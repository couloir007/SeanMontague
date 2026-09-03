<?php

namespace Drupal\geolocation;

/**
 * DTO.
 */
class GeolocationAddress {

  public function __construct(
    public ?string $organization = NULL,
    public ?string $addressLine1 = NULL,
    public ?string $addressLine2 = NULL,
    public ?string $addressLine3 = NULL,
    public ?string $dependentLocality = NULL,
    public ?string $locality = NULL,
    public ?string $administrativeArea = NULL,
    public ?string $postalCode = NULL,
    public ?string $sortingCode = NULL,
    public ?string $countryCode = NULL,
  ) {}

  /**
   * Format address as string.
   *
   * @return string
   *   Address.
   */
  public function __toString(): string {
    return implode(' ', array_filter([
      $this->organization,
      $this->addressLine1,
      $this->addressLine2,
      $this->addressLine3,
      $this->dependentLocality,
      $this->locality,
      $this->administrativeArea,
      $this->postalCode,
      $this->sortingCode,
      $this->countryCode,
    ]));
  }

}
