# Changelog

## [1.3.1] - Unreleased

* Fixed linting and code style issues across the project
* Added action to build docker image on push to Pull Requests
* Optimized the Dockerfile build to include the app and dependencies in different layers

## [1.3.0] - 05-12-2020

* Added Edgebox Browser Development Environment Feature Support
    * Added tasks for handling browser development environment into TaskFactory
    * Added options for handling browser development environment data into OptionsRepository
    * Added BrowserDevHelper class for handling browser development environment tasks in a more convenient way
    * Added API route to ApiController for handling getBrowserDevStatus, enableBrowserDev, disableBrowserDev, setBrowserDevPassword tasks, and returning the status.
    * Added section for handling browser development environment in the Settings page
    * Added Javascript to handle browser development environment task buttons and status updates in the settings page section
    * Added Browser Development Environment button to EdgeApps detail page when this feature is enabled

### Missing Past Releases

Release notes for past versions are not available in this file. Please refer to the [GitHub releases](https://hithub.com/edgebox-iot/api/releases) for more information. Feel free to contribute to this file by adding missing release notes.

