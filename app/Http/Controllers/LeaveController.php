<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\LeaveRequest as StaffLeaveRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use App\Notifications\WorkflowNotice;
use Symfony\Component\HttpFoundation\Response;

class LeaveController extends Controller
{
    public function index(Request $request)
    {
        $isManager = $request->user()->hasRole(['admin', 'staff']);
        $requests = StaffLeaveRequest::with(['requester', 'event'])->when(! $isManager, fn ($query) => $query->where('user_id', $request->user()->id))->latest()->paginate(20);

        return view('leave.index', ['requests' => $requests, 'events' => Event::whereIn('status', ['published', 'scheduled'])->orderByDesc('event_date')->get(), 'isManager' => $isManager]);
    }

    public function template(): Response
    {
        $letter = "{\\rtf1\\ansi\\deff0\n{\\b [Institution or Department Name]}\\par\n\\par\n[Date]\\par\n\\par\nTo: [Principal / Head of Department]\\par\n[Institution Name]\\par\n\\par\n{\\b Subject: Request for leave}\\par\n\\par\nDear Sir/Madam,\\par\n\\par\nI, [Teacher or Guest Lecturer Name], respectfully request leave from [start date] to [end date].\\par\n\\par\nReason for leave: [briefly explain the reason].\\par\nRelated event or seminar (if applicable): [event name].\\par\n\\par\nI have attached any supporting documents for your consideration. Please let me know if further information is required.\\par\n\\par\nThank you for your consideration.\\par\n\\par\nYours sincerely,\\par\n\\par\n[Signature]\\par\n[Teacher or Guest Lecturer Name]\\par\n[Phone / Email]\\par\n}";

        return response($letter, 200, [
            'Content-Type' => 'application/rtf; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="teacher-leave-request-template.rtf"',
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'applicant_name' => ['nullable', 'string', 'max:255'], 'applicant_email' => ['nullable', 'email', 'max:255'],
            'event_id' => ['nullable', 'exists:events,id'], 'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'], 'reason' => ['required', 'string', 'max:3000'],
            'document' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240'],
        ]);
        if ($request->hasFile('document')) {
            $data['document_path'] = $request->file('document')->store('leave-documents');
        }
        unset($data['document']);
        $data['user_id'] = $request->user()->id;
        $data['applicant_name'] = $data['applicant_name'] ?: $request->user()->name;
        $data['applicant_email'] = $data['applicant_email'] ?: $request->user()->email;
        StaffLeaveRequest::create($data);

        return back()->with('success', 'Leave request submitted.');
    }

    public function review(Request $request, StaffLeaveRequest $leaveRequest)
    {
        $data = $request->validate(['status' => ['required', Rule::in(['approved', 'rejected'])], 'review_note' => ['nullable', 'string', 'max:1000']]);
        $leaveRequest->update($data + ['reviewed_by' => $request->user()->id]);
        $leaveRequest->requester?->notify(new WorkflowNotice('Leave request update', "Your leave request was {$data['status']}.", route('leave.index')));

        return back()->with('success', 'Leave request reviewed.');
    }

    public function download(Request $request, StaffLeaveRequest $leaveRequest)
    {
        abort_unless($request->user()->hasRole(['admin', 'staff']) || $leaveRequest->user_id === $request->user()->id, 403);
        abort_unless($leaveRequest->document_path && Storage::exists($leaveRequest->document_path), 404);

        return Storage::download($leaveRequest->document_path);
    }
}
