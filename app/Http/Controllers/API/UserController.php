<?php

namespace App\Http\Controllers\API;

use Exception;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\Manage\DetailUserResource;

class UserController extends Controller
{
    public function getUsers(Request $request)
    {
        $user = $request->user();
        $search = $request->get('search');

        $users = User::with('department')
            ->where('id', '<>', $user->id)
            ->when($search, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->paginate($request->get('per_page', 15));

        return response([
            'users' => UserResource::collection($users),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ]
        ], 200);
    }

    public function getDetailUsers(Request $request)
    {
        $user = $request->user();
        $search = $request->get('search');
        $perPage = $request->get('per_page');

        $query = User::with('department')
            ->when($search, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->where('id', '<>', $user->id)
            ->where('level', '<>', 'admin');

        $users = $query->paginate($perPage ?: $query->count());

        return response([
            'data' => DetailUserResource::collection($users),
            'meta' => collect($users->toArray())->except('data')->toArray()
        ], 200);
    }

    public function readDetailUser(Request $request, $id)
    {
        $user = $request->user();

        $targetUser = User::with('department')
            ->where('id', $id)
            ->where('id', '<>', $user->id)
            ->where('level', '<>', 'admin')
            ->firstOrFail();

        return response([
            'user' => new DetailUserResource($targetUser)
        ], 200);
    }

    public function createUser(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:50',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:20|unique:users,phone',
            'password' => 'required|string|min:6|max:10',
            'department_id' => 'nullable|exists:departments,id',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        try {
            DB::beginTransaction();

            $data = [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'email_verified_at' => now(),
                'level' => 'user',
                'department_id' => $request->department_id,
            ];

            if ($request->hasFile('photo')) {
                $file = $request->file('photo');
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->storeAs('profile', $filename, 'public');
                $data['photo'] = $filename;
            }

            $user = User::create($data);
            $user->load('department');

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            return response([
                'message' => 'Failed to create user',
            ], 500);
        }

        return response([
            'message' => 'User created.',
            'user' => new DetailUserResource($user)
        ], 201);
    }

    public function updateUser(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:50',
            'email' => 'required|email|unique:users,email,' . $id,
            'phone' => 'required|string|max:20|unique:users,phone,' . $id,
            'password' => 'nullable|string|min:6|max:10',
            'department_id' => 'nullable|exists:departments,id',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $user = $request->user();

        $targetUser = User::with('department')
            ->where('id', $id)
            ->where('id', '<>', $user->id)
            ->where('level', '<>', 'admin')
            ->firstOrFail();

        try {
            DB::beginTransaction();

            $data = [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'department_id' => $request->department_id,
            ];

            if ($request->filled('password')) {
                $data['password'] = Hash::make($request->password);
            }

            if ($request->hasFile('photo')) {
                $oldPhoto = $targetUser->getRawOriginal('photo');

                if ($oldPhoto && Storage::disk('public')->exists('profile/' . $oldPhoto)) {
                    Storage::disk('public')->delete('profile/' . $oldPhoto);
                }

                $file = $request->file('photo');
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->storeAs('profile', $filename, 'public');
                $data['photo'] = $filename;
            }

            $targetUser->fill($data);

            if ($targetUser->isDirty()) {
                $targetUser->tokens()->delete();
            }

            $targetUser->save();
            $targetUser->load('department');

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            return response([
                'message' => 'Failed to update user',
            ], 500);
        }

        return response([
            'message' => 'User updated.',
            'user' => new DetailUserResource($targetUser)
        ], 200);
    }

    public function deleteUser(Request $request, $id)
    {
        $user = $request->user();

        $targetUser = User::where('id', $id)
            ->where('id', '<>', $user->id)
            ->where('level', '<>', 'admin')
            ->firstOrFail();

        try {
            DB::beginTransaction();

            $targetUser->tokens()->delete();

            $oldPhoto = $targetUser->getRawOriginal('photo');
            if ($oldPhoto && Storage::disk('public')->exists('profile/' . $oldPhoto)) {
                Storage::disk('public')->delete('profile/' . $oldPhoto);
            }

            $targetUser->delete();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            return response([
                'message' => 'Failed to delete user',
            ], 500);
        }

        return response([
            'message' => 'User deleted.'
        ], 200);
    }
}
