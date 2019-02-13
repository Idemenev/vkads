@extends('app')

@section('title', 'Список кампаний')

@section('content')

    <div class="breadcrumbs">
        {{ $cabinetName }} | <a href="{{ route('cabinets') }}">К списку кабинетов</a>
    </div>

    @include('vk.ads._table')

@endsection
