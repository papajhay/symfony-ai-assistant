---
name: ollama-llama-server-troubleshooter
description: Diagnose and safely repair Ollama llama-server startup failures on WSL2.
---

# Ollama llama-server troubleshooter

Use this skill for Ollama or llama-server startup failures, startup timeouts, and model-load
errors in Ubuntu WSL2 projects, including Symfony applications that use the local Ollama API.
It is reusable: detect actual users, paths, versions, models, backends, and service configuration
instead of assuming the initial environment.

## Scope and safety

- Diagnose first, repair second. A running ollama serve process or successful /api/version
  call does not prove that llama-server can load a model.
- Start with read-only checks. Before any system-wide, destructive, service, binary, ownership,
  WSL, or downloaded-model change, present the evidence, proposed minimal repair, rollback plan,
  and approval request. Do not execute that mutation without approval.
- Never delete, move, or redownload models or /home/*/.ollama without explicit approval.
  Never delete the Symfony project or modify Symfony application code for a runner failure.
- Never replace system libraries, disable security protections, or blindly increase WSL memory,
  CPU, swap, context length, or timeout.
- Back up the exact original file before an approved systemd or runner change. Use sudo only when
  required and explain why. Prefer one reversible variable or file change at a time.
- Redact API keys, tokens, cookies, and unrelated secrets from logs and reports.

## Diagnostic workflow

1. Discover the actual OS/WSL kernel, architecture, service user/group, systemd unit, Ollama
   executable/version, runner paths, model root, model name, API endpoint, and Symfony endpoint.
2. Measure memory, swap, cgroup memory/cpu limits and events, CPU flags, disk/filesystem type,
   process RSS, permissions, and port ownership. Distinguish available memory from free memory.
3. Inspect the runner with file, ldd, readelf, --help, and --version. Check every "not found",
   ELF architecture, RUNPATH/LD_LIBRARY_PATH, executable permissions, and adjacent libraries.
4. Read Ollama and kernel logs. Classify the event as missing dependency, permission error,
   port conflict, process exit, SIGSEGV/SIGILL, OOM, GPU/backend probe failure, client
   cancellation, slow tensor loading, or warmup stall.
5. Establish the selected backend from the exact runner command and logs. Check CPU feature
   compatibility separately from CUDA, Vulkan, ROCm, and GPU-library discovery. A failed GPU
   probe is not root cause if the final runner correctly selects CPU.
6. Validate the existing model without deleting it: resolve its manifest/blob, check ownership,
   size and accessibility, inspect GGUF metadata/quantization, and only hash large files when
   useful. Model parsing is not proof of successful tensor load.
7. Compare Ollama and runner build identities with official Ollama release notes, documentation,
   and architecture-specific artifacts. Do not recommend upgrade/downgrade until the installed
   version and target artifact are verified.
8. Produce a findings table: confirmed facts, ruled-out causes, hypotheses, confidence, evidence,
   and the smallest safe repair. Stop for approval before mutation.

Read the relevant reference before using its procedure:

- [diagnostic-commands.md](references/diagnostic-commands.md)
- [common-errors.md](references/common-errors.md)
- [wsl2-ollama-guide.md](references/wsl2-ollama-guide.md)

## Repair rules

- Missing libraries: repair only the proven dependency or library-path issue; never use arbitrary
  system-library replacements.
- Permissions: change only the affected exact path and preserve model ownership/data.
- CPU/backend: choose a documented compatible backend only when evidence shows the current one is
  wrong or fails initialization; do not force CPU/GPU speculatively.
- Memory/WSL: adjust context, parallelism, swap, or limits only when measured allocation pressure
  supports it and the change is reversible.
- Load timeout: increase OLLAMA_LOAD_TIMEOUT only when logs show continued progress and a live
  client; a timeout increase cannot repair a stalled or crashed runner.
- Version regression: prefer a verified official artifact, retain a rollback copy, and avoid
  reinstalling as the first response.

## Verification gate

After an approved repair, verify:

- systemctl status ollama --no-pager -l
- curl -s http://127.0.0.1:11434/api/version
- ollama list
- ollama ps
- ollama run MODEL "Bonjour, explique-moi Symfony en français."
- a non-streaming /api/generate request returning HTTP 200 and a real response
- model availability after restarting the Ollama service

If the project exposes /chat, test the existing ChatService/ChatController path without changing
application code. Do not claim resolution until llama-server starts, a real response is observed,
the API returns 200, and the model remains available after restart.

