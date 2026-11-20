# Ollama and llama-server on Ubuntu WSL2

## WSL2 and resource interpretation

WSL2 runs a Linux kernel with resource limits that may be configured outside the distribution.
Measure the Linux view using free, swapon, cgroups, lscpu, and process RSS before proposing
changes. A GGUF file's size is not peak memory: include model buffers, repacked buffers, KV
cache, compute buffers, and warmup. Free memory, available memory, swap, and cgroup current are
different measurements.

Prefer model storage on the Linux ext4 filesystem over /mnt/c for load latency, but confirm
actual mount type and measured behavior. Do not blindly change .wslconfig memory, processors,
swap, or page reporting.

## systemd and environment

Interactive shell variables do not automatically reach a systemd service. Compare HOME, PATH,
OLLAMA_HOST, OLLAMA_MODELS, OLLAMA_LLM_LIBRARY, CUDA/Vulkan variables, LD_LIBRARY_PATH,
User/Group, and the actual ExecStart. Check for a separately launched daemon before changing
the unit. Back up the unit before an approved edit and reload systemd only as part of that
approved change.

## CPU and GPU backends

Verify CPU flags against the selected runner. CUDA, Vulkan, or ROCm discovery errors may be
non-fatal when Ollama ultimately selects CPU. Treat backend changes as targeted repairs only
when logs prove incorrect selection or failed initialization. Never replace system libraries
or disable WSL security protections to bypass backend errors.

## Model compatibility

Confirm the model manifest/blob path, file ownership, size, GGUF version, architecture,
quantization, and runner metadata parsing. A model that parses may still fail during tensor
loading or warmup. Preserve the model directory during diagnosis and rollback.

## Official references

- [Ollama Linux documentation](https://docs.ollama.com/linux)
- [Ollama troubleshooting](https://docs.ollama.com/troubleshooting)
- [Ollama environment variables](https://docs.ollama.com/faq#how-do-i-configure-ollama-server)
- [Ollama releases](https://github.com/ollama/ollama/releases)
- [Microsoft WSL configuration](https://learn.microsoft.com/windows/wsl/wsl-config)
- [Microsoft WSL systemd](https://learn.microsoft.com/windows/wsl/systemd)

Release behavior and environment variables can change; verify current official documentation
before recommending a version-specific change.

