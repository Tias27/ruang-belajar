@props(['title' => 'Ruang Belajar', 'subtitle' => null])

@include('layouts.app', ['title' => $title, 'subtitle' => $subtitle, 'slot' => $slot])
