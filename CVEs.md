# CVE Security Documentation

This document tracks CVEs that affected this repository and their remediation status.

---

## Summary

| CVE | Severity | Package | Status | Fixed Date |
|-----|----------|---------|--------|------------|
| CVE-2025-54068 | CRITICAL (9.8) | livewire/livewire | **FIXED** | 2026-01-18 |
| CVE-2025-64500 | HIGH | symfony/http-foundation | **FIXED** | 2026-01-18 |
| CVE-2025-27515 | MEDIUM | laravel/framework | **FIXED** | 2026-01-18 |

---

## CVE-2025-54068 - Livewire Remote Code Execution

### Overview
- **Package:** livewire/livewire
- **Severity:** CRITICAL (CVSS 9.8)
- **Type:** Remote Code Execution (RCE)
- **Affected Versions:** 3.0.0-beta.1 to 3.6.3
- **Fixed Version:** 3.6.4+

### Description
A vulnerability in Livewire v3 allows unauthenticated attackers to achieve remote command execution by smuggling synthesizers through the updates field on any Livewire request. The issue stems from how certain component property updates are hydrated during the `/livewire/update` endpoint processing.

### Attack Vector
1. Attacker sends crafted POST request to `/livewire/update`
2. Malicious payload is smuggled via synthesizer manipulation
3. Server executes arbitrary PHP code during property hydration
4. No authentication required

### Impact on This Repository
**This CVE was actively exploited against this application:**
- Attack period: 2026-01-07 to 2026-01-18
- Evidence: Anomalous 31-byte responses to `/livewire/update` (normal: ~262KB)
- Result: PHP webshells deployed, XMRig crypto-miner installed
- User-Agent signature: `Chrome/91.0.4472.124` (outdated, typical for exploit tools)

### Remediation
```bash
# Previous vulnerable version
livewire/livewire: 3.6.3

# Updated to
livewire/livewire: 3.7.4
```

### References
- [GitHub Advisory GHSA-29cq-5w36-x7w3](https://github.com/livewire/livewire/security/advisories/GHSA-29cq-5w36-x7w3)
- [NVD CVE-2025-54068](https://nvd.nist.gov/vuln/detail/CVE-2025-54068)
- [Synacktiv Technical Advisory](https://www.synacktiv.com/en/advisories/livewire-remote-command-execution-through-unmarshaling)

---

## CVE-2025-64500 - Symfony HTTP Foundation Authorization Bypass

### Overview
- **Package:** symfony/http-foundation
- **Severity:** HIGH
- **Type:** Authorization Bypass
- **Affected Versions:** Multiple version ranges up to 7.3.6
- **Fixed Version:** 7.3.7+, 7.4.0+

### Description
Incorrect parsing of `PATH_INFO` can lead to limited authorization bypass. Attackers can craft requests that bypass route-based authorization checks by manipulating the path information.

### Impact on This Repository
While not confirmed as exploited, this vulnerability could have allowed:
- Bypass of route-level middleware
- Access to protected endpoints
- Circumvention of authentication checks

### Remediation
```bash
# Previous vulnerable version
symfony/http-foundation: 7.3.0

# Updated to
symfony/http-foundation: 7.4.3
```

### References
- [Symfony Security Blog](https://symfony.com/blog/cve-2025-64500-incorrect-parsing-of-path-info-can-lead-to-limited-authorization-bypass)

---

## CVE-2025-27515 - Laravel File Validation Bypass

### Overview
- **Package:** laravel/framework
- **Severity:** MEDIUM
- **Type:** Input Validation Bypass
- **Affected Versions:** < 11.44.1, < 12.1.1
- **Fixed Version:** 11.44.1+, 12.1.1+

### Description
When using wildcard validation to validate file or image fields (e.g., `files.*`), a user-crafted malicious request could potentially bypass the validation rules. This affects file upload handling in forms.

### Impact on This Repository
This vulnerability could have allowed:
- Bypass of file type validation
- Upload of malicious files disguised as legitimate types
- Potential for further exploitation via uploaded payloads

### Remediation
```bash
# Previous vulnerable version
laravel/framework: 12.18.0

# Updated to
laravel/framework: 12.47.0
```

### References
- [GitHub Advisory GHSA-78fx-h6xr-vch4](https://github.com/advisories/GHSA-78fx-h6xr-vch4)

---

## Code Fixes Applied

### 1. FileUpload Validation (ListUsers.php)

**File:** `app/Filament/Resources/UserResource/Pages/ListUsers.php`

**Issue:** FileUpload component accepted all file types without validation at upload time.

**Fix Applied:**
```php
FileUpload::make('csvFile')
    ->label(false)
    ->required()
    ->acceptedFileTypes(['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'])
    ->maxSize(5120) // 5MB max
    ->disk('local') // Use private storage, not public
    ->directory('imports')
    ->visibility('private'),
```

**Changes:**
- Added MIME type whitelist
- Added file size limit
- Changed storage from `public` to `local` (private)
- Set visibility to `private`

### 2. PDF Service Security (PdfExportService.php)

**File:** `app/Services/PdfExportService.php`

**Issue:** Chrome sandbox was unconditionally disabled; directory permissions were too permissive (0777).

**Fix Applied:**
- Changed directory permissions from `0777` to `0755`
- Made `noSandbox()` configurable via `config('laravel-pdf.browsershot.no_sandbox')`
- Sandbox is now enabled by default (more secure)

---

## Version History

| Date | Action | Packages Updated |
|------|--------|------------------|
| 2026-01-18 | Security patch | livewire/livewire 3.6.3 → 3.7.4 |
| 2026-01-18 | Security patch | laravel/framework 12.18.0 → 12.47.0 |
| 2026-01-18 | Security patch | symfony/http-foundation 7.3.0 → 7.4.3 |
| 2026-01-18 | Code fix | ListUsers.php - FileUpload validation |
| 2026-01-18 | Code fix | PdfExportService.php - Sandbox configuration |

---

## Ongoing Monitoring

To check for new vulnerabilities:

```bash
# Run composer security audit
composer audit

# Check for outdated packages
composer outdated --direct

# Run npm security audit (if applicable)
npm audit
```

---

*Document created: 2026-01-18*
*Last updated: 2026-01-18*
