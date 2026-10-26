# Producto: v1

*Resumen del documento "Funcionalidades de la primera versión" del proyecto de Claude (octubre 2026).*

## Decisiones de partida
- **Producto nuevo**, sin reutilizar Aula Pro.
- **Etapa:** Educación Primaria. **Comunidad piloto:** Principado de Asturias (Decreto 57/2022).
- **Modelo:** B2C, paga la docente individual (freemium). Hipótesis: gratis con generaciones limitadas al mes; de pago unos 5–8 €/mes.
- **Beta tester y fuente de requisitos:** tutora de 3.º de Primaria en Asturias (17 alumnos, 21 años de experiencia).
- **Regla:** la norma dice qué debe existir, no cómo se hace en el centro. Si el decreto y la práctica no coinciden, manda lo que haga la beta tester.
- **Riesgo asumido:** sesgo de una única usuaria. Se mitigará con 2–3 compañeras cuando la app le sea útil.

## El competidor real es ChatGPT
Ya paga 20 €/mes y lo usa a diario para programar. La app tiene que ser mejor que ChatGPT en sus tareas: más rápida, más ajustada y menos genérica.

**Principio de diseño: contexto guardado, resultados a medida.** La docente no tiene que explicar cada vez su curso, su clase, el currículo ni su estilo. La app ya lo sabe y genera resultados con criterios reales de Asturias y sin el tono genérico "de IA".

## Imprescindible en la v1
1. **Mi clase (sin datos personales):** curso, ciclo, áreas, número de alumnos y cuántos necesitan refuerzo o ampliación. Sin nombres (Additio ya guarda esos datos).
2. **Currículo de Asturias precargado:** competencias, criterios y saberes con sus códigos.
3. **Generador de situaciones de aprendizaje y proyectos integrados:** a partir de una idea, la situación completa vinculada a criterios reales de varias áreas; refinar por partes; exportar a Word o PDF.
4. **Fichas en tres niveles:** refuerzo, estándar y ampliación de la misma actividad, imprimibles y vinculadas a criterios y al proyecto. Es su dolor principal.
5. **Estilo propio:** marcar qué resultados le gustan y subir ejemplos suyos, para que no salgan "todos casi iguales". (Planificado para después de la v1 en la hoja de ruta técnica.)

## Pendiente de explorar
- **Preparación de reuniones con familias:** la tarea que más le gustaría quitarse de encima. Antes de diseñar hay que saber qué prepara, con qué datos y cuánto tarda.

## v1.1
- Programación de aula semanal a partir de las situaciones de aprendizaje.
- Importar o exportar datos de Additio.
- Ayuda con la corrección (por explorar).
- Planificador semanal y agenda.

## Fuera de la v1
Cuaderno de evaluación (Additio), informes, integración con SAUCE, datos personales de alumnos, gestión de centro, mensajería con familias, app nativa, otras etapas y comunidades.

## Reglas transversales
- **La docente siempre revisa:** la IA propone, ella decide (todo es editable).
- **Más rápido que ChatGPT:** primer resultado útil en menos de 2 minutos, sin escribir un prompt largo.
- **Proveedor de IA:** lo elige solo el administrador para toda la app. Excepción decidida el 26 oct: en las fichas, la docente elige cómo se hace la ilustración (OpenAI o prompt para ChatGPT/Gemini).
