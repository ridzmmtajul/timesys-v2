<?php

namespace App\Http\Controllers;

use App\Http\Requests\PostNumberRequest;
use App\Http\Resources\PostNumber as ResourcesPostNumber;
use App\Models\PostNumber;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PostNumberController extends Controller
{
    public function index(Request $request)
    {
        $postNumbers = [];
        if (isset($request->search)) {
            $postNumbers = PostNumber::where('name', 'like', '%' . $request->search . '%');
        }

        $postNumbers = isset($request->search) && $request->search ? $postNumbers->paginate(10) : PostNumber::paginate(10);
        return ResourcesPostNumber::collection($postNumbers);
    }

    public function store(PostNumberRequest $request)
    {
        try {
            $postNumber = new PostNumber();
            $postNumber->name = ucwords($request->name);
            $postNumber->save();

            return response()->json(['message' => 'Post Number has been successfully saved.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()]);
        }
    }

    public function update($id, PostNumberRequest $request)
    {
        try {
            $postNumber = PostNumber::findOrFail($id);
            $postNumber->name = ucwords($request->name);
            $postNumber->update();

            return response(['message' => 'Post Number has been successfully updated.']);
        } catch (\Exception $e) {
            return response(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function destroy($id)
    {
        PostNumber::findOrFail($id)->delete();
        return response(['message' => 'Post Number has been successfully deleted!']);
    }
}
