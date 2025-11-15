<?php

namespace App\Http\Controllers;

use App\Models\Mahasiswa;
use Illuminate\Http\Request;

class MahasiswaController extends Controller
{
    public function index()
    {
        return Mahasiswa::all();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'npm' => 'required|unique:mahasiswa',
            'nama' => 'required',
            'kelas' => 'required',
            'prodi' => 'required',
            'foto' => 'nullable|string'
        ]);

        return Mahasiswa::create($validated);
    }

    public function update(Request $request, $id)
    {
        $mhs = Mahasiswa::findOrFail($id);

        $mhs->update($request->all());

        return $mhs;
    }

    public function destroy($id)
    {
        $mhs = Mahasiswa::findOrFail($id);
        $mhs->delete();

        return response()->json(['message' => 'Deleted']);
    }

    public function saveDescriptor(Request $req, $id)
    {
        $mhs = Mahasiswa::findOrFail($id);

        $mhs->descriptor = json_encode($req->descriptor);
        $mhs->save();

        return ['status' => 'saved'];
    }

    public function getFaces()
{
    $faces = Mahasiswa::select('id', 'npm', 'nama', 'foto')
        ->get()
        ->map(function ($item) {
            // Full asset URL for face-api.js
            $item->foto = asset('storage/mahasiswa/' . $item->foto);
            return $item;
        });

    return response()->json($faces);
}


}
