<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UploadFileController extends Controller
{
    public function uploadPhoto(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|max:15360', // max 5MB
        ]);

        $photo = $request->file('photo');
        $filename = 'photo_' . Str::random(10) . '.' . $photo->getClientOriginalExtension();
        $photo->move(public_path('uploads/photos'), $filename);

        return response()->json(['url' => url('uploads/photos/' . $filename)]);
    }

    public function uploadVideo(Request $request)
    {
        $request->validate([
            'video' => 'required|mimetypes:video/webm,video/mp4|max:51200', // max 50MB
        ]);

        $video = $request->file('video');
        $filename = 'video_' . Str::random(10) . '.' . $video->getClientOriginalExtension();
        $video->move(public_path('uploads/videos'), $filename);

        return response()->json(['url' => url('uploads/videos/' . $filename)]);
    }
}
