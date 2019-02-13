    <div class="user-info row justify-content-md-right">
        <div class="col-sm-1">
            <img src="{{ $token->photo_url }}">
        </div>
        <div class="col-sm-1">
            {{ $token->first_name }} {{ $token->last_name }}
        </div>
        <div class="col-sm-1">
            <a href="{{ route('logout') }}">Выйти</a>
        </div>
    </div>
