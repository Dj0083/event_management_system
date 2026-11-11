<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Student;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $students = Student::with('department')
            ->when($request->filled('search'), fn ($query) => $query->where(function ($q) use ($request) {
                $term = '%'.(string) $request->input('search').'%';
                $q->where('name', 'like', $term)->orWhere('student_number', 'like', $term)->orWhere('email', 'like', $term);
            }))->orderBy('name')->paginate(20)->withQueryString();

        return view('students.index', ['students' => $students, 'departments' => Department::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'student_number' => ['required', 'string', 'max:50', 'unique:students,student_number'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'academic_program' => ['nullable', 'string', 'max:255'],
            'year_of_study' => ['nullable', 'integer', 'min:1', 'max:12'],
            'phone' => ['nullable', 'string', 'max:40'],
        ]);
        Student::create($data);

        return redirect()->route('students.index')->with('success', 'Student record added.');
    }

    public function storeDepartment(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255', 'unique:departments,name'], 'code' => ['nullable', 'string', 'max:20', 'unique:departments,code']]);
        Department::create($data);

        return back()->with('success', 'Department added.');
    }
}
