<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Club;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClubController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->query('search');
        $status = $request->query('status');

        $clubs = Club::query()
            ->withCount(['userClubs as active_members_count' => function ($query) {
                $query->where('status', 'active');
            }])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('sport', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($status, function ($query) use ($status) {
                $query->where('subscription_status', $status);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $stats = [
            'total' => Club::count(),
            'active' => Club::where('subscription_status', 'active')->count(),
            'trial' => Club::where('subscription_status', 'trial')->count(),
            'pending_setup' => Club::where('is_setup', false)->count(),
        ];

        return view('admin.clubs.index', compact('clubs', 'stats', 'search', 'status'));
    }

    public function create(): View
    {
        $plans = Plan::orderBy('name')->get();
        return view('admin.clubs.create', compact('plans'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateData($request);
        $validated['is_setup'] = $request->boolean('is_setup');

        $club = Club::create($validated);

        return redirect()->route('admin.clubs.index')
            ->with('success', "Đã tạo CLB {$club->name} thành công");
    }

    public function edit(Club $club): View
    {
        $plans = Plan::orderBy('name')->get();
        return view('admin.clubs.edit', compact('club', 'plans'));
    }

    public function update(Request $request, Club $club): RedirectResponse
    {
        $validated = $this->validateData($request, $club->id);
        $validated['is_setup'] = $request->boolean('is_setup');

        $club->update($validated);

        return redirect()->route('admin.clubs.index')
            ->with('success', "Đã cập nhật CLB {$club->name}");
    }

    public function destroy(Club $club): RedirectResponse
    {
        $clubName = $club->name;
        $club->delete();

        return redirect()->route('admin.clubs.index')
            ->with('success', "Đã xoá CLB {$clubName}");
    }

    private function validateData(Request $request, ?int $clubId = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'sport' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'subscription_status' => 'nullable|in:trial,active,expired,canceled',
            'plan_id' => 'nullable|exists:plans,id',
            'subscription_expired_at' => 'nullable|date',
            'trial_expired_at' => 'nullable|date',
            'is_setup' => 'nullable|boolean',
        ]);
    }
}
