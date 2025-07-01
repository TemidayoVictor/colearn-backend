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
use App\Models\Consultant;
use App\Models\AvailabilitySlot;

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
                    'organization' => $cert['organization'],
                    'iss_date' => $cert['iss_date'] ?? null,
                    'exp_date' => $cert['exp_date'] ?? null,
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

    public function editCert(Request $request) {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:certifications,id',
            'name' => 'required|string|max:255',
            'organization' => 'required|string|max:255',
            'iss_date' => 'nullable|string|max:255',
            'exp_date' => 'nullable|string|max:255',
            'credential_url' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $certification = Certification::where('id', $request->id)->first();

        $path = $certification->certificate_file_path;
        if ($request->hasFile('image')) {
            // store new image
            $path = $request->file('image')->store('uploads/certifications', 'public');

            // delete previous image
            if (Storage::disk('public')->exists($certification->certificate_file_path)) {
                Storage::disk('public')->delete($certification->certificate_file_path);
            }
        }

        $update = $certification->update([
            'name' => $request->name,
            'organization' => $request->organization,
            'iss_date' => $request->iss_date,
            'exp_date' => $request->exp_date,
            'credential_url' => $request->credential_url,
            'certificate_file_path' => $path,
        ]);

        return ResponseHelper::success('Certification updated successfully');

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
        $path = $instructor->intro_video_url;
        if ($request->hasFile('video')) {
            $path = $request->file('video')->store('uploads/introduction_videos', 'public');
        }

        // delete previous video if any
        if($instructor->intro_video_url != null) {
            if (Storage::disk('public')->exists($instructor->intro_video_url)) {
                Storage::disk('public')->delete($instructor->intro_video_url);
            }
        }

        $instructor->consultant_progress = 3;
        $instructor->intro_video_url = $path;
        $instructor->save();

        return ResponseHelper::success('Details Updated Successfully', ['instructor' => $instructor]);
    }

    public function submitApplication(Request $request) {
        $validator = Validator::make($request->all(), [
            'instructorId' => 'required|exists:instructors,id',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $instructor = Instructor::where('id', $request->instructorId)->first();

        $instructor->consultant_progress = 4;
        $instructor->save();

        return ResponseHelper::success('Application submitted successfully', ['instructor' => $instructor]);
    }

    public function createConsultantAccount(Request $request) {
        $validator = Validator::make($request->all(), [
            'instructorId' => 'required|exists:instructors,id',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $instructor = Instructor::where('id', $request->instructorId)->first();
        $instructor->consultant = true;
        $instructor->save();

        // check if consultant account already exists
        $existingConsultant = Consultant::where('instructor_id', $request->instructorId)->first();
        if ($existingConsultant) {
            return ResponseHelper::error('Consultant account already exists', [], 422);
        }

        // create consultant account
        $consultant = Consultant::create([
            'instructor_id' => $request->instructorId,
        ]);

        // create available slots for the consultant
        $availableDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        foreach ($availableDays as $day) {
            $consultant->slots()->create([
                'consultant_id' => $consultant->id,
                'day' => $day,
                'start_time' => '',
                'end_time' => '',
                'enabled' => false,
            ]);
        }

        return ResponseHelper::success('Account updated successfully', ['consultant' => $consultant]);
    }

    public function setAvailability(Request $request) {
        $validator = Validator::make($request->all(), [
            'consultantId' => 'required|exists:consultants,id',
            'type' => 'required|string',
            'rate' => 'nullable|string',
            'slots' => 'required|array',
            'slots.*.day' => 'required|string|max:255',
            'slots.*.start_time' => 'nullable|string|max:255',
            'slots.*.end_time' => 'nullable|string|max:255',
            'slots.*.enabled' => 'nullable|boolean',
            'slots.*.id' => 'required|integer|exists:availability_slots,id',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $consultant = Consultant::where('id', $request->consultantId)->first();

        $consultant->type = $request->type;
        $consultant->hourly_rate = $request->rate;
        $consultant->save();

        // update availability slot
        foreach ($request->slots as $slot) {
            AvailabilitySlot::where('id', $slot['id'])
                ->where('consultant_id', $request->consultantId) // extra safety
                ->update([
                    'day' => $slot['day'],
                    'start_time' => $slot['start_time'] ? $slot['start_time'] : "" ,
                    'end_time' => $slot['end_time'] ? $slot['end_time'] : "",
                    'enabled' => $slot['enabled'] , // default to false if null
                ]);
        }

        return ResponseHelper::success('Availability updated successfully', ['consultant' => $consultant]);

    }

    public function getAllConsultants() {
        $consultants = Consultant::where('status', 'Active')->with('instructor.user')->get();
        return ResponseHelper::success('Consultants fetched successfully', ['consultants' => $consultants]);
    }
}
