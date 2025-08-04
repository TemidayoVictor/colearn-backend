<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Helpers\ResponseHelper;
use Illuminate\Support\Carbon;

use App\Models\User;
use App\Models\Blog;

class BlogController extends Controller
{
    //
    public function createBlog(Request $request) {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'excerpt' => 'nullable|string',
            'body' => 'required|string',
            'thumbnail' => 'required|image|max:2048',
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $thumbnail = null;
        if ($request->hasFile('thumbnail')) {
            $thumbnail = $request->file('thumbnail')->store('uploads/blogs', 'public');
        }

        $blog = Blog::create([
            'user_id' => $request->user_id,
            'title' => $request->title,
            'slug' => uniqid(),
            'excerpt' => $request->excerpt,
            'body' => $request->body,
            'thumbnail' => $thumbnail,
            'is_published' => false, // default to false
        ]);

        return ResponseHelper::success('Blog created successfully', ['blog' => $blog]);
    }

    public function editBlog(Request $request) {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'excerpt' => 'nullable|string',
            'body' => 'required|string',
            'thumbnail' => 'required|image|max:2048',
            'user_id' => 'required|exists:users,id',
            'id' => 'required|exists:blogs,id'
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return ResponseHelper::error($firstError, $validator->errors(), 422);
        }

        $blog = Blog::where('id', $request->id)->first();

        $thumbnail = $blog->thumbnail;
        if ($request->hasFile('thumbnail')) {
            if ($blog->thumbnail) {
                Storage::disk('public')->delete($blog->thumbnail);
            }
            $thumbnail = $request->file('thumbnail')->store('uploads/blogs', 'public');
        }

        $update = $blog->update([
            'title' => $request->title,
            'excerpt' => $request->excerpt,
            'body' => $request->body,
            'thumbnail' => $thumbnail,
        ]);

        return ResponseHelper::success('Blog updated successfully', ['blog' => $blog]);
    }
}
