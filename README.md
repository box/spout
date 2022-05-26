# Spout

[![Latest Stable Version](https://poser.pugx.org/box/spout/v/stable)](https://packagist.org/packages/box/spout)
[![Project Status](https://opensource.box.com/badges/inactive.svg)](https://opensource.box.com/badges)
[![example workflow](https://github.com/box/spout/actions/workflows/ci.yml/badge.svg?branch=master)](https://github.com/box/spout/actions/workflows/ci.yml?query=branch%3Amaster)
[![Coverage Status](https://coveralls.io/repos/github/box/spout/badge.svg?branch=master)](https://coveralls.io/github/box/spout?branch=master)
[![Total Downloads](https://poser.pugx.org/box/spout/downloads)](https://packagist.org/packages/box/spout)

## 🪦 Archived project 🪦 

This project has been archived and is no longer maintained. No bug fix and no additional features will be added.<br>
You won't be able to submit new issues or pull requests, and no additional features will be added

You can still use Spout as is in your projects though :)

> Thanks to everyone who contributed to this project, from a typo fix to the new cool feature.<br>
> It was great to see the involvement of this community!

<br>

## About

Spout is a PHP library to read and write spreadsheet files (CSV, XLSX and ODS), in a fast and scalable way.
Unlike other file readers or writers, it is capable of processing very large files, while keeping the memory usage really low (less than 3MB).

Join the community and come discuss Spout: [![Gitter](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/box/spout?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge)


## Documentation

Full documentation can be found at [https://opensource.box.com/spout/](https://opensource.box.com/spout/).


## Requirements

* PHP version 7.2 or higher
* PHP extension `php_zip` enabled
* PHP extension `php_xmlreader` enabled

## Upgrade guide

Version 3 introduced new functionality but also some breaking changes. If you want to upgrade your Spout codebase from version 2 please consult the [Upgrade guide](UPGRADE-3.0.md). 

## Running tests

The `master` branch includes unit, functional and performance tests.
If you just want to check that everything is working as expected, executing the unit and functional tests is enough.

* `phpunit` - runs unit and functional tests
* `phpunit --group perf-tests` - only runs the performance tests

For information, the performance tests take about 10 minutes to run (processing 1 million rows files is not a quick thing).

## Support

Spout is no longer actively supported. You can still ask questions, or discuss about it in the chat room:<br>
[![Gitter](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/box/spout?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge)

## Copyright and License

Copyright 2022 Box, Inc. All rights reserved.

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

   http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
