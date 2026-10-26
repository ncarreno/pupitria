# Sprint 0: base de la aplicación

*Octubre 2026 · Laravel 13, Livewire 3 + Volt, starter kit con Breeze, laravel/ai 1.3 · Diseño en [../arquitectura.md](../arquitectura.md) · **Cerrado***

## Hecho
- **Migraciones:** rol y preferencias en `users`; currículo (`normativas`, `areas`, `competencias_especificas`, `criterios`, `saberes`, `descriptores`, `competencia_descriptor`); IA (`ia_configuraciones`, `generaciones`).
- **Modelos:** Normativa, Area, CompetenciaEspecifica, Criterio, Saber, Descriptor, IaConfiguracion, Generacion. `User::esAdministrador()`.
- **Comandos:**
  - `curriculo:importar`: importa `database/data/curriculo-primaria-asturias.json`; idempotente.
  - `usuarios:admin {email}`: da el rol de administrador.
  - `ia:probar`: llamada corta a la IA configurada para comprobar claves y proveedor.
- **Servicios:**
  - `CatalogoCurriculo`: bloque de currículo para el prompt, filtro de ids inventados y cálculo de competencias y descriptores.
  - `ClienteIA`: llamada estructurada con proveedor, modelo y respaldo leídos de `ia_configuraciones`; registra cada generación (sin guardar el texto).
- **Middleware `admin`** para las pantallas de administración.
- **Seeders:** importa el currículo y configura Claude (`anthropic`, modelo por defecto) para las tareas proyecto, ajuste, ficha y prueba.
- **Página `/curriculo`** (con sesión iniciada): navegación por área y ciclo, con buscador.
- **Tests:** `CurriculoTest` y `ClienteIATest`.
- **`.env`:** nombre "Taller de Proyectos", URL `http://app-colegios.test`, idioma `es` y huecos para las claves.

## Verificado
- `migrate:fresh --seed`: 10 áreas, 58 competencias, 429 criterios, 865 saberes y 34 descriptores.
- Migraciones, admin, `ia:probar`, tests y Laravel Boost funcionando en el Mac de Nacho.

## Siguiente: [sprint 1](sprint-1.md) (Mis clases)
