# AS-BUILT: GitHub Integration Bootstrap

> **AS-BUILT DOCUMENTATION**  
> This document is being reconstructed after implementation from the actually existing system. It records the observed GitHub-side state and is not an original design specification.

## Scope

This first pass covers only the GitHub-side state relevant to the bootstrap implementation of the Dispatcher and GitHub Adapter. IONOS runtime state, filesystem layout, DNS, PHP/runtime configuration, secrets stored outside GitHub, and endpoint verification are intentionally deferred to a later pass.

## Status

**Incomplete — GitHub pass in progress.**

The following GitHub-side areas are to be captured and verified:

- repository and branch structure
- GitHub Actions/workflows relevant to deployment or integration
- repository rules / branch protection where observable
- tracked deployment/API artifacts that interact with external infrastructure
- boundaries between GitHub-hosted configuration and externally hosted runtime components

## External runtime components known but not yet verified

The current IONOS webspace contains dedicated runtime directories named `dispatcher` and `github.nerozon.de`. Their implementation and configuration are outside the scope of this GitHub-only pass and must not be inferred from repository state.

## Verification rule

Only state directly observable through GitHub is treated as verified in this pass. External configuration or previously discussed design intent is marked as unverified until inspected at the source.
