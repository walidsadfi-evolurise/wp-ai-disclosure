# Security

Please report vulnerabilities to walid.sadfi@evolurise.com, not in a public issue. Expect an answer within 5 working days.

Scope notes: the impression counter endpoint is public and unauthenticated by design (it only increments a daily integer); settings are only writable by users with `manage_options` through the WordPress Settings API with nonce verification; all output is escaped at render time.
