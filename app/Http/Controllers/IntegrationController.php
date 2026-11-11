<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Event;
use App\Models\Result;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class IntegrationController extends Controller
{
    public function index()
    {
        $logs = DB::table('mis_sync_logs')->leftJoin('events', 'events.id', '=', 'mis_sync_logs.event_id')
            ->select('mis_sync_logs.*', 'events.name as event_name')->latest('mis_sync_logs.created_at')->paginate(20);

        return view('mis.index', ['events' => Event::orderByDesc('event_date')->get(), 'logs' => $logs]);
    }

    public function importStudents(Request $request)
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:10240']]);
        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $header = $handle ? fgetcsv($handle) : false;
        $required = ['student_number', 'name', 'email', 'department_code', 'academic_program', 'year_of_study', 'phone'];
        if (! $header || array_map(fn ($value) => trim((string) $value), $header) !== $required) {
            if ($handle) fclose($handle);
            return back()->withErrors(['file' => 'CSV header must be: '.implode(',', $required)])->withInput();
        }

        $created = 0; $updated = 0; $skipped = 0; $errors = []; $line = 1;
        while (($row = fgetcsv($handle)) !== false && $line < 2002) {
            $line++;
            if (count($row) !== count($required)) { $skipped++; $errors[] = "Row {$line}: wrong number of columns."; continue; }
            $record = array_combine($required, array_map(fn ($value) => trim((string) $value), $row));
            $validator = Validator::make($record, [
                'student_number' => ['required', 'string', 'max:50'], 'name' => ['required', 'string', 'max:255'],
                'email' => ['nullable', 'email', 'max:255'], 'department_code' => ['nullable', 'string', 'max:20'],
                'academic_program' => ['nullable', 'string', 'max:255'], 'year_of_study' => ['nullable', 'integer', 'min:1', 'max:12'],
                'phone' => ['nullable', 'string', 'max:40'],
            ]);
            if ($validator->fails()) { $skipped++; $errors[] = "Row {$line}: ".implode(' ', $validator->errors()->all()); continue; }
            $department = $record['department_code'] ? Department::where('code', $record['department_code'])->first() : null;
            $student = Student::firstOrNew(['student_number' => $record['student_number']]);
            $wasNew = ! $student->exists;
            $student->fill([
                'name' => $record['name'], 'email' => $record['email'] ?: null, 'department_id' => $department?->id,
                'academic_program' => $record['academic_program'] ?: null,
                'year_of_study' => $record['year_of_study'] !== '' ? (int) $record['year_of_study'] : null,
                'phone' => $record['phone'] ?: null,
            ])->save();
            $wasNew ? $created++ : $updated++;
        }
        fclose($handle);
        if ($line >= 2002) $errors[] = 'Import stopped at the 2,000 row limit.';

        $status = $skipped ? 'partially_imported' : 'completed';
        DB::table('mis_sync_logs')->insert([
            'user_id' => $request->user()->id, 'direction' => 'student_csv_import', 'status' => $status,
            'details' => json_encode(['created' => $created, 'updated' => $updated, 'skipped' => $skipped, 'errors' => array_slice($errors, 0, 50)]),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return back()->with('success', "Student import finished: {$created} added, {$updated} updated, {$skipped} skipped.");
    }

    public function exportResults(Request $request, Event $event)
    {
        $rows = Result::with('registration.student')->where('event_id', $event->id)->where('status', 'final')->orderBy('rank')->get();
        abort_if($rows->isEmpty(), 422, 'This event has no finalized results to export.');
        DB::table('mis_sync_logs')->insert([
            'event_id' => $event->id, 'user_id' => $request->user()->id, 'direction' => 'results_csv_export', 'status' => 'completed',
            'details' => json_encode(['rows' => $rows->count(), 'format' => 'evently-results-v1']), 'created_at' => now(), 'updated_at' => now(),
        ]);
        $filename = 'mis-results-'.$event->id.'-'.now()->format('Ymd').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $safeCell = fn ($value) => is_string($value) && preg_match('/^[\s]*[=+\-@]/', $value) ? "'".$value : $value;
            $output = fopen('php://output', 'w');
            fputcsv($output, ['student_number', 'student_name', 'event_name', 'final_score', 'rank']);
            foreach ($rows as $result) fputcsv($output, array_map($safeCell, [$result->registration->student->student_number, $result->registration->student->name, $result->registration->event->name, $result->total_score, $result->rank]));
            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
