# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

# Release Notes

## [Unreleased](https://github.com/algolia/search-bundle/compare/5.1.0...master)

## [v5.1.0](https://github.com/algolia/search-bundle/compare/5.0.0...5.1.0)

### Changed
- Update the Algolia API client version ([#360](https://github.com/algolia/search-bundle/pull/360))

## [v5.0.0](https://github.com/algolia/search-bundle/compare/4.1.2...5.0.0)

### Breaking Changes
- Update Doctrine components and add Symfony 5.2 support ([#355](https://github.com/algolia/search-bundle/pull/355))
- Drops support for PHP < 7.2


## [v4.1.2](https://github.com/algolia/search-bundle/compare/4.1.1...4.1.2)

### Fixed
- Import for aggregated models ([#350](https://github.com/algolia/search-bundle/pull/350))


## [v4.1.1](https://github.com/algolia/search-bundle/compare/4.1.0...4.1.1)

### Fixed
- Use ClassUtils from Doctrine to avoid having Proxy into Algolia ([#341](https://github.com/algolia/search-bundle/pull/341))


## [v4.1.0](https://github.com/algolia/search-bundle/compare/4.0.0...4.1.0)
### Added
- Adds atomic reindex support, via `--atomic` flag in `search:import` ([#324](https://github.com/algolia/search-bundle/pull/324))

## [v4.0.0](https://github.com/algolia/search-bundle/compare/3.4.0...4.0.0)

### Changed
- Major version - [Upgrade Guide](https://github.com/algolia/search-bundle/blob/master/UPGRADE-4.0.md)
