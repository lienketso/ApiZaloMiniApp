@extends('layouts.admin')

@section('title', 'Thêm thành viên')
@section('page_title', 'Tạo thành viên mới')

@section('content')
<div class="space-y-6">
    <a href="{{ route('admin.members.index') }}" class="text-sm text-white/60 hover:text-white">← Quay về danh sách</a>

    <form method="POST" action="{{ route('admin.members.store') }}" class="space-y-6 rounded-3xl border border-white/5 bg-white/5 p-6">
        @csrf
        @include('admin.members._form', ['member' => isset($member) ? $member : null, 'clubs' => $clubs])

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.members.index') }}" class="rounded-2xl border border-white/10 px-4 py-2 text-sm font-semibold text-white/70">Huỷ</a>
            <button type="submit" class="rounded-2xl bg-emerald-500 px-5 py-2 text-sm font-semibold text-white shadow-lg shadow-emerald-500/30">Tạo thành viên</button>
        </div>
    </form>
</div>
@endsection
