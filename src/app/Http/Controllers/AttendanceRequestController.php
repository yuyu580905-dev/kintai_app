<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\AttendanceRequest;
use Illuminate\Http\Request;
use App\Http\Controllers\Admin\AdminRequestController;

class AttendanceRequestController extends Controller
{
    public function index(Request $request)
    {
        if (auth()->user()->admin_status) {
            return app(AdminRequestController::class)->index($request);
        }

        $status = $request->query('status', 'pending');

        $requests = AttendanceRequest::with([
            'user',
            'attendance',
        ])
            ->where('attendance_requests.user_id', Auth::id())
            ->where('attendance_requests.status', $status)
            ->orderByWorkDate()
            ->get();

        return view('requests.index', compact(
            'requests',
            'status'
        ));
    }
}
