# MitraNet Rinjani 1.0.2 --- Cross-Model Recovery Protocol

**Purpose:** Safely resume work when an AI model runs out of tokens or a
session is interrupted.\
**Applies to:** Gemini, Claude Sonnet, Claude Opus, GPT-OSS, and other
assistants.\
**Default mode:** Inspect first; modify only when the task and
authorization are clear.

## 1. Core rule

When the user says "lanjutkan", "lanjutkan perbaikan", "lanjutkan
pengecekan", or "lanjutkan audit", do not immediately edit files or run
scripts. First establish the actual project state and the safest next
step.

Do not assume the previous model completed its last action, saved all
changes, or verified the result.

## 2. Recovery procedure

1.  Read the applicable project instructions and relevant AI checkpoint,
    handoff, and decision records, if available.
2.  Inspect the actual repository state when access is available:
    current branch, HEAD commit, working-tree status, diff, new files,
    changed files, and relevant test/log results.
3.  Identify the active task and what evidence supports its current
    status.
4.  Separate:
    -   **VERIFIED** --- supported by current evidence.
    -   **DOCUMENTED** --- stated in documentation but not independently
        checked.
    -   **INFERRED** --- plausible but not proven.
    -   **UNKNOWN** --- insufficient information.
5.  Identify saved changes, potentially partial changes, and work that
    has not been verified.
6.  Do not overwrite, delete, reset, revert, clean, or replace
    unfamiliar changes as an automatic recovery action.
7.  Report the current state, risks, and safest next step before
    proceeding if there is material uncertainty.
8.  If the task, scope, and authorization are clear, continue only with
    the next safe, permitted action. Do not repeat work that has already
    been verified.
9.  After a meaningful unit of work is verified, prepare a concise
    handoff for the next model.

If the repository or required evidence is inaccessible, state that
limitation plainly. Do not pretend to have inspected files or Git state.

## 3. MitraNet project safeguards

-   Preserve established architecture and components that have been
    explicitly locked.
-   The canonical release ISO belongs only in `iso/`.
-   Do not create a duplicate release ISO in `build/`.
-   Do not rebuild the ISO for changes that do not affect the image.
    Rebuild only when justified by the project policy or explicitly
    requested.
-   Never assume that every code change requires deployment to the Mini
    PC.
-   Do not commit, push, deploy to the Mini PC, alter services or
    production configuration, install dependencies, run migrations, or
    perform other high-impact operations without the required explicit
    authorization.
-   Do not run scripts during a read-only audit. Inspect their contents
    first.
-   If project instructions conflict, report the conflict and do not
    silently choose one.
-   Never overwrite uncommitted user work.
-   Do not treat a successful command as proof that the system works;
    inspect the actual result and run relevant verification where
    authorized.

## 4. Task-specific behavior

### "Lanjutkan"

Reconstruct the last verified state and identify the next authorized
step. Continue only if the state and scope are clear.

### "Lanjutkan perbaikan"

Confirm the issue and its evidence, inspect the relevant implementation,
identify the likely root cause, and define the narrowest fix plus
verification criteria. Do not perform unrelated refactoring.

### "Lanjutkan pengecekan"

Inspect the relevant changes and run only safe, relevant checks that are
authorized. Report results accurately as PASS, FAIL, SKIPPED, or NOT
RUN. Never invent test results.

### "Lanjutkan audit"

Continue the audit within its established scope. Report findings with
evidence, impact, severity, and recommendations. An audit does not
itself authorize code changes.

## 5. Handoff format

At the end of a meaningful work unit, provide a short handoff with:

-   Project and task.
-   Branch and HEAD commit, if verified.
-   Working-tree state, if verified.
-   Changes made and relevant files.
-   Tests actually run and their results.
-   Verified work versus unverified work.
-   Risks, conflicts, and unresolved questions.
-   Actions not performed (for example, commit, push, deployment, or ISO
    build).
-   One safest next step.

Write the handoff to the agreed project location only if documentation
changes are authorized. Otherwise, provide it as text for the user to
save.

## 6. First response after switching models

Start by stating whether repository access is available. Then perform
the read-only recovery inspection before changing anything. Summarize
the evidence and propose the next safe action.

If a critical detail is missing, instructions conflict, or a change may
risk data loss, stop and ask the user before acting.

## 7. Important limitation

This document is a protocol, not an automatic trigger. The user must ask
the new model to read it, and the model must have access to the file or
its contents. It cannot independently detect token exhaustion in another
model or guarantee error-free changes.
