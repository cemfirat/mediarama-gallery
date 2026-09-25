# Authentication Boundary

Status: foundation

HTTP controllers must not parse actor identity directly from arbitrary request headers.

Controllers depend on the application-level `CurrentUser` abstraction.

For local development and tests only, `DevelopmentActorSubscriber` can map `X-Mediarama-User` into the request authentication context.

Production environments ignore that header.

The production authenticator will populate the same request context after validating a real session/token. This keeps upload, library and administration use cases independent of the final authentication transport.

## Rules

- no production trust of `X-Mediarama-User`
- authorization remains separate from authentication
- UUID actor identity is resolved once at the HTTP/security boundary
- application services receive explicit user identity
- collection ACLs remain resource-scoped

## Next

Implement the product authentication UX and authenticator before public deployment. The exact transport (session-first web UI, optional API token later) should not leak into domain/application code.
