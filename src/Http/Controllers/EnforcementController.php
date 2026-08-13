<?php

declare(strict_types=1);

namespace EloquentWorks\Sentinel\Http\Controllers;

use EloquentWorks\Sentinel\Models\ModerationCase;
use EloquentWorks\Sentinel\Services\EnforcementManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class EnforcementController extends Controller
{
    private function target(string|int $id): mixed { $model = config('sentinel.user_model'); return $model::query()->findOrFail($id); }
    public function warn(Request $request, string $user, EnforcementManager $enforcement): RedirectResponse
    { $d=$request->validate(['reason'=>['required','string','max:2000'],'severity'=>['nullable','in:low,medium,high'],'case_id'=>['nullable','integer']]); $case=$d['case_id']??null ? config('sentinel.models.case')::query()->find($d['case_id']) : null; $enforcement->warn($this->target($user),$request->user(),$d['reason'],$d['severity']??'medium',$case); return back()->with('status','Warning issued.'); }
    public function strike(Request $request, string $user, EnforcementManager $enforcement): RedirectResponse
    { $d=$request->validate(['reason'=>['required','string','max:2000'],'points'=>['required','integer','min:1','max:100'],'category'=>['nullable','string','max:100'],'case_id'=>['nullable','integer']]); $case=$d['case_id']??null ? config('sentinel.models.case')::query()->find($d['case_id']) : null; $enforcement->strike($this->target($user),$request->user(),$d['reason'],$d['points'],$d['category']??'other',$case); return back()->with('status','Strike issued.'); }
    public function ban(Request $request, string $user, EnforcementManager $enforcement): RedirectResponse
    { $d=$request->validate(['reason'=>['required','string','max:2000'],'days'=>['nullable','integer','min:1','max:3650'],'category'=>['nullable','string','max:100'],'case_id'=>['nullable','integer']]); $expires=isset($d['days'])?now()->addDays($d['days']):null; $case=$d['case_id']??null ? config('sentinel.models.case')::query()->find($d['case_id']) : null; $enforcement->ban($this->target($user),$request->user(),$d['reason'],$expires,$d['category']??'other',$case); return back()->with('status','Ban issued.'); }
    public function restrict(Request $request, string $user, EnforcementManager $enforcement): RedirectResponse
    { $d=$request->validate(['reason'=>['required','string','max:2000'],'restriction'=>['required','in:posting,read_only,login,shadow'],'hours'=>['nullable','integer','min:1','max:87600'],'case_id'=>['nullable','integer']]); $expires=isset($d['hours'])?now()->addHours($d['hours']):null; $case=$d['case_id']??null ? config('sentinel.models.case')::query()->find($d['case_id']) : null; $enforcement->restrict($this->target($user),$request->user(),$d['restriction'],$d['reason'],$expires,$case); return back()->with('status','Restriction issued.'); }
    public function masquerade(Request $request, string $user, EnforcementManager $enforcement): RedirectResponse
    { $d=$request->validate(['reason'=>['required','string','max:1000'],'case_id'=>['nullable','integer']]); $case=$d['case_id']??null ? config('sentinel.models.case')::query()->find($d['case_id']) : null; $enforcement->masquerade($this->target($user),$request->user(),$d['reason'],$case); return redirect('/'); }
}
