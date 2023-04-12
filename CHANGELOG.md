# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]
### Added
### Changed
### Deprecated
### Removed
### Fixed
### Security

## 0.1.0

- Updated depencencies

## 0.0.2
### Added
- Support de-duplication on multiple payload fields [PR#8](https://github.com/liip/roger-q/pull/8) by [@thePanz](https://github.com/thePanz)
### Changed
- Using pnz/json-exception for JSON decode/encode exception handling
### Fixed
- Use correct default port and catch port in hostname [PR#6](https://github.com/liip/roger-q/pull/6) by [@dbu](https://github.com/dbu)
- Stopped using RequestOptions::SINK because it caused stream seek errors on some systems [PR#19](https://github.com/liip/roger-q/pull/19) by [@dbu](https://github.com/dbu)

## 0.0.1
### Fixed
- Fixed TravisCI deployment process and phar releasing

## 0.0.1-beta.1
### Added
- Added TravisCI integration
- Added documentation
- Added commands: dump, dedupe, publish
