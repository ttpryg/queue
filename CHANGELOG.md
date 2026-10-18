# Changelog

All notable changes to `ttpryg/queue` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2026-10-18

### Added

- Root `ExceptionInterface` marker interface (`Ttpryg\Queue\Exceptions\ExceptionInterface`)
  that extends `Throwable` and is implemented by `QueueException`, so consumers can
  catch all library-specific errors with a single type.
- `.editorconfig` for consistent editor settings across the project.

### Changed

- Added `declare(strict_types=1)` to all source files that were missing it.

## [1.0.0] - 2026-08-09

### Added

- Initial release: framework-agnostic queue abstraction (`QueueInterface`, `QueueManager`).
- Database handler backed by `PdoDatabaseAdapter` for MySQL/MariaDB, PostgreSQL, and SQLite,
  plus native `SymfonyMessengerHandler` integration.
- Advanced job control: priorities, delayed execution, retry attempts with backoff,
  and memory limits (`BaseJob`, `QueueJob`, `QueuePushResult`, `Payload`/`PayloadCollection`).
- PSR-14 style queue events (`QueueEvent`, `QueueEventManager`).
- `symfony/console` commands: `WorkCommand`, `FailedCommand`, `RetryCommand`,
  `ForgetCommand`, `FlushCommand`, and `ClearCommand`.
- `QueueException` for consistent queue error reporting.

[Unreleased]: https://github.com/ttpryg/queue/compare/1.0.0...HEAD
[1.0.0]: https://github.com/ttpryg/queue/releases/tag/1.0.0
