<li class="dropdown {{ \App\Misc\Helper::menuSelectedHtml('overflowachievement') }}">
    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false" aria-haspopup="true" v-pre>
        {{ __('Achievement') }} <span class="caret"></span>
    </a>
    <ul class="dropdown-menu">
        <li class="{{ \App\Misc\Helper::menuSelectedHtml('overflowachievement.my') }}">
            <a href="{{ route('overflowachievement.my') }}">{{ __('My Progress') }}</a>
        </li>
        <li class="{{ \App\Misc\Helper::menuSelectedHtml('overflowachievement.achievements') }}">
            <a href="{{ route('overflowachievement.achievements') }}">{{ __('Trophies') }}</a>
        </li>
        @if (\Option::get('overflowachievement.show_leaderboard', 1))
            <li class="{{ \App\Misc\Helper::menuSelectedHtml('overflowachievement.leaderboard') }}">
                <a href="{{ route('overflowachievement.leaderboard') }}">{{ __('Leaderboard') }}</a>
            </li>
        @endif
    </ul>
</li>
