<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        return view('admin.users', ['users' => User::latest()->paginate(15)]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate(['role' => ['required', 'in:admin,mahasiswa']]);
        $user->update($data);

        return back()->with('status', 'Peran pengguna diperbarui.');
    }

    public function destroy(User $user)
    {
        abort_if($user->id === auth()->id(), 422, 'Admin tidak dapat menghapus akun sendiri.');
        $user->delete();

        return back()->with('status', 'Pengguna dihapus.');
    }
}
