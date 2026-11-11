<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class ActivityController extends Controller
{
    public function index()
    {
        $activities = DB::table('activity_logs')->leftJoin('users', 'users.id', '=', 'activity_logs.user_id')
            ->select('activity_logs.*', 'users.name as user_name', 'users.email as user_email')
            ->latest('activity_logs.created_at')->paginate(40);

        return view('activity.index', compact('activities'));
    }
}
