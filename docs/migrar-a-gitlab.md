# Migrar Velia de GitHub a GitLab

Estado del repositorio al prepararlo (24/11/2026):

- **Repositorio actual:** https://github.com/AnaFraileHerran/velia-mvp
- **Ramas:**
  - `main`: va 9 commits por detrás; no tiene nada que no esté en la otra rama.
  - `migracion-filament-5`: el trabajo actual (Laravel 13 + Filament 5). No hay etiquetas.
- **Sin secretos en el historial:** `.env`, la base de datos SQLite y `auth.json` nunca se han subido; `.gitignore` los excluye.
- **Sin nada propio de GitHub:** no hay `.github/`, Actions, issues ni pull requests que migrar. La integración continua de GitLab está en `.gitlab-ci.yml` y pasa `php artisan test`.
- **Instalación:** `bin/setup.sh` está actualizado a Laravel 13 y Filament 5. Antes instalaba Filament 3 y rompía el proyecto al clonarlo.

## Pasos

1. **Llevar `main` al día** (recomendado, para que la rama por defecto de GitLab tenga el código actual):
   ```bash
   git checkout main && git merge --ff-only migracion-filament-5 && git push origin main
   ```
2. **Crear el proyecto vacío en GitLab**, en el grupo del equipo: sin README y sin `.gitignore`, para que no choque con el historial.
3. **Subir todo el historial:**
   ```bash
   git remote rename origin github
   git remote add origin <URL del proyecto en GitLab>
   git push origin --all
   git push origin --tags
   ```
   Alternativa sin terminal: en GitLab, «New project → Import project → GitHub». Hace lo mismo, pero pide conectar la cuenta de GitHub.
4. **En GitLab:** poner `main` como rama por defecto y protegerla, y comprobar que el primer pipeline pasa (CI/CD → Pipelines).
5. **Dar acceso al equipo** (Antonio Peña incluido) desde Manage → Members.
6. **Cuando todo funcione en GitLab:** archivar el repositorio de GitHub (Settings → Archive) o borrar el remoto `github`.

## Qué cambia fuera del repositorio
- **Claude Code en la web** trabaja con repositorios de GitHub. Si el código pasa a vivir solo en GitLab, habrá que trabajar con Claude Code desde el Mac (en local), o mantener GitHub como espejo de GitLab (GitLab → Settings → Repository → Mirroring repositories → Push).
- Los enlaces a Figma, FigJam y la landing no dependen del repositorio.

## Qué falta por validar
- El primer pipeline no se ha ejecutado: puede que necesite ajustes (imagen de PHP o runners del equipo).
