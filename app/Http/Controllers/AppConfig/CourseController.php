<?php

namespace App\Http\Controllers\AppConfig;

use App\AppCourse;
use App\AppCourseEnrollment;
use App\BusinessLocation;
use App\Contact;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Cursos que Celfix ofrece a sus socios. Los admins programan cursos con
 * fecha/hora/capacidad desde el POS; los clientes se inscriben desde la app.
 */
class CourseController extends Controller
{
    private function guard()
    {
        if (! auth()->user()->can('business_settings.access')
            && ! auth()->user()->can('celfix.app_config.access')) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function index(Request $request)
    {
        $this->guard();
        $business_id = $request->session()->get('user.business_id');
        $courses = AppCourse::where('business_id', $business_id)
            ->with('location')
            ->withCount('enrollments')
            ->orderByDesc('starts_at')
            ->get();
        return view('app_config.courses.index', compact('courses'));
    }

    public function create(Request $request)
    {
        $this->guard();
        $business_id = $request->session()->get('user.business_id');
        $locations = BusinessLocation::where('business_id', $business_id)->orderBy('name')->pluck('name', 'id');
        $course = new AppCourse([
            'is_active' => true,
            'capacity' => 0,
            'starts_at' => now()->addDay()->setTime(10, 0),
            'ends_at'   => now()->addDay()->setTime(12, 0),
        ]);
        return view('app_config.courses.form', compact('course', 'locations'));
    }

    public function store(Request $request)
    {
        $this->guard();
        $data = $this->validated($request);
        $business_id = $request->session()->get('user.business_id');
        $data['business_id'] = $business_id;
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();
        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('app_courses', 'public');
        }
        AppCourse::create($data);
        return redirect()->route('app-config.courses.index')
            ->with('status', ['success' => 1, 'msg' => 'Curso creado.']);
    }

    public function edit(Request $request, $id)
    {
        $this->guard();
        $business_id = $request->session()->get('user.business_id');
        $course = AppCourse::where('business_id', $business_id)->findOrFail($id);
        $locations = BusinessLocation::where('business_id', $business_id)->orderBy('name')->pluck('name', 'id');
        return view('app_config.courses.form', compact('course', 'locations'));
    }

    public function update(Request $request, $id)
    {
        $this->guard();
        $business_id = $request->session()->get('user.business_id');
        $course = AppCourse::where('business_id', $business_id)->findOrFail($id);
        $data = $this->validated($request);
        $data['updated_by'] = auth()->id();
        if ($request->hasFile('image')) {
            if ($course->image_path) Storage::disk('public')->delete($course->image_path);
            $data['image_path'] = $request->file('image')->store('app_courses', 'public');
        }
        $course->update($data);
        return redirect()->route('app-config.courses.index')
            ->with('status', ['success' => 1, 'msg' => 'Curso actualizado.']);
    }

    public function destroy(Request $request, $id)
    {
        $this->guard();
        $business_id = $request->session()->get('user.business_id');
        $course = AppCourse::where('business_id', $business_id)->findOrFail($id);
        if ($course->image_path) Storage::disk('public')->delete($course->image_path);
        // ON DELETE CASCADE en enrollments se encarga del resto.
        $course->delete();
        return response()->json(['success' => 1, 'msg' => 'Curso eliminado.']);
    }

    /**
     * GET /app-config/courses/{id}/enrollments
     * Lista de personas inscritas al curso.
     */
    public function enrollments(Request $request, $id)
    {
        $this->guard();
        $business_id = $request->session()->get('user.business_id');
        $course = AppCourse::where('business_id', $business_id)
            ->with('location')
            ->findOrFail($id);

        $enrollments = AppCourseEnrollment::where('course_id', $course->id)
            ->with('contact')
            ->orderBy('enrolled_at')
            ->get();

        return view('app_config.courses.enrollments', compact('course', 'enrollments'));
    }

    /**
     * DELETE /app-config/courses/{id}/enrollments/{enrollmentId}
     * Admin puede quitar manualmente a un inscrito (por ejemplo, no-show,
     * o si el cliente pidió cancelar por teléfono).
     */
    public function removeEnrollment(Request $request, $id, $enrollmentId)
    {
        $this->guard();
        $business_id = $request->session()->get('user.business_id');
        $course = AppCourse::where('business_id', $business_id)->findOrFail($id);

        AppCourseEnrollment::where('course_id', $course->id)
            ->where('id', $enrollmentId)
            ->firstOrFail()
            ->delete();

        return response()->json(['success' => 1, 'msg' => 'Inscrito removido.']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:200',
            'description' => 'nullable|string',
            'instructor_name' => 'nullable|string|max:150',
            'target_location_id' => 'nullable|integer|exists:business_locations,id',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'capacity' => 'nullable|integer|min:0|max:10000',
            'is_active' => 'nullable|boolean',
            'image' => 'nullable|image|max:2048',
        ]);
    }
}
