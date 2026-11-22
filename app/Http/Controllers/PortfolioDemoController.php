<?php

namespace App\Http\Controllers;

use App\Models\Plant;
use App\Models\Audit;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PortfolioDemoController extends Controller
{
    public function enter(Request $request): RedirectResponse
    {
        $plant = Plant::query()->where('code', 'DEMO-PUBLIC')->first();
        abort_unless($plant, 503, 'La cuenta de evaluación no está configurada.');

        $user = User::query()->where('is_portfolio_demo_admin', true)
            ->where('is_portfolio_owner', false)->first();
        abort_unless($user, 503, 'La cuenta de evaluación no está habilitada.');

        $memberships = DB::table('plant_user')->where('user_id', $user->id)->get();
        abort_unless($memberships->count() === 1
            && (int) $memberships->first()->plant_id === (int) $plant->id
            && $memberships->first()->role === 'supervisor',
            503, 'La cuenta de evaluación tiene permisos incorrectos.');

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('portfolio.guide');
    }

    public function guide(Request $request): View
    {
        $user = $request->user();
        $isDemo = (bool) $user->is_portfolio_demo_admin;

        $plantIds = DB::table('plant_user')->where('user_id', $user->id)->pluck('plant_id');
        $sampleAudits = Audit::query()->whereIn('plant_id', $plantIds)
            ->with('template:id,name')->orderByDesc('id')->limit(5)->get();
        $totalAudits = Audit::query()->whereIn('plant_id', $plantIds)->count();

        return view('portfolio.guide', compact('isDemo', 'sampleAudits', 'totalAudits'));
    }
}
