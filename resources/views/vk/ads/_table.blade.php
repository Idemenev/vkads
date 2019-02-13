@if (!empty($data))
    <table class="table table-striped table-bordered">
        <thead>
        <tr>
            @foreach ($data[0] as $k => $v)
                <th>{{ $k }}</th>
            @endforeach
        </tr>
        </thead>
        <tbody>
        @foreach ($data as $v)
            <tr>
                @foreach ($v as $k => $vv)
                    @if (!isset($campaignId) && $k == 'account_id')
                        <td><a href="{{ route('cabinet', [$vv, $v['account_name']]) }}">{{ $vv }}</a></td>
                    @elseif ($k == 'id')
                        <td><a href="{{ route('campaign', [$cabinetId, $cabinetName, $vv, $v['name']]) }}">{{ $vv }}</a></td>
                    @else
                        <td>{{ $vv }}</td>
                    @endif
                @endforeach
            </tr>
        @endforeach
        </tbody>
    </table>
@else
    Нет результатов
@endif
