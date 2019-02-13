@extends('app')

@section('title', 'Список объявлений')

@section('content')

    <div class="breadcrumbs">
        {{ $cabinetName }} / {{ $campaignName }} | <a href="{{ route('cabinet', [$cabinetId, $cabinetName]) }}">К списку кампаний</a>
    </div>

    @forelse ($data as $ad)
        @php
            $VkAdHelper = new VkAdHelper($ad);
        @endphp
        <table class="table table-striped table-bordered ad">
            <thead>
                <tr>
                    <th colspan="2">{{ $VkAdHelper->getName() }} <input type="button" value="удалить" class="destroy" name="id-{{ $ad['id'] }}"></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Название кампании</td>
                    <td>{{ $campaignName }}</td>
                </tr>
                <tr>
                    <td>Дневной лимит</td>
                    <td>{{ $VkAdHelper->getDayLimit() }}</td>
                </tr>
                <tr>
                    <td>Лимит объявления</td>
                    <td>{{ $VkAdHelper->getAllLimit() }}</td>
                </tr>
                <tr>
                    <td>{{ $VkAdHelper->getCostTitle() }}</td>
                    <td>{{ $VkAdHelper->getCost() }}</td>
                </tr>
                <tr>
                    <td>Статус</td>
                    <td>{{ $VkAdHelper->getStatus() }}</td>
                </tr>
                <tr>
                    <td>Дата запуска</td>
                    <td>{{ $VkAdHelper->getStartTime() }}</td>
                </tr>
                <tr>
                    <td>Дата остановки</td>
                    <td>{{ $VkAdHelper->getStopTime() }}</td>
                </tr>
                <tr>
                    <td>ad_platform</td>
                    <td>{{ $VkAdHelper->getAdPlatform() }}</td>
                </tr>
                <tr>
                    <td>impressions_limit</td>
                    <td>{{ $VkAdHelper->getImpressionsLimit() }}</td>
                </tr>
                <tr>
                    <td>impressions_limited</td>
                    <td>{{ $VkAdHelper->getImpressionsLimited() }}</td>
                </tr>
                <tr>
                    <td>Тематики</td>
                    <td>{{ $VkAdHelper->getCategory() }}</td>
                </tr>
                <tr>
                    <td>Целевая аудитория</td>
                    <td>{{ $VkAdHelper->getAudienceCount() }}</td>
                </tr>
                <tr>
                    <td>Города</td>
                    <td>{{ $VkAdHelper->getCities() }}</td>
                </tr>
                <tr>
                    <td>Демография</td>
                    <td>{{ $VkAdHelper->getTargetGroups() }}</td>
                </tr>
                <tr>
                    <td>Категории интересов</td>
                    <td>{{ $VkAdHelper->getTargetInterests() }}</td>
                </tr>
                @if ($VkAdHelper->getLink())
                    <tr>
                        <td>Ссылка</td>
                        <td><a href="{{ $VkAdHelper->getLink() }}">{{ $VkAdHelper->getLink() }}</a></td>
                    </tr>
                @endif
                <tr>
                    <td>Примечание:</td>
                    <td>
                        <textarea maxlength="100">{{ (isset($comments[$ad['id']]) ? $comments[$ad['id']]->comment : '') }}</textarea>
                        <input type="button" value="сохранить" class="update" name="id-{{ $ad['id'] }}">
                    </td>
                </tr>
            </tbody>
        </table>
        <hr>
    @empty
        Нет результатов
    @endforelse

@endsection
