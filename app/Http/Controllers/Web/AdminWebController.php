<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class AdminWebController extends Controller
{
    public function index(): View    { return view('admin.index'); }
    public function spaces(): View  { return view('admin.spaces'); }
    public function employees(): View { return view('admin.employees'); }
    public function roles(): View   { return view('admin.roles'); }
    public function auditLogs(): View { return view('admin.audit-logs'); }
    public function settings(): View { return view('admin.settings'); }
}
