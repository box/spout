# Spout

[![Latest Stable Version](https://poser.pugx.org/box/spout/v/stable)](https://packagist.org/packages/box/spout)
[![Project Status](http://opensource.box.com/badges/active.svg)](http://opensource.box.com/badges)
[![Build Status](https://travis-ci.org/box/spout.svg?branch=master)](https://travis-ci.org/box/spout)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/box/spout/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/box/spout/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/box/spout/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/box/spout/?branch=master)
[![Total Downloads](https://poser.pugx.org/box/spout/downloads)](https://packagist.org/packages/box/spout)

Spout is a PHP library to read and write spreadsheet files (CSV, XLSX and ODS), in a fast and scalable way.
Contrary to other file readers or writers, it is capable of processing very large files while keeping the memory usage really low (less than 3MB).

Join the community and come discuss about Spout: [![Gitter](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/box/spout?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge)


## Documentation

Full documentation can be found at [http://opensource.box.com/spout/](http://opensource.box.com/spout/).


## Requirements

* PHP version 5.4.0 or higher
* PHP extension `php_zip` enabled
* PHP extension `php_xmlreader` enabled


## Running tests

On the `master` branch, only unit and functional tests are included. The performance tests require very large files and have been excluded.
If you just want to check that everything is working as expected, executing the tests of the `master` branch is enough.

If you want to run performance tests, you will need to checkout the `perf-tests` branch. Multiple test suites can then be run, depending on the expected output:

* `phpunit` - runs the whole test suite (unit + functional + performance tests)
* `phpunit --exclude-group perf-tests` - only runs the unit and functional tests
* `phpunit --group perf-tests` - only runs the performance tests

For information, the performance tests take about 30 minutes to run (processing 1 million rows files is not a quick thing).

> Performance tests status: [![Build Status](https://travis-ci.org/box/spout.svg?branch=perf-tests)](https://travis-ci.org/box/spout)


## Support

You can ask questions, submit new features ideas or discuss about Spout in the chat room:<br>
[![Gitter](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/box/spout?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge)

## Copyright and License

Copyright 2017 Box, Inc. All rights reserved.

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

   http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
