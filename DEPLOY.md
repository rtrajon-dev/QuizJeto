# Deploying QuizJeto to cPanel

## Everyday development
```bash
cd quizjeto
git add -A
git commit -m "what changed"
git push
```

## Cutting a release (builds the cPanel ZIP automatically)
When you're ready to deploy, create and push a **version tag**:

```bash
cd quizjeto
git tag v1.0.0          # bump the number each release: v1.0.1, v1.1.0, ...
git push origin v1.0.0
```

This triggers `.github/workflows/release.yml`, which:
1. Builds `quizjeto-v1.0.0.zip` (project contents, cPanel-ready).
2. Excludes `.git`, `.github`, `.env`, `*.sqlite`, `*.log`, `.DS_Store`.
3. Includes `.htaccess` + `database/.htaccess` (the security files).
4. Publishes a **GitHub Release** with the ZIP attached.

> Tip: you can also run it manually from the repo's **Actions → "Build cPanel ZIP on release" → Run workflow** (produces a downloadable artifact instead of a release).

## Installing on cPanel
1. Download the ZIP from the repo's **Releases** page.
2. In cPanel **File Manager**, open `public_html` and **Upload** the ZIP.
3. Select the ZIP → **Extract** (this preserves the `.htaccess` dotfiles).
4. **Create `.env`** in `public_html` (it is NOT in the ZIP) using `.env.example` as the
   template, and fill in the real `BDAPPS_APP_ID` / `BDAPPS_PASSWORD`.
5. Ensure the `database/` folder is **writable (755)** — the SQLite file auto-creates
   from `schema.sql` + `seed.sql` on first page load.

## Verify after deploy (must both return 403 Forbidden)
- `https://yoursite.com/.env`
- `https://yoursite.com/database/quizjeto.sqlite`

## Updating an existing install
- Re-extract a newer release ZIP over the same folder (overwrites code).
- Your `.env` stays (it's never in the ZIP).
- The `database/` SQLite file stays unless you delete it; delete it only if you changed
  `schema.sql`/`seed.sql` and want it rebuilt.
