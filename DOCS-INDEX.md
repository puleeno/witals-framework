# Witals Framework - Documentation Index

Welcome to the Witals Framework documentation! This framework supports multiple runtime environments for maximum flexibility and performance.

## 📚 Quick Navigation

### Getting Started
- **[QUICKSTART.md](QUICKSTART.md)** - Get up and running in 5 minutes.
- **[README.md](README.md)** - Complete framework overview and features.
- **[TOM-TAT-VI.md](TOM-TAT-VI.md)** - Tóm tắt bằng tiếng Việt.

### Core Documentation
- **[RUNTIME.md](RUNTIME.md)** - Detailed runtime support guide.
  - Performance adapters and unified CLI (`witals`).
  - Installation instructions for each runtime.
  - Performance comparison.
  - Memory & Isolation mechanisms.

- **[LIFECYCLE.md](LIFECYCLE.md)** - Application lifecycle and IoC scopes.
  - Traditional vs long-running lifecycle.
  - Request scoping mechanism.
  - Isolation Scope details.

- **[ARCHITECTURE.md](ARCHITECTURE.md)** - System architecture diagrams.
  - Server Adapter architecture.
  - Request flow (Init -> Execute -> Respond -> Shutdown).
  - Use case recommendations.

- **[LOGGING.md](LOGGING.md)** - Enterprise Logging System.
  - PSR-3 compliance and drivers.
  - Performance buffering and flushing.
  - Traceability and JSON formatting.

- **[HOOKS.md](HOOKS.md)** - Hook System (Actions & Filters).
  - Actions vs Filters.
  - HookInterface contract (8 methods).
  - Global helpers: add_action, do_action, add_filter, apply_filters.
  - Priority system & best practices.
  - Container binding & project override guide.

- **[docs/ASSET-MANAGER.md](docs/ASSET-MANAGER.md)** - Intelligent Asset Resolution.
  - Context-aware rendering (external vs inline).
  - Dependency resolution (Topological Sort).
  - Discovery Roots & Handle Registry (WordPress compatible).

### Implementation Details
- **[CHANGELOG-RUNTIME.md](CHANGELOG-RUNTIME.md)** - Complete changelog of runtime extensions.
- **[IMPLEMENTATION-SUMMARY.md](IMPLEMENTATION-SUMMARY.md)** - Summary of the unified runner implementation.

### Testing
- **[TESTING.md](TESTING.md)** - Testing guide.
  - Manual testing checklist.
  - Load testing & Benchmarks.

## 🚀 Supported Runtimes

| Runtime | Performance | Async | Coroutines | Control |
|---------|------------|-------|------------|---------|
| **Traditional** | 1x | ❌ | ❌ | `public/index.php` |
| **RoadRunner** | 10x | ❌ | ❌ | `witals serve` |
| **ReactPHP** | 8x | ✅ | ❌ | `witals serve --reactphp` |
| **Swoole** | 15x | ✅ | ✅ | `witals serve --swoole` |
| **OpenSwoole** | 15x | ✅ | ✅ | `witals serve --openswoole` |

## 🎯 Common Tasks

### Starting High-Performance Server
```bash
# Auto-detect engine
php witals serve

# Specify engine and port
php witals serve --swoole --port=9090
```

### Deployment
Deployment guides for Systemd and Docker are available in [QUICKSTART.md](QUICKSTART.md#4-production-deployment).

## 🤝 Contributing
Contributions are welcome! Please follow PSR-12 and ensure all tests pass.

## 📄 License
MIT License - see [LICENSE](LICENSE).

---
**Version:** 1.0+ | **Status:** ✅ Production Ready
