# MITRANET — PRIVILEGE BOUNDARY MODEL

**Official Identity:** MitraNet 0.1.0-dev (Code OS: Rinjani)

## 1. Privilege Separation
- **Read Operations**: Unprivileged access (displaying status, configuration, routes, telemetry).
- **Write Operations**: Controlled via Configuration Engine transactions with validation guards.
- **System Command Execution**: Bounded parameter list invocation (`subprocess.run(list, shell=False)`), completely disallowing shell interpolation.
