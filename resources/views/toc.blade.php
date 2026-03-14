<div class="pdf-toc" style="page-break-after: always;">
    <h1 style="text-align: center; margin-bottom: 1.5em;">{{ $title }}</h1>
    @foreach ($entries as $entry)
        <div class="toc-entry toc-level-{{ $entry->level }}" style="display: flex; margin-left: {{ ($entry->level - 1) * 20 }}px; margin-bottom: 0.4em; font-size: {{ $entry->level === 1 ? '14px' : '12px' }}; font-weight: {{ $entry->level === 1 ? 'bold' : 'normal' }};">
            <a href="#{{ $entry->anchorId }}" style="text-decoration: none; color: #333; flex: 1;">{{ $entry->text }}</a>
            <span class="toc-dots" style="flex: 1; border-bottom: 1px dotted #ccc; margin: 0 4px; position: relative; top: -4px;"></span>
            <span class="toc-page" style="white-space: nowrap;">{{ $entry->pageNumber }}</span>
        </div>
    @endforeach
</div>
