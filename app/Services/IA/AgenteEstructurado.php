<?php

namespace App\Services\IA;

use Laravel\Ai\Attributes\CacheInstructions;
use Laravel\Ai\StructuredAnonymousAgent;

/**
 * El agente anónimo del SDK con la caché de prompts de Anthropic activada.
 *
 * Anthropic solo cachea si se le marca un punto de corte: `CacheInstructions` lo pone al final de
 * las instrucciones de sistema. Por eso el currículo va en las instrucciones y no en la petición:
 * un ajuste, un reintento o un proyecto con las mismas áreas y ciclo lo leen de la caché (a un 10 %
 * del precio) en lugar de pagarlo entero otra vez. OpenAI y Gemini cachean solos e ignoran el atributo.
 *
 * En los tests se simula con `AgenteEstructurado::fake()`.
 */
#[CacheInstructions]
class AgenteEstructurado extends StructuredAnonymousAgent {}
