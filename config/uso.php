<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Límite mensual de generaciones por cuenta
    |--------------------------------------------------------------------------
    |
    | Cuántas generaciones (crear o regenerar un proyecto, un ajuste, crear o
    | regenerar una ficha) puede hacer cada cuenta al mes. Vacío = sin límite.
    | El administrador y las cuentas marcadas "sin límite" no lo tienen nunca.
    | Cuando haya planes de pago, el límite dependerá del plan de cada cuenta.
    |
    */

    'limite_mensual' => env('LIMITE_GENERACIONES_MES'),

];
