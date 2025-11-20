@php
    $statuses = [
        'trial' => 'Trial',
        'active' => 'Active',
        'expired' => 'Expired',
        'canceled' => 'Canceled',
    ];
@endphp

<div class="grid gap-6">
    <div class="grid gap-4 md:grid-cols-2">
        <div>
            <label class="mb-2 block text-sm font-semibold">Tên câu lạc bộ *</label>
            <input type="text" name="name" value="{{ old('name', $club->name ?? '') }}" required class="w-full rounded-2xl border border-white/10 bg-white/10 px-4 py-3 focus:border-blue-400 focus:ring-2 focus:ring-blue-400" />
            @error('name') <p class="mt-1 text-sm text-red-300">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="mb-2 block text-sm font-semibold">Môn thể thao</label>
            <input type="text" name="sport" value="{{ old('sport', $club->sport ?? '') }}" class="w-full rounded-2xl border border-white/10 bg-white/10 px-4 py-3" />
            @error('sport') <p class="mt-1 text-sm text-red-300">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <div>
            <label class="mb-2 block text-sm font-semibold">Email</label>
            <input type="email" name="email" value="{{ old('email', $club->email ?? '') }}" class="w-full rounded-2xl border border-white/10 bg-white/10 px-4 py-3" />
            @error('email') <p class="mt-1 text-sm text-red-300">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="mb-2 block text-sm font-semibold">Số điện thoại</label>
            <input type="text" name="phone" value="{{ old('phone', $club->phone ?? '') }}" class="w-full rounded-2xl border border-white/10 bg-white/10 px-4 py-3" />
            @error('phone') <p class="mt-1 text-sm text-red-300">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label class="mb-2 block text-sm font-semibold">Địa chỉ</label>
        <input type="text" name="address" value="{{ old('address', $club->address ?? '') }}" class="w-full rounded-2xl border border-white/10 bg-white/10 px-4 py-3" />
        @error('address') <p class="mt-1 text-sm text-red-300">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="mb-2 block text-sm font-semibold">Mô tả</label>
        <textarea name="description" rows="4" class="w-full rounded-2xl border border-white/10 bg-white/10 px-4 py-3">{{ old('description', $club->description ?? '') }}</textarea>
        @error('description') <p class="mt-1 text-sm text-red-300">{{ $message }}</p> @enderror
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <div>
            <label class="mb-2 block text-sm font-semibold">Trạng thái</label>
            <select name="subscription_status" class="w-full rounded-2xl border border-white/10 bg-white/10 px-4 py-3">
                <option value="">-- Chọn trạng thái --</option>
                @foreach($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(old('subscription_status', $club->subscription_status ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('subscription_status') <p class="mt-1 text-sm text-red-300">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="mb-2 block text-sm font-semibold">Gói đăng ký</label>
            <select name="plan_id" class="w-full rounded-2xl border border-white/10 bg-white/10 px-4 py-3">
                <option value="">-- Chọn gói --</option>
                @foreach($plans as $plan)
                    <option value="{{ $plan->id }}" @selected(old('plan_id', $club->plan_id ?? '') == $plan->id)>{{ $plan->name }}</option>
                @endforeach
            </select>
            @error('plan_id') <p class="mt-1 text-sm text-red-300">{{ $message }}</p> @enderror
        </div>
        <div class="flex items-center gap-3 pt-8">
            <input type="checkbox" name="is_setup" value="1" @checked(old('is_setup', $club->is_setup ?? false)) class="size-5 rounded border-white/20 bg-white/10" />
            <label class="text-sm font-semibold">Đã hoàn tất setup</label>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <div>
            <label class="mb-2 block text-sm font-semibold">Trial hết hạn</label>
            <input type="date" name="trial_expired_at" value="{{ old('trial_expired_at', (isset($club) && $club->trial_expired_at) ? $club->trial_expired_at->format('Y-m-d') : '') }}" class="w-full rounded-2xl border border-white/10 bg-white/10 px-4 py-3 text-white" />
            @error('trial_expired_at') <p class="mt-1 text-sm text-red-300">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="mb-2 block text-sm font-semibold">Gói hết hạn</label>
            <input type="date" name="subscription_expired_at" value="{{ old('subscription_expired_at', (isset($club) && $club->subscription_expired_at) ? $club->subscription_expired_at->format('Y-m-d') : '') }}" class="w-full rounded-2xl border border-white/10 bg-white/10 px-4 py-3 text-white" />
            @error('subscription_expired_at') <p class="mt-1 text-sm text-red-300">{{ $message }}</p> @enderror
        </div>
    </div>
</div>
