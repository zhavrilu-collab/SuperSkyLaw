@extends('layouts.app')
@section('title', 'Nova stranka')
@section('nav-suffix', 'Stranke')
@section('content')
<h1 class="h5 text-tema mb-3">Nova stranka</h1>
@include('organization.parties.form-fields')
@endsection
