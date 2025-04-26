<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Staff;
use App\Services\UserService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{

    public $userService;

    public function __construct(
        UserService         $userService,
    )
    {
        $this->userService = $userService;
    }

    public function index(Request $request)
    {

        if ($request->ajax()) {
            $userQuery = User::query()->with('userStaff')->orderByDesc('id');
            if (isset($request->search)) {

                $searchTerm =  $request->search;

                $userQuery->where(function ($query) use ($searchTerm) {
                    $query->where('email', 'like', "%{$searchTerm}%")
                          ->orWhere('name', 'like', "%{$searchTerm}%");
                });
            }

            return $this->userService->getDataTables($userQuery , Auth::user());
        }

        return view('portal.user.index');
    }

    public function create()
    {
        $roles = Role::oldest()->get();
        return view('portal.user.create', [
            'roles' => $roles
        ]);
    }

    public function store(Request $request)
    {

        $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required'
        ]);

        try {

            $data = $request->all();

            $user = $this->userService->createUser($data, Auth::user()->id);
            if ($user instanceof \Exception) {
                return $user;
            }
            return redirect()->route('user.index')->with('success', 'User added Successfully');

        } catch (\Exception $exception) {
            return redirect()->back()->with('error', 'Something went wrong')->withInput();

        }

    }

    public function edit($user)
    {

        $user = User::with('userStaff')->where('id',$user)->first();

        $roles = Role::oldest()->get();
        return view('portal.user.edit', [
            'user' => $user,
            'roles' => $roles
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required',
            'role' => 'required'
        ]);
        try {
            $user = $this->userService->updateUser($request->all());

            if ($user instanceof \Exception) {
                return $user;
            }
            return redirect()->back()->with('success', 'User Updated Successfully');

        } catch (\Exception $exception) {
            return redirect()->back()->with('error', 'Something went wrong')->withInput();

        }

    }

    public function resetPassword(Request $request)
    {
        try {
            $user = $this->userService->resetPassword($request->all());

            if ($user instanceof \Exception) {
                return $user;
            }
            return redirect()->back()->with('success', 'Password Reset Successfully');

        } catch (\Exception $exception) {
            return redirect()->back()->with('error', 'Something went wrong')->withInput();

        }

    }


    public function delete(User $user)
    {
        try {

            $user = $this->userService->deleteUser($user);

            if ($user instanceof \Exception) {
                return $user;
            }

            return redirect()->back()->with('success', 'User Deleted Successfully');

        } catch (\Exception $exception) {
            return redirect()->back()->with('error', 'Something went wrong')->withInput();

        }

    }

}
