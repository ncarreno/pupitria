# Documentación de Taller de Proyectos

App para que docentes de Primaria (piloto en Asturias) generen proyectos y fichas ajustados a su clase y al currículo, con IA. Esta carpeta es el contexto para trabajar en el código. La investigación de producto, las entrevistas y los análisis siguen en el proyecto de Claude ("Aplicación para ayudar a maestros…").

## Qué leer
| Archivo | Para qué |
|---|---|
| [producto.md](producto.md) | Qué es la v1, para quién, qué entra y qué no, reglas de producto |
| [arquitectura.md](arquitectura.md) | Decisiones técnicas, módulos, IA, datos, despliegue y hoja de ruta |
| [sprints/](sprints/) | Qué se hizo en cada sprint y qué quedó pendiente |
| [sprints/sprint-4.md](sprints/sprint-4.md) | **Próximo sprint** (plan, sin empezar) |

## Estado (26 oct 2026)
- **Sprints 0–3 hechos.** Cuentas y roles, currículo importado, Mis clases, generador de proyectos con versiones y fichas en 3 niveles con ilustración (OpenAI o prompt para ChatGPT/Gemini). Tests en verde en el Mac de Nacho.
- **En prueba con la beta tester:** proyectos ("Exploradores del planeta", artes escénicas) y fichas. Sus comentarios deciden los ajustes de prompts y los casos de la batería del sprint 4.
- **Siguiente:** sprint 4, panel de administración de IA y batería de pruebas comparativa.

## Entorno local
- Laravel 13, Livewire 3 + Volt, Alpine, Tailwind, `laravel/ai`. PHP 8.4 con Laravel Herd, MySQL. URL `http://app-colegios.test`.
- `composer run dev` arranca Vite y el worker de la cola. **Sin la cola no se genera nada** (proyectos y fichas van en jobs).
- `php artisan test` antes de dar algo por terminado.
- Comandos propios: `curriculo:importar`, `usuarios:admin {email}`, `ia:probar`, `ia:probar-imagen`.
- Claves en `.env`: `ANTHROPIC_API_KEY`, `OPENAI_API_KEY`.

## Reglas para trabajar en el código
- **Interfaz, dominio y tests en español** (modelos `Ficha`, `Clase`; métodos como `estaGenerando()`; textos dirigidos a "la docente"). Mantener ese estilo.
- **Sin datos personales de alumnos.** Solo curso, número de alumnos, cuántos con refuerzo o ampliación y áreas. Nada de eso cambia sin decisión de Nacho.
- **Toda llamada a la IA pasa por `App\Services\IA\ClienteIA`**, que lee `ia_configuraciones` y registra en `generaciones` (sin guardar el texto). No llamar al SDK directamente desde otro sitio.
- **Validación curricular independiente de la IA:** los ids de criterios y saberes se comprueban contra la base de datos y se descartan los inventados.
- **Nunca texto dentro de las imágenes.** Los textos van en HTML editable.
- **Cada docente solo ve lo suyo** (policies en clases, proyectos y fichas). Cada pantalla nueva necesita su test de aislamiento.
- Prompts en vistas Blade en `resources/views/prompts/`, con el currículo al principio (caché).
- Al cerrar un sprint: actualizar su archivo en `docs/sprints/` (Hecho / Pendiente de Nacho / Siguiente) y la tabla de la hoja de ruta en `arquitectura.md`.
