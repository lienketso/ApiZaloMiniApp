@extends('layouts.admin')

@section('title', 'Chỉnh sửa CLB')
@section('page_title', 'Cập nhật câu lạc bộ')

@section('content')
    <div class="space-y-6">
        <a href="{{ route('admin.clubs.index') }}" class="text-sm text-white/60 hover:text-white">← Quay về danh sách</a>

        <form method="POST" action="{{ route('admin.clubs.update', $club) }}" class="space-y-6 rounded-3xl border border-white/5 bg-white/5 p-6">
            @csrf
            @method('PUT')
            @include('admin.clubs._form', ['club' => $club, 'plans' => $plans])
            <div class="flex justify-end gap-3">
                <a href="{{ route('admin.clubs.index') }}" class="rounded-2xl border border-white/10 px-4 py-2 text-sm font-semibold text-white/70">Huỷ</a>
                <button type="submit" class="rounded-2xl bg-blue-500 px-5 py-2 text-sm font-semibold text-white shadow-lg shadow-blue-500/30">Cập nhật</button>
            </div>
        </form>
    </div>
@endsection
