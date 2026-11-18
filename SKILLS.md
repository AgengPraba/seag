# SKILLS.md — SIMRS External Access Gateway

## 1. Project Identity

You are a senior Laravel engineer, software architect, and application security engineer.

Your task is to develop a production-oriented application named:

**SIMRS External Access Gateway**

The application will serve as an external access management and integration layer for an existing hospital information system (SIMRS).

The existing SIMRS application uses CodeIgniter 3 (CI3) and PostgreSQL. The new gateway must integrate with the existing application without requiring a complete migration or rewriting the existing SIMRS modules.

### Primary objectives

1. Manage employees authorized to access SIMRS from outside the hospital network.
2. Manage identity mapping between Keycloak identities and existing SIMRS accounts.
3. Provide an approval and revocation workflow for external access.
4. Provide secure integration endpoints consumed by the existing CI3 application.
5. Maintain comprehensive audit logs.
6. Support Keycloak as the Identity Provider.
7. Support deployment behind Nginx and Cloudflare Tunnel.
8. Keep the application configurable across local development, staging, and production environments.

---

## 2. Technology Stack

Use the following technologies:

| Component | Technology |
|---|---|
| Backend | Laravel 13 |
| Language | PHP version compatible with Laravel 13 |
| Frontend | Livewire 4 |
| UI | Tailwind CSS |
| Database | PostgreSQL |
| Identity Provider | Keycloak |
| Authentication Protocol | OpenID Connect (OIDC) |
| Web Server | Nginx |
| External Connectivity | Cloudflare Tunnel |
| Network Firewall | Existing FortiGate |
| Deployment | Docker Compose |
| Testing | Pest or PHPUnit |

Use stable, compatible package versions.

Before installing dependencies, inspect the Laravel and PHP versions available in the development environment.

Do not introduce unnecessary dependencies.

---

## 3. Architecture and System Boundaries

The architecture must follow these responsibilities.

### A. FortiGate

Managed by the hospital network team.

Responsibilities:
- Network segmentation.
- DMZ firewall policies.
- Restricting traffic between DMZ and internal LAN.
- Controlling access to the SIMRS server.
- Enforcing approved network access rules.

The application must not attempt to configure or manage FortiGate automatically.

### B. Cloudflare Tunnel

Used to provide external connectivity without exposing the origin server through direct inbound port forwarding.

Responsibilities:
- Establish outbound tunnel connectivity.
- Publish approved application hostnames.
- Forward requests to the designated reverse proxy.
- Support integration with Cloudflare Access where appropriate.

Important:
- Cloudflare Tunnel is not a replacement for network segmentation.
- Do not expose PostgreSQL through Cloudflare Tunnel.
- Do not expose unrestricted internal network services.
- Do not configure a public tunnel directly to the SIMRS database.
- Keep Cloudflare credentials outside the source code.
- Use least-privilege tunnel configuration.
- The Cloudflare Tunnel connector must only reach the approved reverse proxy or application endpoints.

Cloudflare Access policies and Keycloak authentication must be designed together to avoid conflicting authentication flows.

### C. Nginx Reverse Proxy

Responsibilities:
- Reverse proxy routing.
- HTTPS handling according to the selected TLS architecture.
- Forwarding requests to approved upstream services.
- Security headers.
- Request size limits.
- Rate limiting.
- Access logging.

Never trust arbitrary client-supplied forwarded headers.

Configure trusted proxy addresses explicitly.

### D. Keycloak

Keycloak is the Identity Provider.

Responsibilities:
- User authentication.
- SSO.
- OTP/TOTP MFA.
- OIDC authorization.
- Token issuance.
- Identity management.
- Authentication session management.

Keycloak must remain independent of the SIMRS application database.

### E. Laravel External Access Gateway

Laravel is responsible for:
- External access administration.
- Employee approval workflow.
- Keycloak-to-SIMRS identity mapping.
- External access policies.
- Integration APIs for CI3.
- Audit logs.
- Administrative dashboard.
- System configuration and integration monitoring.

Laravel is NOT responsible for:
- Replacing the existing SIMRS application.
- Reimplementing SIMRS modules.
- Storing SIMRS user passwords.
- Acting as a general-purpose proxy to the internal network.
- Replacing Keycloak authentication.
- Replacing CI3 session management.

### F. Existing SIMRS CI3

CI3 remains the main application.

CI3 responsibilities:
- OIDC client.
- OIDC callback handling.
- OIDC token validation.
- Identity mapping verification.
- External access eligibility verification.
- Creation of the native CI3 session.
- Existing role and permission enforcement.
- Existing application functionality.

CI3 must remain responsible for its own application session.

Do not make Laravel create or manipulate CI3 session cookies directly.

---

## 4. Expected Network Architecture

The target architecture is:

```text
                    EXTERNAL USERS
                          |
                          |
                       Internet
                          |
                          v
                    Cloudflare Edge
                          |
                          |
                  Cloudflare Tunnel
                          |
                          v
                 Tunnel Connector
                          |
                          v
                    FORTIGATE
                  DMZ Segmentation
                          |
                          v
                    NGINX PROXY
                          |
             +------------+-------------+
             |            |             |
             v            v             v
         Keycloak      Laravel       SIMRS CI3
          OIDC         Gateway       Server 101
             |            |             |
             v            v             v
       Keycloak DB   Gateway DB    SIMRS Database
```

This diagram is conceptual.

The actual tunnel connector placement, FortiGate routing, reverse proxy placement, and permitted connections must be finalized with the hospital network team.

Do not assume all components can communicate freely.

### Network security requirements

- No direct Internet access to the SIMRS server.
- No direct Internet access to PostgreSQL.
- Restrict DMZ-to-LAN communication.
- Only allow required upstream destinations and ports.
- Do not publish internal administrative services.
- Do not use unrestricted reverse proxy routes.
- All production traffic must use HTTPS.
- Use private network communication between services where supported.

---

## 5. Application Modules

Develop the application as modular Laravel code.

### Module 1 — Dashboard

Provide an administrative overview containing:

- Total registered users.
- Approved external users.
- Pending approval requests.
- Rejected requests.
- Revoked access.
- Recent login activity.
- Failed authentication attempts.
- Recent administrative activities.
- Keycloak integration status.
- SIMRS integration status.

Do not display sensitive clinical information.

### Module 2 — Employee Management

Manage employee access records.

Features:
- List employees.
- Search by name.
- Search by employee identifier.
- View employee access status.
- View mapped SIMRS account.
- View Keycloak identity association.
- View approval history.

Do not assume that every active SIMRS user is eligible for external access.

### Module 3 — External Access Approval

Implement an approval workflow.

Possible statuses:

- pending
- approved
- rejected
- revoked
- expired

Features:
- Submit access request.
- Approve request.
- Reject request.
- Revoke approved access.
- Set access expiration.
- Record approval administrator.
- Record approval timestamp.
- Record rejection or revocation reason.

Only authorized administrators may approve or revoke access.

Every administrative action must be auditable.

### Module 4 — SIMRS User Mapping

Maintain a mapping between Keycloak identities and existing SIMRS accounts.

Use stable OIDC identity identifiers:

- issuer (`iss`)
- subject (`sub`)

Do not rely exclusively on:
- Username.
- Email.
- Display name.
- Employee name.

Recommended mapping structure:

```text
Keycloak Identity
    |
    +-- issuer
    |
    +-- subject
             |
             v
      SIMRS User Mapping
             |
             +-- user_id
             |
             +-- mapping_status
             |
             +-- created_at
             |
             +-- updated_at
```

The SIMRS `user_id` must reference the existing `app_user.user_id`.

Inspect the actual PostgreSQL schema before defining foreign keys or data types.

Do not guess existing column types.

### Module 5 — Access Policies

Implement policies for external access.

Possible policy attributes:

- User approval status.
- Account active status.
- Access expiration.
- Allowed authentication method.
- MFA requirement.
- Administrative revocation.
- Access scope.

The gateway must not grant SIMRS roles.

Existing SIMRS roles remain authoritative and must be obtained from the existing SIMRS authentication/session mechanism.

### Module 6 — Audit Logs

Record security-relevant events:

- Access request created.
- Access request approved.
- Access request rejected.
- Access revoked.
- Identity mapping created.
- Identity mapping updated.
- Login eligibility checked.
- Login eligibility denied.
- Administrative login.
- Administrative action.
- Integration failure.

Suggested audit fields:

- event_type
- actor_type
- actor_id
- target_user_id
- source_ip
- user_agent
- event_status
- description
- metadata
- created_at

Never store:
- Passwords.
- Access tokens.
- Refresh tokens.
- Client secrets.
- OTP values.
- Full authentication credentials.

Avoid recording sensitive medical information.

### Module 7 — System Integration Settings

Provide configuration and health checks for:

- Keycloak.
- SIMRS.
- OIDC.
- Internal integration API.
- Database connection.

Sensitive secrets must remain in environment variables or a secure secret store.

Do not store client secrets in plain-text application settings.

---

## 6. OIDC Integration Design

The primary authentication architecture is:

**CI3 acts as the OIDC Client.**

Keycloak is the Identity Provider.

Laravel manages external access eligibility and identity mapping.

The intended authentication flow:

```text
User
 |
 v
SIMRS CI3
 |
 | No active session
 v
Keycloak Login
 |
 | Username + Password + OTP
 v
Keycloak
 |
 | Authorization Code
 v
CI3 OIDC Callback
 |
 | Validate state, nonce, PKCE
 | Exchange authorization code
 | Validate ID Token
 v
CI3 Identity Verification
 |
 | Query Laravel internal eligibility endpoint
 v
Laravel Gateway
 |
 | Verify issuer + subject mapping
 | Verify approval status
 | Verify expiration and revocation
 v
CI3
 |
 | Load existing SIMRS account
 | Verify account is active
 | Build native CI3 session
 v
SIMRS Dashboard
```

### OIDC security requirements

Implement or use a mature OIDC client library.

Never manually implement JWT cryptographic verification.

The implementation must validate:

- Issuer.
- Audience.
- Authorized party where applicable.
- Token signature.
- Expiration.
- Issued-at time.
- Nonce.
- State.
- Authorization code.
- PKCE S256.
- Redirect URI.
- TLS certificate validity.

Use Authorization Code Flow.

Disable unnecessary flows such as Implicit Flow and Direct Access Grants.

Never use a client secret in browser-side JavaScript.

Use stable Keycloak issuer URLs.

The callback URL must match the registered Keycloak redirect URI exactly.

---

## 7. Laravel Internal Integration API

Laravel must provide a restricted internal API for CI3.

Example endpoint:

```http
POST /internal/v1/access/verify
```

Example request:

```json
{
  "issuer": "https://sso.example.id/realms/rsud-dev",
  "subject": "keycloak-subject-identifier"
}
```

Example approved response:

```json
{
  "allowed": true,
  "status": "approved",
  "simrs_user_id": 1024,
  "expires_at": "2027-01-01T00:00:00Z"
}
```

Example denied response:

```json
{
  "allowed": false,
  "status": "revoked"
}
```

These are illustrative API contracts, not final database values.

### API security

- Require strong service-to-service authentication.
- Use TLS.
- Apply rate limiting.
- Validate request schemas.
- Reject malformed identities.
- Do not trust client-supplied SIMRS roles.
- Do not return unnecessary employee information.
- Never expose this endpoint publicly without explicit authorization.
- Log security-relevant requests without recording secrets.
- Use fail-closed behavior if eligibility cannot be verified.

CI3 must independently verify that the mapped SIMRS account exists and is active.

Laravel must not become a general-purpose endpoint for querying arbitrary SIMRS data.

### Identity mapping verification

The gateway should resolve the identity using both:

```text
issuer + subject
```

It must not resolve identities based only on a submitted username.

If a mapping is missing, duplicated, inactive, revoked, or expired, deny access.

---

## 8. Database Design

Use a dedicated PostgreSQL database for the Gateway.

Do not reuse the SIMRS database for gateway application data.

Start with these conceptual tables:

### administrators

Stores gateway administrator accounts and roles.

### external_access_requests

Stores external access requests and approval workflow.

Suggested fields:

- id
- employee_identifier
- requested_by
- status
- requested_at
- approved_by
- approved_at
- rejected_by
- rejected_at
- revoked_by
- revoked_at
- expires_at
- reason
- timestamps

### simrs_user_mappings

Stores the Keycloak identity mapping.

Suggested fields:

- id
- issuer
- subject
- simrs_user_id
- status
- timestamps

Create a unique constraint for the appropriate identity combination.

### access_audit_logs

Stores security and administrative activity.

### gateway_service_credentials

Only if required by the chosen service-authentication architecture.

Never store plaintext secrets.

### Important database rules

- Use migrations.
- Use foreign keys where appropriate.
- Add indexes for frequent lookups.
- Use transactions for approval and revocation.
- Define unique constraints.
- Use soft deletion only when appropriate.
- Preserve historical audit records.
- Inspect existing SIMRS schema before implementing cross-system references.

Do not create foreign keys directly into the SIMRS database unless the database architecture explicitly supports and approves it.

---

## 9. Administrator Authentication and Authorization

The Gateway admin panel must have its own secure authentication and authorization.

Support role-based access control.

Suggested roles:

### Super Administrator
- Manage gateway administrators.
- Configure integrations.
- Manage access policies.
- Review all audit logs.

### Access Administrator
- Review access requests.
- Approve and reject access.
- Revoke access.
- View employee mappings.

### Security Auditor
- Read audit logs.
- Review security events.
- View access history.
- Cannot approve or modify access.

Apply least privilege.

Protect administrative routes with middleware and authorization policies.

Do not rely only on hiding buttons in the frontend.

Use server-side authorization checks.

---

## 10. UI/UX Guidelines

Build a professional hospital IT administration interface.

Design characteristics:

- Clean and modern.
- Professional enterprise dashboard.
- Responsive layout.
- Consistent spacing.
- Clear information hierarchy.
- Accessible forms.
- Clear success and error states.
- Confirmation dialogs for sensitive actions.
- Loading indicators.
- Empty states.
- Search and pagination.

Use Indonesian for the primary interface.

Use technical English terminology where appropriate.

Suggested navigation:

```text
Dashboard

Manajemen Akses
  - Daftar Pengguna
  - Permintaan Akses
  - Approval Akses
  - Mapping Akun SIMRS
  - Kebijakan Akses

Keamanan
  - Audit Log
  - Aktivitas Login

Integrasi
  - Keycloak
  - SIMRS
  - Status Layanan

Administrasi
  - Administrator
  - Role & Permission
  - Pengaturan Sistem
```

Do not display unnecessary sensitive information on the dashboard.

---

## 11. Security Requirements

Security is a core requirement, not a later enhancement.

Implement:

- CSRF protection.
- XSS prevention.
- SQL injection prevention.
- Input validation.
- Output escaping.
- Secure session configuration.
- Secure cookies.
- Rate limiting.
- Strong administrator authentication.
- Authorization policies.
- Audit logging.
- Secret management.
- Dependency security checks.
- Error handling without information disclosure.
- Secure password hashing for local administrator accounts.
- Protection against session fixation.
- Protection against replay attacks where relevant.

Never:
- Hardcode passwords or client secrets.
- Commit `.env` files.
- Disable TLS verification.
- Trust arbitrary `X-Forwarded-For` headers.
- Trust usernames as identity proof.
- Expose internal database credentials.
- Return stack traces in production.
- Log access tokens or passwords.
- Allow unrestricted proxying to internal services.

Use secure defaults.

---

## 12. Cloudflare Tunnel Deployment Requirements

Prepare the application for deployment behind Cloudflare Tunnel.

The application must not depend on a specific public IP.

Use environment-based configuration.

Support:
- Public hostname.
- HTTPS external URL.
- Internal service URL.
- Trusted proxy configuration.
- Secure cookie configuration.
- Correct URL generation.
- Health-check endpoint.

Cloudflare Tunnel configuration must be maintained separately from Laravel application code.

Do not put tunnel credentials inside Laravel configuration files or Git.

The deployment design must allow the tunnel to expose only approved hostnames and services.

The tunnel must not expose:
- PostgreSQL.
- SSH.
- Internal administration ports.
- Unrestricted LAN resources.

Cloudflare Access may be used as an additional protection layer for selected administrative interfaces, but must not accidentally block the intended Keycloak OIDC redirect/callback flow.

Validate the interaction between Cloudflare Access, Keycloak, Nginx, and CI3 before production deployment.

---

## 13. Environment Configuration

Use environment variables for all deployment-specific values.

Examples:

```env
APP_NAME="SIMRS External Access Gateway"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=simrs_gateway
DB_USERNAME=postgres
DB_PASSWORD=

KEYCLOAK_BASE_URL=http://localhost:8081
KEYCLOAK_REALM=rsud-dev
KEYCLOAK_CLIENT_ID=simrs-gateway

SIMRS_BASE_URL=http://localhost:8080

INTERNAL_API_SERVICE_SECRET=
```

These are development placeholders only.

Do not assume the production Keycloak hostname, SIMRS IP, or public domain.

Production secrets must be generated securely.

Do not commit actual credentials.

---

## 14. Testing Requirements

Create automated tests for:

### Access approval
- Pending user cannot access SIMRS.
- Approved user can pass eligibility checks.
- Rejected user cannot access.
- Revoked user cannot access.
- Expired access is denied.

### Identity mapping
- Correct issuer and subject are accepted.
- Unknown subject is denied.
- Wrong issuer is denied.
- Duplicate mapping is prevented.
- Inactive mapping is denied.

### Administrator authorization
- Unauthorized administrator cannot approve access.
- Auditor cannot modify mappings.
- Super administrator can manage configuration.

### Internal API
- Missing authentication is denied.
- Invalid service credentials are denied.
- Invalid payload is denied.
- Rate limits are enforced.
- Internal API failure results in access denial.

### Security
- CSRF protection.
- Session security.
- Input validation.
- Authorization bypass attempts.
- Secret leakage prevention.

Use fake identities and test data.

Never use real patient data in automated tests.

---

## 15. Development Workflow for Antigravity

Follow an incremental implementation strategy.

Do not attempt to generate the entire application in one step.

### Phase 1 — Inspect and plan

Before writing code:

1. Inspect the existing project directory.
2. Check PHP and Composer versions.
3. Check whether Laravel 13 is already installed.
4. Inspect existing dependencies.
5. Identify any compatibility problems.
6. Produce a proposed architecture.
7. Produce an initial database schema.
8. Explain security-critical design decisions.

Do not overwrite existing work.

### Phase 2 — Foundation

Implement:

1. Laravel project foundation.
2. PostgreSQL connection.
3. Livewire integration.
4. Admin authentication.
5. Role and permission system.
6. Base layout.
7. Navigation.
8. Dashboard.

### Phase 3 — Access management

Implement:

1. Employee access records.
2. Approval workflow.
3. Identity mapping.
4. Access revocation.
5. Access expiration.
6. Audit logs.

### Phase 4 — Integration

Implement:

1. Keycloak configuration.
2. OIDC integration support.
3. Internal eligibility API.
4. Service-to-service authentication.
5. Integration health checks.
6. CI3 integration documentation.

Do not implement a second, competing OIDC flow in Laravel unless the architecture is explicitly changed.

### Phase 5 — Security testing

Run tests.

Review:
- Authentication.
- Authorization.
- Session management.
- API protection.
- Logging.
- Secret handling.
- Network assumptions.

### Phase 6 — Deployment preparation

Prepare:

- Dockerfile.
- Docker Compose.
- Nginx configuration.
- Production environment template.
- Database migration instructions.
- Backup instructions.
- Deployment documentation.
- Cloudflare Tunnel integration guide.
- FortiGate network requirement documentation.

Do not deploy or expose the application to the Internet without explicit approval.

---

## 16. Coding Standards

Follow these rules:

1. Use Laravel conventions.
2. Use Form Requests for complex validation.
3. Use service classes for integration and business logic.
4. Use policies and middleware for authorization.
5. Use database transactions for critical workflows.
6. Keep controllers thin.
7. Avoid massive controllers.
8. Avoid unnecessary abstraction.
9. Use dependency injection.
10. Use clear naming.
11. Add PHPDoc where it improves clarity.
12. Write automated tests.
13. Keep configuration outside business logic.
14. Avoid duplicating authentication and authorization logic.
15. Document security-sensitive behavior.

Prefer maintainable and readable code over clever implementations.

Do not introduce microservices without a concrete requirement.

---

## 17. Agent Behavior Rules

When working on this project:

- Think like a senior engineer responsible for a hospital security-sensitive application.
- Never invent existing SIMRS database columns.
- Never assume existing CI3 session structures beyond those provided by the project owner or verified in source code.
- Inspect source code before modifying integration behavior.
- Ask for missing information when it affects security or correctness.
- Explain architectural decisions briefly.
- Identify security implications before implementing risky functionality.
- Do not silently change the agreed architecture.
- Do not remove existing functionality.
- Do not perform destructive database operations without explicit confirmation.
- Do not use real patient data.
- Do not expose credentials in generated files.
- Run relevant tests after changes.
- Summarize modified files and test results after each implementation phase.

If a requirement conflicts with security, explain the risk and propose a safer implementation.

---

## 18. Definition of Done

The project is considered ready for integration testing when:

- The administrator dashboard works.
- Administrators can manage external access requests.
- Access approval and revocation work correctly.
- Keycloak identity mapping works.
- The internal eligibility API is secured.
- CI3 can verify external access eligibility.
- Audit events are recorded.
- Automated tests pass.
- The application can run locally.
- Production configuration is environment-based.
- Docker deployment is documented.
- Cloudflare Tunnel deployment requirements are documented.
- No database or administrative service is unintentionally exposed.

**Final principle: Build a secure, maintainable integration gateway. Do not rewrite SIMRS, do not replace Keycloak, and do not turn the gateway into an unrestricted proxy.**