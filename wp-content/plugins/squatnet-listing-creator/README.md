# Squatnet Listing Creator

## Overview

It's early days folks, but this is intended to be a simplified interfact for non-technical users to propose squatnet listings using a specified group id from a wordpress website.

This project uses [composer](https://getcomposer.org/) for dependency management and [Nette Forms](https://doc.nette.org/en/forms) to handle form validation/submission logic, it uses [Twig](https://twig.symfony.com/) to make the form rendering more straightforward.

## Installing

With an intial release now up, you can simply download the latest version from the releases page and copy the extracted folder across to your 'plugins' directory with wp-content. No further work is required for it to function.

If you wish to checkout the source from git, then please follow the instructions below before installing to your wordpress:

After installing Composer, run `composer install` in the root directory of this project. This will install all the necessary php libraries to make the plugin work. After that you can upload it to your website's 'plugins' directory inside wp-content.

After activating the plugin is should be available to use! Add the following shortcode to the page you want to display it on:

`[squatnet-listing-creator]`

More deets coming as the plugin gets f**king built!

## TODO:
- [x] Location grabbing from squatnet database
- [x] Form validation
- [x] Submission to squatnet
- [x] Test categories/tags
- [x] Stylesheet
- [ ] Customisation
- [ ] Theme overrides