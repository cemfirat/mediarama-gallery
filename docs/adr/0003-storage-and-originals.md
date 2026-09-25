# ADR-0003: Storage abstraction and immutable originals

- Status: Accepted
- Date: 2026-09-25

## Decision

Media storage will be accessed through a storage abstraction.

Initial required adapters:

1. local filesystem;
2. S3-compatible object storage.

A media asset stores a stable storage key, not a public URL or webroot-relative filename as its identity.

Original uploads are immutable by default. Operations such as resizing, orientation normalization, watermarking, poster generation and web optimization produce derivatives.

Derivatives are first-class records with their own storage key, MIME type, dimensions/size and processing version.

## Why

This removes Coppermine's coupling between database identity and `fullpath + filepath + filename`, supports modern deployments, makes regeneration possible, and prevents destructive media processing.

## Consequences

- public URLs are resolved by the storage layer;
- renaming a title/original filename does not require moving an object;
- watermark policies can change without destroying the source;
- local self-hosting remains simple;
- object storage can scale without redesigning the domain.
