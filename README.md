# SuperSkyLaw

SaaS za odvjetničke urede na Core platformi (port **8006**).

## Dev setup

```powershell
cd C:\Users\zoran.havriluk\SuperSkyLaw
copy .env.example .env
C:\xampp\php\php.exe artisan key:generate
C:\xampp\php\php.exe artisan migrate --seed
C:\xampp\php\php.exe artisan serve --host=127.0.0.1 --port=8006
```

## Integracija s Core konzolom

- **Application slug:** `legal-saas`
- **Sync API:** `GET/PATCH /api/admin/organizations` (Bearer `ADMIN_SYNC_API_KEY`)
- **Core auth:** `IDENTITY_CORE_AUTH_ENABLED=true` → prijava preko `/api/v1/auth/login`

## Test prijava (lokalno bez Core-a)

- E-mail: `partner@law.test`
- Lozinka: `password`
- Ured: `ured-horvat`
