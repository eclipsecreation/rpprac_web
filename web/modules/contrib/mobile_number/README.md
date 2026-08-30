# Mobile Number

The Mobile Number module is a field type that provides mobile number
validation and verification, and can be used for two factor authentication.
It works with SMS Framework and TFA modules, respectively, and both
features are optional.

The module differs from the Phone module in that it puts security and
authenticity first, and focuses on mobile numbers which therefore assumes sms
and smartphone capabilities by the user. The number is sanitized before saving,
country is validated, the number is validated to be a mobile number type,
allows uniqueness validation, and, if enabled, allows verifying number
ownership by the user. For verification, codes are hashed and tokenized in the
database. See the case for a dedicated mobile number field.
[Mobile Number field](https://www.drupal.org/node/2783505)

The module uses libphonenumber (developed by Google for use in Android) for
validation, and supports the mobile number formats of the countries listed
here [giggsey/libphonenumber-for-php](https://github.com/giggsey/libphonenumber-for-php/blob/master/src/libphonenumber/ShortNumbersRegionCodeSet.php), which is basically all of them.


## Table of contents

- Introduction
- Requirements
- Recommended Modules
- Features
- Integration (with other modules)
- Installation
- Configuration
- Maintainers


## Requirements

This module requires the following php library:
- [giggsey/libphonenumber-for-php](https://github.com/giggsey/libphonenumber-for-php)


## Recommended modules

- [SMS Framework](https://www.drupal.org/project/smsframework):
- [TFA](https://www.drupal.org/project/tfa):


## Features

- Mobile number validation
- Mobile number verification
- Uniqueness validation
- Two factor authentication


## Integration (with other modules)

- Feeds
- Telephone
- Devel
- TFA
- SMS Framework


## Installation

- Install as you would normally install a contributed Drupal module.
  For further information, see
  [Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).
- Install the libphonenumber project using Drupal's root composer.
- For mobile number verification, download, enable, and configure
  [SMS Framework](https://www.drupal.org/project/smsframework). You will need to setup an
  [SMS gateway](https://www.drupal.org/node/2641028), which will require setting up an account and paying fees on a 3rd party service, or use SMS Framework's default logger gateway for testing.
- Two factor authentication is not available for D8


## Configuration

1. Enable the module at Administration > Extend.
2. When creating a new field on a content type or custom entity type,
  choose "Mobile Number" from the drop-down menu.
3. In the field settings, choose the default country and 
  allowed country(optional).
4. Enable/disable verification and set verification message.


## Maintainers

- nyariv - [nyariv](https://www.drupal.org/u/nyariv)
