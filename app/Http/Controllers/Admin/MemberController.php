<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Club;
use App\Models\User;
use App\Models\UserClub;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MemberController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->query('search');
        $role = $request->query('role');
        $clubId = $request->query('club_id');

        $members = User::query()
            ->with(['clubs' => function ($query) {
                $query->select('clubs.id', 'clubs.name');
            }])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($role, fn ($query) => $query->where('role', $role))
            ->when($clubId, function ($query) use ($clubId) {
                $query->whereHas('clubs', fn ($q) => $q->where('clubs.id', $clubId));
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $clubs = Club::orderBy('name')->get(['id', 'name']);
        $stats = [
            'total' => User::count(),
            'super_admin' => User::where('role', 'super_admin')->count(),
            'admin' => User::where('role', 'admin')->count(),
            'member' => User::where('role', 'member')->count(),
        ];

        return view('admin.members.index', compact('members', 'clubs', 'stats', 'search', 'role', 'clubId'));
    }

    public function create(): View
    {
        $clubs = Club::orderBy('name')->get(['id', 'name']);
        return view('admin.members.create', compact('clubs'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateData($request);
        $validated['password'] = Hash::make($validated['password']);
        $validated['role'] = $request->input('role', 'member');

        $user = User::create($validated);
        $this->syncClubs($user, $request->input('club_ids', []), $request->input('club_role', 'member'));

        return redirect()->route('admin.members.index')->with('success', 'Đã tạo thành viên mới.');
    }

    public function edit(User $member): View
    {
        $clubs = Club::orderBy('name')->get(['id', 'name']);
        $member->load('clubs');
        return view('admin.members.edit', compact('member', 'clubs'));
    }

    public function update(Request $request, User $member): RedirectResponse
    {
        $validated = $this->validateData($request, $member->id, false);

        if ($request->filled('password')) {
            $validated['password'] = Hash::make($request->password);
        } else {
            unset($validated['password']);
        }

        $member->update($validated);
        $this->syncClubs($member, $request->input('club_ids', []), $request->input('club_role', 'member'));

        return redirect()->route('admin.members.index')->with('success', 'Đã cập nhật thành viên.');
    }

    public function destroy(User $member): RedirectResponse
    {
        $member->delete();
        return redirect()->route('admin.members.index')->with('success', 'Đã xoá thành viên.');
    }

    public function resetPassword(User $member): RedirectResponse
    {
        $newPassword = Str::random(10);
        $member->update(['password' => Hash::make($newPassword)]);

        return back()->with('success', "Mật khẩu mới: {$newPassword}");
    }

    private function validateData(Request $request, ?int $userId = null, bool $isCreate = true): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255|unique:users,email,' . $userId,
            'phone' => 'nullable|string|max:20|unique:users,phone,' . $userId,
            'role' => 'nullable|in:super_admin,admin,member',
            'password' => $isCreate ? 'required|string|min:6' : 'nullable|string|min:6',
            'gender' => 'nullable|in:male,female,other',
            'birthday' => 'nullable|date',
        ]);
    }

    private function syncClubs(User $user, array $clubIds, string $role): void
    {
        $syncData = collect($clubIds)
            ->filter()
            ->mapWithKeys(fn ($id) => [$id => ['role' => $role]])
            ->toArray();

        if (!empty($syncData)) {
            $user->clubs()->sync($syncData);
        } else {
            $user->clubs()->detach();
        }
    }
}
