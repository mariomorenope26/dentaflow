@extends('errors.minimal')

@section('title', 'Demasiadas solicitudes')
@section('code', '429')
@section('message', 'Has realizado demasiadas peticiones en poco tiempo. Espera un minuto antes de volver a intentarlo.')
