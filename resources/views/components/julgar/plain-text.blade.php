@props(['text' => null])
@php
    $plainText = html_entity_decode($text ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $plainText = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', '', $plainText);
    $plainText = preg_replace('/<\/(p|div|li|h[1-6]|tr)>|<br\s*\/?\s*>/i', "\n", $plainText);
    $plainText = trim(str_replace("\u{00A0}", ' ', strip_tags($plainText)));
@endphp
{{ $plainText !== '' ? $plainText : '—' }}
