{{--
    Reusable cover-header section (proposal + quotation pages share the same design).

    Props (merged into this view's scope):
      $metalRailData   — associative array with keys:
                          document_label, client_label, scope_label,
                          reference_label, date_label, client_name, scope
                          reference_value (optional — overrides $proposal->reference_number)
                          date_value      (optional — overrides $issueDate)
      $logoData        — inherited from parent scope
      $proposal        — inherited from parent scope
      $issueDate       — inherited from parent scope
--}}
@php
    $headerScopeLines = preg_split('/\r\n|\r|\n/', (string) ($metalRailData['scope'] ?? '')) ?: [];
    $refDisplay       = $metalRailData['reference_value'] ?? $proposal->reference_number ?? '';
    $dateDisplay      = $metalRailData['date_value']      ?? $issueDate ?? '';
@endphp

<section class="cover-header">

    <div class="brand">
        @if ($logoData)
            <img src="{{ $logoData }}" alt="Drenla">
        @else
            <div style="font-size:40px;font-weight:300;letter-spacing:-0.08em;color:white;">DRENLA</div>
        @endif
    </div>

    <div class="meta-rail">
        <div class="meta-item">
            <p class="meta-label">{{ $metalRailData['document_label'] }}</p>
        </div>
        <div class="meta-item">
            <p class="meta-label">{{ $metalRailData['client_label'] }}</p>
            <div class="meta-value">{{ $metalRailData['client_name'] }}</div>
        </div>
        <div class="meta-item">
            <p class="meta-label">{{ $metalRailData['scope_label'] }}</p>
            <div class="meta-value scope">{{ implode("\n", array_filter($headerScopeLines, fn ($l) => trim($l) !== '')) }}</div>
        </div>
        <div class="meta-item">
            <p class="meta-label">{{ $metalRailData['reference_label'] }}</p>
            <div class="meta-value">{{ $refDisplay }}</div>
        </div>
        <div class="meta-item">
            <p class="meta-label">{{ $metalRailData['date_label'] }}</p>
            <div class="meta-value">{{ $dateDisplay }}</div>
        </div>
    </div>

</section>
