@props(['title' => 'RuangBelajar AI', 'subtitle' => null])

@include('layouts.app', ['title' => $title, 'subtitle' => $subtitle, 'slot' => $slot])
