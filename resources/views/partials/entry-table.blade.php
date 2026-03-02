@if($entries->isEmpty())
    <div class="wd-empty">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/>
            <path d="M16 16s-1.5-2-4-2-4 2-4 2"/>
            <line x1="9" y1="9" x2="9.01" y2="9"/>
            <line x1="15" y1="9" x2="15.01" y2="9"/>
        </svg>
        <div class="wd-empty-title">No entries found</div>
        <div class="wd-empty-text">There are no {{ $typeName ?? 'entries' }} to display.</div>
    </div>
@else
    <table class="wd-table">
        <thead>
            <tr>
                @foreach($columns as $column)
                    <th>{{ $column['label'] }}</th>
                @endforeach
                <th>Time</th>
            </tr>
        </thead>
        <tbody>
            @foreach($entries as $entry)
                <tr>
                    @foreach($columns as $column)
                        <td>
                            @if(isset($column['link']))
                                <a href="{{ route($column['link'], $entry->uuid) }}" style="color: var(--wd-primary); text-decoration: none;">
                                    {{ \Illuminate\Support\Str::limit(data_get($entry, $column['field'], 'N/A'), $column['limit'] ?? 80) }}
                                </a>
                            @elseif(isset($column['badge']))
                                <span class="wd-badge wd-badge-{{ $column['badge']($entry) ?? 'muted' }}">
                                    {{ data_get($entry, $column['field'], 'N/A') }}
                                </span>
                            @elseif(isset($column['code']) && $column['code'])
                                <code class="wd-code-inline">{{ \Illuminate\Support\Str::limit(data_get($entry, $column['field'], 'N/A'), $column['limit'] ?? 80) }}</code>
                            @elseif(isset($column['duration']) && $column['duration'])
                                @php $dur = data_get($entry, $column['field'], 0); @endphp
                                <span class="wd-duration {{ $dur > 1000 ? 'wd-duration-slow' : ($dur > 100 ? 'wd-duration-medium' : 'wd-duration-fast') }}">
                                    {{ number_format($dur, 2) }}ms
                                </span>
                            @else
                                {{ \Illuminate\Support\Str::limit(data_get($entry, $column['field'], 'N/A'), $column['limit'] ?? 80) }}
                            @endif
                        </td>
                    @endforeach
                    <td style="white-space: nowrap; color: var(--wd-text-muted); font-size: 13px;">
                        {{ \Carbon\Carbon::parse($entry->created_at)->diffForHumans() }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if($entries->count() >= 50)
        <div class="wd-pagination">
            <a href="?before={{ $entries->last()->id }}" class="wd-btn">Load More</a>
        </div>
    @endif
@endif
