# Common errors and evidence-based solutions

| Evidence | Likely cause | Required confirmation |
|---|---|---|
| ldd contains not found | Missing shared library or wrong library path | Resolve the exact dependency and inspect LD_LIBRARY_PATH/RUNPATH. |
| Exec format error or wrong ELF architecture | Runner does not match WSL architecture | Compare file output with uname -m. |
| --help fails | Loader, dependency, permission, or executable failure | Capture stderr and exit status. |
| signal: illegal instruction | CPU/backend instruction mismatch | Compare selected runner features with /proc/cpuinfo. |
| libcuda.so.1 missing during probe | CUDA unavailable | Fatal only if CUDA is selected; harmless when CPU is selected. |
| Vulkan/Mesa permission error | Vulkan backend/cache permission problem | Confirm Vulkan selection and cache ownership. |
| address already in use | Competing process or duplicate service | Identify the listener before stopping anything. |
| llama-server process terminated | Runner crash or explicit exit | Inspect process state, signal, stderr, kernel logs, and exit code. |
| timed out waiting for llama-server | Stall, cancellation, hidden crash, or too-short load budget | Correlate timestamps, health status, client lifetime, and progress logs. |
| GGUF metadata loads then load_tensors stalls | Model is parseable but load/backend/memory/warmup is unresolved | Inspect buffers, RSS/swap, backend, filesystem and runner progress. |
| memory.events oom/oom_kill increments | Actual cgroup memory pressure | Measure peak and identify the allocation causing it. |
| service active but no runner | Lazy model loading is normal | Trigger a bounded model request and inspect runner lifecycle. |
| port responds to /api/version but inference fails | Ollama daemon works; model runner does not | Test model generation and inspect runner logs separately. |

Do not infer model corruption from a timeout. Do not redownload a model unless integrity evidence
directly proves the existing blob is damaged. OLLAMA_LOAD_TIMEOUT is a stall budget, not a fix for
a crashed or non-progressing runner.

