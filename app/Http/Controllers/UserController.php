<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Http\Resources\User as ResourcesUser;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with(['employee', 'role']);

        if (isset($request->search) && $request->search) {
            $users = $users->where('username', 'like', '%' . $request->search . '%');
        }

        $users = $users->paginate(10);
        return ResourcesUser::collection($users);
    }

    public function options()
    {
        return response()->json([
            'employees' => Employee::orderBy('last_name')->orderBy('first_name')->get()->map(fn($e) => [
                'id'          => $e->id,
                'name'        => $e->last_name . ', ' . $e->first_name,
                'employee_no' => $e->employee_no,
                'first_name'  => $e->first_name,
                'middle_name' => $e->middle_name,
                'last_name'   => $e->last_name,
            ]),
            'roles' => Role::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(UserRequest $request)
    {
        try {
            $user = new User();
            $user->username    = $request->username;
            $user->password    = "*1234#"; //default password
            $user->employee_id = $request->employee_id;
            $user->role_id     = $request->role_id;
            $user->save();

            return response()->json(['message' => 'User has been successfully saved.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()]);
        }
    }

    public function update($id, UserRequest $request)
    {
        try {
            $user = User::findOrFail($id);
            $user->username    = $request->username;
            $user->employee_id = $request->employee_id;
            $user->role_id     = $request->role_id;

            if ($request->filled('password')) {
                $user->password = $request->password;
            }

            $user->update();
            return response(['message' => 'User has been successfully updated.']);
        } catch (\Exception $e) {
            return response(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function destroy($id)
    {
        User::findOrFail($id)->delete();
        return response(['message' => 'User has been successfully deleted!']);
    }
}
