<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\Event;
use App\Models\Registration;
use App\Models\Result;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CertificateController extends Controller
{
    public function index()
    {
        return view('certificates.index', [
            'certificates' => Certificate::with(['event', 'registration.student'])->latest()->paginate(20),
            'events' => Event::whereIn('status', ['completed', 'published', 'scheduled'])->orderByDesc('event_date')->get(),
            'registrations' => Registration::with(['event', 'student'])->where('status', 'approved')->latest()->limit(500)->get(),
            'templates' => CertificateTemplate::orderBy('name')->get(),
        ]);
    }

    public function storeTemplate(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'body' => ['required', 'string', 'max:3000']]);
        CertificateTemplate::create($data);

        return back()->with('success', 'Certificate template saved.');
    }

    public function issue(Request $request)
    {
        $data = $request->validate([
            'event_id' => ['required', 'exists:events,id'],
            'registration_id' => ['required', 'exists:registrations,id'],
            'type' => ['required', Rule::in(['participation', 'winner'])],
            'template_id' => ['nullable', 'exists:certificate_templates,id'],
        ]);
        $registration = Registration::with('event')->findOrFail($data['registration_id']);
        abort_unless($registration->event_id === (int) $data['event_id'] && $registration->status === 'approved', 422, 'Choose an approved participant for the selected event.');
        if ($data['type'] === 'winner') {
            abort_unless(Result::where('event_id', $registration->event_id)->where('registration_id', $registration->id)->where('status', 'final')->whereNotNull('rank')->exists(), 422, 'Winner certificates require finalized results.');
        }

        $certificate = Certificate::firstOrCreate(
            ['event_id' => $registration->event_id, 'registration_id' => $registration->id, 'type' => $data['type']],
            ['certificate_number' => 'EVT-'.now()->format('Y').'-'.Str::upper(Str::random(8)), 'template_id' => $data['template_id'] ?? null],
        );

        return redirect()->route('certificates.show', $certificate)->with('success', 'Certificate is ready to print or save as PDF.');
    }

    public function show(Certificate $certificate)
    {
        return view('certificates.show', ['certificate' => $certificate->load(['event', 'registration.student', 'template']), 'result' => Result::where('registration_id', $certificate->registration_id)->where('event_id', $certificate->event_id)->where('status', 'final')->first()]);
    }
}
