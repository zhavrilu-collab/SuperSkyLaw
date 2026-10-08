@extends('errors.layout')

@section('title', 'Pristup odbijen')
@section('code', '403')
@section('heading', 'Nemate pristup')
@section('message')
    {{ ($exception->getMessage() ?? '') !== '' ? $exception->getMessage() : 'Nemate ovlasti za pristup ovoj stranici. Ako mislite da je riječ o pogrešci, obratite se administratoru ureda.' }}
@endsection
