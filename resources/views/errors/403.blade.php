@extends('errors.minimal')

@section('title', 'Acceso restringido')
@section('code', '403')
@section('message', $exception->getMessage() ?: 'No cuentas con permisos suficientes para acceder a esta sección o recurso del consultorio.')
