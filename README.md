# a2sp-cartalk-api-client

## Introduction

This is a simple API client to pull vehicle data from dutch A2SP's "Voertuiginfo" API at domain cartalk.nl.

See: https://a2sp.nl/diensten/software-leveranciers/voertuiginfo-api/ for more information.

## Usage:

```
$a2spApiClient = new \DOvereem\A2SP\VehicleInfoApiClient('<your api username>', '<your api password>');
$vehicleInformation = $a2spApiClient->getVehicleInformationByLicensePlateNumber('<A valid licenseplate number>');
```

