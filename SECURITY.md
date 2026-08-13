# Security Policy

Report vulnerabilities privately rather than through a public issue.

Sentinel is an orchestration and staff-workflow layer. Enforcement security remains dependent on Exile and impersonation security remains dependent on Masquerade.

Important deployment requirements:

- Restrict `sentinel.access` and every enforcement permission to trusted staff.
- Keep the Exile hash key outside source control.
- Do not permit moderator enforcement while masquerading.
- Require re-authentication / MFA for especially sensitive admin operations where appropriate.
- Treat internal notes, evidence references, IP information and audit metadata as sensitive staff data.
- Do not expose Sentinel routes to unauthenticated users.
