<p align="center">
    <img src="https://raw.githubusercontent.com/hillelcoren/invoice-ninja/master/public/images/round_logo.png" alt="Sublime's custom image"/>
</p>

[![Build Status](https://travis-ci.org/invoiceninja/invoiceninja.svg?branch=v2)](https://travis-ci.org/invoiceninja/invoiceninja)
[![codecov](https://codecov.io/gh/invoiceninja/invoiceninja/branch/v2/graph/badge.svg)](https://codecov.io/gh/invoiceninja/invoiceninja)
[![Codacy Badge](https://api.codacy.com/project/badge/Grade/d39acb4bf0f74a0698dc77f382769ba5)](https://www.codacy.com/app/turbo124/invoiceninja?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=invoiceninja/invoiceninja&amp;utm_campaign=Badge_Grade)

# Invoice Ninja version 2.0 is coming! 

We will be using the lessons learnt in Invoice Ninja 4.0 to build a bigger better platform to work from. If you would like to contribute to the project we will gladly accept contributions for code, user guides, bug tracking and feedback! Please consider the following guidelines prior to submitting a pull request:

## Quick Start

Curently the client portal and API are of alpha quality, to get started:

```bash
git clone https://github.com/invoiceninja/invoiceninja.git
git checkout v2
cp .env.example .env
cp .env.dusk.example .env.dusk.local
composer update
npm i
npm run production
php artisan migrate:fresh --seed && php artisan db:seed --class=RandomDataSeeder
```

Navigate to
```
http://ninja.test:8000/client/login
user: user@example.com
pass: password
```

## Contribution guide.

Code Style to follow [PSR-2](https://www.php-fig.org/psr/psr-2/) standards.

All methods names to be in CamelCase

All variables names to be in snake_case

Where practical code should be strongly typed, ie your methods must return a type ie

`public function doThis() : void`

PHP >= 7.3 allows the return type Nullable so there should be no circumstance a type cannot be return by using the following:

`public function doThat() ?:string`

To improve chances of PRs being merged please include tests to ensure your code works well and integrates with the rest of the project.

## Documentation

API documentation is hosted using Swagger and can be found [HERE](https://app.swaggerhub.com/apis-docs/InvoiceNinja/InvoiceNinjaV2/1.0.3)

## Current work in progress

Invoice Ninja is currently being written in a combination of Laravel for the API and Client Portal and Flutter for the front end management console. This will allow an immersive and consistent experience across any device: mobile, tablet or desktop.

To manage our workflow we will be creating separate branches for the client (Flutter) and server (Laravel API / Client Portal) and merge these into a release branch for deployments.
