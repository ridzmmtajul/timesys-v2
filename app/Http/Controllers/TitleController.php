<?php

namespace App\Http\Controllers;

use App\Http\Requests\TitleRequest;
use App\Http\Resources\Title as ResourcesTitle;
use App\Models\Title;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TitleController extends Controller
{
    public function index(Request $request)
    {
        $titles = [];
        if (isset($request->search)) {
            $titles = Title::where('abbreviation', 'like', '%' . $request->search . '%')
                ->orWhere('description', 'like', '%' . $request->search . '%');
        }

        $titles = isset($request->search) && $request->search ? $titles->paginate(10) : Title::paginate(10);
        return ResourcesTitle::collection($titles);
    }

    public function store(TitleRequest $request)
    {
        try {
            $title = new Title();
            $title->abbreviation = strtoupper($request->abbreviation);
            $title->description  = $request->description;
            $title->save();

            return response()->json(['message' => 'Title has been successfully saved.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()]);
        }
    }

    public function update($id, TitleRequest $request)
    {
        try {
            $title = Title::findOrFail($id);
            $title->abbreviation = strtoupper($request->abbreviation);
            $title->description  = $request->description;
            $title->update();

            return response(['message' => 'Title has been successfully updated.']);
        } catch (\Exception $e) {
            return response(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function destroy($id)
    {
        Title::findOrFail($id)->delete();
        return response(['message' => 'Title has been successfully deleted!']);
    }
}
