@extends('layouts.app')
@section('title', 'Space yoxdur')
@section('page-title', 'Space yoxdur')

@section('content')
<div class="min-h-[calc(100vh-74px)] bg-gradient-to-br from-[#132e69] via-[#1d2f67] to-[#39245f] flex items-center justify-center px-4 py-10 text-white">
    <section class="w-full max-w-xl rounded-[26px] border border-white/12 bg-[#102756]/92 shadow-[0_24px_80px_rgba(5,14,45,0.36)] px-6 sm:px-8 py-8 text-center">
        <div class="mx-auto mb-5 h-16 w-16 rounded-2xl bg-white/10 border border-white/12 flex items-center justify-center text-[#82b9ff] text-2xl font-bold">
            TIS
        </div>
        <h1 class="text-2xl sm:text-3xl font-semibold leading-tight">Siz heç bir space üzvü deyilsiniz</h1>
        <p class="mt-3 text-sm sm:text-base text-white/60 leading-6">
            Sistemə daxil olmusunuz, amma hazırda sizə təyin edilmiş aktiv departament və ya space yoxdur.
            Space rəhbəri və ya administrator sizi üzvlüyə əlavə etdikdən sonra səhifələr açılacaq.
        </p>

        <form method="POST" action="{{ route('logout') }}" class="mt-7">
            @csrf
            <button type="submit" class="h-12 px-7 rounded-xl bg-[#d9364f] hover:bg-[#c92d45] text-white font-semibold shadow-[0_12px_28px_rgba(217,54,79,0.28)] transition">
                Çıxış
            </button>
        </form>
    </section>
</div>
@endsection
