@props(['name'])

@php
    $fullName = trim($name ?? '');
    $words = $fullName === '' ? [] : preg_split('/\s+/', $fullName);
    $firstWord = $words[0] ?? '';
    $secondWord = $words[1] ?? '';

    $abbreviated = trim(mb_substr($firstWord, 0, 5) . ' ' . ($secondWord !== '' ? mb_substr($secondWord, 0, 2) : ''));
    $displayName = $abbreviated !== '' ? $abbreviated : $fullName;
@endphp

<span title="{{ $fullName }}" {{ $attributes->class(['block overflow-hidden text-ellipsis whitespace-nowrap']) }}>
    {{ $displayName }}
</span>
