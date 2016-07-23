# Revision Strike Change Log

All notable changes to this project will be documented in this file, according to [the Keep a Changelog standards](http://keepachangelog.com/).

This project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]

* Move from the manual pre-commit hook to [WP-Enforcer](https://github.com/stevegrunwell/wp-enforcer).

## [0.3.0] - 2016-06-20

* Lock Composer dependency versions to ensure more consistent testing via Travis-CI.
* Add the `revisionstrike_get_revision_ids` filter to enable third-party plugins and themes to alter the array of revision IDs. ([#21])
* Implement Grunt to more consistently build releases. ([#18])


## [0.2.0] - 2015-08-16

* Added a "Limit" setting to Settings &rsaquo; Writing. ([#13])
* Added a "clean-all" WP-CLI command. ([#14])
* Clarified language on the Settings &rsaquo; Writing and Tools &rsaquo; Revision Strike pages. Props to @GhostToast for the suggestion! ([#16])
* Strike requests are now batched into groupings of 50 IDs at a time to avoid overwhelming underpowered machines. ([#17])


## [0.1.0] - 2015-08-09

Initial public release.


[Unreleased]: https://github.com/stevegrunwell/revision-strike/compare/master...develop
[0.3.0]: https://github.com/stevegrunwell/revision-strike/compare/v0.2.0...v0.3.0
[0.2.0]: https://github.com/stevegrunwell/revision-strike/compare/v0.1.0...v0.2.0
[0.1.0]: https://github.com/stevegrunwell/revision-strike/releases/tag/v0.1.0
[#13]: https://github.com/stevegrunwell/revision-strike/issues/13
[#14]: https://github.com/stevegrunwell/revision-strike/issues/14
[#16]: https://github.com/stevegrunwell/revision-strike/issues/16
[#17]: https://github.com/stevegrunwell/revision-strike/issues/17
[#18]: https://github.com/stevegrunwell/revision-strike/issues/18
[#21]: https://github.com/stevegrunwell/revision-strike/issues/21