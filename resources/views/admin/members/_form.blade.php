@php
    $roles = [
        'super_admin' => 'Super Admin',
        'admin' => 'Admin CLB',
        'member' => 'Member'
    ];
@endphp

<div class="space-y-6">
    <div class="grid gap-4 md:grid-cols-2">
        <div>
            <label class="mb-2 block text-sm font-semibold">Họ và tên *</label>
            <input type="text" name="name" value="{{ old('name', $member->name ?? '') }}" required class="w-full rounded-2xl border border-white/10 bg-white/10 px-4 py-3" />
            @error('name') <p class="mt-1 text-sm text-red-300">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="mb-2 block text-sm font-semibold">Email</label>
            <input type="email" name="email" value="{{ old('email', $member->email ?? '') }}" class="w-full rounded-2xl border border-white/10 bg-white/10 px-4 py-3" />
            @error('email') <p class="mt-1 text-sm text-red-300">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <div>
            <label class="mb-2 block text-sm font-semibold">Số điện thoại</label>
            <input type="text" name="phone" value="{{ old('phone', $member->phone ?? '') }}" class="w-full rounded-2xl border border-white/10 bg-white/10 px-4 py-3" />
            @error('phone') <p class="mt-1 text-sm text-red-300">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="mb-2 block text-sm font-semibold">Vai trò</label>
            <select name="role" class="w-full rounded-2xl border border-white/10 bg-white/10 px-4 py-3">
                @foreach($roles as $value => $label)
                    <option value="{{ $value }}" @selected(old('role', $member->role ?? 'member') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('role') <p class="mt-1 text-sm text-red-300">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <div>
            <label class="mb-2 block text-sm font-semibold">Mật khẩu {{ isset($member) ? '(để trống nếu không đổi)' : '*' }}</label>
            <input type="password" name="password" class="w-full rounded-2xl border border-white/10 bg-white/10 px-4 py-3" />
            @error('password') <p class="mt-1 text-sm text-red-300">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="mb-2 block text-sm font-semibold">Giới tính</label>
            <select name="gender" class="w-full rounded-2xl border border-white/10 bg-white/10 px-4 py-3">
                <option value="">Không xác định</option>
                <option value="male" @selected(old('gender', $member->gender ?? '')==='male')>Nam</option>
                <option value="female" @selected(old('gender', $member->gender ?? '')==='female')>Nữ</option>
                <option value="other" @selected(old('gender', $member->gender ?? '')==='other')>Khác</option>
            </select>
            @error('gender') <p class="mt-1 text-sm text-red-300">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label class="mb-2 block text-sm font-semibold">Ngày sinh</label>
        <input type="date" name="birthday" value="{{ old('birthday', isset($member) && $member->birthday ? $member->birthday->format('Y-m-d') : '') }}" class="w-full rounded-2xl border border-white/10 bg-white/10 px-4 py-3 text-white" />
        @error('birthday') <p class="mt-1 text-sm text-red-300">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="mb-2 block text-sm font-semibold">Gán vào CLB</label>
        <div class="grid gap-3 md:grid-cols-2">
            @foreach($clubs as $club)
                <label class="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                    <input type="checkbox" name="club_ids[]" value="{{ $club->id }}" @checked(in_array($club->id, old('club_ids', isset($member) ? $member->clubs->pluck('id')->toArray() : []))) class="size-4 rounded border-white/20 bg-white/10" />
                    <span class="text-sm">{{ $club->name }}</span>
                </label>
            @endforeach
        </div>
        <div class="mt-4">
            <label class="mb-2 block text-sm font-semibold">Vai trò trong CLB</label>
            <select name="club_role" class="w-full rounded-2xl border border-white/10 bg-white/10 px-4 py-3">
                <option value="member" @selected(old('club_role', 'member')==='member')>Member</option>
                <option value="admin" @selected(old('club_role', 'member')==='admin')>Admin</option>
            </select>
        </div>
    </div>
</div>
