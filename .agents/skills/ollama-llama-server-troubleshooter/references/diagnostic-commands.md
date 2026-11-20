# Diagnostic commands

Run these commands read-only first. Adapt paths and users from discovery. Capture timestamps,
exit codes, and relevant stderr; redact secrets.

## Identity and service

```bash
date -Is; uname -a; cat /etc/os-release
command -v ollama; ollama --version
systemctl cat ollama.service
systemctl show ollama -p LoadState -p ActiveState -p SubState -p User -p Group -p Environment -p ExecStart
systemctl status ollama --no-pager -l
```

Check whether systemd manages the process. A manually started ollama serve can own port 11434
while systemd is inactive or repeatedly fails with address already in use.

## WSL2 resources

```bash
free -h; swapon --show; ulimit -a
cat /proc/meminfo
findmnt -t cgroup,cgroup2 -o TARGET,FSTYPE,OPTIONS
cat /sys/fs/cgroup/system.slice/ollama.service/memory.max 2>/dev/null
cat /sys/fs/cgroup/system.slice/ollama.service/memory.current 2>/dev/null
cat /sys/fs/cgroup/system.slice/ollama.service/memory.events 2>/dev/null
cat /sys/fs/cgroup/system.slice/ollama.service/cpu.max 2>/dev/null
cat /sys/fs/cgroup/system.slice/ollama.service/cpu.stat 2>/dev/null
df -hT; mount | rg ' /home|/var|/mnt/'
lscpu
rg -o 'avx2?|fma|f16c|bmi2' /proc/cpuinfo | sort -u
```

Record total/available RAM, swap free, cgroup ceilings/events, CPU flags, and whether the model
is on ext4/Linux storage or /mnt/* 9p storage. Do not label an OOM without OOM counters, kernel
evidence, or process evidence.

## Runner and libraries

```bash
readlink -f "$(command -v ollama)"
find /usr/local/lib/ollama /usr/lib/ollama -maxdepth 3 -type f -name 'llama-server*' -print 2>/dev/null
file /path/to/llama-server
ldd /path/to/llama-server
readelf -d /path/to/llama-server | rg 'NEEDED|RPATH|RUNPATH'
timeout 15s /path/to/llama-server --help
/path/to/llama-server --version
namei -l /path/to/llama-server
```

Every ldd dependency must resolve. --help proves executable startup only, not model loading.
Record ownership, mode, RUNPATH, LD_LIBRARY_PATH, and backend subdirectories.

## Processes, ports, logs, model

```bash
ss -ltnp 'sport = :11434'
ps auxww | rg '[o]llama|[l]lama-server'
journalctl -u ollama --since '-30 min' --no-pager -o short-precise
journalctl -k --since '-30 min' --no-pager | rg -i 'oom|killed|segfault|illegal|cuda|vulkan|ollama|llama'
curl -sS http://127.0.0.1:11434/api/version
ollama list
ollama show MODEL --modelfile
find MODEL_ROOT -maxdepth 3 -type f -printf '%p %s bytes %m %u:%g\n'
```

Match the Modelfile blob to the actual file. GGUF metadata and quantization prove parsing, not
tensor loading or warmup readiness. A bounded inference request may be used as a diagnostic,
but do not perform service restarts or process termination without approval.

## Official version evidence

Use official Ollama release pages, documentation, and architecture-specific artifacts. Verify
version, architecture, checksum, and release date before recommending an upgrade or downgrade.

