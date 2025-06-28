<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

use App\Models\User;
use App\Models\Instructor;
use App\Models\School;
use App\Models\Certification;

class ConsultantController extends Controller
{
    //
    public function submitSchools(Request $request) {
        $validator = Validator::make($request->all(), [
            'instructorId' => 'required|exists:instructors,id',
            'schools' => 'required|array',
            'schools.*.name' => 'required|string|max:255',
            'schools.*.degree' => 'required|string|max:255',
            'schools.*.field_of_study' => 'required|string|max:255',
            'schools.*.start_year' => 'required|string|max:255',
            'schools.*.end_year' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $instructor = Instructor::where('id', $request->instructorId)->first();

        if($instructor) {
            foreach($request->schools as $school) {
                School::create([
                    'instructor_id' => $request->instructorId,
                    'name' => $school['name'],
                    'degree' => $school['degree'],
                    'field_of_study' => $school['field_of_study'],
                    'start_year' => $school['start_year'],
                    'end_year' => $school['end_year'],
                    'description' => null
                ]);
            }

            $currentProgress = $instructor->consultant_progress;

            if($currentProgress < 1) {
                $instructor->consultant_progress = 1;
                $instructor->save();
            }

            return ResponseHelper::success('Details Updated Successfully', ['instructor' => $instructor]);
        }

        return ResponseHelper::error('Instructor not found');

    }

    public function editSchools(Request $request) {
        Log::info($request);
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:schools,id',
            'name' => 'required|string|max:255',
            'degree' => 'required|string|max:255',
            'field_of_study' => 'nullable|string|max:255',
            'start_year' => 'nullable|string|max:255',
            'end_year' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $school = School::where('id', $request->id)->first();

        $update = $school->update([
            'name' => $request->name,
            'degree' => $request->degree,
            'field_of_study' => $request->field_of_study,
            'start_year' => $request->start_year,
            'end_year' => $request->end_year,
        ]);

        return ResponseHelper::success('School updated successfully');
    }

    public function submitCerts(Request $request) {
        $validator = Validator::make($request->all(), [
            'instructorId' => 'required|exists:instructors,id',
            'certs' => 'required|array',
            'certs.*.name' => 'required|string|max:255',
            'certs.*.organization' => 'required|string|max:255',
            'certs.*.iss_date' => 'nullable|string|max:255',
            'certs.*.exp_date' => 'nullable|string|max:255',
            'certs.*.credential_url' => 'nullable|string|max:255',
            'certs.*.image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        Log::info($request);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $instructor = Instructor::where('id', $request->instructorId)->first();

        if($instructor) {
            foreach($request->certs as $index => $cert) {

                // check if image is present and upload it
                if ($request->hasFile("certs.$index.image")) {
                    $imagePath = $request->file("certs.$index.image")->store('uploads/certifications', 'public');
                } else {
                    $imagePath = null;
                }

                Certification::create([
                    'instructor_id' => $request->instructorId,
                    'certification_name' => $cert['name'],
                    'issuing_organization' => $cert['organization'],
                    'issue_date' => $cert['iss_date'] ?? null,
                    'expiry_date' => $cert['exp_date'] ?? null,
                    'credential_url' => $cert['credential_url'] ?? null,
                    'certificate_file_path' => $imagePath,
                ]);
            }

            $currentProgress = $instructor->consultant_progress;

            if($currentProgress < 2) {
                $instructor->consultant_progress = 2;
                $instructor->save();
            }

            return ResponseHelper::success('Details Updated Successfully', ['instructor' => $instructor]);
        }

        return ResponseHelper::error('Instructor not found');
    }

    public function submitIntroVideo(Request $request) {
        $validator = Validator::make($request->all(), [
            'instructorId' => 'required|exists:instructors,id',
            'video' => 'required|file|mimes:mp4,mov,avi,webm,mkv|max:512000', // 500MB max
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $instructor = Instructor::where('id', $request->instructorId)->first();

        // store video
        $path = null;
        if ($request->hasFile('video')) {
            $path = $request->file('video')->store('uploads/introduction_videos', 'public');
        }

        $instructor->consultant_progress = 3;
        $instructor->intro_video_url = $path;
        $instructor->save();

        return ResponseHelper::success('Details Updated Successfully', ['instructor' => $instructor]);
    }
}
