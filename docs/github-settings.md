# GitHub Container Registry (GHCR) and Secrets Documentation

## 1. Authentication Mechanism
GHCR publication in `.github/workflows/ci.yml` uses the standard GitHub-provided token:
```yaml
password: ${{ secrets.GITHUB_TOKEN }}
```
No manual personal access token (PAT) or external container registry secret is required.

## 2. GitHub Actions Permissions
The `deploy` job defines job-level permissions:
```yaml
permissions:
  contents: read
  packages: write
```
To enable GHCR package pushes:
1. Go to repository **Settings** → **Actions** → **General**.
2. Under **Workflow permissions**, verify either:
   - "Read and write permissions" is selected, OR
   - "Read repository contents and packages permissions" is selected (job-level `packages: write` handles this).
3. Ensure "Allow GitHub Actions to create and approve pull requests" is configured per branch protection policy.

## 3. GHCR Image Naming and Tags
- Published images:
  - Backend: `ghcr.io/<lowercase-repo-owner>/<lowercase-repo-name>/backend:<commit-sha>`
  - Frontend: `ghcr.io/<lowercase-repo-owner>/<lowercase-repo-name>/frontend:<commit-sha>`
- The workflow normalizes the repository name to lowercase using `tr '[:upper:]' '[:lower:]'` to satisfy GHCR's strict lowercase naming specification.
- Tags are strictly immutable commit SHAs (`${{ github.sha }}`). The `latest` tag is intentionally avoided.

## 4. Application Secrets Policy
- **No secrets required for GHCR deploy:** `APP_KEY`, database credentials (`DB_PASSWORD`, `DB_USERNAME`, `DB_DATABASE`) are runtime environment variables used by local Docker Compose (`.env`) or live hosting environments, not build-time secrets for publishing Docker images.
- Neither Dockerfile contains baked-in keys, secrets, or build arguments.
- Real production `.env` remains gitignored; only `.env.example` is committed with placeholder values.
