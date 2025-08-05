<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;
use Illuminate\Support\Facades\Storage;

use App\Models\Country;
use App\Models\Category;
use App\Models\Faq;

class UtilitiesController extends Controller
{
    public function countries() {
        $countries = Country::all();
        return ResponseHelper::success('Countries Fetched', ['countries' => $countries]);
    }

    public function downloadResource($filename, $title = null) {
        $path = 'uploads/resources/' . $filename;

        if (!Storage::exists($path)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        // Get the extension from the original filename
        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        // Set a fallback name if title is not provided
        $safeTitle = $title ? $title : 'document';

        // Optionally sanitize title (remove special characters/spaces)
        $safeTitle = preg_replace('/[^A-Za-z0-9\-_]/', '_', $safeTitle);

        $customFilename = $safeTitle . '.' . $extension;

        return Storage::download($path, $customFilename);
    }

    public function getCategories() {
        $categories = Category::all();
        return ResponseHelper::success('Data fetched successfully', ['categories' => $categories]);
    }

    public function createCategory(Request $request) {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'thumbnail' => 'required|image|max:2048',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $thumbnail = null;
        if ($request->hasFile('thumbnail')) {
            $thumbnail = $request->file('thumbnail')->store('uploads/category', 'public');
        }

        $category = Category::create([
            'name' => $request->name,
            'slug' => uniqid(),
            'thumbnail' => $thumbnail,
            'description' => $request->name,
        ]);

        return ResponseHelper::success('Category created successfully', ['category' => $category]);
    }

    public function editCategory(Request $request) {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'id' => 'required|exists:categories,id'
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $category = Category::where('id', $request->id)->first();

        $thumbnail = $category->thumbnail;
        if ($request->hasFile('thumbnail')) {
            $thumbnail = $request->file('thumbnail')->store('uploads/category', 'public');
        }

        $update = $category->update([
            'name' => $request->name,
            'thumbnail' => $thumbnail,
        ]);

        return ResponseHelper::success('Category updated successfully', ['category' => $category]);
    }

    public function deleteCategory(Request $request) {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:categories,id'
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $category = Category::where('id', $request->id)->first();

        if ($category->thumbnail) {
            Storage::disk('public')->delete($category->thumbnail);
        }

        $category->delete();

        return ResponseHelper::success('Category deleted successfully');
    }

    public function getFaqs() {
        $faqs = Faq::all();
        return ResponseHelper::success('Data fetched successfully', ['faqs' => $faqs]);
    }

    public function createFaq(Request $request) {
        $validator = Validator::make($request->all(), [
            'question' => 'required|string|max:255',
            'answer' => 'required',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $faq = Faq::create([
            'question' => $request->name,
            'answer' => $request->answer,
        ]);

        return ResponseHelper::success('FAQ created successfully', ['category' => $category]);
    }

    public function editFaq(Request $request) {
        $validator = Validator::make($request->all(), [
            'question' => 'required|string|max:255',
            'answer' => 'required',
            'id' => 'required|exists:faqs,id'
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $faq = Faq::where('id', $request->id)->first();

        $update = $category->update([
            'question' => $request->name,
            'answer' => $request->answer,
        ]);

        return ResponseHelper::success('Faq updated successfully', ['category' => $category]);
    }

    public function deleteFaq(Request $request) {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:faqs,id'
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $faq = Faq::where('id', $request->id)->first();

        $faq->delete();

        return ResponseHelper::success('Faq deleted successfully');
    }
}
